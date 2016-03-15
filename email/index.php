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
 * Front page for eMail.
 *
 * @package     email
 * @copyright   2015 Toni Mas <antoni.mas@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');   // The eMail library funcions.

// For apply ajax and javascript functions.
require_once($CFG->libdir. '/ajax/ajaxlib.php');

$courseid   = optional_param('id', SITEID, PARAM_INT);          // Course Id.
$folderid   = optional_param('folderid', 0, PARAM_INT);         // folder Id.
$filterid   = optional_param('filterid', 0, PARAM_INT);         // filter Id.

$mailid     = optional_param('mailid', 0, PARAM_INT);           // Email Id.
$action     = optional_param('action', '', PARAM_ALPHANUM);     // Action to execute.

$page       = optional_param('page', 0, PARAM_INT);             // Which page to show.
$perpage    = optional_param('perpage', 10, PARAM_INT);         // How many per page.

// Only contain value, when moving mails to other folder.
$folderoldid    = optional_param('folderoldid', 0, PARAM_INT);  // Folder Id Old.

// Get course.
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($course, false); // No autologin guest.

$context = context_course::instance($course->id);
if ( !$course->visible and has_capability('moodle/legacy:student', $context, $USER->id, false) ) {
    print_error('coursehidden', 'moodle');
}

// Register view inbox or email event.
$params = array(
    'context' => $context,
    'objectid' => $mailid,
    'userid' => $USER->id,
    'courseid' => $course->id
);
$event = \block_email_list\event\email_viewed::create($params);
$event->trigger();

// Set default page parameters.
$PAGE->set_pagelayout('incourse');
$PAGE->set_context($context);

$stremail  = get_string('name', 'block_email_list');

$PAGE->set_url('/blocks/email_list/index.php', array('id' => $courseid));
$PAGE->set_title($course->shortname.': '.$stremail);

// Get actual folder, for show.
if (! $folder = \block_email_list\label::get($folderoldid)) {
    if (! $folder = \block_email_list\label::get($folderid) ) {
        // Default, is inbox.
        $folder = \block_email_list\label::get_root($USER->id, EMAIL_INBOX);
    }
}

// Print middle table.
$PAGE->set_heading(get_string('mailbox', 'block_email_list'). ': '. $folder->name);

// Print "blocks" of this account.
email_printblocks($USER->id, $courseid);

// Get renderer.
$renderer = $PAGE->get_renderer('block_email_list');

// Print the page header.
echo $renderer->header();

// Options for new mail and new folder.
$options = new stdClass();
$options->id = $courseid;
$options->course = $courseid;
$options->folderid = $folderid;
$options->filterid = $filterid;
$options->folderoldid = $folderoldid;

// Print the main part of the page.

echo '<div>&#160;</div>';

// Print tabs options.
email_print_tabs_options($courseid, $folderid);

// Prepare action.
if ( ! empty( $action ) and $mailid > 0 ) {
    // When remove an mail, this functions only accept array in param, overthere converting this param.
    if (! is_array($mailid)) {
        $mailids = array($mailid);
    } else {
        $mailids = $mailid;
    }

    // Print action in case.
    switch( $action ) {
        case 'removemail':
            // Fix bug.
            $options->folderoldid = $folderoldid;

            $success = true;
            foreach ($mailids as $mail) {
                $email = new eMail();
                $email->set_email($mail);
                $success &= $email->remove($USER->id, $courseid, $folder->id, true);
            }
            if ($success) {
                notify( get_string('removeok', 'block_email_list'), 'notifysuccess' );
            } else {
                notify(get_string('removefail', 'block_email_list'));
            }
            break;

        case 'toread':

            $success = true;
            foreach ($mailids as $mail) {
                $email = new eMail();
                $email->set_email($mail);
                $success &= $email->mark2read($USER->id, $courseid, true);
            }
            if ($success) {
                notify(get_string('toreadok', 'block_email_list'), 'notifysuccess');
            } else {
                notify(get_string('failmarkreaded', 'block_email_list'));
            }
            break;

        case 'tounread':
            $success = true;
            foreach ($mailids as $mail) {
                $email = new eMail();
                $email->set_email($mail);
                $success &= $email->mark2unread($USER->id, $courseid, true);
            }
            if ($success) {
                notify(get_string('tounreadok', 'block_email_list'), 'notifysuccess');
            } else {
                notify(get_string('failmarkunreaded', 'block_email_list'));
            }
            break;

        case 'move2folder':
            // In variable folderid.
            $success = true;
            // Move mails -- This variable is an array of ID's.
            if ( is_array($mailid) ) {
                foreach ($mailid as $mail) {
                    // Get foldermail reference.
                    $foldermail = email_get_reference2foldermail($mail, $folderoldid);

                    // Move this mail into folder.
                    if (! email_move2folder($mail, $foldermail->id, $folderid) ) {
                        $success = false;
                    }
                }
                // Show.
                if ( !$success ) {
                    notify( get_string('movefail', 'block_email_list') );
                } else {
                    notify( get_string('moveok', 'block_email_list') );
                }
            }
            // Show folders.
            $options->folderid = $folderoldid;
            break;
    }
}

// Show list all mails.
$renderer->showmails($USER->id, '', $page, $perpage, $options);

// Finish the page.
if ( isset( $course ) ) {
    echo $OUTPUT->footer($course);
} else {
    echo $OUTPUT->footer($SITE);
}