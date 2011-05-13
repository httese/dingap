<?php

///////////////////////////////////////////////////////////////////////////////
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

header('Content-Type:application/x-javascript');
?>

$(document).ready(function() {
	$("#sync").click(function(){
		$("#result").html('<div class="theme-loading"></div>');

		$.ajax({
			url: 'date/sync',
			method: 'GET',
			dataType: 'json',
			success : function(payload) {
				showData(payload);
            },
			error: function (XMLHttpRequest, textStatus, errorThrown) {
				$("#result").html('Ooops: ' + textStatus);
			}

		});
	});

	function showData(payload) {
        $("#result").html(payload.data.diff);
	}
});

// vim: ts=4 syntax=javascript
