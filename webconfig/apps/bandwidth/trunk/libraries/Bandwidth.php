<?php

/**
 * Bandwidth class.
 *
 * @category   Apps
 * @package    Bandwidth
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/bandwidth/
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

namespace clearos\apps\bandwidth;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('bandwidth');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\bandwidth\Bandwidth as Bandwidth;
use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\File as File;
use \clearos\apps\firewall\Firewall as Firewall;
use \clearos\apps\firewall\Rule as Rule;
use \clearos\apps\network\Iface as Iface;
use \clearos\apps\network\Iface_Manager as Iface_Manager;

clearos_load_library('bandwidth/Bandwidth');
clearos_load_library('base/Configuration_File');
clearos_load_library('base/File');
clearos_load_library('firewall/Firewall');
clearos_load_library('firewall/Rule');
clearos_load_library('network/Iface');
clearos_load_library('network/Iface_Manager');

// Exceptions
//-----------

use \Exception as Exception;
use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');

//////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Bandwidth class.
 *
 * @category   Apps
 * @package    Bandwidth
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/bandwidth/
 */

class Bandwidth extends Firewall
{
    //////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/clearos/bandwidth.conf';
    const CONSTANT_SPEED_NOT_SET = 0;

    const MIN_SPEED = 2;
    const MAX_SPEED = 10000000;
    const MAX_IP_RANGE = 255;

    const MATCH_DESTINATION = 0;
    const MATCH_SOURCE = 1;

    const MODE_LIMIT = 'limit';
    const MODE_RESERVE = 'reserve';

    const DIRECTION_FROM_NETWORK = 'from_network';
    const DIRECTION_TO_NETWORK = 'to_network';
    const DIRECTION_FROM_SYSTEM = 'from_system';
    const DIRECTION_TO_SYSTEM = 'to_system';

    const PRIORITY_HIGHEST = 0;
    const PRIORITY_VERY_HIGH = 1;
    const PRIORITY_HIGH = 2;
    const PRIORITY_MEDIUM = 3;
    const PRIORITY_LOW = 4;
    const PRIORITY_VERY_LOW = 5;
    const PRIORITY_LOWEST = 6;

    const TYPE_ADVANCED = 0;
    const TYPE_BASIC = 1;
    const TYPE_ALL = 2;

    //////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $is_loaded = FALSE;
    protected $config = array();

    protected $directions = array();
    protected $matches = array();
    protected $modes = array();
    protected $priorities = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Bandwidth constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct();

        $this->directions = array(
            self::DIRECTION_FROM_NETWORK => lang('bandwidth_flowing_from_network'),
            self::DIRECTION_TO_NETWORK => lang('bandwidth_flowing_to_network'),
            self::DIRECTION_FROM_SYSTEM => lang('bandwidth_flowing_from_system'),
            self::DIRECTION_TO_SYSTEM => lang('bandwidth_flowing_to_system'),
        );

        $this->modes = array(
            self::MODE_LIMIT => lang('bandwidth_limit'),
            self::MODE_RESERVE => lang('bandwidth_reserve'),
        );

        $this->priorities = array(
            self::PRIORITY_HIGHEST => lang('base_highest'),
            self::PRIORITY_VERY_HIGH => lang('base_very_high'),
            self::PRIORITY_HIGH => lang('base_high'),
            self::PRIORITY_MEDIUM => lang('base_medium'),
            self::PRIORITY_LOW => lang('base_low'),
            self::PRIORITY_VERY_LOW => lang('base_very_low'),
            self::PRIORITY_LOWEST => lang('base_lowest'),
        );

