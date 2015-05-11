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
 * @package 	email
 * @copyright   2015 Toni Mas <antoni.mas@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');			// eMail library funcions.

// For apply ajax and javascript functions.
require_once($CFG->libdir. '/ajax/ajaxlib.php');
require_once($CFG->dirroot.'/blocks/email_list/email/email.class.php');

//require_js('treemenu.js');
//require_js('email.js');

$courseid	= optional_param('id', SITEID, PARAM_INT); 			// Course ID
$folderid	= optional_param('folderid', 0, PARAM_INT); 		// folder ID
$filterid	= optional_param('filterid', 0, PARAM_INT);		// filter ID

$mailid		= optional_param('mailid', 0, PARAM_INT); 			// eMail ID
$action 	= optional_param('action', '', PARAM_ALPHANUM); 	// Action to execute

$page       = optional_param('page', 0, PARAM_INT);          	// which page to show
$perpage    = optional_param('perpage', 10, PARAM_INT);  		// how many per page

// Only contain value, when moving mails to other folder
$folderoldid	= optional_param('folderoldid', 0, PARAM_INT); 		// folder ID Old

// Get course.
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($course, false); // No autologin guest

$context = get_context_instance(CONTEXT_COURSE, $course->id);
if ( !$course->visible and has_capability('moodle/legacy:student', $context, $USER->id, false) ) {
	print_error('courseavailablenot', 'moodle');
}

// Add log for one course
add_to_log($courseid, 'email', 'view course mails', 'view.php?id='.$courseid, 'View all mails of '.$course->shortname, 0, $USER->id);

/// Print the page header

$preferencesbutton = email_get_preferences_button($courseid);

$stremail  = get_string('name', 'block_email_list');

if ( function_exists( 'build_navigation') ) {
	// Prepare navlinks
	$navlinks = array();
	$navlinks[] = array('name' => get_string('nameplural', 'block_email_list'), 'link' => 'index.php?id='.$course->id, 'type' => 'misc');
	$navlinks[] = array('name' => get_string('name', 'block_email_list'), 'link' => null, 'type' => 'misc');

// Build navigation
$navigation = build_navigation($navlinks);

print_header("$course->shortname: $stremail", "$course->fullname",
      $navigation,
      "", '<link type="text/css" href="email.css" rel="stylesheet" /><link type="text/css" href="treemenu.css" rel="stylesheet" /><link type="text/css" href="tree.css" rel="stylesheet" /><script type="text/javascript" src="treemenu.js"></script><script type="text/javascript" src="email.js"></script>',
      true, $preferencesbutton);
} else {
	$navigation = '';
if ( isset($course) ) {
if ($course->category) {
    $navigation = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">'.$course->shortname.'</a> ->';
}
}

$stremails = get_string('nameplural', 'block_email_list');

	print_header("$course->shortname: $stremail", "$course->fullname",
             "$navigation <a href=index.php?id=$course->id>$stremails</a> -> $stremail",
              "", '<link type="text/css" href="email.css" rel="stylesheet" /><link type="text/css" href="treemenu.css" rel="stylesheet" /><link type="text/css" href="tree.css" rel="stylesheet" /><script type="text/javascript" src="treemenu.js"></script><script type="text/javascript" src="email.js"></script>',
              true, $preferencesbutton);
}

// Options for new mail and new folder
$options = new stdClass();
$options->id = $courseid;
$options->course = $courseid;
$options->folderid = $folderid;
$options->filterid = $filterid;
$options->folderoldid = $folderoldid;

/// Print the main part of the page

// Print principal table. This have 2 columns . . .  and possibility to add right column.
echo '<table id="layout-table"><tr>';

// Print "blocks" of this account
echo '<td style="width: 180px;" id="left-column">';
email_printblocks($USER->id, $courseid);

// Close left column
echo '</td>';

// Print principal column
echo '<td id="middle-column">';

// Get actual folder, for show
if (! $folder = email_get_folder($folderoldid)) {
	if (! $folder = email_get_folder($folderid) ) {
		// Default, is inbox
		$folder = email_get_root_folder($USER->id, EMAIL_INBOX);
	}
}

// Print middle table
print_heading_block(get_string('mailbox', 'block_email_list'). ': '. $folder->name);

echo '<div>&#160;</div>';

// Print tabs options
email_print_tabs_options($courseid, $folderid);

/// Prepare action
if ( ! empty( $action ) and $mailid > 0 ) {
	// When remove an mail, this functions only accept array in param, overthere converting this param ...
	if (! is_array($mailid)) {
		$mailids = array($mailid);
	} else {
		$mailids = $mailid;
	}

	// Print action in case . . .
	switch( $action ) {
		case 'removemail':
				// Fix bug
				$options->folderoldid = $folderoldid;

				$success = true;
				foreach ( $mailids as $mail ) {
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
				foreach ( $mailids as $mail ) {
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
				foreach ( $mailids as $mail ) {
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
				// In variable folderid
				$success = true;
				// Move mails -- This variable is an array of ID's
				if (is_array($mailid) ) {
					foreach ( $mailid as $mail ) {
						// Get foldermail reference
						$foldermail = email_get_reference2foldermail($mail, $folderoldid);

						// Move this mail into folder
						if (! email_move2folder($mail, $foldermail->id, $folderid) ) {
							$success = false;
						}
					}
					// Show
					if (! $success ) {
						notify( get_string('movefail', 'block_email_list') );
					} else {
						notify( get_string('moveok', 'block_email_list') );
					}
				}
				// Show folders
				$options->folderid = $folderoldid;
			break;
	}
}

//Show list all mails
email_showmails($USER->id, '', $page, $perpage, $options);

// Close principal column
echo '</td>';

// Close table
echo '</tr> </table>';

/// Finish the page
if ( isset( $course ) ) {
	print_footer($course);
} else {
	print_footer($SITE);
}