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
 * Class of folder form.
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

class folder_form extends moodleform {

    // Define the form.
    public function definition () {
        global $CFG, $USER, $DB;

        $mform =& $this->_form;

        // Get customdata.
        $action          = $this->_customdata['action'];
        $courseid        = $this->_customdata['course'];
        $folderid        = $this->_customdata['id'];

        // Print the required moodle fields first.
        $mform->addElement('header', 'moodle', get_string('folder', 'block_email_list'));

        $mform->addElement('text', 'name', get_string('namenewfolder', 'block_email_list'));
        $mform->setDefault('name', '');
        $mform->addRule('name', get_string('nofolder', 'block_email_list'), 'required', null, 'client');

        // Get root folders.
        $folders = email_get_my_folders($USER->id, $courseid, true, true);

        // Get inbox, there default option on menu.
        $inbox = \block_email_list\label::get_root($USER->id, EMAIL_INBOX);

        $menu = array();

        // Insert into menu, only name folder.
        foreach ($folders as $key => $foldername) {
            $menu[$key] = $foldername;
        }

        if ( $parent = email_get_parent_folder($folderid) ) {
            $parentid = $parent->id;
        } else {
            $parentid = 0;
        }

        // Select parent folder.
        $mform->addElement('select', 'parentfolder', get_string('linkto', 'block_email_list'), $menu);
        $mform->setDefault('parentfolder', $parentid);

        $mform->addElement('hidden', 'gost');

        if ( $preference = $DB->get_record('email_preference', array('userid' => $USER->id)) ) {
            if ( $preference->marriedfolders2courses ) {
                // Get my courses..
                $mycourses = get_my_courses($USER->id);

                $courses = array();
                // Prepare array.
                foreach ($mycourses as $mycourse) {
                    if ( strlen($mycourse->fullname) > 60 ) {
                        $course = substr($mycourse->fullname, 0, 60) . ' ...';
                    } else {
                        $course = $mycourse->fullname;
                    }
                    $courses[$mycourse->id] = $course;
                }
                $mform->addElement('select', 'foldercourse', get_string('course'), $courses);
                $mform->setDefault('foldercourse', $courseid);
            }
        }

        // Add some extra hidden fields.
        $mform->addElement('hidden', 'course', $courseid);
        $mform->addElement('hidden', 'oldname');
        $mform->addElement('hidden', 'id');
        $mform->addElement('hidden', 'action', $action);

        // Buttons.
        $this->add_action_buttons();
    }

    public function definition_after_data() {
        global $USER;

        // Drop actualfolder if it proceding.
        $mform    =& $this->_form;

        // Get parentfolder.
        $parentfolder =& $mform->getElementValue('parentfolder');

        // Get (actual) folderid.
        $folderid =& $mform->getElementValue('id');

        // Drop element.
        $mform->removeElement('parentfolder');

        // Get root folders.
        $folders = email_get_my_folders($USER->id, $mform->getElementValue('course'), true, true);

        // Get inbox, there default option on menu.
        $inbox = \block_email_list\label::get_root($USER->id, EMAIL_INBOX);

        $menu = array();

        // Insert into menu, only name folder.
        foreach ($folders as $key => $foldername) {
            if ( $key != $folderid ) {
                $menu[$key] = $foldername;
            }
        }

        // Select parent folder.
        $select = &MoodleQuickForm::createElement('select', 'parentfolder', get_string('linkto', 'block_email_list'), $menu);
        $mform->insertElementBefore($select, 'gost');
        $mform->setDefault('parentfolder', $parentfolder);

    }
}