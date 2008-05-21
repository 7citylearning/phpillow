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
 * @version $Revision: 491 $
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GPL
 */

/**
 * Wrapper for group views
 *
 * @package Core
 * @subpackage CouchDbBackend
 * @version $Revision: 491 $
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GPL
 */
class arbitBackendCouchDbGroupView extends arbitBackendCouchDbView
{
    /**
     * View functions to be registered on the server
     *
     * @var array
     */
    protected $viewDefinitions = array(
        // Add view for all groups indexed by their name
        'group' => 'function( doc )
{
    if ( doc.type == "group" )
    {
        map( doc.name, doc._id );
    }
}',
        // Fetch all rights of one user, which is defined by the groups a user
        // belongs to.
        //
        // @TODO: The future CouchDB feature reduce() will help a lot here.
        'user_permissions' => 'function( doc )
{
    if ( doc.type == "group" )
    {
        for ( var i = 0; i < doc.users.length; ++i )
        {
            for ( var j = 0; j < doc.permissions.length; ++j )
            {
                map( doc.users[i], doc.permissions[j] );
            }
        }
    }
}',
    );

    /**
     * Get name of view
     * 
     * Get name of view
     * 
     * @return string
     */
    protected static function getViewName()
    {
        return 'groups';
    }
}
