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
 * @version $Revision: 349 $
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GPL
 */

/**
 * Validate date inputs
 *
 * @package Core
 * @subpackage CouchDbBackend
 * @version $Revision: 349 $
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GPL
 */
class arbitBackendCouchDbDateValidator extends arbitBackendCouchDbValidator
{
    /**
     * Validate input as string
     * 
     * @param mixed $input 
     * @return string
     */
    public function validate( $input )
    {
        // Check if we received a unix timestamp, in this case we can just
        // directly convert and return it.
        if ( is_numeric( $input ) )
        {
            $date = new DateTime( '@' . $input );
            return $date->format( DATE_RFC2822 );
        }

        // Otherwise we received most presumably some arbitrary string, which
        // we first just try to parse with datetime (strtotime).
        if ( ( $date = new DateTime( $input ) ) !== false )
        {
            return $date->format( DATE_RFC2822 );
        }

        // If DateTime could not parse the string, we got a problem. Maybe
        // handle more datetime formats here manually, but now we just fail.
        //
        // Since PHP 5.3 datetime seems to accept everything and just returns
        // NOW, if it fails to parse. So this seems untestable for now.
        throw new arbitBackendCouchDbValidationException( 
            'Error parsing the date: %date', 
            array(
                'date' => $input,
            )
        );
    }
}