        $this->matches = array(
            self::MATCH_SOURCE => lang('bandwidth_source'),
            self::MATCH_DESTINATION => lang('bandwidth_destination')
        );
    }

    /**
     * Add an advanced bandwidth rule.
     *
     * @param string  $name            bandwidth rule name
     * @param string  $iface           external interface
     * @param string  $addr_type       addr type: 0 destination, 1 source
     * @param string  $port_type       port type: 0 destination, 1 source
     * @param string  $ip              IP address
     * @param string  $port            port
     * @param integer $priority        priority
     * @param integer $upstream        upstream rate
     * @param integer $upstream_ceil   upstream ceiling
     * @param integer $downstream      downstream rate
     * @param integer $downstream_ceil downstream ceiling
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function add_advanced_rule($name, $iface, $addr_type, $port_type, $ip, $port, $priority, $upstream = 0, $upstream_ceil = 0, $downstream = 0, $downstream_ceil = 0)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_name($name));
        Validation_Exception::is_valid($this->validate_interface($iface));
        Validation_Exception::is_valid($this->validate_match($addr_type));
        Validation_Exception::is_valid($this->validate_match($port_type));
        Validation_Exception::is_valid($this->validate_ip($ip));
        Validation_Exception::is_valid($this->validate_port($port));
        Validation_Exception::is_valid($this->validate_priority($priority));
        Validation_Exception::is_valid($this->validate_rate($upstream));
        Validation_Exception::is_valid($this->validate_rate($upstream_ceil));
        Validation_Exception::is_valid($this->validate_rate($downstream));
        Validation_Exception::is_valid($this->validate_rate($downstream_ceil));

// FIXME
        /*
        if ($upstream == 0 && $downstream == 0)
            $this->AddValidationError(BANDWIDTH_LANG_ERRMSG_SPEED_MISSING, __METHOD__, __LINE__);
        */

        $rule = new Rule();
        $rule->set_flags(Rule::BANDWIDTH_RATE | Rule::ENABLED);
        $rule->set_name($name);

        if (strlen($ip)) {
            $rule->set_address($ip);

            if (preg_match('/:/', $ip)) {
                list($lo, $hi) = explode(':', $ip);
                if (ip2long($hi) - ip2long($lo) > self::MAX_IP_RANGE) {
                    $this->AddValidationError(BANDWIDTH_LANG_ERRMSG_IPRANGE_TOO_LARGE, __METHOD__, __LINE__);
                }
            }
        }

        if (strlen($port))
            $rule->set_port($port);

        $rule->set_parameter(
            sprintf(
                '%s:%d:%d:%d:%d:%d:%d:%d',
                $iface, $addr_type, $port_type, $priority, $upstream, $upstream_ceil, $downstream, $downstream_ceil
            )
        );

        $this->add_rule($rule);
    }

    /**
     * Adds a basic bandwidth rule.
     *
     * @param string  $mode      rule mode, limit or reserve
     * @param array   $service   service
     * @param string  $direction rule direction
     * @param integer $rate      upstream/downstream rate
     * @param integer $priority  rule priority
     * 
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    public function add_basic_rule($mode, $service, $direction, $rate, $priority)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_mode($mode));
        Validation_Exception::is_valid($this->validate_service($service));
        Validation_Exception::is_valid($this->validate_direction($direction));
        Validation_Exception::is_valid($this->validate_rate($rate));
        Validation_Exception::is_valid($this->validate_priority($priority));

        $flags = Rule::BANDWIDTH_RATE | Rule::BANDWIDTH_BASIC | Rule::ENABLED;

        $name = sprintf('bw_basic_%s_%c%c%c%c%c',
            preg_replace('/\//', '', strtolower($service)),
            97 + rand() % 26,
            65 + rand() % 26,
            48 + rand() % 10,
            48 + rand() % 10,
            65 + rand() % 26);

        if ($mode == self::MODE_LIMIT)
            $ceil = $rate;
        else if ($mode == self::MODE_RESERVE)
            $ceil = 0;

        $saddr = FALSE;
        $sport = FALSE;
        $internal = FALSE;

        switch ($direction) {
            case self::DIRECTION_FROM_NETWORK:
                $flags |= Rule::LOCAL_NETWORK;
                $saddr = FALSE;
                $sport = FALSE;
                $internal = TRUE;
                break;
            case self::DIRECTION_TO_NETWORK:
                $flags |= Rule::LOCAL_NETWORK;
                $saddr = FALSE;
                $sport = TRUE;
                $internal = TRUE;
                break;
            case self::DIRECTION_FROM_SYSTEM:
                $flags |= Rule::EXTERNAL_ADDR;
                $saddr = FALSE;
                $sport = FALSE;
                break;
            case self::DIRECTION_TO_SYSTEM:
                $flags |= Rule::EXTERNAL_ADDR;
                $saddr = FALSE;
                $sport = TRUE;
                break;
            default:
                return;
        }

        // TODO: Basic rules should use 'all' for the external interface name,
        // and the firewall should dynamically duplicate these rules for each
        // external interface.
        $ifm = new Iface_Manager();
        $ext_iflist = $ifm->get_external_interfaces();
        $ports = $this->get_ports_list();

        foreach ($ports as $port_info) {

            if ($port_info[3] == $service) {
                foreach ($ext_iflist as $ext_iface) {
                    $rule = new Rule();
                    $rule->set_name($name);
                    $rule->set_flags($flags);
                    $rule->set_address('0.0.0.0');
                    $rule->set_port($port_info[2]);
                    $rule->set_protocol($port_info[1]);
                    $rule->set_parameter(
                        sprintf(
                            '%s:%d:%d:%d:%d:%d:%d:%d',
                            $ext_iface, $saddr, $sport, $priority, $rate, $ceil, $rate, $ceil
                        )
                    );

                    $this->add_rule($rule);
                }
            }
        }
    }

    /**
     * Returns list of supported directions.
     *
     * @return array list of supported directions
     */

    public function get_directions()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->directions;
    }

    /**
     * Returns match types.
     *
     * @return array match types
     */

    public function get_matches()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->matches;
    }

    /**
     * Returns list of supported modes.
     *
     * @return array list of supported modes
     */

    public function get_modes()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->modes;
    }

    /**
     * Returns list of supported priorities.
     *
     * @return array list of supported priorities
     */

    public function get_priorities()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->priorities;
    }

    /**
     * Toggle the enabled status of an existing bandwidth rule.
     *
     * @param boolean $enabled         status
     * @param string  $iface           external interface
     * @param string  $addr_type       addr type: 0 destination, 1 source
     * @param string  $port_type       port type: 0 destination, 1 source
     * @param string  $ip              IP address
     * @param string  $port            port
     * @param integer $priority        priority
     * @param integer $upstream        upstream rate
     * @param integer $upstream_ceil   upstream ceiling
     * @param integer $downstream      downstream rate
     * @param integer $downstream_ceil downstream ceiling
     *
     * @return void
     * @throws Engine_Exception
     */

    public function toggle_enable_bandwidth_rule($enabled, $iface, $addr_type, $port_type, $ip, $port, $priority, $upstream, $upstream_ceil, $downstream, $downstream_ceil)
    {
        clearos_profile(__METHOD__, __LINE__);

        $rule = new Rule();
        $rule->set_flags(Rule::BANDWIDTH_RATE);

        if (strlen($ip))
            $rule->set_address($ip);

        if (strlen($port))
            $rule->set_port($port);

        $rule->set_parameter(
            sprintf(
                '%s:%d:%d:%d:%d:%d:%d:%d',
                $iface, $addr_type, $port_type, $priority, $upstream, $upstream_ceil, $downstream, $downstream_ceil
            )
        );

        if (! ($rule = $this->find_rule($rule)))
            return;

        $this->delete_rule($rule);

        if ($enabled)
            $rule->enable();
        else
            $rule->disable();

        $this->add_rule($rule);
    }

    /**
     * Deletes an existing "basic" bandwidth rule.
     *
     * @param string $name basic bandwidth rule ID
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_basic_rule($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        $rules = $this->get_rules();

        foreach ($rules as $rule) {
            if (!($rule->get_flags() & Rule::BANDWIDTH_RATE) || !($rule->get_flags() & Rule::BANDWIDTH_BASIC))
                continue;

            if (strcmp($rule->get_name(), $name))
                continue;

            $this->delete_rule($rule);
        }
    }

    /**
     * Delete an existing bandwidth rule.
     *
     * @param string  $iface           external interface
     * @param string  $addr_type       addr type: 0 destination, 1 source
     * @param string  $port_type       port type: 0 destination, 1 source
     * @param string  $ip              IP address
     * @param string  $port            port
     * @param integer $priority        priority
     * @param integer $upstream        upstream rate
     * @param integer $upstream_ceil   upstream ceiling
     * @param integer $downstream      downstream rate
     * @param integer $downstream_ceil downstream ceiling
     *
     * @return  void
     * @throws  Engine_Exception
     */

    public function delete_bandwidth_rule($iface, $addr_type, $port_type, $ip, $port, $priority, $upstream, $upstream_ceil, $downstream, $downstream_ceil)
    {
        clearos_profile(__METHOD__, __LINE__);

        $rule = new Rule();

        $rule->set_flags(Rule::BANDWIDTH_RATE);

        if (strlen($ip))
            $rule->set_address($ip);

        if (strlen($port))
            $rule->set_port($port);

        $rule->set_parameter(
            sprintf(
                '%s:%d:%d:%d:%d:%d:%d:%d',
                $iface, $addr_type, $port_type, $priority, $upstream, $upstream_ceil, $downstream, $downstream_ceil
            )
        );

        $this->delete_rule($rule);
    }

    /**
     * Get all bandwidth rules.
     *
     * @param string $type type
     *
     * @return array list of all bandwidth rules
     * @throws Engine_Exception
     */

    public function get_bandwidth_rules($type = self::TYPE_ALL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $entries = array();

        $rules = $this->get_rules();

        foreach ($rules as $rule) {
            if (!($rule->get_flags() & Rule::BANDWIDTH_RATE))
                continue;

            $info = array();
            $info['name'] = $rule->get_name();
            $info['enabled'] = $rule->is_enabled();
            $info['type'] = ($rule->get_flags() & Rule::BANDWIDTH_BASIC) ? self::TYPE_BASIC : self::TYPE_ADVANCED;
            $info['host'] = $rule->get_address();
            $info['port'] = $rule->get_port();
            $info['service'] = $this->lookup_service(self::PROTOCOL_TCP, $info['port']);
            list(
                $info['wanif'],
                $info['addr_type'],
                $info['port_type'],
                $info['priority'],
                $info['upstream'],
                $info['upstream_ceil'],
                $info['downstream'],
                $info['downstream_ceil']) = preg_split('/:/', $rule->get_parameter());

            settype($info['addr_type'], 'int');
            settype($info['port_type'], 'int');
            settype($info['priority'], 'int');
            settype($info['upstream'], 'int');
            settype($info['upstream_ceil'], 'int');
            settype($info['downstream'], 'int');
            settype($info['downstream_ceil'], 'int');

            if ($rule->get_flags() & Rule::BANDWIDTH_BASIC) {
                if ($rule->get_flags() & Rule::LOCAL_NETWORK && $info['addr_type'] == 0 && $info['port_type'] == 0)
                    $info['direction'] = self::DIRECTION_FROM_NETWORK;
                else if ($rule->get_flags() & Rule::LOCAL_NETWORK && $info['addr_type'] == 0 && $info['port_type'] == 1)
                    $info['direction'] = self::DIRECTION_TO_NETWORK;
                else if ($rule->get_flags() & Rule::EXTERNAL_ADDR && $info['addr_type'] == 0 && $info['port_type'] == 0)
                    $info['direction'] = self::DIRECTION_FROM_SYSTEM;
                else if ($rule->get_flags() & Rule::EXTERNAL_ADDR && $info['addr_type'] == 0 && $info['port_type'] == 1)
                    $info['direction'] = self::DIRECTION_TO_SYSTEM;
            } else {
                $info['direction'] = -1;
            }

            $info['direction_text'] = $this->directions[$info['direction']];

            if ($info['upstream'] == $info['upstream_ceil'])
                $info['mode'] = self::MODE_LIMIT;
            else
                $info['mode'] = self::MODE_RESERVE;

            $info['mode_text'] = $this->modes[$info['mode']];

            $info['priority_text'] = $this->priorities[$info['priority']];

            if (($type === self::TYPE_BASIC) && ($info['type'] === self::TYPE_ADVANCED))
                continue;

            if (($type === self::TYPE_ADVANCED) && ($info['type'] === self::TYPE_BASIC))
                continue;

            $entries[] = $info;
        }

        return $entries;
    }

    /**
     * Returns network interface details.
     *
     * @return array information about network interfaces
     * @throws Engine_Exception
     */

    public function get_interface_settings($iface)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($iface));

        $ifaces = $this->get_interfaces();

        return $ifaces[$iface];
    }

    /**
     * Returns network interface details for all interfaces.
     *
     * @return array information about network interfaces
     * @throws Engine_Exception
     */

    public function get_interfaces()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_configuration();

        $ifacemanager = new Iface_Manager();
        $ifaces = $ifacemanager->get_external_interfaces();

        // TODO: setting up/down to zero if undefined ... is this still desirable?

        $ifaceinfo = array();

        foreach ($ifaces as $iface) {
            $ifaceinfo[$iface]['configured'] = TRUE;

            if (array_key_exists($iface, $this->config['BANDWIDTH_UPSTREAM'])) {
                $ifaceinfo[$iface]['upstream'] = $this->config['BANDWIDTH_UPSTREAM'][$iface];
            } else {
                $ifaceinfo[$iface]['upstream'] = '';
                $ifaceinfo[$iface]['configured'] = FALSE;
            }

            if (array_key_exists($iface, $this->config['BANDWIDTH_DOWNSTREAM'])) {
                $ifaceinfo[$iface]['downstream'] = $this->config['BANDWIDTH_DOWNSTREAM'][$iface];
            } else {
                $ifaceinfo[$iface]['downstream'] = '';
                $ifaceinfo[$iface]['configured'] = FALSE;
            }
        }

        return $ifaceinfo;
    }

    /**
     * Returns the state of the bandwidth manager.
     *
     * @return boolean TRUE if bandwidth manager is enabled
     * @throws Engine_Exception
     */

    public function get_engine_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_configuration();

        $state = ($this->config['BANDWIDTH_QOS']) ? TRUE : FALSE;

        return $state;
    }

    /**
     * Returns state of network interface configuration details.
     *
     * @return boolean TRUE if all network interfaces have been configured.
     * @throws Engine_Exception
     */

    public function is_initialized()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ifaces = $this->get_interfaces();

        foreach ($ifaces as $iface => $info) {
            if (!$info['configured'])
                return FALSE;
        }

        return TRUE;
    }

    /**
     * Sets the state of a rule.
     *
     * @param boolean $state state
     * @param string  $name  name
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_basic_rule_state($state, $name)
    {
        clearos_profile(__METHOD__, __LINE__);

        $rules = $this->get_rules();

        foreach ($rules as $rule) {
            if (!($rule->get_flags() & Rule::BANDWIDTH_RATE) || !($rule->get_flags() & Rule::BANDWIDTH_BASIC))
                continue;

            if (strcmp($rule->get_name(), $name))
                continue;

            $this->delete_rule($rule);

            if ($state)
                $rule->enable();
            else
                $rule->disable();

            $this->add_rule($rule);
        }
    }

    /**
     * Sets the state of the bandwidth manager.
     *
     * @param boolean $state state
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_engine_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_configuration();

        $this->config['BANDWIDTH_QOS'] = $state;

        $this->_save_configuration();
    }

    /**
     * Updates network interface information for a given interface.
     *
     * @param string  $iface      network interface
     * @param integer $upstream   upstream speed in kbit/s
     * @param integer $downstream downstream speed in kbit/s
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function update_interface($iface, $upstream, $downstream)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($iface));
        Validation_Exception::is_valid($this->validate_rate($upstream));
        Validation_Exception::is_valid($this->validate_rate($downstream));

        if (! $this->is_loaded)
            $this->_load_configuration();

        if ((!strlen($upstream) || ($upstream === Bandwidth::CONSTANT_SPEED_NOT_SET))
            && isset($this->config['BANDWIDTH_UPSTREAM'][$iface])
        )
            unset($this->config['BANDWIDTH_UPSTREAM'][$iface]);
        else
            $this->config['BANDWIDTH_UPSTREAM'][$iface] = $upstream;

        if ((!strlen($downstream) || ($downstream === Bandwidth::CONSTANT_SPEED_NOT_SET)) 
            && array_key_exists($iface, $this->config['BANDWIDTH_DOWNSTREAM'][$iface])
        )
            unset($this->config['BANDWIDTH_DOWNSTREAM'][$iface]);
        else
            $this->config['BANDWIDTH_DOWNSTREAM'][$iface] = $downstream;

        $this->_save_configuration();
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validates direction.
     *
     * @param string $direction direction
     *
     * @return void
     */

    public function validate_direction($direction)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! array_key_exists($direction, $this->directions))
            return lang('bandwidth_direction_invalid');
    }

    /**
     * Validates network interface.
     *
     * @param string $iface interface
     *
     * @return void
     */

    public function validate_interface($iface)
    {
        clearos_profile(__METHOD__, __LINE__);

        $iface_manager = new Iface_Manager();

        $ifaces = $iface_manager->get_interfaces();

        if (!in_array($iface, $ifaces))
            return lang('bandwidth_network_interface_invalid');
    }

    /**
     * Validates match.
     *
     * @param string $match match
     *
     * @return void
     */

    public function validate_match($match)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! array_key_exists($match, $this->matches))
            return lang('bandwidth_match_invalid');
    }

    /**
     * Validates mode.
     *
     * @param string $mode mode
     *
     * @return void
     */

    public function validate_mode($mode)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! array_key_exists($mode, $this->modes))
            return lang('bandwidth_mode_invalid');
    }

    /**
     * Validates priority.
     *
     * @param string $priority priority
     *
     * @return void
     */

    public function validate_priority($priority)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! array_key_exists($priority, $this->priorities))
            return lang('bandwidth_priority_invalid');
    }

    /**
     * Validates rate.
     *
     * @param integer $rate rate in kbit/s
     *
     * @return void
     */

    public function validate_rate($rate)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/^\d+$/", $rate))
            return lang('bandwidth_rate_invalid');
        else if (($rate > self::MAX_SPEED) || ($rate < self::MIN_SPEED))
            return lang('bandwidth_rate_out_of_range');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Loads bandwidth configuration.
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    protected function _load_configuration()
    {
        clearos_profile(__METHOD__, __LINE__);

        $config = array();
        $config['BANDWIDTH_QOS'] = FALSE;
        $config['BANDWIDTH_UPSTREAM'] = array();
        $config['BANDWIDTH_DOWNSTREAM'] = array();
        $config['BANDWIDTH_UPSTREAM_BURST'] = array();
        $config['BANDWIDTH_UPSTREAM_CBURST'] = array();
        $config['BANDWIDTH_DOWNSTREAM_BURST'] = array();
        $config['BANDWIDTH_DOWNSTREAM_CBURST'] = array();

        $file = new Configuration_File(self::FILE_CONFIG);

        if (! $file->exists())
            throw new Engine_Exception(lang('bandwidth_configuration_file_missing'));

        $rawconfig = $file->load();

        foreach ($rawconfig as $key => $value) {
            $value = trim(str_replace(array('\'', '"'), '', $value));

            if ($key == 'BANDWIDTH_QOS') {
                $config['BANDWIDTH_QOS'] = (preg_match("/on/i", $value)) ? TRUE : FALSE;
            } else if (preg_match("/^(BANDWIDTH_UPSTREAM|BANDWIDTH_DOWNSTREAM)/", $key)) {
                $pairs = explode(' ', $value);

                foreach ($pairs as $pair) {
                    list($iface, $rate) = explode(':', $pair, 2);
                    if (! empty($iface))
                        $config[$key][$iface] = $rate;
                }
            }
        }

        $this->is_loaded = TRUE;
        $this->config = $config;
    }

    /**
     * Saves bandwidth configuration.
     *
     * @return void
     * @throws Validation_Exception, Engine_Exception
     */

    protected function _save_configuration()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->is_loaded = FALSE;

        $file = new File(self::FILE_CONFIG);

        if (! $file->exists())
            $file->create('root', 'root', '644');

        foreach ($this->config as $key => $value) {
            if ($key == 'BANDWIDTH_QOS') {
                if ($value === TRUE)
                    $value = 'on';
                else
                    $value = 'off';

                if (!$file->replace_lines("/.*$key=/", "$key=\"$value\"\n"))
                    $file->add_lines("$key=\"$value\"\n");
            } else if (!count($this->config[$key])) {
                $file->replace_lines("/.*$key=/", "#$key=\"\"\n");
            } else {
                $pairs = '';

                foreach ($this->config[$key] as $iface => $rate)
                    $pairs .= "$iface:$rate ";

                $pairs = trim($pairs);

                if (!$file->replace_lines("/^.*$key=/", "$key=\"$pairs\"\n"))
                    $file->add_lines("$key=\"$pairs\"\n");
            }
        }
    }
}