<?php

/**
 * OpenLDAP accounts driver class.
 *
 * @category   Apps
 * @package    OpenLDAP_Directory
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap_directory/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\openldap_directory;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

// clearos_load_language('base');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\accounts\Accounts_Configuration as Accounts_Configuration;
use \clearos\apps\accounts\Accounts_Engine as Accounts_Engine;
use \clearos\apps\accounts\Nslcd as Nslcd;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\openldap\LDAP_Driver as LDAP_Driver;
use \clearos\apps\openldap_directory\OpenLDAP as OpenLDAP;
use \clearos\apps\openldap_directory\Utilities as Utilities;

clearos_load_library('accounts/Accounts_Configuration');
clearos_load_library('accounts/Accounts_Engine');
clearos_load_library('accounts/Nslcd');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('openldap/LDAP_Driver');
clearos_load_library('openldap_directory/OpenLDAP');
clearos_load_library('openldap_directory/Utilities');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;

clearos_load_library('base/Engine_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * OpenLDAP accounts driver class.
 *
 * @category   Apps
 * @package    OpenLDAP_Directory
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap_directory/
 */

class Accounts_Driver extends Accounts_Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const DRIVER_NAME = 'openldap_directory';
    const COMMAND_AUTHCONFIG = '/usr/sbin/authconfig';
    const FILE_INITIALIZING = '/var/clearos/openldap_directory/initializing';
    const PATH_EXTENSIONS = '/var/clearos/openldap_directory/extensions';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $ldaph = NULL;
    protected $config = NULL;
    protected $modes = NULL;
    protected $extensions = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * OpenLDAP_Accounts constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Returns capabililites.
     *
     * @return string capabilities
     */

    public function get_capability()
    {
        clearos_profile(__METHOD__, __LINE__);

        return Accounts_Engine::CAPABILITY_READ_WRITE;
    }

    /**
     * Returns state of driver.
     *
     * @return boolean state of driver
     * @throws Engine_Exception
     */

    public function get_driver_status()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_driver_status(self::DRIVER_NAME);
    }

    /**
     * Returns list of directory extensions.
     *
     * @return array extension list
     * @throws Engine_Exception
     */

    public function get_extensions()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! empty($this->extensions))
            return $this->extensions;

        $folder = new Folder(self::PATH_EXTENSIONS);

        $list = $folder->get_listing();

        foreach ($list as $extension_file) {
            if (preg_match('/\.php$/', $extension_file)) {
                $extension = array();
                include self::PATH_EXTENSIONS . '/' . $extension_file;
                $this->extensions[$extension['extension']] = $extension;
            }
        }

        return $this->extensions;
    }

    /**
     * Returns the mode of the accounts engine.
     *
     * The return values are:
     * - Accounts_Engine::MODE_STANDALONE
     * - Accounts_Engine::MODE_MASTER
     * - Accounts_Engine::MODE_SLAVE
     *
     * @return string mode of the directory
     * @throws Engine_Exception
     */

    public function get_mode()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ldap = new LDAP_Driver();

        return $ldap->get_mode();
    }

    /**
     * Returns a list of available modes.
     *
     * @return array list of modes
     * @throws Engine_Exception
     */

    public function get_modes()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ldap = new LDAP_Driver();

        return $ldap->get_modes();
    }

    /**
     * Returns the next available user ID.
     *
     * @return integer next available user ID
     * @throws Engine_Exception
     */

    public function get_next_uid_number()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        // FIXME: discuss with David -- move "Master" node?
        $dn = 'cn=Master,' . OpenLDAP::get_servers_container();

        $attributes = $this->ldaph->read($dn);

        // TODO: should have some kind of semaphore to prevent duplicate IDs
        $next['uidNumber'] = $attributes['uidNumber'][0] + 1;

        $this->ldaph->modify($dn, $next);

        return $attributes['uidNumber'][0];
    }

    /**
     * Returns status of account system.
     *
     * - Accounts_Engine::STATUS_INITIALIZING
     * - Accounts_Engine::STATUS_UNINITIALIZED
     * - Accounts_Engine::STATUS_OFFLINE
     * - Accounts_Engine::STATUS_ONLINE
     *
     * @return string account system status
     * @throws Engine_Exception
     */

    public function get_system_status()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Check initializing
        //-------------------

        $file = new File(self::FILE_INITIALIZING);

        if ($file->exists()) {
            if (time() - $file->last_modified() < 120)
                return Accounts_Engine::STATUS_INITIALIZING;
        }

        // Check initialized
        //------------------

        try {
            $accounts = new Accounts_Configuration();
            $accounts->get_driver();
        } catch (Accounts_Driver_Not_Set_Exception $e) {
            return Accounts_Engine::STATUS_UNINITIALIZED;
        }

        // Check online/offline
        //---------------------

        if ($this->ldaph === NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        if ($this->ldaph->is_online())
            $status = Accounts_Engine::STATUS_ONLINE;
        else
            $status = Accounts_Engine::STATUS_OFFLINE;

        return $status;
    }

    /**
     * Restarts the relevant daemons in a sane order.
     *
     * @return void
     */

    public function synchronize()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ldap = new LDAP_Driver();
        $ldap->synchronize();

        try {
            $nslcd = new Nslcd();
            $nslcd->reset();
        } catch (Engine_Exception $e) {
            // Not fatal.
        }
    }
}
