<?php

/**
 * Theme configuration controller.
 *
 * @category   Apps
 * @package    Base
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/base/
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
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Theme configuration controller.
 *
 * @category   Apps
 * @package    Base
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/base/
 */

class Theme extends ClearOS_Controller
{
    /**
     * Theme overview.
     *
     * @return view
     */

    function index()
    {
        echo "nothing to see here";
    }

    /**
     * Sets given them.
     *
     * @param string $theme theme name
     *
     * @return view
     */

    function set($theme)
    {
        // TODO -- just a temporary hack for testing

        if ($theme === 'clearos6x') {
            $this->session->set_userdata('theme', 'clearos6x');
            $this->session->set_userdata('theme_mode', 'normal');
        } else if ($theme === 'clearos6xmobile') {
            $this->session->set_userdata('theme', 'clearos6xmobile');
            $this->session->set_userdata('theme_mode', 'control_panel');
        }

        $this->load->library('user_agent');

        if ($this->agent->is_referral()) {
            $baseapp = preg_replace('/.*\/app\//', '', $this->agent->referrer());
            redirect('/' . $baseapp);
        } else {
            redirect('/base/index');
        }
    }
}
