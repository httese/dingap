<?php

/**
 * Global ajax helpers.
 *
 * @category   ClearOS
 * @package    Base
 * @subpackage Javascript
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/base/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.  
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');

///////////////////////////////////////////////////////////////////////////////
// J A V A S C R I P T
///////////////////////////////////////////////////////////////////////////////

header('Content-Type:application/x-javascript');
?>

///////////////////////////////////////////////////////////////////////////
// M A I N
///////////////////////////////////////////////////////////////////////////

$(document).ready(function() {
    var clearos_daemon_name = $('#clearos_daemon_name').val();
    var clearos_app_name = $('#clearos_app_name').val();

    if (clearos_daemon_name)
        clearosDaemon(clearos_daemon_name, clearos_app_name);
    $('tbody', $('#sidebar_summary_table')).append(
      '<tr>' +
      '  <td><b><?php echo lang('base_status'); ?></b></td>' +
      '  <td id=\'clearos_daemon_status\'><div class=\'theme-loading-small\'></div></td>' +
      '</tr>' +
      '<tr>' +
      '  <td><b><?php echo lang('base_action'); ?></b></td>' +
      '  <td>' +
      '  <a href=\'#\' id=\'clearos_daemon_action\' class=\'theme-button-set-first theme-button-set-last theme-anchor theme-anchor-javascript theme-anchor-important\'>' +
      '  </a>' +
      '</td>' +
      '</tr>'
    );

    // Click Events
    //-------------

    $('#clearos_daemon_action').click(function(e) {
        var service_status = $("#clearos_daemon_action").html();
        $("#clearos_daemon_action").html('<span class="theme-loading-small" style="padding-right: 5px; height: 15px; margin-bottom: -5px;"></span>');
        // Hacks to get style right
        $(".theme-loading-small").css('background-position', '5 0');
        $(".theme-loading-small").css('padding-left', '5');

        if ($('#clearos_daemon_status_lock').val() == 'on') {
            // Prevent double click
        } else if (service_status == lang_stop) {
            $('#clearos_daemon_status_lock').val('on');
            $('#clearos_daemon_status').html(lang_stopping + '<span class="theme-loading"></span>');
            clearosStopDaemon(clearos_daemon_name);
        } else {
            $('#clearos_daemon_status_lock').val('on');
            $('#clearos_daemon_status').html(lang_starting + '<span class="theme-loading"></span>');
            clearosStartDaemon(clearos_daemon_name);
        }
    });

    // Main
    //-----

});

///////////////////////////////////////////////////////////////////////////
// D A E M O N
///////////////////////////////////////////////////////////////////////////

function clearosDaemon(daemon, app_name) {

    // Translations
    //-------------

    lang_busy = '<?php echo lang("base_busy"); ?>';
    lang_restarting = '<?php echo lang("base_restarting"); ?>';
    lang_running = '<?php echo lang("base_running"); ?>';
    lang_start = '<?php echo lang("base_start"); ?>';
    lang_starting = '<?php echo lang("base_starting"); ?>';
    lang_stop = '<?php echo lang("base_stop"); ?>';
    lang_stopping = '<?php echo lang("base_stopping"); ?>';
    lang_stopped = '<?php echo lang("base_stopped"); ?>';
    basename = '/app/' + app_name + '/server';

    $('#clearos_daemon_status').html('');

    clearosGetDaemonStatus();
}

// Functions
//----------

function clearosStartDaemon(daemon) {
    $.ajax({
        url: basename + '/start/' + daemon, 
        method: 'GET',
        dataType: 'json',
        success : function(payload) {
            $('#clearos_daemon_status_lock').val('off');
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
        }
    });
}

function clearosStopDaemon(daemon) {
    $.ajax({
        url: basename + '/stop/' + daemon, 
        method: 'GET',
        dataType: 'json',
        success : function(payload) {
            $('#clearos_daemon_status_lock').val('off');
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
        }
    });
}

function clearosGetDaemonStatus(daemon, timeout) {

    var clearos_daemon_name = $('#clearos_daemon_name').val();

    $.ajax({
        url: basename + '/status/' + clearos_daemon_name, 
        method: 'GET',
        dataType: 'json',
        success : function(payload) {
            var clearos_daemon_status_lock = $('#clearos_daemon_status_lock').val();
            if (clearos_daemon_status_lock == 'off')
                clearosShowDaemonStatus(payload);

            window.setTimeout(clearosGetDaemonStatus, 1000);
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            window.setTimeout(clearosGetDaemonStatus, 1000);
        }
    });
}

function clearosShowDaemonStatus(payload) {
    if (payload.status == 'running') {
        $("#clearos_daemon_status").html(lang_running);
        $("#clearos_daemon_action").html(lang_stop);
    } else if (payload.status == 'stopped') {
        $("#clearos_daemon_status").html(lang_stopped);
        $("#clearos_daemon_action").html(lang_start);
    } if (payload.status == 'starting') {
        $('#clearos_daemon_status').html(lang_starting + '<span class="theme-loading"></span>');
    } if (payload.status == 'stopping') {
        $('#clearos_daemon_status').html(lang_stopping + '<span class="theme-loading"></span>');
    } if (payload.status == 'restarting') {
        $('#clearos_daemon_status').html(lang_restarting + '<span class="theme-loading"></span>');
    } if (payload.status == 'busy') {
        $('#clearos_daemon_status').html(lang_busy + '<span class="theme-loading"></span>');
    }
}

// vim: syntax=javascript
