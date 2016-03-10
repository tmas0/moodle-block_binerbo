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
 * Class of preferences form.
 *
 * @author Toni Mas
 * @version 1.0
 * @package email
 * @license The source code packaged with this file is Free Software, Copyright (C) 2006 by
 *          <toni.mas at uib dot es>.
 *          It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
 *          You can get copies of the licenses here:
 *                         http://www.affero.org/oagpl.html
 *          AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".
 */

global $CFG;

require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/blocks/email_list/email/lib.php');

class preferences_form extends moodleform {

    // Define the form.
    public function definition () {
        global $CFG, $COURSE;

        $mform =& $this->_form;

        // Print the required moodle fields first.
        $mform->addElement('header', 'moodle', get_string('preferences', 'block_email_list'));

        // Options.
        $options = array(0 => get_string('no'), 1 => get_string('yes'));

        if ( $CFG->email_trackbymail ) {
            $mform->addElement('select', 'trackbymail', get_string('sendmail', 'block_email_list'), $options);
            $mform->setDefault('trackbymail', 0);
        }

        if ( $CFG->email_marriedfolders2courses ) {
            // Married folder at courses.
            $mform->addElement('select',
                'marriedfolders2courses',
                get_string('marriedfolders2courses', 'block_email_list'),
                $options
            );
            $mform->setDefault('marriedfolders2courses', 0);
        }

        // Add some extra hidden fields.
        $mform->addElement('hidden', 'id', $COURSE->id);

        // Buttons.
        $this->add_action_buttons();
    }
}
