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
 * This file used for print email.
 *
 * @author Toni Mas
 * @version 1.3
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

$mailid     = required_param('id', PARAM_INT);              // Email ID.
$courseid   = optional_param('course', SITEID, PARAM_INT);  // Course ID.
$action     = optional_param('action', '', PARAM_ALPHANUM); // Action to execute.
$folderid   = optional_param('folderid', 0, PARAM_INT);     // folder ID.

$mails      = optional_param('mails', '', PARAM_ALPHANUM);  // Next and previous mails.
$selectedusers = optional_param('selectedusers', '', PARAM_ALPHANUM); // User who send mail.

// If defined course to view.
if ( !$course = $DB->get_record('course', array('id' => $courseid)) ) {
    print_error('invalidcourseid', 'block_email_list');
}

if ($course->id == SITEID) {
    $context = context_system::instance();   // SYSTEM context.
} else {
    $context = context_course::instance($course->id);   // Course context.
}

// The eMail.
$email = new \block_email_list\email();
$email->set_email($mailid);

require_login($course->id, false); // No autologin guest.

// Get renderer.
$renderer = $PAGE->get_renderer('block_email_list');

// Add log for one course.
add_to_log($courseid, 'email', 'view mail', 'view.php?id=$mailid', "View mail: ".$email->subject, 0, $USER->id);

// Set default page parameters.
$PAGE->set_pagelayout('incourse');
$PAGE->set_context($context);
$PAGE->set_url('/blocks/email_list/email/view.php',
    array(
        'id' => $mailid,
        'course' => $course->id
    )
);

// Print the page header.

$preferencesbutton = email_get_preferences_button($courseid);

// Add subject on information page.
$stremail .= ' :: '.$email->subject;

$PAGE->set_title($course->shortname . ': ' . $stremail);

$PAGE->set_heading(get_string('mailbox', 'block_email_list'). ': '. $folder->name);

// Print the page header.
echo $renderer->header();

// Print "blocks" of this account.
email_printblocks($USER->id, $courseid);

// Options.
$options = new stdClass();
$options->id = $mailid;
$options->mailid = $mailid;
$options->course = $courseid;
$options->folderid = $folderid;

// Prepare url's to sending.
$baseurl = email_build_url($options);

// Print the main part of the page.

// Get actual folder, for show.
if ( !$folder = email_get_folder($folderid) ) {
    // Default, is inbox.
    $folder->name = get_string('inbox', 'block_email_list');
}

unset($options->id);

// Print action in case . . .
// Get user, for show this fields.
if ( !$user = $DB->get_record('user', array('id' => $USER->id)) ) {
    notify('Fail reading user');
}

// Prepare next and previous mail.
if ( $mails ) {
    $urlnextmail  = '';
    $next = email_get_nextprevmail($mailid, $mails, true);
    if ( $next ) {
        $action = (PHP_VERSION < 5) ? $options : clone($options);   // Thanks Ann.
        $action->id = $next;
        $urlnextmail  = email_build_url($action);
        $urlnextmail .= '&amp;mails='. $mails;
        $urlnextmail .= '&amp;action='.EMAIL_VIEWMAIL;
    }

    $urlpreviousmail  = '';
    $prev = email_get_nextprevmail($mailid, $mails, false);
    if ( $prev ) {
        $action = (PHP_VERSION < 5) ? $options : clone($options);   // Thanks Ann.
        $action->id = $prev;
        $urlpreviousmail  = email_build_url($action);
        $urlpreviousmail .= '&amp;mails='. $mails;
        $urlpreviousmail .= '&amp;action='.EMAIL_VIEWMAIL;
    }
}

add_to_log("$email->userid : $email->course", "email", "view mail", "view.php?$baseurl&amp;action=".EMAIL_VIEWMAIL);

$email->display($courseid,
    $folderid,
    $urlpreviousmail,
    $urlnextmail,
    $baseurl,
    $user,
    has_capability('moodle/site:viewfullnames', $context)
);

// Finish the page.
echo $renderer->footer();
