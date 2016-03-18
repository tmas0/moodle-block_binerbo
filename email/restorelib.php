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
 * Library of functions to restore for module email
 *
 * @author Toni Mas
 * @version $Id: restorelib.php,v 1.2 2008/08/01 11:27:13 tmas Exp $
 * @package email
 * @license The source code packaged with this file is Free Software, Copyright (C) 2006 by
 *          <toni.mas at uib dot es>.
 *          It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
 *          You can get copies of the licenses here:
 *                         http://www.affero.org/oagpl.html
 *          AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".
 *
 * Modified by Sam Chaffee 2008/07/19
 */

function email_restore_instance($data, $restore) {
    $status = true;

    // Restore the folders first.
    if ( !empty($data->info) and !empty($data->info['EMAIL_FOLDERS']['0']['#']['EMAIL_FOLDER']) ) {
        $info = $data->info['EMAIL_FOLDERS']['0']['#']['EMAIL_FOLDER'];
        $status = email_folders_restore($info, $restore);
    }

    if ( !empty($data->info) and !empty($data->info['EMAILS']['0']['#']['EMAIL']) ) {
        $info = $data->info['EMAILS']['0']['#']['EMAIL'];
        $status = email_restore_mail($info, $restore);
    }

    // Restore the sent mail.
    if ( !empty($data->info) and !empty($data->info['SENTEMAILS']['0']['#']['SENTEMAIL']) ) {
        $info = $data->info['SENTEMAILS']['0']['#']['SENTEMAIL'];
        $status = email_sends_restore($info, $restore);
    }

    return $status;
}

function email_restore_mail ($info, $restore) {
    global $CFG, $DB;

    $status = true;
    for ($i = 0; $i < count($info); $i++) {
        $emailinfo = $info[$i];
        $email = new stdClass;

        // Remap user ID.
        $oldid = backup_todb($emailinfo['#']['USERID']['0']['#']);

        if ($newid = backup_getid($restore->backup_unique_code, 'user', $oldid)) {
            $email->userid = $newid->new_id;
        } else {
            // OK, this is bad.
            $status = false;
            break;
        }
        unset($oldid, $newid);

        // Remap course ID.
        $oldid = backup_todb($emailinfo['#']['COURSE']['0']['#']);

        if ($newid = backup_getid($restore->backup_unique_code, 'course', $oldid)) {
            $email->course = $newid->new_id;
        } else {
            // OK, this is bad.
            $status = false;
            break;
        }

        $email->subject = backup_todb($emailinfo['#']['SUBJECT']['0']['#']);

        $email->timecreated = backup_todb($emailinfo['#']['TIMECREATED']['0']['#']);

        $email->body = backup_todb($emailinfo['#']['BODY']['0']['#']);

        if ( !$newemailid = $DB->insert_record('email_mail', $email) ) {
            $status = false;
            break;
        }

        // Get the old id.
        $oldid = backup_todb($emailinfo['#']['ID']['0']['#']);
        // Put the new email id for later use.
        backup_putid($restore->backup_unique_code, 'email_mail', $oldid, $newemailid);

        // Check the email/folder associations.
        if ( !empty($emailinfo['#']['FOLDERMAILS']['0']['#']['FOLDERMAIL']) ) {
            $foldersmailinfo = $emailinfo['#']['FOLDERMAILS']['0']['#']['FOLDERMAIL'];

            // Iterate through.
            for ($ii = 0; $ii < count($foldersmailinfo); $ii++) {
                $foldermail = $foldersmailinfo[$ii];

                $newfoldermail = new stdClass;

                $newfoldermail->mailid = $newemailid;

                // Get the old folder id.
                $oldfolderid = backup_todb($foldermail['#']['FOLDERID']['0']['#']);

                // Get the new folder id.
                if ( !$newfolderid = backup_getid($restore->backup_unique_code, 'email_folder', $oldfolderid) ) {
                    $status = false;
                    break;
                }

                $newfoldermail->folderid = $newfolderid->new_id;
                // Save the new email/folder association.
                if ( !$newfoldermailid = $DB->insert_record('email_foldermail', $newfoldermail) ) {
                    $status = false;
                    break;
                }

            }
        }
    }

    return $status;
}

/**
 * This function restores the email_folder.
 *
 * @uses $CFG
 * @param int $account Account ID
 * @param $info
 * @param $restore
 * @return boolean Success/Fail
 */
