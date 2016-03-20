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
 * This page recive an actions for folder's
 *
 * @uses $CFG, $COURSE
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

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot.'/blocks/email_list/email/lib.php');
require_once($CFG->dirroot.'/blocks/email_list/email/folder_form.php');

$id         = optional_param('id', 0, PARAM_INT);               // Folder Id.
$courseid   = optional_param('course', SITEID, PARAM_INT);      // Course Id.
$action     = optional_param('action', '', PARAM_ALPHANUM);     // Action.

// If defined course to view.
if ( !$course = $DB->get_record('course', array('id' => $courseid)) ) {
    print_error('invalidcourseid', 'block_email_list');
}

// Only return, if user have login.
require_login($course->id, false);

if ($course->id == SITEID) {
    $context = context_system::instance(); // SYSTEM context.
} else {
    $context = context_course::instance($course->id); // Course context.
}

// Get renderer.
$renderer = $PAGE->get_renderer('block_email_list');

// Set default page parameters.
$PAGE->set_pagelayout('incourse');
$PAGE->set_context($context);
$PAGE->set_url('/blocks/email_list/email/folder.php',
    array(
        'id' => $mailid,
        'course' => $course->id
    )
);

// Options for new mail and new folder.
$options = new stdClass();
$options->id = $id;
$options->course = $courseid;

$preferencesbutton = email_get_preferences_button($courseid);

$stremail = get_string('name', 'block_email_list');
$PAGE->set_title($course->shortname . ': ' . $stremail);

// Print the page header.
echo $renderer->header();

email_printblocks($USER->id, $courseid);

if ( isset($folderid) ) {
    if (! $folder = $DB->get_record('email_folder', array('id' => $folderid)) ) {
        print_error( 'failgetfolder', 'block_email_list');
    }
}

