<?php
/**
 * arbit CouchDB backend
 *
 * This file is part of arbit.
 *
 * arbit is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 3 of the License.
 *
 * arbit is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with arbit; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @package Core
 * @subpackage CouchDbBackend
 * @version $Revision: 479 $
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GPL
 */

/**
 * Wrapper base for views in the database
 *
 * @package Core
 * @subpackage CouchDbBackend
 * @version $Revision: 479 $
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GPL
 */
abstract class arbitBackendCouchDbView extends arbitBackendCouchDbDocument
{
    /**
     * List of required properties. For each required property, which is not
     * set, a validation exception will be thrown on save.
     * 
     * @var array
     */
    protected $requiredProperties = array(
        'language',
        'views'
    );

    /**
     * Document type, may be a string matching the regular expression:
     *  (^[a-zA-Z0-9_]+$)
     * 
     * @var string
     */
    protected static $type = '_view';

    /**
     * View functions to be registered on the server
     *
     * @var array
     */
    protected $viewDefinitions = array();

    /**
     * Construct new document
     * 
     * Construct new document
     * 
     * @return void
     */
    public function __construct()
    {
        $this->properties = array(
            'language'  => new arbitBackendCouchDbRegexpValidator( '(^text/(?:javascript)$)' ),
            'views'     => new arbitBackendCouchDbArrayValidator(),
        );

        parent::__construct();

        $this->language = 'text/javascript';
        $this->views = $this->viewDefinitions;
    }

    /**
     * Get name of view
     * 
     * Get name of view
     * 
     * @return string
     */
    protected static function getViewName()
    {
        throw new arbitRuntimeException(
            'This method should be considerd abstract, but PHP does not allow this.'
        );
    }

    /**
     * Get document ID from object ID
     *
     * Composes the document ID out of the document type and the generated ID
     * for the current document.
     * 
     * @param string $type 
     * @param string $id 
     * @return string
     */
    protected static function getDocumentId( $type, $id )
    {
        return '_design/' . $id;
    }

    /**
     * Get ID from document
     *
     * The ID normally should be calculated on some meaningful / unique
     * property for the current ttype of documents. The returned string should
     * not be too long and should not contain multibyte characters.
     * 
     * @return string
     */
    protected function generateId()
    {
        return $this->stringToId( static::getViewName() );
    }

    /**
     * Wrapper for more convenient view queries
     *
     * Wrap all static calls to a extended view class, instantiate it and then
     * call query method on the view object, reusing the called method name to
     * query the view.
     * 
     * @param string $method 
     * @param array $parameters 
     * @return arbitBackendCouchDbResultArray
     */
    public static function __callStatic( $method, $parameters )
    {
        $view = new static();

        // Check if options were set
        $options = ( isset( $parameters[0] ) ? $parameters[0] : array() );

        // Execute query in normal manner
        return $view->query( $method, $options );
    }

    /**
     * Build view query string from options
     *
     * Validates and transformed paased options to limit the view data, to fit
     * the specifications in the HTTP view API, documented at:
     * http://www.couchdbwiki.com/index.php?title=HTTP_View_API#Querying_Options
     * 
     * @param array $options 
     * @return string
     */
    protected function buildViewQuery( array $options )
    {
        // Return empty query string, if no options has been passed
        if ( $options === array() )
        {
            return '';
        }

        $queryString = '?';
        foreach ( $options as $key => $value )
        {
            switch ( $key )
            {
                case 'key':
                case 'startkey':
                case 'endkey':
                    // These values has to be valid JSON encoded strings, so we
                    // just encode the passed data, whatever it is, as CouchDB
                    // may use complex datatypes as a key, like arrays or
                    // objects.
                    $queryString .= $key . '=' . urlencode( json_encode( $value ) );
                    break;

                case 'startkey_docid':
                    // The docidstartkey is handled differntly then the other
                    // keys and is just passed as a string, because it always
                    // is and can only be a string.
                    $queryString .= $key . '=' . urlencode( (string) $value );
                    break;

                case 'update':
                case 'descending':
                    // These two values may only contain boolean values, passed
                    // as "true" or "false". We just perform a typical PHP
                    // boolean typecast to transform the values.
                    $queryString .= $key . '=' . ( $value ? 'true' : 'false' );
                    break;

                case 'skip':
                case 'count':
                    // Theses options accept integers defining the limits of
                    // the query. We try to typecast to int.
                    $queryString .= $key . '=' . ( (int) $value );
                    break;

                default:
                    throw new arbitBackendCouchDbNoSuchPropertyException( $key );
            }

            $queryString .= '&';
        }

        // Return query string, but remove appended '&' first.
        return substr( $queryString, 0, -1 );
    }

    /**
     * Query a view
     *
     * Query the specified view to get a set of results. You may optionally use
     * the view query options as additional paramters to limit the returns
     * values, specified at:
     * http://www.couchdbwiki.com/index.php?title=HTTP_View_API#Querying_Options
     * 
     * @param string $view 
     * @param array $options 
     * @return arbitBackendCouchDbResultArray
     */
    public function query( $view, array $options = array() )
    {
        // Build query string, just as a normal HTTP GET query string
        $url = arbitBackendCouchDbConnection::getDatabase() . 
            '_view/' . $this->getViewName() . '/' . $view;
        $url .= $this->buildViewQuery( $options );

        // Get database connection, because we directly execute a query here.
        $db = arbitBackendCouchDbConnection::getInstance();

        try
        {
            // Try to execute query, a failure most probably means, the view
            // has not been added, yet.
            $response = $db->get( $url );
        }
        catch ( arbitBackendCouchDbResponseErrorException $e )
        {
            // Ensure view has been created properly and then try to execute
            // the query again. If it still fails, there is most probably a
            // real problem.
            $this->verifyView();
            $response = $db->get( $url );
        }

        return $response;
    }

    /**
     * Verify stored views
     *
     * Check if the views stored in the database equal the view definitions
     * specified by the vew classes. If the implmentation differs update to the
     * view specifications in the class.
     * 
     * @return void
     */
    public function verifyView()
    {
        // Fetch view definition from database
        try
        {
            $view = static::fetchById( '_design/' . static::getViewName() );
        }
        catch ( arbitBackendCouchDbResponseErrorException $e )
        {
            // If the view does not exist yet, recreate it
            $view = static::createNew();
        }
        
        // Force setting of view definitions
        $view->views = $this->viewDefinitions;
        $view->save();
    }
}