function email_folders_restore($info, $restore) {
    global $CFG, $DB;

    $status = true;

    for ($i = 0; $i < count($info); $i++) {
        $folderinfo = $info[$i];

        $folder = new stdClass;

        // Remap user ID.
        $oldid = backup_todb($folderinfo['#']['USERID']['0']['#']);

        if ($newid = backup_getid($restore->backup_unique_code, 'user', $oldid)) {
            $folder->userid = $newid->new_id;
        } else {
            // OK, this is bad.
            $status = false;
            break;
        }
        unset($oldid);

        // Remap coruse ID.
        $oldid = backup_todb($folderinfo['#']['COURSE']['0']['#']);
        if ($oldid != 0) {
            // The folder is associated with a course.
            if ( $newid = backup_getid($restore->backup_unique_code, 'course', $oldid) ) {
                $folder->course = $newid->new_id;
            } else {
                // OK, this is bad.
                $status = false;
                break;
            }
        } else {
            // The folder is not associated with a course.
            $folder->course = 0;
        }

        // Folder isparenttype.
        $folder->isparenttype = backup_todb($folderinfo['#']['ISPARENTTYPE']['0']['#']);

        // Folder name.
        $folder->name = backup_todb($folderinfo['#']['NAME']['0']['#']);

        // Get the times.
        $folder->timecreated = backup_todb($folderinfo['#']['TIMECREATED']['0']['#']);

        // Make sure the folder doesn't exists.
        $existingfolder = $DB->get_record('email_folder',
            array('userid' => $folder->userid,
                'name' => $folder->name,
                'course' => $folder->course
            )
        );
        if ( $existingfolder ) {
            $newfolderid = $existingfolder->id;
        } else {
            if ( !$newfolderid = $DB->insert_record('email_folder', $folder) ) {
                $status = false;
                break;
            }
        }

        // We have a new id; find the old and put the new.
        $oldid = backup_todb($folderinfo['#']['ID']['0']['#']);

        backup_putid($restore->backup_unique_code, 'email_folder', $oldid, $newfolderid);

        // Check for subfolders of this folder.
        if ( !empty($folderinfo['#']['SUBFOLDERS']['0']['#']['SUBFOLDER']) ) {
            $subinfo = $folderinfo['#']['SUBFOLDERS']['0']['#']['SUBFOLDER'];

            $subfolders = array();
            for ($ii = 0; $ii < count($subinfo); $ii++) {
                $subfolderinfo = $subinfo[$ii];

                $subfolder = new stdClass;

                // Parent folder id is the folder id of the folder we just restored.
                $subfolder->folderparentid = $newfolderid;

                // Try to get the new id of the child folder, but it may not have been restored yet.
                $oldchildid = backup_todb($subfolderinfo['#']['FOLDERCHILDID']['0']['#']);

                $subfolder->oldfolderchildid = $oldchildid;
                $subfolders[] = $subfolder;

            } // End restoring subfolders of this folder.
        }  // End if subfolders block.
    } // End restoring folders.

    // Restore the subfolders.
    $status = email_subfolders_restore($subfolders, $restore);

    return $status;
}

/**
 * This function restores the email_subfolder.
 *
 * @uses $CFG
 * @param int $folder Folder ID
 * @param $info
 * @param $restore
 * @return boolean Success/Fail
 */
function email_subfolders_restore($subfolders, $restore) {
    global $CFG, $DB;

    $status = true;

    // Restore the actual subfolders now.
    foreach ($subfolders as $subfolder) {
        $newchildid = backup_getid($restore->backup_unique_code, 'email_folder', $subfolder->oldfolderchildid);
        if ( $newchildid ) {
            // Found the new id.
            $subfolder->folderchildid = $newchildid->new_id;
        } else {
            $status = false;
            break;
        }
        // Insert the new record.
        if ( !$newsubid = $DB->insert_record('email_subfolder', $subfolder) ) {
            $status = false;
            break;
        }
    }

    return $status;
}

/**
 * This function restores the email_foldermail.
 *
 * @uses $CFG
 * @param int $folder New Folder ID
 * @param $info
 * @param $restore
 * @return boolean Success/Fail
 */
function email_foldersmails_restore_mods($folder, $info, $restore) {
    global $CFG;

    $status = true;
    return $status;
}

/**
 * This function restores the email_send.
 *
 * @uses $CFG
 * @param int $account Account ID
 * @param $info
 * @param $restore
 * @return boolean Success/Fail
 */
function email_sends_restore($info, $restore) {
    global $DB;

    $status = true;

    for ($i = 0; $i < count($info); $i++) {
        $sentinfo = $info[$i];

        $sentemail = new stdClass;

        // Remap user ID.
        $oldid = backup_todb($sentinfo['#']['USERID']['0']['#']);

        if ( $newid = backup_getid($restore->backup_unique_code, 'user', $oldid) ) {
            $sentemail->userid = $newid->new_id;
        } else {
            // OK, this is bad.
            $status = false;
            break;
        }
        unset($oldid, $newid);

        // Remap course ID.
        $oldid = backup_todb($sentinfo['#']['COURSE']['0']['#']);

        if ( $newid = backup_getid($restore->backup_unique_code, 'course', $oldid) ) {
            $sentemail->course = $newid->new_id;
        } else {
            // OK, this is bad.
            $status = false;
            break;
        }

        unset($oldid, $newid);
        // Remap the mailid.
        $oldid = backup_todb($sentinfo['#']['MAILID']['0']['#']);

        if ( $newid = backup_getid($restore->backup_unique_code, 'email_mail', $oldid) ) {
            $sentemail->mailid = $newid->new_id;
        } else {
            // OK, this is bad.
            $status = false;
            break;
        }

        $sentemail->type = backup_todb($sentinfo['#']['TYPE']['0']['#']);

        $sentemail->readed = backup_todb($sentinfo['#']['READED']['0']['#']);

        $sentemail->sended = backup_todb($sentinfo['#']['SENDED']['0']['#']);

        $sentemail->answered = backup_todb($sentinfo['#']['ANSWERED']['0']['#']);

        if ( !$newsentid = $DB->insert_record('email_send', $sentemail) ) {
            $status = false;
            break;
        }
    }

    return $status;
}