switch ( $action ) {
    case md5('admin'):
        $hassubfolders = email_print_administration_folders($options);

        if ( ! $hassubfolders  ) {

            // Can create subfolders?
            if ( ! has_capability('block/email_list:createfolder', $context)) {
                print_error('forbiddencreatefolder',
                    'block_email_list',
                    $CFG->wwwroot . '/blocks/email_list/email/index.php?id=' . $course->id
                );
            }

            // Print form to new folder.
            notify( get_string ('nosubfolders', 'block_email_list') );
            $mform = new folder_form('folder.php', array('id' => $id, 'course' => $courseid, 'action' => ''));
            $mform->display();
        }

        break;
    case 'cleantrash':
        $trash = \block_email_list\label::get_root($USER->id, EMAIL_TRASH);

        // If necessary, delete mail and delete attachments.
        $options->folderid = $trash->id;

        $success = true;

        $mails = email_get_mails($USER->id, $course->id, null, '', '', $options);

        // Delete reference mails.
        if (! $DB->delete_records('email_foldermail', 'folderid', $trash->id)) {
            $success = false;
        }

        // Get all trash mails.
        if ( $mails ) {
            foreach ($mails as $mail) {
                // If mailid exist, continue.
                if ( $DB->get_records('email_foldermail', array('mailid' => $mail->id)) ) {
                    continue;
                } else {
                    // Mail is not reference by never folder (not possibility readed).
                    if ( email_delete_attachments($mail->id) and $DB->delete_records('email_mail', 'id', $mail->id) ) {
                        $success = true;
                    }
                }

            }
        }

        $url = email_build_url($options);

        // Notify.
        if ( $success ) {
            notify( get_string('cleantrashok', 'block_email_list') );
        } else {
            notify( get_string('cleantrashfail', 'block_email_list') );
        }

        $options->folderid = $id;
        $options->folderoldid = 0;
        email_showmails($USER->id, '', 0, 10, $options);

        break;

    case md5('edit'):

        // Can create subfolders?
        if ( ! has_capability('block/email_list:createfolder', $context) ) {
            print_error('forbiddencreatefolder',
                'block_email_list',
                $CFG->wwwroot.'/blocks/email_list/email/index.php?id=' . $course->id
            );
        }

        $mform = new folder_form('folder.php', array('id' => $id, 'action' => $action, 'course' => $courseid));

        $folder = email_get_folder($id);
        $folder->foldercourse = $folder->course;
        unset($folder->course);
        $mform->set_data($folder);

        if ( $data = $mform->get_data() ) {
            $updatefolder = new stdClass();

            // Clean name.
            $updatefolder->name = strip_tags($data->name);

            // Add user and course.
            $updatefolder->userid = $USER->id;

            $updatefolder->course = $data->foldercourse;

            // Add id.
            $updatefolder->id = $data->id;


            // Update folder.

            // Get old folder params.
            if (! $oldfolder = $DB->get_record('email_folder', array('id' => $data->id)) ) {
                print_error('failgetfolder', 'block_email_list');
            }

            if ( $subfolder = email_is_subfolder($oldfolder->id) ) {

                // If user changed parent folder.
                if ( $subfolder->folderparentid != $data->parentfolder ) {
                    if ( !$DB->set_field('email_subfolder', 'folderparentid', $data->parentfolder, 'id', $subfolder->id) ) {
                            print_error('failchangingparentfolder', 'block_email_list');
                    }
                }
            }

            // Unset parentfolder.
            unset($data->parentfolder);

            if ( $preference = $DB->get_record('email_preference', array('userid' => $USER->id)) ) {
                if ( $preference->marriedfolders2courses ) {
                    // Change on all subfolders if this course has changed.
                    if ( $oldfolder->course != $data->foldercourse ) {
                        if ( $subfolders = \block_email_list\label::get_all_sublabels($data->id) ) {
                            foreach ($subfolders as $subfolder0) {
                                $DB->set_field('email_folder', 'course', $data->foldercourse, 'id', $subfolder0->id);
                            }
                        }
                    }
                }
            }

            // Update record.
            if ( !$DB->update_record('email_folder', $updatefolder) ) {
                return false;
            }

            add_to_log($courseid, 'email', "update subfolder", 'folder.php?id='.$id, "$data->name", 0, $USER->id);

            notify(get_string('modifyfolderok', 'block_email_list'));

            email_print_administration_folders($options);
        } else {
            $mform->display();
        }

        break;

    case md5('remove'):

        email_removefolder($id, $options);

        email_print_administration_folders($options);

    default:

        // Can create subfolders?
        if ( ! has_capability('block/email_list:createfolder', $context) ) {
            print_error('forbiddencreatefolder',
                'block_email_list',
                $CFG->wwwroot . '/blocks/email_list/email/index.php?id=' . $course->id
            );
        }

        $mform = new folder_form('folder.php', array('id' => $id, 'course' => $courseid, 'action' => ''));

        // If the form is cancelled.
        if ($mform->is_cancelled()) {
            redirect($CFG->wwwroot . '/blocks/email_list/email/index.php?id=' . $courseid,
                get_string('foldercancelled', 'block_email_list')
            );
            // Get form sended.
        } else if ( $form = $mform->get_data() ) {
            $foldernew = new stdClass();

            // Clean name.
            $foldernew->name = strip_tags($form->name);

            // Add user and course.
            $foldernew->userid = $USER->id;

            // Add courseid.
            $foldernew->course = $form->foldercourse;

            // Apply this information.
            $stralert = get_string('createfolderok', 'block_email_list');

            // Use this field, for known if folder exist o none.
            if (! $form->oldname ) {
                // Add new folder.
                if ( !email_newfolder($foldernew, $form->parentfolder) ) {
                    print_error('failcreatingfolder', 'block_email_list');
                }
            } else {
                $updatefolder = new stdClass();

                $updatefolder->id = $form->folderid;
                $updatefolder->name = $form->name;
                $updatefolder->parentfolder = $form->parentfolder;
                $updatefolder->course = $form->course;

                // If exist folderid (sending in form), set field.
                if ( !email_update_folder($updatefolder) ) {
                    print_error('failupdatefolder', 'block_email_list');
                }

                // Apply this information.
                $stralert = get_string('modifyfolderok', 'block_email_list');
            }

            redirect($CFG->wwwroot.'/blocks/email_list/email/index.php?id='.$courseid, $stralert, '3');

        } else {
            // Set data.
            if ( isset($folder) ) {
                $folder->oldname = $folder->name;
                $parentfolder = email_get_parent_folder($folder);
                $folder->parentfolder = $parentfolder->id;
                $folder->folderid = $folder->id;

                // FIX BUG: When update an folder, on this id has been put $COURSE->id.
                $folder->id = $COURSE->id;

                $mform->set_data($folder);
            }

            $mform->display();
        }
}

echo $renderer->footer();
