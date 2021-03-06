<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007 Point Clark Networks.
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

require_once("../../gui/Webconfig.inc.php");
require_once("../../api/Network.class.php");

// For locale
$network = new Network();

echo "
var getWanStatusCallback = {
	success : function(o) {
		var root = o.responseXML.documentElement; 
		var wanstatus_node = root.getElementsByTagName('wanstatus')[0];
		var wanstatus = (wanstatus_node) ? wanstatus_node.firstChild.nodeValue : '" . LOCALE_LANG_UNKNOWN . "';

		if (wanstatus == 'online')
			wanhtml = '" . preg_replace("/'/", "\"", WEBCONFIG_ICON_ENABLED) . " <span class=\'ok\'>" . NETWORK_LANG_CONNECTED . "</span>';
		else
			wanhtml = '" . preg_replace("/'/", "\"", WEBCONFIG_ICON_LOADING) . " " .NETWORK_LANG_WAITING_FOR_CONNECTION . "';

		var wanstatus = document.getElementById('wanstatus');
		wanstatus.innerHTML = wanhtml;

		var conn = YAHOO.util.Connect.asyncRequest('GET', '/public/proxy.xml.php?nocache=' + new Date().getTime(), getWanStatusCallback);
	},

	failure : function(o) {
		var wanstatus = document.getElementById('wanstatus');
		wanstatus.innerHTML = '';
	}
};

function getWanStatus() {
	var wanstatus = document.getElementById('wanstatus');
	wanstatus.innerHTML = '" . preg_replace("/'/", "\"", WEBCONFIG_ICON_LOADING) . "';

	var conn = YAHOO.util.Connect.asyncRequest('GET', '/public/proxy.xml.php?nocache=' + new Date().getTime(), getWanStatusCallback);
};

YAHOO.util.Event.onContentReady('wanstatus', getWanStatus);

";

?>
