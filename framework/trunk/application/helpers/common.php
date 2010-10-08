<?php

//////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 ClearFoundation
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * Base settings, functions and environment for the ClearOS framework.
 *
 * The functions and environment in this file are shared by both the base API
 * and the front-end modules.  For example, the COMMON_TEMP_DIR constant is
 * accessible in an API class file, a view, or in a controller.
 *
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Framework
 * @copyright Copyright 2010, ClearFoundation
 */

//////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Logger.php');
require_once('Error.php');


///////////////////////////////////////////////////////////////////////////////
// E N V I R O N M E N T 
///////////////////////////////////////////////////////////////////////////////

// The environment is defined in /usr/clearos/framework/config.php or,
// if you are in development mode, a file defined by the CLEAROS_CONFIG
// environment variable.


///////////////////////////////////////////////////////////////////////////////
// T I M E  Z O N E
///////////////////////////////////////////////////////////////////////////////

// date_default_timezone_set must be called or the time zone must be set
// in PHP's configuration when date() functions are called.  On a ClearOS 
// system, the default time zone for the system is correct.

@date_default_timezone_set(@date_default_timezone_get());


///////////////////////////////////////////////////////////////////////////////
// L O G G I N G
///////////////////////////////////////////////////////////////////////////////

// FIXME: this might be a temporary hack... test it
@ini_set('include_path', '.');

if (ClearOsEnvironment::$debug_mode) {
    @ini_set('display_errors', true); 
    @ini_set('display_startup_error', true);
    @ini_set('log_errors', true);
    @ini_set('error_log', ClearOsEnvironment::$debug_log_path . '/framework_log');
}


///////////////////////////////////////////////////////////////////////////////
// C L E A R O S  G L O B A L  F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

/**
 * Pulls in a library.
 *
 * This function makes it possible to load different library versions -
 * a very useful feature in development environments.
 */

function clearos_load_library($fulllibrary) {
    list($app, $library) = split('/', $fulllibrary);

	// FIXME: point to online document on what's going on here
	if (!empty(ClearOsEnvironment::$clearos_devel_versions['app'][$app]))
		$version = ClearOsEnvironment::$clearos_devel_versions['app'][$app];
	else if (!empty(ClearOsEnvironment::$clearos_devel_versions['app']['default']))
		$version = ClearOsEnvironment::$clearos_devel_versions['app']['default'];
	else
		$version = '';

    require_once(ClearOsEnvironment::$apps_path . '/' . $app . '/' . $version . '/libraries/' . $library . '.php');
}

function clearos_plugin($basepath, $fullmethod, $options = null) {
    list($class, $method) = preg_split('/::/', $fullmethod);
    echo "plugin debug $basepath -- $class -- $method\n";
}


///////////////////////////////////////////////////////////////////////////////
// E R R O R  A N D  E X C E P T I O N  H A N D L E R S
///////////////////////////////////////////////////////////////////////////////

/** 
 * Error handler used by set_error_handler().
 *
 * @access private
 * @param integer $errno error number
 * @param string $errmsg error message
 * @param string $file file name where occurred
 * @param integer $line line in file where the error occurred
 * @param array $context entire context where error was triggered
 */

function _clearos_error_handler($errno, $errmsg, $file, $line, $context)
{
	// If the @ symbol was used to suppress errors, bail
	//--------------------------------------------------

    if (error_reporting(0) === 0)
        return;

    // Log the error
	//--------------

    $error = new Error($errno, $errmsg, $file, $line, $context, Error::TYPE_ERROR, false);
    Logger::Log($error);

    // Show error on standard out if running from command line
	//--------------------------------------------------------

    if (preg_match('/cli/', php_sapi_name())) {
		$errstring = $error->GetCodeString();
        echo $errstring . ": " . $errmsg . " - $file ($line)\n";
	}
}

/**
 * Exception handler used by set_exception_handler().
 * 
 * @access private
 * @param Exception $exception exception object
 */

function _clearos_exception_handler(Exception $exception)
{
	// Log the exception
	//------------------

    Logger::LogException($exception, false);

    // Show error on standard out if running from command line
	//--------------------------------------------------------

    if (preg_match('/cli/', php_sapi_name()))
        echo "Fatal - uncaught exception: " . $exception->getMessage() . "\n";
    else
        echo "<div>Ooooops: " . $exception->getMessage() . "</div>";
}

// Set error and exception handlers
//---------------------------------

set_error_handler("_clearos_error_handler");
set_exception_handler("_clearos_exception_handler");

// vim: syntax=php ts=4
