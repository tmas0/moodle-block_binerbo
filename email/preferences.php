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
 * This page apply changes on my preferences.
 *
 * @author Toni Mas
 * @version 1.0.0
 * @package email
 * @license The source code packaged with this file is Free Software, Copyright (C) 2006 by
 *          <toni.mas at uib dot es>.
 *          It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
 *          You can get copies of the licenses here:
 *                         http://www.affero.org/oagpl.html
 *          AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot.'/blocks/email_list/email/lib.php');
require_once($CFG->dirroot.'/blocks/email_list/email/preferences_form.php');

$courseid = optional_param('id', SITEID, PARAM_INT); // Course ID.

// If defined course to view.
if ( !$course = $DB->get_record('course', array('id' => $courseid)) ) {
    print_error('courseavailablenot', 'moodle');
}

require_login($course->id, false); // No autologin guest.

// Get renderer.
$renderer = $PAGE->get_renderer('block_email_list');

if ($course->id == SITEID) {
    $context = context_system::instance(); // SYSTEM context.
} else {
    $context = context_course::instance($course->id); // Course context.
}

// Can edit settings?.
if ( !has_capability('block/email_list:editsettings', $context) ) {
    print_error('forbiddeneditsettings',
        'block_email_list',
        $CFG->wwwroot . '/blocks/email_list/email/index.php?id=' . $course->id
    );
}

// Security enable user's preference.
if ( empty($CFG->email_trackbymail) and empty($CFG->email_marriedfolders2courses) ) {
    redirect( $CFG->wwwroot . '/blocks/email_list/email/index.php?id' . $courseid,
        get_string('preferencesnotenable', 'block_email_list', '2')
    );
}

// Set default page parameters.
$PAGE->set_pagelayout('incourse');
$PAGE->set_context($context);
$PAGE->set_url('/blocks/email_list/email/preferences.php',
    array(
        'id' => $course->id
    )
);

// Options for new mail and new folder.
$options = new stdClass();
$options->id = $courseid;

// Print the page header.

$stremail = get_string('name', 'block_email_list');
$PAGE->set_title($course->shortname . ': ' . $stremail);

// Print the page header.
echo $renderer->header();

email_printblocks($USER->id, $courseid);

$mform = new preferences_form('preferences.php');

if ( $mform->is_cancelled() ) {
    // Only redirect.
    redirect($CFG->wwwroot . '/blocks/email_list/email/index.php?id=' . $courseid, '', 0);

} else if ( $form = $mform->get_data() ) {
    // Add log for one course.
    add_to_log($courseid, 'email', 'edit preferences', 'preferences.php', 'Edit my preferences', 0, $USER->id);

    $preference = new stdClass();

    if ( $DB->record_exists('email_preference', array('userid' => $USER->id)) ) {

        if ( !$preference = $DB->get_record('email_preference', array('userid' => $USER->id)) ) {
            print_error('failreadingpreferences',
                'block_email_list',
                $CFG->wwwroot . '/blocks/email_list/email/index.php?id=' . $courseid
            );
        }

        // Security.
        if ( $CFG->email_trackbymail ) {
            $preference->trackbymail = $form->trackbymail;
        } else {
            $preference->trackbymail = 0;
        }
        // Security.
        if ( $CFG->email_marriedfolders2courses ) {
            $preference->marriedfolders2courses = $form->marriedfolders2courses;
        } else {
            $preference->marriedfolders2courses = 0;
        }

        if ( $DB->update_record('email_preference', $preference) ) {
            redirect( $CFG->wwwroot . '/blocks/email_list/email/index.php?id=' . $courseid,
                get_string('savedpreferences', 'block_email_list'),
                '2'
            );
        }
    } else {

        $preference->userid = $USER->id;

        // Security.
        if ( $CFG->email_trackbymail ) {
            $preference->trackbymail = $form->trackbymail;
        } else {
            $preference->trackbymail = 0;
        }
        // Security.
        if ( $CFG->email_marriedfolders2courses ) {
            $preference->marriedfolders2courses = $form->marriedfolders2courses;
        } else {
            $preference->marriedfolders2courses = 0;
        }

        if ( $DB->insert_record('email_preference', $preference) ) {
            redirect( $CFG->wwwroot . '/blocks/email_list/email/index.php?id=' . $courseid,
                get_string('savedpreferences', 'block_email_list'),
                '2'
            );
        }
    }

    error( get_string('errorsavepreferences',
        'block_email_list'),
        $CFG->wwwroot.'/blocks/email_list/email/index.php?id=' . $courseid
    );
} else {

    // Get my preferences, if I have.
    $preferences = $DB->get_record('email_preference', array('userid' => $USER->id));

    // Add course.
    $preferences->id = $courseid;

    // Set data.
    $mform->set_data($preferences);
    $mform->display();
}

echo $renderer->footer();
