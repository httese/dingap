<?php

/**
 * Group item view.
 *
 * @category   Apps
 * @package    Groups
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/groups/
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
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('groups');
$this->lang->load('users');

///////////////////////////////////////////////////////////////////////////////
// Buttons
///////////////////////////////////////////////////////////////////////////////

if (empty($basename)) {
    $base_app = '/app/groups';
    $form = '/groups/edit_members/' . $group_info['group_name'];
} else {
    $base_app = '/app/' . $basename . '/policy';
    $form = $basename . '/policy/edit_members/' . $group_info['group_name'];
}

if ($mode === 'view')
    $buttons = array(anchor_cancel($base_app));
else
    $buttons = array(anchor_cancel($base_app, 'high'), form_submit_update('submit', 'high'));

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('users_username'),
    lang('users_full_name'),
);

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

foreach ($users as $username => $details) {
    $item['title'] = $username;
    $item['name'] = 'users[' . $username . ']';
    $item['state'] = (in_array($username, $group_info['members'])) ? TRUE : FALSE;
    $item['details'] = array(
        $username,
        $details['core']['full_name']
    );

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// List table
///////////////////////////////////////////////////////////////////////////////

echo form_open($form);

// FIXME: implement read_only in theme
echo list_table(
    $group_info['description'],
    $buttons,
    $headers,
    $items,
    array('read_only' => TRUE)
);

echo form_close();
