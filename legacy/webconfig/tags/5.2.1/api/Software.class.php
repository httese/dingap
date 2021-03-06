<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2006 Point Clark Networks.
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
 * Software package management tools.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('ShellExec.class.php');

///////////////////////////////////////////////////////////////////////////////
// E X C E P T I O N  C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Software not installed exception.
 *
 * @package Api
 * @subpackage Exception
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class SoftwareNotInstalledException extends EngineException
{
	/**
	 * SoftwareNotInstalledException constructor.
	 *
	 * @param string $pkgname software package name
	 * @param int $code error code
	 */

	public function __construct($pkgname, $code)
	{
		parent::__construct(SOFTWARE_LANG_ERRMSG_NOT_INSTALLED . " - $pkgname", $code);
	}
}


///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Software package management tools.
 *
 * The software classes contains information about a given RPM package.
 * The software constructor requires the pkgname - release and version are
 * optional.  Why do you need the release and version?  Some packages 
 * can have multiple version installed, notably the kernel.
 * 
 * If you do not specify the release and version name (which is the typical
 * way to call this constructor), then this class will assume that you mean
 * the most recent version.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Software extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// F I E L D S
	///////////////////////////////////////////////////////////////////////////////

	protected $pkgname = null;
	protected $copyright = null;
	protected $description = null;
	protected $installsize = null;
	protected $installtime = null;
	protected $packager = null;
	protected $release = null;
	protected $summary = null;
	protected $version = null;

	const COMMAND_RPM = '/bin/rpm';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Software constructor.
	 * 
	 * @param string $pkgname software package name
	 * @param string $release release number (optional)
	 * @param string $version version number (optional)
	 */

	function __construct($pkgname, $version = "", $release = "")
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();
		require_once(GlobalGetLanguageTemplate(__FILE__));

		if (($version) && ($release)) {
			$this->pkgname = "$pkgname-$version-$release";
		} else {
			$this->pkgname = $pkgname;
		}
	}


	/**
	 * Returns the copyright of the software - eg GPL.
	 *
	 * @return string copyright
	 * @throws EngineException, SoftwareNotInstalledException
	 */

	function GetCopyright()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (is_null($this->copyright))
			$this->LoadInfo();

		return $this->copyright;
	}


	/**
	 * Returns a long description in text format.
	 *
	 * Descriptions can be anywhere from one-sentence long to several paragraphs.
	 *
	 * @return string description
	 * @throws EngineException, SoftwareNotInstalledException
	 */

	function GetDescription()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (is_null($this->description))
			$this->LoadInfo();

		return $this->description;
	}


	/**
	 * Returns the installed size (not the download size).
	 *
	 * @return integer install size in bytes
	 * @throws EngineException, SoftwareNotInstalledException
	 */

	function GetInstallSize()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (is_null($this->installsize))
			$this->LoadInfo();

		return $this->installsize;
	}


	/**
	 * Returns install time in seconds since Jan 1, 1970.
	 *
	 * @return integer install time
	 * @throws EngineException, SoftwareNotInstalledException
	 */

	function GetInstallTime()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (is_null($this->installtime))
			$this->LoadInfo();

		return $this->installtime;
	}


	/**
	 * Returns the package name.
	 *
	 * @return string package name
	 * @throws EngineException, SoftwareNotInstalledException
	 */

	function GetPackageName()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return $this->pkgname;
	}


	/**
	 * Returns the packager.
	 *
	 * @return string packager
	 * @throws EngineException, SoftwareNotInstalledException
	 */

	function GetPackager()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (is_null($this->packager))
			$this->LoadInfo();

		return $this->packager;
	}


	/**
	 * Returns the release.  
	 *
	 * The release is not necessarily numeric!
	 *
	 * @return string release
	 * @throws EngineException, SoftwareNotInstalledException
	 */

	function GetRelease()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (is_null($this->release))
			$this->LoadInfo();

		return $this->release;
	}


	/**
	 * Returns the version.
	 *
	 * The version is not necessarily numeric!
	 *
	 * @return string version
	 * @throws EngineException, SoftwareNotInstalledException
	 */

	function GetVersion()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (is_null($this->version))
			$this->LoadInfo();

		return $this->version;
	}


	/**
	 * Returns a one-line description.
	 *
	 * @return string description
	 * @throws EngineException, SoftwareNotInstalledException
	 */

	function GetSummary()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (is_null($this->summary))
			$this->LoadInfo();

		return $this->summary;
	}


	/**
	 * Returns true if the package is installed.
	 *
	 * @return boolean true if package is installed
	 * @throws EngineException, SoftwareNotInstalledException
	 */

	function IsInstalled()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rpm = escapeshellarg($this->pkgname);
		$exitcode = 1;

		try {
			// KLUDGE: rpm does not seem to have a nice way to get around
			// running multiple rpm commands simultaneously.  You can get a
			// temporary "cannot get shared lock" error in this case.

			$shell = new ShellExec();
			$options['env'] = "LANG=en_US";

			for ($i = 0; $i < 5; $i++) {
				$exitcode = $shell->Execute(self::COMMAND_RPM, "-q $rpm 2>&1", false, $options);
				$lines = implode($shell->GetOutput());

				if (($exitcode === 1) && (preg_match("/shared lock/", $lines)))
					sleep(1);
				else
					break;
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		if ($exitcode == 0)
			return true;
		else
			return false;
	}

	/**
	 * Generic function to grab information from the RPM database.  
	 *
	 * There are dozens of bits of information in an RPM file accessible via the
	 * "rpm -q --queryformat" command.  See list of tags at
	 * http://www.rpm.org/max-rpm-snapshot/ch-queryformat-tags.html
	 *
	 * @param string $tag queryformat tag in RPM
	 * @return string value from queryformat command
	 * @throws EngineException, SoftwareNotInstalledException
	 */

	function GetRpmInfo($tag)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->IsInstalled())
			throw new SoftwareNotInstalledException($this->pkgname, COMMON_NOTICE);

		$rpm = escapeshellarg($this->pkgname);

		// For some reason, the output formatting with "rpm --last" is fubar.
		// We have to implement it here instead.

		try {
			$shell = new ShellExec();
			$exitcode = $shell->Execute(self::COMMAND_RPM, "-q --queryformat \"%{VERSION}\\n\" $rpm", false);
			if ($exitcode != 0)
				throw new EngineException(SOFTWARE_LANG_ERRMSG_LOOKUP_ERROR, COMMON_WARNING);
			$rawoutput = $shell->GetOutput();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		// More than 1 version?  Sort and grab the latest.
		if (count($rawoutput) > 1) {
			rsort($rawoutput);
			$version = $rawoutput[0];
			$rpm = escapeshellarg($this->pkgname . "-" . $version);
			unset($rawoutput);

			try {
				$exitcode = $shell->Execute(self::COMMAND_RPM, "-q --queryformat \"%{RELEASE}\\n\" $rpm", false);
				if ($exitcode != 0)
					throw new EngineException(SOFTWARE_LANG_ERRMSG_LOOKUP_ERROR, COMMON_WARNING);
				$rawoutput = $shell->GetOutput();
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_WARNING);
			}

			// More than 1 release?  Sort and grab the latest.
			if (count($rawoutput) > 1) {
				rsort($rawoutput);
				$release = $rawoutput[0];
				$rpm = escapeshellarg($this->pkgname . "-" . $version . "-" . $release);
			}
		}

		// Add formatting for bare tags (e.g. COPYRIGHT -> %{COPYRIGHT})
		if (!preg_match("/%/", $tag))
			$tag = "%{" . $tag . "}";

		unset($rawoutput);
		try {
			$exitcode = $shell->Execute(self::COMMAND_RPM, "-q --queryformat \"" . $tag . "\" $rpm", false);
			if ($exitcode != 0)
				throw new EngineException(SOFTWARE_LANG_ERRMSG_LOOKUP_ERROR, COMMON_WARNING);
			$rawoutput = $shell->GetOutput();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		return implode(" ", $rawoutput);
	}

	/**
	 * Loads all the fields in this class.
	 *
	 * @access private
	 */

	function LoadInfo()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$rawoutput = explode("|", $this->GetRpmInfo(
			"%{COPYRIGHT}|%{DESCRIPTION}|%{SIZE}|%{INSTALLTIME}|%{PACKAGER}|%{RELEASE}|%{SUMMARY}|%{VERSION}"));

		$this->copyright = $rawoutput[0];
		$this->description = $rawoutput[1];
		$this->installsize = $rawoutput[2];
		$this->installtime = $rawoutput[3];
		$this->packager = $rawoutput[4];
		$this->release = $rawoutput[5];
		$this->summary = $rawoutput[6];
		$this->version = $rawoutput[7];
	}

    /**
     * @access private
     */

    public function __destruct()
    {
        if (COMMON_DEBUG_MODE)
            $this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        parent::__destruct();
    }
}

// vim: syntax=php ts=4
?>
