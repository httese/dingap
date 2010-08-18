#!/usr/webconfig/bin/php -q
<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006-2007 Point Clark Networks
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

require_once(dirname($_SERVER['argv'][0]) . '/avscan.inc.php');

// Fatal error
function fatal_error($code, $error, $filename = null, $line = 0)
{
	if ($filename != null) printf('%s:%d: ', basename($filename), $line);
	echo("$error\n");
	exit($code);
}

// Register custom error handler
set_error_handler('fatal_error');

// Debug mode?
$debug = false;
$ph = popen('/usr/bin/tty', 'r');
list($tty) = chop(fgets($ph, 4096));
pclose($ph);
if ($tty != 'not a tty') $debug = true;

// Must be run as root
if (posix_getuid() != 0) {
	fatal_error(1, 'Must be run as superuser (root)');
}

// Ensure we are the only instance running
if (file_exists(AVSCAN_LOCKFILE)) {
	$fh = @fopen(AVSCAN_LOCKFILE, 'r');
	list($pid) = fscanf($fh, '%d');

	// Perhaps this is a stale lock file?
	if (!file_exists("/proc/$pid")) {
		// Yes, the process 'appears' to no longer be running...
		@unlink(AVSCAN_LOCKFILE);
	} else {
		// Only one instance can run at a time
		fatal_error(1, 'A scan is already in progress');
	}

	fclose($fh);
} else {
	// Grab the lock ASAP...
	touch(AVSCAN_LOCKFILE);
}

// Register a shutdown function so we can do some clean-up
function avscan_shutdown()
{
	@unlink(AVSCAN_LOCKFILE);
}

register_shutdown_function('avscan_shutdown');

// Lock state file, write serialized state
function serialize_state($fh)
{
	global $av_state;

	if (SerializeState($fh, $av_state) == false)
		fatal_error(1, 'State serialization failure');
}

// Lock state file, read and unserialized status
function unserialize_state($fh)
{
	global $av_state;

	if (UnserializeState($fh, $av_state) === false)
		fatal_error(1, 'State unserialization failure');
}

// Save our PID to the lock file
$fh = @fopen(AVSCAN_LOCKFILE, 'w');

fprintf($fh, "%d\n", posix_getpid());
fclose($fh);

// Open configuration (list of directories to recursively scan).  Read it
// into an array quicky in case webconfig writes over it (no file locking)
$dirs = array();
$fh = @fopen(AVSCAN_CONFIG, 'r');

while (!feof($fh)) {
	$dir = chop(fgets($fh, 4096));
	if (strlen($dir) && file_exists($dir)) $dirs[] = $dir;
}

fclose($fh);
sort($dirs);

// Open state file.  This is where we dump scanner status
$fh = @fopen(AVSCAN_STATE, 'a+');
chown(AVSCAN_STATE, 'webconfig');
chgrp(AVSCAN_STATE, 'webconfig');
chmod(AVSCAN_STATE, 0750);
unserialize_state($fh);
$av_state['timestamp'] = time();
serialize_state($fh);

// Scan each directory for: TEH VIRUSES!
foreach ($dirs as $dir) {
	$safe_dir = escapeshellarg($dir);

	$av_state['dir'] = $dir;
	$av_state['filename'] = '-';
	$av_state['count'] = 0;

	serialize_state($fh);

	// First, find out how many files we'll be scanning so we can have a
	// nifty progress bar in webconfig
	if ($debug) echo("Counting files: $safe_dir\n");
	$ph = popen("find $safe_dir -type f | wc -l", 'r');
	list($av_state['total']) = fscanf($ph, '%d');
	if (pclose($ph)) fatal_error(1, "Unable to determine file count in: $safe_dir");

	serialize_state($fh);

	// Run scanner...
	if ($debug) echo("Scanning directory: $safe_dir\n");
	$ph = popen(AVSCAN_SCANNER . " --stdout -r $safe_dir 2>/dev/null", 'r');

	while (!feof($ph)) {
		$buffer = fgets($ph, 4096);
		if (($pos = strrpos($buffer, ':')) === false) break;

		// Sync our state file, may have been modified externally...
		unserialize_state($fh);

		$av_state['count']++;

		// Extract filename and scan result
		$av_state['filename'] = substr($buffer, 0, $pos);
		$av_state['result'] = substr(chop($buffer), $pos + 2);

		$hash = md5($av_state['filename']);

		// Evaluate result
		switch ($av_state['result']) {
		case 'OK':
		case 'Empty file':
			break;
		case 'ERROR':
			// Remember files with errors...
			$av_state['error'][$hash] = array('filename' => $av_state['filename'],
				'error' => $av_state['result'], 'timestamp' => time());
			break;
		default:
			// Virus found...
			$av_state['virus'][$hash] = array('filename' => $av_state['filename'],
				'virus' => str_replace(' FOUND', '', $av_state['result']),
				'dir' => $dir, 'timestamp' => time());
			break;
		}

		serialize_state($fh);

		if ($debug) printf('%.02f: %s', $av_state['count'] * 100 / $av_state['total'], $buffer);
	}

	$av_state['rc'] = pclose($ph);

	serialize_state($fh);
}

fclose($fh);

// vi: ts=4 syntax=php
?>
