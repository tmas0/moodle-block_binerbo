<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Settings for eMail List.
 *
 * @package     email
 * @copyright   2015 Toni Mas <antoni.mas@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    $options = array('0' => get_string('no'), '1' => get_string('yes'));

    // General options.

    // Track by email.
    $settings->add(new admin_setting_configselect('block_binerbo/trackbymail', get_string('trackbymail', 'block_binerbo'),
                       get_string('configtrackbymail', 'block_binerbo'), '1', $options));

    // Labels by course.
    $settings->add(new admin_setting_configselect('block_binerbo/marriedlabels2courses',
        get_string('marriedlabels2courses', 'block_binerbo'),
        get_string('configmarriedlabels2courses', 'block_binerbo'),
        '1',
        $options)
    );

    // Add admins in possible users sent.
    $settings->add(new admin_setting_configselect('block_binerbo/add_admins', get_string('add_admins', 'block_binerbo'),
                       get_string('configaddadmins', 'block_binerbo'), '0', $options));

    // The eMail Colors.

    // Answered color.
    $name = 'block_binerbo/answered_color';
    $title = get_string('answeredcolor', 'block_binerbo');
    $description = get_string('answeredcolor_desc', 'block_binerbo');
    $default = '#333366';
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default, null, false);
    $settings->add($setting);

    // Table color.
    $name = 'block_binerbo/table_field_color';
    $title = get_string('tablefieldcolor', 'block_binerbo');
    $description = get_string('tablefieldcolor_desc', 'block_binerbo');
    $default = '#333366';
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default, null, false);
    $settings->add($setting);

    // Block settings.
    $options = array();
    $options[0] = get_string('all');
    $options[5] = 5;
    $options[10] = 10;
    $options[15] = 15;
    $options[20] = 20;
    $options[25] = 25;
    $options[30] = 30;
    $options[35] = 35;
    $options[40] = 40;
    $options[45] = 45;
    $options[50] = 50;

    // Number of displayed courses.
    $settings->add(new admin_setting_configselect('block_binerbo/max_number_courses',
        get_string('maxnumbercourses', 'block_binerbo'),
        get_string('configmaxnumbercourses', 'block_binerbo'),
        '0',
        $options)
    );
}
