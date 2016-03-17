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
 * Library of functions and constants for email
 *
 * @author Toni Mas
 * @version 1.0.2
 * @package email
 * @license The source code packaged with this file is Free Software, Copyright (C) 2006 by
 *          <toni.mas at uib dot es>.
 *          It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
 *          You can get copies of the licenses here:
 *                         http://www.affero.org/oagpl.html
 *          AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".
 */


// Standard definitions.
/**
 * Inbox folder.
 */
define('EMAIL_INBOX', 'inbox');

/**
 * Sendbox folder.
 */
define('EMAIL_SENDBOX', 'sendbox');

/**
 * Trash folder.
 */
define('EMAIL_TRASH', 'trash');

/**
 * Draft folder.
 */
define('EMAIL_DRAFT', 'draft');


// Standard actions.

/**
 * View mail.
 */
define('EMAIL_VIEWMAIL', 'view');

/**
 * Write mail.
 */
define('EMAIL_WRITEMAIL', 'write');

/**
 * Reply mail.
 */
define('EMAIL_REPLY', 're');

/**
 * Forward mail.
 */
define('EMAIL_FORWARD', 'fw');

/**
 * Reply all mail.
 */
define('EMAIL_REPLYALL', 'reall');

/**
 * Edit Draft mail.
 */
define('EMAIL_EDITDRAFT', 'edraft');



/**
 * Config PARAMS.
 */

/**
 * Enable track mail on user's preference.
 */
define('EMAIL_TRACKBYMAIL', 1);

/**
 * Enable married user folder to this courses on user's preference.
 */
define('EMAIL_MARRIEDFOLDERS2COURSES', 1);

/**
 * Max number of courses who it's unread mails, have display. (on block)
 *
 * Default, no limit.
 */
define('EMAIL_MAX_NUMBER_COURSES', 0);


/**
 * Color for answered mails.
 *
 */
define('EMAIL_ANSWERED_COLOR', '#83CC83');

/**
 * Odd table fields.
 */
define('EMAIL_TABLE_FIELD_COLOR', '#B7B7B7');

// Default configs.

// First, drop old configs .. if exist.
if (isset($CFG->email_display_course_principal)) {
    unset_config('email_display_course_principal');  // Default show principal course in blocks who containg list of courses.
}

if (isset($CFG->email_number_courses_display_in_blocks_course)) {
    unset_config('email_number_courses_display_in_blocks_course');  // Default show all courses.
}

// Second, define new configs.
if (!isset($CFG->email_trackbymail)) {
    set_config('email_trackbymail', EMAIL_TRACKBYMAIL);
}
if (!isset($CFG->email_marriedfolders2courses)) {
    set_config('email_marriedfolders2courses', EMAIL_MARRIEDFOLDERS2COURSES);
}
if (!isset($CFG->email_max_number_courses)) {
    set_config('email_max_number_courses', EMAIL_MAX_NUMBER_COURSES);
}
if (!isset($CFG->email_answered_color)) {
    set_config('email_answered_color', EMAIL_ANSWERED_COLOR);
}
if (!isset($CFG->email_table_field_color)) {
    set_config('email_table_field_color', EMAIL_TABLE_FIELD_COLOR);
}

// Default disable old screen for select participants to send mail.
if (!isset($CFG->email_old_select_participants)) {
    set_config('email_old_select_participants', 0);
}

// UIB needs define this param. This define if users show Admins on select users sent mail.
if (!isset($CFG->email_add_admins)) {
    set_config('email_add_admins', 1);
}

// Temporal enable/unable Ajax use for select users to send mail... now it's UNSTABLE!!! Only for developers.
if (!isset($CFG->email_enable_ajax)) {
    set_config('email_enable_ajax', 0);
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function email_cron() {
    return true;
}

/**
 * This function prints to choose mycourses.
 *
 * @uses $USER
 * @param int $courseid Course ID
 * @return boolean Fail or success
 * @todo Finish documenting this function
 */
function email_choose_course($courseid) {

    global $USER;

    // Get my courses.
    $mycourses = get_my_courses($USER->id);

    $courses = array();
    // Prepare array.
    foreach ($mycourses as $mycourse) {
        $courses[$mycourse->id] = $mycourse->fullname;
    }

    // Load participants. This functions avoid selected users from diferents courses. Only select users of one course.
    $javascript = '<script type="text/javascript" language="JavaScript">
                <!--
                    function loadParticipants(f) {
                            var www = "participants.php?id="+f.value;
                            window.location.href = www;
                    }
                -->
                </script>';

    // Print javascript.
    echo $javascript;

    // Print choose menu of my courses.
    choose_from_menu ($courses, 'choose_course', $courseid, 'choose', 'loadParticipants(this);');

}

/**
 * This function get name of file, to the path pass.
 *
 * @param string $path $path
 * @return string File name
 * @todo Finish documenting this function
 */
function email_strip_attachment($path) {

    $part = explode('/', $path);

    return $part[count($part) - 1];
}

/**
 * This function add copy attachments.
 *
 * @uses $CFG
 * @param $mailidsrc Mail ID source for attached files
 * @param $mailiddst Mail ID destiny for attached files
 * @param $internalmailpath Only use this parametrer in migration of internalmail
 * to email!!! Warning please! PASS ABSOLUTE PATH!!!
 * @param $email Email object
 * @return string Array of all name attachments upload
 * @todo Finish documenting this function
 */

function email_copy_attachments($dirsrc, $mailiddst, $internalmailpath=null, $attachment=null) {
    global $CFG;

    // Get directory for save this attachments.
    $dirsrc = $CFG->dataroot .'/'.$dirsrc;
    $dirdst = email_file_area($mailiddst) .'/'.$attachment;

    if (! $internalmailpath) {
        // Copy this attachments.
        if (! copy($dirsrc, $dirdst) ) {
            debugging( "src:$dirsrc, dst: $dirdst ");
            print_error('failcopingattachments', 'block_email_list');
        }
    } else {
        if ( file_exists($internalmailpath.'/'.$attachment) ) {
            // Copy attachments but src is internalmail path.
            if (! copy($internalmailpath.'/'.$attachment, $dirdst) ) {
                notify('Failed when copying attachmets in migration. src: '.$internalmailpath.'/'.$attachment.' to dst:'.$dirdst);
            }
        }
    }

    return true;
}

/**
 * This function remove all attachments associated
 * an one mail. First delele records of database, also
 * remove files.
 *
 * @param int $mailid Mail ID
 * @return boolean Success/Fail
 * @todo Finish documenting this function
 **/
function email_delete_attachments($mailid) {

    $result = true;

    if ( $basedir = email_file_area($mailid) ) {

        // Delete all files of mail.
        if ( $files = get_directory_list($basedir) ) {

            foreach ($files as $file) {

                if (! $result = unlink("$basedir/$file") ) {
                    notify("Existing file '$file' has been deleted!");
                }
            }
        }

        // Delete directory as well, if empty.
        rmdir("$basedir");
    }

    return $result;
}

/**
 * This functions return string language of root folder (default en).
 *
 * @param string $type Type
 * @return string Name
 * @todo Finish documenting this function
 */
function email_get_root_folder_name($type) {

    if ($type == EMAIL_INBOX) {
        $name = get_string('inbox', 'block_email_list');
    } else if ($type == EMAIL_SENDBOX) {
        $name = get_string('sendbox', 'block_email_list');
    } else if ($type == EMAIL_TRASH) {
        $name = get_string('trash', 'block_email_list');
    } else if ($type == EMAIL_DRAFT) {
        $name = get_string('draft', 'block_email_list');
    } else {
        // Type is not defined.
        $name = '';
    }

    return $name;
}

/**
 * This function is called recursive for print all subfolders.
 *
 * @uses $CFG
 * @param array $subfolders $subfolders to explore.
 * @param int $userid User ID
 * @param int $courseid Course ID
 * @param boolean $foredit For edit folders.
 * @param boolean $admin Admin folders
 * @todo Finish documenting this function
 */
function email_print_subfolders($subfolders, $userid, $courseid, $foredit=false, $admin=false) {

    global $CFG;

    $numbermails = 0;
    $unreaded = '';

    // String for alt of img.
    $strremove = get_string('removefolder', 'block_email_list');

    echo '<ul>';

    $subrow = 0;
    foreach ($subfolders as $subfolder) {

        unset($numbermails);
        unset($unreaded);
        // Get number of unreaded mails.
        $numbermails = email_count_unreaded_mails($userid, $courseid, $subfolder->id);
        if ( $numbermails > 0 ) {
            $unreaded = '('.$numbermails.')';
        } else {
            $unreaded = '';
        }

        echo '<li class="r'. $subrow .'">';

        // If edit folder.
        if ( $foredit ) {

            echo '<a href="' . $CFG->wwwroot .
                '/blocks/email_list/email/folder.php?course=' . $courseid .
                '&amp;id=' . $subfolder->id .
                '&amp;action=' . md5('edit') . '">' . $subfolder->name . '</a>';

            echo '&#160;&#160;<a href="' . $CFG->wwwroot .
                '/blocks/email_list/email/folder.php?course=' . $courseid .
                '&amp;id=' . $subfolder->id .
                '&amp;action=' . md5('edit') .
                '"><img src="' . $CFG->pixpath . '/t/edit.gif" alt="' . $strremove . '" /></a>';

            echo '&#160;&#160;<a href="' . $CFG->wwwroot .
                '/blocks/email_list/email/folder.php?course=' . $courseid .
                '&amp;id=' . $subfolder->id .
                '&amp;action=' . md5('remove') . '"><img src="' .
                $CFG->pixpath . '/t/delete.gif" alt="' . $strremove . '" /></a>';

        } else {
            echo '<a href="' . $CFG->wwwroot . '/blocks/email_list/email/index.php?id= ' .$courseid .
                    '&amp;folderid=' . $subfolder->id . '">' . $subfolder->name . $unreaded . '</a>';
        }

        // Now, print all subfolders it.
        $subfoldersrecursive = \block_email_list\label::get_sublabels($subfolder->id, null, $admin);

        // Print recursive all this subfolders.
        if ( $subfoldersrecursive ) {
            email_print_subfolders($subfoldersrecursive, $userid, $courseid, $foredit, $admin);
        }

        echo '</li>';
        $subrow = $subrow ? 0 : 1;
    }

    echo '</ul>';
}

/**
 * This function print block for show my folders.
 * Prints all tree.
 *
 * @uses $CFG, $PAGE, $OUTPUT
 * @param int $userid User ID
 * @param int $courseid Course ID
 * @todo Finish documenting this function
 */
function email_print_tree_myfolders($userid, $courseid) {
    global $CFG, $PAGE, $OUTPUT;

    $strfolders = get_string('folders', 'block_email_list');
    $stredit    = get_string('editfolders', 'block_email_list');
    $strcourse  = get_string('course');
    $strfolderopened = s(get_string('folderopened'));
    $strfolderclosed = s(get_string('folderclosed'));

    if ($courseid == SITEID) {
        $context = context_system::instance();   // SYSTEM context.
    } else {
        $context = context_course::instance($courseid);   // Course context.
    }

    $spancounter = 1;

    // Get my folders.
    if ( $folders = email_get_root_folders($userid) ) {

        $numbermails = 0;
        $unreaded = '';
        $row = 0;

        $content = html_writer::start_tag('ul', array('class' => 'c_menu'));

        // Clean trash.
        $clean = '';

        // Get courses.
        foreach ($folders as $folder) {
            unset($numbermails);
            unset($unreaded);
            // Get number of unreaded mails.
            if ( $numbermails = email_count_unreaded_mails($userid, $courseid, $folder->id) ) {
                $unreaded = ' ('.$numbermails.')';
            } else {
                $unreaded = '';
            }

            if ( \block_email_list\label::is_type($folder, EMAIL_TRASH) ) {
                $clean .= html_writer::link( new moodle_url('/blocks/email_list/email/folder.php',
                    array('course' => $courseid,
                        'folderid' => $folder->id,
                        'action' => 'cleantrash')),
                    get_string('cleantrash', 'block_email_list')
                );
            }

            // Now, print all subfolders it.
            $subfolders = \block_email_list\label::get_sublabels($folder->id, $courseid);

            // LI.
            $content .= html_writer::start_tag('li', array('class' => 'r'. $row));
            $url = new moodle_url('/blocks/email_list/email/index.php',
                    array('id' => $courseid, 'folderid' => $folder->id)
            );

            $content .= html_writer::link($url,
                $OUTPUT->pix_icon('t/email', '') .
                $OUTPUT->spacer(array('height' => 5, 'width' => 5)) .
                $folder->name .
                $unreaded
            );

            // If subfolders.
            if ( $subfolders ) {
                email_print_subfolders( $subfolders, $userid, $courseid );
            }
            $content .= html_writer::end_tag('li');
            $row = $row ? 0 : 1;
        }

        $content .= html_writer::end_tag('ul');

        $content .= html_writer::tag('div',
            $OUTPUT->pix_icon('e/cleanup_messy_code' , '') .
                $OUTPUT->spacer(array('height' => 5, 'width' => 5)) .
                $clean,
            array('class' => 'footer'));

        // For admin folders.
        $content .= '<div class="footer"><a href="' . $CFG->wwwroot . '/blocks/email_list/email/folder.php?course=' . $courseid .
                '&amp;action=' . md5('admin') . '"><b>' . $stredit . '</b></a></div>';

        // Create new label.
        if ( has_capability('block/email_list:createlabel', $context)) {
            $newlabel = html_writer::link(
                new moodle_url('/blocks/email_list/email/folder.php',
                    array('course' => $courseid)),
                get_string('newfolderform', 'block_email_list')
            );
            $content .= html_writer::tag('div', $newlabel, array('class' => 'text-center'));
        }

        // Create the block content.
        $bc = new block_contents();
        $bc->title = $strfolders;
        $bc->content = $content;

        $defaultregion = $PAGE->blocks->get_default_region();
        $PAGE->blocks->add_fake_block($bc, $defaultregion);
    }

}

/**
 * This function prints blocks.
 *
 * @uses $CGF, $USER, $PAGE
 * @param int $userid User ID
 * @param int $courseid Course ID
 * @param boolean $printsearchblock Print search block
 * @return null
 * @todo Finish documenting this function
 **/
function email_printblocks($userid, $courseid, $printsearchblock=true) {
    global $CFG, $USER, $PAGE, $OUTPUT;

    $strcourse  = get_string('course');
    $strcourses = get_string('mailboxs', 'block_email_list');
    $strsearch  = get_string('search');
    $strmail    = get_string('name', 'block_email_list');

    $defaultregion = $PAGE->blocks->get_default_region();
    
    if ( $printsearchblock ) {
        // Print search block.
        $form = email_get_search_form($courseid);

        // Create the block content.
        $bc = new block_contents();
        $bc->title = $strsearch;
        $bc->content = $form;

        $PAGE->blocks->add_fake_block($bc, $defaultregion);
    }

    // Print my folders.
    email_print_tree_myfolders( $userid, $courseid );

    // Get my course.
    $mycourses = enrol_get_my_courses();

    // Get courses.
    $list = get_string('arenotenrolledanycourse', 'block_email_list');
    $icons = '';
    foreach ($mycourses as $mycourse) {

        $context = get_context_instance(CONTEXT_COURSE, $mycourse->id);

        // Get the number of unread mails.
        $numberunreadmails = email_count_unreaded_mails($USER->id, $mycourse->id);
        $unreadmails = '';

        // Only show if has unreaded mails.
        if ( $numberunreadmails > 0 ) {
            $unreadmails = '<b>('.$numberunreadmails.')</b>';
            // Define default path of icon for course.
            $icon .= '<img src="' . $CFG->wwwroot .
                '/blocks/email_list/email/images/openicon.gif" height="16" width="16" alt="' . $strcourse . '" />';
        } else {
            // Define default path of icon for course.
            $icon .= '<img src="' . $CFG->wwwroot .
                '/blocks/email_list/email/icon.gif" height="16" width="16" alt="' . $strcourse . '" />';
        }

        $linkcss = $mycourse->visible ? '' : ' class="dimmed" ';

        if ( ( !$mycourse->visible and !has_capability('moodle/legacy:student', $context, $USER->id, false) )
                or !has_capability('moodle/legacy:student', $context, $USER->id, false)
                or ( has_capability('moodle/legacy:student', $context, $USER->id, false) and $mycourse->visible) ) {
            $list .= '<a href="' . $CFG->wwwroot .
                        '/blocks/email_list/email/index.php?id=' . $mycourse->id .
                        '" ' . $linkcss . '>' . $mycourse->fullname . ' ' . $unreadmails . '</a>';
            $list .= $icon;
        }
    }

    // Create the block content.
    $bc = new block_contents();
    $bc->title = $strcourses;
    $bc->content = $list;
    $defaultregion = $PAGE->blocks->get_default_region();
    $PAGE->blocks->add_fake_block($bc, $defaultregion);
}

/**
 * This fuctions return the root parent folder, of that folderchild.
 *
 * @param int $folderid Folder ID
 * @return Object Contain root parent folder
 * @todo Finish documenting this function
 */
function email_get_parentfolder($folderid) {
    global $DB;

    // Get parent for this child.
    $parent = $DB->get_record('email_subfolder', 'folderchildid', $folderid);

    // If has parent.
    if ( $parent ) {

        $folder = email_get_folder($parent->folderparentid);

        // While not find parent root, searching.
        while ( is_null($folder->isparenttype) ) {
            // Searching.
            $parent = $DB->get_record('email_subfolder', 'folderchildid', $folder->id);
            $folder = email_get_folder($parent->folderparentid);
        }

        return $folder;
    } else {
        // If no parent, return false => FATAL ERROR!.
        return false;
    }
}

/**
 * This function return form for searching emails.
 *
 * @uses $CGF
 * @param int $courseid Course Id
 * @return string HTML search form
 * @todo Finish documenting this function
 */
function email_get_search_form($courseid) {
    global $CFG;

    $inputhidden = '<input type="hidden" name="courseid" value="' . $courseid . '" />';

    $form = '<form method="post" name="searchform" action="' . $CFG->wwwroot . '/blocks/email_list/email/search.php">
                    <table>
                        <tr>
                            <td>
                                <input type="text" value="' .
                                get_string('searchtext', 'block_email_list') . '" name="words" />
                            </td>
                        </tr>
                        <tr>
                            <td align="center">' .
                                        $inputhidden.'
                                <input type="submit" name="send" value="' . get_string('search') . '" />
                            </td>
                        </tr>
                        <tr valign="top">
                            <td align="center">
                                <a href="'.$CFG->wwwroot.'/blocks/email_list/email/search.php?courseid=' .
                                $courseid . '&amp;action=1">' . get_string('advancedsearch', 'search') . '</a>
                            </td>
                        </tr>
                    </table>
            </form>';
    return $form;
}

/**
 * This function print formated users to send mail ( This had choosed before ).
 *
 * @uses $CFG
 * @param Array $users Users to print.
 * @param boolean $nosenders No users choose (error log)
 * @todo Finish documenting this function
 */
function email_print_users_to_send($users, $nosenders = false, $options = null) {
    global $CFG, $DB;

    $url = '';
    if ( $options ) {
        $url = email_build_url($options);
    }

    echo '<tr valign="middle">
        <td class="legendmail">
            <b>' . get_string('for', 'block_email_list') . '
                :
            </b>
        </td>
        <td class="inputmail">';

    if ( !empty($users) ) {

        echo '<div id="to">';

        foreach ($users as $userid) {
            echo '<input type="hidden" value="'.$userid.'" name="to[]" />';
        }

        echo '</div>';

        echo '<textarea id="textareato" class="textareacontacts"
                name="to" cols="65" rows="3" disabled="true" multiple="multiple">';

        foreach ($users as $userid) {
            echo fullname( $DB->get_record('user', 'id', $userid) ) . ', ';
        }

        echo '</textarea>';
    }

    echo '</td><td class="extrabutton">';

    link_to_popup_window('/blocks/email_list/email/participants.php?' .$url,
        'participants',
        get_string('participants', 'block_email_list') . ' ...',
        470,
        520,
        get_string('participants', 'block_email_list')
    );

    echo '</td></tr>';
    echo '<tr valign="middle">
            <td class="legendmail">
                <div id="tdcc"></div>
            </td>
            <td><div id="fortextareacc"></div><div id="cc"></div><div id="url">' . $urltoaddcc .
            '<span id="urltxt">&#160;|&#160;</span>' . $urltoaddbcc .
            '</div></td><td><div id="buttoncc"></div></td></tr>';
    echo '<tr valign="middle"><td class="legendmail"><div id="tdbcc"></div></td>
            <td><div id="fortextareabcc"></div><div id="bcc"></div></td>
            <td><div id="buttonbcc"></div></td>';
}

/**
 * This function show all participants of one course. Choose user/s to sent mail.
 *
 * @uses $CFG, $USER
 * @param int $courseid Course ID
 * @param int $roleid   Role ID
 * @param int $currentgroup Current group
 * @return Array Users to sending mail.
 * @todo Finish documenting this function
 */
function email_choose_users_to_send($courseid, $roleid, $currentgroup) {
    global $CFG, $USER, $DB;

    if ( !$course = $DB->get_record('course', 'id', $courseid) ) {
        print_error('invalidcourseid', 'block_email_list');
    }

    // Prepare users to choose us.
    if ( $courseid ) {

        if ($course->id == SITEID) {
            $context = get_context_instance(CONTEXT_SYSTEM, SITEID);   // SYSTEM context.
        } else {
            $context = get_context_instance(CONTEXT_COURSE, $course->id);   // Course context.
        }

        // Security issue.
        $sitecontext = get_context_instance(CONTEXT_SYSTEM);
        $frontpagectx = get_context_instance(CONTEXT_COURSE, SITEID);

        if ($context->id != $frontpagectx->id) {
            require_capability('moodle/course:viewparticipants', $context);
        } else {
            require_capability('moodle/site:viewparticipants', $sitecontext);
        }

        $rolesnames = array();
        $avoidroles = array();

        if ($roles = get_roles_used_in_context($context, true)) {
            $canviewroles    = get_roles_with_capability('moodle/course:view', CAP_ALLOW, $context);
            $doanythingroles = get_roles_with_capability('moodle/site:doanything', CAP_ALLOW, $sitecontext);

            if ( ! $CFG->email_add_admins ) {
                $adminsroles = get_roles_with_capability('moodle/legacy:admin', CAP_ALLOW, $sitecontext);
            }

            foreach ($roles as $role) {
                if (!isset($canviewroles[$role->id])) {   // Avoid this role (eg course creator).
                    $avoidroles[] = $role->id;
                    unset($roles[$role->id]);
                    continue;
                }
                if (isset($doanythingroles[$role->id])) {   // Avoid this role (ie admin).
                    $avoidroles[] = $role->id;
                    unset($roles[$role->id]);
                    continue;
                }

                if ( !$CFG->email_add_admins ) {
                    if (isset($adminsroles[$role->id])) {   // Avoid this role (ie admin).
                        $avoidroles[] = $role->id;
                        unset($roles[$role->id]);
                        continue;
                    }
                }

                // Prevent - CONTRIB-609.
                if ( function_exists('role_get_name') ) {
                    $rolenames[$role->id] = strip_tags(role_get_name($role, $context));   // Used in menus etc later on.
                } else {
                    $rolenames[$role->id] = strip_tags(format_string($role->name));   // Used in menus etc later on.
                }

            }
        }

        // We are looking for all users with this role assigned in this context or higher.
        if ($usercontexts = get_parent_contexts($context)) {
            $listofcontexts = '(' . implode(',', $usercontexts) . ')';
        } else {
            $listofcontexts = '(' . $sitecontext->id . ')'; // Must be site.
        }
        if ($roleid) {
            $selectrole = " AND r.roleid = $roleid ";
        } else {
            $selectrole = " ";
        }

        if ($context->id != $frontpagectx->id) {
            $select = 'SELECT DISTINCT u.id, u.username, u.firstname, u.lastname ';
        } else {
            $select = 'SELECT u.id, u.username, u.firstname, u.lastname ';
        }

        if ($context->id != $frontpagectx->id) {
            $from   = "FROM {user} u
                    LEFT OUTER JOIN {context} ctx
                        ON (u.id=ctx.instanceid AND ctx.contextlevel = ".CONTEXT_USER.")
                    JOIN {role_assignments} r
                        ON u.id=r.userid
                    LEFT OUTER JOIN {user}_lastaccess ul
                        ON (r.userid=ul.userid and ul.courseid = $course->id) ";
        } else {
            $from = "FROM {user} u
                    LEFT OUTER JOIN {context} ctx
                        ON (u.id=ctx.instanceid AND ctx.contextlevel = ".CONTEXT_USER.") ";

        }

        $hiddensql = has_capability('moodle/role:viewhiddenassigns', $context) ? '' : ' AND r.hidden = 0 ';

        // Exclude users with roles we are avoiding.
        if ($avoidroles) {
            $adminroles = 'AND r.roleid NOT IN (';
            $adminroles .= implode(',', $avoidroles);
            $adminroles .= ')';
        } else {
            $adminroles = '';
        }

        // Join on 2 conditions.
        // Otherwise we run into the problem of having records in ul table, but not relevant course.
        // And user record is not pulled out.

        if ($context->id != $frontpagectx->id) {
            $where  = "WHERE (r.contextid = $context->id OR r.contextid in $listofcontexts)
                AND u.deleted = 0 $selectrole
                AND (ul.courseid = $course->id OR ul.courseid IS null)
                AND u.username != 'guest'
                $adminroles
                $hiddensql ";
        } else {
            $where = "WHERE u.deleted = 0
                AND u.username != 'guest'";
        }

        if ($currentgroup and $course->groupmode != 0) {    // Displaying a group by choice.
            $from  .= 'LEFT JOIN {groups_members} gm ON u.id = gm.userid ';

            // The $currentgroup can be an array of groups id.
            if (is_array($currentgroup)) {
                $where .= ' AND gm.groupid IN (' . implode(',', $currentgroup) . ') ';
            } else {
                if ($currentgroup == 0) {
                    if ( !has_capability('block/email_list:viewallgroups', $context) && $COURSE->groupmode == 1 ) {
                        $groupids = groups_get_groups_for_user($USER->id, $COURSE->id);
                        $where .= 'AND gm.groupid IN ('.implode(',', $groupids).')';
                    }
                } else {
                    $where .= 'AND gm.groupid = ' . $currentgroup;
                }
            }

            $where .= ' AND gm.groupid = ' . $currentgroup;
        }

        $sort = ' ORDER BY u.firstname, u.lastname';

        $userlist = $DB->get_records_sql($select . $from . $where . $sort);

        if ( $userlist ) {
            foreach ($userlist as $user) {
                $unselectedusers[$user->id] = addslashes(fullname($user, has_capability('moodle/site:viewfullnames', $context)));
            }
        }

        // If there are multiple Roles in the course, then show a drop down menu for switching.
        if ( count($rolenames) > 1 ) {
            echo '<div class="rolesform">';
            echo get_string('currentrole', 'role') . ': ';
            $rolenames = array(0 => get_string('all')) + $rolenames;
            popup_form("$CFG->wwwroot/blocks/email_list/email/
                participants.php?id=$courseid&amp;
                group=$currentgroup&amp;
                contextid=$context->id&amp;roleid=", $rolenames,
                'rolesform', $roleid, '');
            echo '</div>';
        }

        // Prints group selector for users with a viewallgroups capability if course groupmode is separate.
        echo '<br />';
        groups_print_course_menu($course, $CFG->wwwroot.'/blocks/email_list/email/participants.php?id=' . $course->id);
        echo '<br /><br />';
    }

    // Prepare tags.
    $straddusersto  = get_string('addusersto', 'block_email_list');
    $stradduserscc = get_string('cc', 'block_email_list');
    $straddusersbcc = get_string('bcc', 'block_email_list');
    $stradd = get_string('ok');
    $strto = get_string('to', 'block_email_list');
    $strcc = get_string('cc', 'block_email_list');
    $strbcc = get_string('bcc', 'block_email_list');
    $strselectedusersremove = get_string('selectedusersremove', 'block_email_list');
    $straction = get_string('selectaction', 'block_email_list');
    $strcancel = get_string('cancel');

    // Create an object for define parametrer.
    $options = new stdClass();
    $options->id = $courseid;
    // Prepare url.
    $toform = email_build_url($options, true);

    $url = $CFG->wwwroot . '/blocks/email_list/email/sendmail.php';

    if ( $options ) {
        $urlhtml = email_build_url($options);
    }

    include_once('participants.html');
}

/**
 * This function return true or false if barn contains needle.
 *
 * @param string Needle
 * @param Array Barn
 * @return boolean True or false if barn contains needle
 * @todo Finish documenting this function
 */
function email_contains($needle, $barn) {

    // If not empty.
    if ( !empty($barn) ) {
        // Search string.
        foreach ($barn as $straw) {
            if ( $straw == $needle ) {
                return true;
            }
        }
    }

    return false;
}

/**
 * This funcion assign default line on reply or forward mail
 *
 * @param object $user User
 * @param int $date Date on write mail
 * @return string Default line
 * @todo Finish documenting this function
 */
function email_make_default_line_replyforward($user, $date, $override = false) {

    $line = get_string('on', 'block_email_list') .
                ' ' . userdate($date) .
                ', ' . fullname($user, $override) .
                ' ' . get_string('wrote', 'block_email_list') .
                ': <br />'."\n";

    return $line;
}


/**
 * This function read folder's to one mail.
 *
 * @uses $CFG;
 * @param object $mail Mail who has get folder
 * @param int $userid User ID
 * @return array Folders contains mail
 * @todo Finish documenting this function
 */
function email_get_foldermail($mailid, $userid) {

    global $CFG, $DB;

    // Prepare select.
    $sql = "SELECT f.id, f.name, fm.id as foldermail
                   FROM {email_folder} f
                   INNER JOIN {email_foldermail} fm ON f.id = fm.folderid
                   WHERE fm.mailid = $mailid
                   AND f.userid = $userid
                   ORDER BY f.timecreated";

    // Return value of select.
    return $DB->get_records_sql($sql);
}

/**
 * This function read Id to reference mail and folder.
 *
 * @param int $mailid Mail ID
 * @param int $folderid Folder ID
 * @return object Contain reference
 * @todo Finish documenting this function
 */
function email_get_reference2foldermail($mailid, $folderid) {
    global $DB;

    return $DB->get_record('email_foldermail', 'mailid', $mailid, 'folderid', $folderid);

}

/**
 * This function move mail to folder indicated.
 *
 * @param int $mailid Mail ID
 * @param int $foldermailid Folder Mail ID reference
 * @param int $folderidnew Folder ID New
 * @return boolean Success/Fail
 * @todo Finish documenting this function
 */
function email_move2folder($mailid, $foldermailid, $folderidnew) {
    global $DB;

    if ( $DB->record_exists('email_folder', 'id', $folderidnew) ) {

        // Folder have exist in this new folder?.
        if ( !$DB->get_record('email_foldermail', 'mailid', $mailid, 'folderid', $folderidnew) ) {

            // Change folder reference to mail.
            if ( !$DB->set_field('email_foldermail', 'folderid', $folderidnew, 'id', $foldermailid, 'mailid', $mailid) ) {
                return false;
            }
        } else {
            if ( !$DB->delete_records('email_foldermail', 'id', $foldermailid) ) {
                return false;
            }
        }
    } else {
        return false;
    }

    return true;
}

/**
 * This functions print form to create a new folder.
 *
 * @return boolean Success/Fail
 * @todo Finish documenting this function
 */
function email_newfolderform() {
    include_once('folder.php');

    return true;
}

/**
 * This functions created news folders.
 *
 * @param object $folder Fields of new folder
 * @param int $parentfolder Parent folder
 * @return boolean Success/Fail
 * @todo Finish documenting this function
 */
function email_newfolder($folder, $parentfolder) {
    global $DB;

    // Add actual time.
    $folder->timecreated = time();

    // Make sure course field is not null.
    if ( !isset($folder->course) ) {
        $folder->course = 0;
    }

    // Insert record.
    if ( !$folder->id = $DB->insert_record('email_folder', $folder) ) {
        return false;
    }

    // Prepare subfolder.
    $subfolder = new stdClass();
    $subfolder->folderparentid = $parentfolder;
    $subfolder->folderchildid  = $folder->id;

    // Insert record reference.
    if ( !$DB->insert_record('email_subfolder', $subfolder) ) {
        return false;
    }

    add_to_log($folder->userid, "email", "add subfolder", "$folder->name");

    return true;
}

/**
 * This function get folders for one user.
 *
 * @param int $userid
 * @param string $sort Sort order
 * @return object Object contain all folders
 * @todo Finish documenting this function
 */
function email_get_folders($userid, $sort = 'id') {
    global $DB;

    return $DB->get_records('email_folder', 'userid', $userid, $sort);
}

/**
 * This function remove one folder.
 *
 * @uses $CFG
 * @param int $folderid Folder ID
 * @param object $options Options
 * @return boolean Success/Fail
 * @todo Finish documenting this function
 */
function email_removefolder($folderid, $options) {
    global $CFG, $DB;

    // Check if this folder have subfolders.
    if ( $DB->get_record('email_subfolder', 'folderparentid', $folderid) ) {
        // This folder is parent of other/s folders. Don't remove this.
        // Notify.
        redirect($CFG->wwwroot .
            '/blocks/email_list/email/view.php?id=' . $options->id .
            '&amp;action=viewmails',
            '<div class="notifyproblem">' . get_string('havesubfolders', 'block_email_list') . '</div>'
        );
    }

    // Get folder.
    if ($folders = $DB->get_records('email_folder', 'id', $folderid)) {

        // For all folders.
        foreach ($folders as $folder) {

            // Before removing references to foldermail, move this mails to root folder parent.
            if ($foldermails = $DB->get_records('email_foldermail', 'folderid', $folder->id) ) {

                // Move mails.
                foreach ($foldermails as $foldermail) {
                    // Get folder.
                    if ( $folder = email_get_folder($foldermail->folderid) ) {
                        // Get root folder parent.
                        if ( $parent = email_get_parentfolder($foldermail->folderid) ) {
                            // Assign mails it.
                            email_move2folder($foldermail->mailid, $foldermail->id, $parent->id);
                        } else {
                            print_error('failgetparentfolder', 'block_email_list');
                        }
                    } else {
                        print_error('failreferencemailfolder', 'block_email_list');
                    }
                }
            }

            // Delete all subfolders of this.
            if ( !$DB->delete_records('email_subfolder', 'folderparentid', $folder->id) ) {
                return false;
            }

            // Delete all subfolders of this.
            if ( !$DB->delete_records('email_subfolder', 'folderchildid', $folder->id) ) {
                return false;
            }

            // Delete all filters of this.
            if ( !$DB->delete_records('email_filter', 'folderid', $folder->id) ) {
                return false;
            }

            // Delete all foldermail references.
            if ( !$DB->delete_records('email_foldermail', 'folderid', $folder->id) ) {
                return false;
            }
        }

        // Delete all folders.
        if ( !$DB->delete_records('email_folder', 'id', $folderid) ) {
            return false;
        }
    }

    add_to_log($folderid, "email", "remove subfolder", "$folderid");

    notify(get_string('removefolderok', 'block_email_list'));

    return true;
}

/**
 * This function admin's folders.
 *
 * @todo Finish documenting this function
 */
function email_print_administration_folders($options) {
    global $CFG, $USER, $DB;

    echo '<form method="post" name="folderform" action="' .
            $CFG->wwwroot . '/blocks/email_list/email/folder.php?id=' .
            $options->id . '&amp;action=none"><table align="center"><tr><td>';

    print_heading(get_string('editfolder', 'block_email_list'));

    if ( $folders = email_get_root_folders($USER->id, false) ) {

        $course = $DB->get_record('course', 'id', $options->course);

        echo '<ul>';

        // Has subfolders.
        $hassubfolders = false;

        // Get courses.
        foreach ($folders as $folder) {
            // Trash folder is not showing.
            if ( !\block_email_list\label::is_type($folder, EMAIL_TRASH) ) {
                echo '<li>'.$folder->name.'</li>';

                // Now, print all subfolders it.
                $subfolders = \block_email_list\label::get_sublabels($folder->id, null, true);

                // If subfolders.
                if ( $subfolders ) {
                    email_print_subfolders($subfolders, $USER->id, $options->course, true, true);
                    $hassubfolders = true;
                }
            }
        }
        echo '</ul>';
    }
    echo '</td></tr></table></form>';

    return $hassubfolders;
}

/**
 * This function return subfolder if it is.
 *
 * @param int $folderid Folder ID
 * @return object/boolean Return subfolder or false if it isn't subfolder
 * @todo Finish documenting this function
 */
function email_is_subfolder($folderid) {
    global $DB;

    return $DB->get_record('email_subfolder', 'folderchildid', $folderid);
}

function email_createfilter($folderid) {
    notice();
    return true;
}

function email_modityfilter($filterid) {
    return true;
}

function email_removefilter($filterid) {
    return true;
}

/**
 * This function prints all mails
 *
 * @uses $CFG, $COURSE, $SESSION, $DB, $OUTPUT
 * @param int $userid User ID
 * @param string $order Order by ...
 * @param object $options Options for url
 * @param boolean $search When show mails on search
 * @param array $mailssearch Mails who has search
 * @return boolean Success/Fail
 * @todo Finish documenting this function
 */
function email_showmails($userid, $order = '', $page=0, $perpage=10, $options=null, $search=false, $mailssearch=null) {
    global $CFG, $COURSE, $SESSION, $DB, $OUTPUT;

    // CONTRIB-690.
    if ( !empty( $_POST['perpage'] ) and is_numeric($_POST['perpage']) ) {
        $SESSION->email_mailsperpage = $_POST['perpage'];
    } else if ( !isset($SESSION->email_mailsperpage) or empty($SESSION->email_mailsperpage) ) {
        $SESSION->email_mailsperpage = 10; // Default value.
    }

    // Get actual course.
    if ( !$course = $DB->get_record('course', array('id' => $COURSE->id)) ) {
        print_error('invalidcourseid', 'block_email_list');
    }

    if ($course->id == SITEID) {
        $coursecontext = context_system::instance(); // SYSTEM context.
    } else {
        $coursecontext = context_course::instance($course->id); // Course context.
    }

    $url = '';
    // Build url part options.
    if ($options) {
        $url = email_build_url($options);
    }

    // Print all mails in this HTML file.

    // Should use this variable so that we don't break stuff every time a variable is added or changed.
    $baseurl = $CFG->wwwroot . '/blocks/email_list/email/index.php?' . $url .
        '&amp;page=' . $page . '&amp;perpage=' . $perpage;

    // Print init form from send data.
    echo '<form id="sendmail" action="' . $CFG->wwwroot .
        '/blocks/email_list/email/index.php?id=' . $course->id .
        '&amp;folderid=' . $options->folderid . '" method="post" name="sendmail">';

    if ( $course->id == SITEID ) {
        $tablecolumns = array('', 'icon', 'course', 'subject', 'writer', 'timecreated');
    } else {
        $tablecolumns = array('', 'icon', 'subject', 'writer', 'timecreated');
    }

    $folder = null;
    if ( isset( $options->folderid) ) {
        if ( $options->folderid != 0 ) {
            // Get folder.
            $folder = \block_email_list\label::get($options->folderid);
        } else {
            // Solve problem with select an x mails per page for maintein in this folder.
            if ( isset($options->folderoldid) && $options->folderoldid != 0 ) {
                $options->folderid = $options->folderoldid;
                $folder = \block_email_list\label::get($options->folderid);
            }
        }
    }

    // If actual folder is inbox type, ... change tag showing.
    if ( $folder ) {
        if ( ( \block_email_list\label::is_type($folder, EMAIL_INBOX) ) ) {
            $strto = get_string('from', 'block_email_list');
        } else {
            $strto = get_string('to', 'block_email_list');
        }
    } else {
        $strto = get_string('from', 'block_email_list');
    }

    if ( $course->id == SITEID ) {
        $tableheaders = array('',
                            '',
                            get_string('course'),
                            get_string('subject', 'block_email_list'),
                            $strto,
                            get_string('date', 'block_email_list')
        );
    } else {
        $tableheaders = array('',
                             '',
                             get_string('subject', 'block_email_list'),
                             $strto,
                             get_string('date', 'block_email_list')
        );
    }

    $table = new html_table('list-mails-' . $userid);

    $table->head = $tableheaders;
    
    $table->align = array(null, 'center');
    $table->attributes['class'] = 'emailtable';

    // When no search.
    if ( !$search ) {
        // Get mails.
        $mails = \block_email_list\email::get_user_mails($userid, $course->id, null, '', '', $options);
    } else {
        $mails = $mailssearch;
    }

    // Define long page.
    $emailcount = count($mails);
    echo $OUTPUT->paging_bar($emailcount, $page, $perpage, $baseurl);

    // Now, re-getting emails, apply pagesize (limit).
    if ( !$search) {
        // Get mails.
        $mails = \block_email_list\email::get_user_mails($userid,
            $course->id,
            null,
            null,
            '',
            $options
        );
    }

    if ( !$mails ) {
        $mails = array();
    }

    $mailsids = email_get_ids($mails);

    // Print all rows.
    foreach ($mails as $mail) {
        $attribute = array();
        $email = new \block_email_list\email();
        $email->set_email($mail);

        if ( $folder ) {
            if ( \block_email_list\label::is_type($folder, EMAIL_SENDBOX) ) {
                $struser = $email->get_users_send(has_capability('moodle/site:viewfullnames', $coursecontext));
            } else if ( \block_email_list\label::is_type($folder, EMAIL_INBOX) ) {

                $struser = $email->get_fullname_writer(has_capability('moodle/site:viewfullnames', $coursecontext));
                if ( !$email->is_readed($userid, $mail->course) ) {
                    $attribute = array( 'bgcolor' => $CFG->email_table_field_color);
                }
            } else if ( \block_email_list\label::is_type($folder, EMAIL_TRASH) ) {
                $struser = $email->get_fullname_writer(has_capability('moodle/site:viewfullnames', $coursecontext));

                if ( !$email->is_readed($userid, $mail->course) ) {
                    $attribute = array( 'bgcolor' => $CFG->email_table_field_color);
                }
            } else if ( \block_email_list\label::is_type($folder, EMAIL_DRAFT) ) {

                $struser = $email->get_users_send(has_capability('moodle/site:viewfullnames', $coursecontext));

                if ( !$email->is_readed($userid, $mail->course) ) {
                    $attribute = array( 'bgcolor' => $CFG->email_table_field_color);
                }
            } else {
                $struser = $email->get_fullname_writer(has_capability('moodle/site:viewfullnames', $coursecontext));

                if ( !$email->is_readed($userid, $mail->course) ) {
                    $attribute = array( 'bgcolor' => $CFG->email_table_field_color);
                }
            }
        } else {
            // Format user's.
            $struser = $email->get_fullname_writer(has_capability('moodle/site:viewfullnames', $coursecontext));
            if ( !$email->is_readed($userid, $mail->course) ) {
                $attribute = array( 'bgcolor' => $CFG->email_table_field_color);
            }
        }

        if ( !isset($options->folderid) ) {
            $options->folderid = 0;
        }

        if ( \block_email_list\label::is_type($folder, EMAIL_DRAFT) ) {
            $urltosent = '<a href="' . $CFG->wwwroot .
                '/blocks/email_list/email/sendmail.php?id=' . $mail->id .
                '&amp;action=' . EMAIL_EDITDRAFT . '&amp;course=' . $course->id .
                '">' . $mail->subject . '</a>';
        } else {
            if ( $course->id == SITEID ) {
                $urltosent = '<a href="' . $CFG->wwwroot .
                    '/blocks/email_list/email/view.php?id=' . $mail->id .
                    '&amp;action=' . EMAIL_VIEWMAIL . '&amp;course=' . $mail->course .
                    '&amp;folderid=' . $options->folderid . '&amp;mails=' . $mailsids . '">' .
                    $mail->subject . '</a>';
            } else {
                $urltosent = '<a href="' . $CFG->wwwroot .
                    '/blocks/email_list/email/view.php?id=' . $mail->id .
                    '&amp;action=' . EMAIL_VIEWMAIL . '&amp;course=' .$course->id .
                    '&amp;folderid=' . $options->folderid . '&amp;mails=' . $mailsids . '">' .
                    $mail->subject . '</a>';
            }
        }

        $attachment = '';
        if ( $email->has_attachments() ) {
            $attachment = '<img src="'.$CFG->wwwroot.'/blocks/email_list/email/images/clip.gif" alt="attachment" /> ';
        }

        $newemailicon = '<img src="'.$CFG->wwwroot.'/blocks/email_list/email/images/icon.gif" alt="this email was read" /> ';
        if ( $email->is_readed($userid, $mail->course) ) {
            $newemailicon = '<img src="'.$CFG->wwwroot.'/blocks/email_list/email/images/openicon.gif" alt="new email" /> ';
        }

        // Display diferent color if mail is reply or reply all.
        $extraimginfo = '';
        if ( $email->is_answered($userid, $course->id) ) {
            // Color td.
            unset($attribute);
            $attribute = array('bgcolor' => $CFG->email_answered_color);

            // Adding info img.
            $extraimginfo = '<img src="'.$CFG->wwwroot.'/blocks/email_list/email/images/answered.gif" alt="" /> ';

        }

        if ( !$coursemail = $DB->get_record("course", "id", $mail->course) ) {
            print_error('invalidcourseid', 'block_email_list');
        }

        if ( $course->id == SITEID ) {
            $table->data[] = array (
                                '<input id="mail" type="checkbox" name="mailid[]" value="'.$mail->id.'" />',
                                $coursemail->fullname,
                                $newemailicon.$attachment.$extraimginfo,
                                $urltosent,
                                $struser,
                                userdate($mail->timecreated)
                            );
        } else {
            $table->data[] = array (
                                '<input id="mail" type="checkbox" name="mailid[]" value="'.$mail->id.'" />',
                                $newemailicon.$attachment.$extraimginfo,
                                $urltosent,
                                $struser,
                                userdate($mail->timecreated)
                            );
        }

        // Save previous mail.
        $previousmail = $mail->id;
    }

    //echo html_writter::table($table);
    print_table($table);
    
    // Print select action, if have mails.
    if ( $mails ) {
        email_print_select_options($options, $SESSION->email_mailsperpage);
    }

    // End form.
    echo '</form>';

    return true;
}

/**
 * This functions prints tabs options.
 *
 * @uses $CFG, $OUTPUT
 * @param int $courseid Course Id
 * @param int $folderid Folder Id
 * @param string $action  Actual action
 * @return boolean Success/Fail
 * @todo Finish documenting this function
 */
function email_print_tabs_options($courseid, $folderid, $action=null) {
    global $CFG, $OUTPUT;

    if ($courseid == SITEID) {
        $context = context_system::instance();   // SYSTEM context.
    } else {
        $context = context_course::instance($courseid);   // Course context.
    }

    // Declare tab array.
    $tabrow = array();

    // Tab for writting new email.
    if ( has_capability('block/email_list:sendmessage', $context)) {
        $tabrow[] = new tabobject('newmail',
            new moodle_url('/blocks/email_list/email/sendmail.php', 
                array('course' => $courseid, 'folderid' => $folderid)),
            get_string('newmail', 'block_email_list')
        );
    }

    if ( has_capability('block/email_list:createlabel', $context)) {
        $tabrow[] = new tabobject('newfolderform',
            new moodle_url('/blocks/email_list/email/folder.php',
                array('course' => $courseid, 'folderid' => $folderid)),
            get_string('newfolderform', 'block_email_list')
        );
    }

    // FUTURE: Implement filters.

    // If empty tabrow, add vspace. Only apply on Site Course.
    if ( empty($tabrow) ) {
        $spacer = new html_image();
        $spacer->height = 50;
        $spacer->width = 1;
        echo $OUTPUT->spacer($spacer);
    }

    // Print tabs, and if it's in case, selected this.
    switch($action) {
        case 'newmail':
            echo $OUTPUT->tabtree($tabrow, 'newmail');
            break;
        case 'newfolderform':
            echo $OUTPUT->tabtree($tabrow, 'newfolderform');
            break;
        case 'newfilter':
            echo $OUTPUT->tabtree($tabrow, 'filter');
            break;
        default:
            echo $OUTPUT->tabtree($tabrow);
    }

    return true;
}

// SQL funcions.

/**
 * This function get write mails from user
 *
 * @param int $userid User ID
 * @param string $order Order by ...
 * @return object Contain all write mails
 * @todo Finish documenting this function
 */
function email_get_my_writemails($userid, $order = null) {
    global $DB;

    // Get my write mails.
    if ($order) {
        $mails = $DB->get_records('email_mail', 'userid', $userid, $order);
    } else {
        $mails = $DB->get_records('email_mail', 'userid', $userid);
    }

    return $mails;
}

/**
 * This function return folder parent.
 *
 * @param object $folder Folder
 * @return object Contain parent folder
 * @todo Finish documenting this function
 */
function email_get_parent_folder($folder) {
    global $DB;

    if ( !$folder ) {
        return false;
    }

    if ( is_int($folder) ) {
        if ( !$subfolder = $DB->get_record('email_subfolder', 'folderchildid', $folder) ) {
            return false;
        }
    } else {
        if ( !$subfolder = $DB->get_record('email_subfolder', 'folderchildid', $folder->id) ) {
            return false;
        }
    }

    return $DB->get_record('email_folder', 'id', $subfolder->folderparentid);

}

/**
 * This function return my folders. it's recursive function.
 *
 */
function email_my_folders($folderid, $courseid, $myfolders, $space) {

    $space .= '&#160;&#160;&#160;';

    $folders = \block_email_list\label::get_sublabels($folderid, $courseid);
    if ( $folders ) {
        foreach ($folders as $folder) {
            $myfolders[$folder->id] = $space.$folder->name;
            $myfolders = email_my_folders($folder->id, $courseid, $myfolders, $space);
        }
    }
    return $myfolders;
}

/**
 * This function return my folders.
 *
 * @param int $userid User ID
 * @param int $courseid Course ID
 * @param boolean $excludetrash Exclude Trash
 * @param boolean $excludedraft Exclude Draft
 * @param boolean $excludesendbox Exclude Sendbox
 * @param boolean $excludeinbox Exclude Inbox
 * @return array Contain my folders
 * @todo Finish documenting this function
 */
function email_get_my_folders($userid, $courseid, $excludetrash, $excludedraft, $excludesendbox=false, $excludeinbox=false) {
    // Save my folders in this variable.
    $myfolders = array();

    // Get especific root folders.
    $folders = email_get_root_folders($userid, !$excludedraft, !$excludetrash, !$excludesendbox, !$excludeinbox);

    // For every root folder.
    foreach ($folders as $folder) {
        $myfolders[$folder->id] = $folder->name;
        $myfolders = email_my_folders($folder->id, $courseid, $myfolders, '&#160;&#160;&#160;');
    }

    return $myfolders;
}

/**
 * This function return root folders parent with it.
 *
 * @param int $userid User ID
 * @param boolean $draft Add draft folder
 * @param boolean $trash Add trash folder
 * @param boolean $sendbox Add sendbox folder
 * @param boolean $inbox Add inbox folder
 * @return array Contain all parents folders
 * @todo Finish documenting this function
 */
function email_get_root_folders($userid, $draft=true, $trash=true, $sendbox=true, $inbox=true) {
    \block_email_list\label::create_parents($userid);

    $folders = array();

    // Include inbox folder.
    if ( $inbox ) {
        $folders[] = \block_email_list\label::get_root($userid, EMAIL_INBOX);
    }

    // Include return draft folder.
    if ( $draft ) {
        $folders[] = \block_email_list\label::get_root($userid, EMAIL_DRAFT);
    }

    // Include sendbox folder.
    if ( $sendbox ) {
        $folders[] = \block_email_list\label::get_root($userid, EMAIL_SENDBOX);
    }

    if ( $trash ) {
        $folders[] = \block_email_list\label::get_root($userid, EMAIL_TRASH);
    }

    return $folders;
}

/**
 * This function get users to sent an mail.
 *
 * @param int $mailid Mail ID
 * @param boolean $forreplyall Indicates if getting user's for reply all. If true return object contain formated names (Optional)
 * @param object $writer Contain user who write mail (if not null, exclude this user for returned)
 * @param string Type of mail for users
 * @return object Contain all users object send mails
 * @todo Finish documenting this function
 */
function email_get_users_sent($mailid, $forreplyall=false, $writer=null, $type='') {
    global $DB;

    // Get mails with send to my.
    if (! $sends = $DB->get_records('email_send', 'mailid', $mailid) ) {
        return false;
    }

    $users = array();

    // Get username.
    foreach ($sends as $send) {

        // Get user.
        if ( !$user = $DB->get_record('user', 'id', $send->userid) ) {
            return false;
        }

        // Exclude user.
        if ( $writer ) {
            if ( $user->id != $writer->id) {
                if ( !$forreplyall ) {
                    $users[] = fullname($user);
                } else {
                    // Separe type, if it's corresponding.
                    if ( $type == 'to') {
                        if ($send->type == 'to' ) {
                            $users[] = $user->id;
                        }
                    } else if ( $type == 'cc' ) {
                        if ($send->type == 'cc' ) {
                            $users[] = $user->id;
                        }
                    } else if ( $type == 'bcc' ) {
                        if ($send->type == 'bcc' ) {
                            $users[] = $user->id;
                        }
                    } else {
                        $users[] = $user->id;
                    }
                }
            }
        } else {
            if ( !$forreplyall ) {

                // Separe type, if it's corresponding.
                if ( $type == 'to') {
                    if ($send->type == 'to' ) {
                        $users[] = fullname($user);
                    }
                } else if ( $type == 'cc' ) {
                    if ($send->type == 'cc' ) {
                        $users[] = fullname($user);
                    }
                } else if ( $type == 'bcc' ) {
                    if ($send->type == 'bcc' ) {
                        $users[] = fullname($user);
                    }
                } else {
                    $users[] = fullname($user);
                }
            } else {
                // Separe type, if it's corresponding.
                if ( $type == 'to') {
                    if ($send->type == 'to' ) {
                        $users[] = $user->id;
                    }
                } else if ( $type == 'cc' ) {
                    if ($send->type == 'cc' ) {
                        $users[] = $user->id;
                    }
                } else if ( $type == 'bcc' ) {
                    if ($send->type == 'bcc' ) {
                        $users[] = $user->id;
                    }
                } else {
                    $users[] = $user->id;
                }
            }
        }
    }

    return $users;
}

/**
 * This function return format fullname users.
 *
 * @param array $users Fullname of user's
 * @param boolean $forreplyall If it's true, no return string error (default false)
 * @return string format fullname user's.
 * @todo Finish documenting this function
 */
function email_format_users($users, $forreplyall=false) {
    if ($users) {
        $usersend = '';

        // Index of record.
        $i = 0;
        foreach ($users as $user) {

            // If no first record, add semicolon.
            if ( $i != 0 ) {
                $usersend .= ', '.$user;
            } else {
                // If first add name only.
                $usersend .= $user;
            }

            // Increment index record.
            $i++;
        }
    } else {
        if ( !$forreplyall ) {
            // If no users sent's, inform this act.
            $usersend = get_string('neverusers', 'block_email_list');
        } else {
            $usersend = '';
        }
    }

    // Return string format name's.
    return $usersend;
}

/**
 * This functions print select form, who it's options to have mails.
 *
 * @uses $CFG, $USER
 * @param object $options Options for redirect this form
 * @return boolean Success/Fail
 * @todo Finish documenting this function
 */
function email_print_select_options($options, $perpage) {
    global $CFG, $USER;

    $baseurl = $CFG->wwwroot . '/blocks/email_list/email/index.php?' . email_build_url($options);

    echo '<br />';

    echo '<div class="content emailfloatcontent">';

    if ( $options->id != SITEID ) {
        echo '<div class="emailfloat emailleft">';
        if ( ! \block_email_list\label::is_type(email_get_folder($options->folderid), EMAIL_SENDBOX) ) {
            echo '<select name="action" onchange="this.form.submit()">
                            <option value="" selected="selected">' . get_string('markas', 'block_email_list') .':</option>
                            <option value="toread">' . get_string('toread', 'block_email_list') . '</option>
                        <option value="tounread">' . get_string('tounread', 'block_email_list') . '</option>
                    </select>';

            print_spacer(1, 20, false);
        }
        echo '</div>';
        echo '<div class="emailleft">';
        email_print_movefolder_button($options);
        // Idaho State University & MoodleRooms contrib - Thanks!.
        email_print_preview_button($options->id);
        echo '</div>';
        echo '<div id="move2folder"></div>';
        echo '</div>';
    }

    // ALERT!: Now, I'm printing end start form, for choose number mail per page.
    $url = '';
    // Build url part options.
    if ($options) {
        $url = email_build_url($options);
    }

    echo '</form>';
    echo '<form id="mailsperpage" name="mailsperpage" action="' .
            $CFG->wwwroot . '/blocks/email_list/email/index.php?' . $url . '" method="post">';

    // Choose number mails perpage.
    echo '<div id="sizepage" class="emailright">' . get_string('mailsperpage', 'block_email_list') .': ';

    // Define default separator.
    $spaces = '&#160;&#160;&#160;';

    echo '<select name="perpage" onchange="javascript:this.form.submit();">';

    for ($i = 5; $i < 80; $i = $i + 5) {

        if ( $perpage == $i ) {
            echo '<option value="'.$i.'" selected="selected">' . $i . '</option>';
        } else {
            echo '<option value="'.$i.'">' . $i . '</option>';
        }
    }

    echo '</select>';

    echo '</div>';

    return true;
}

/**
 * This function prints select folders combobox, for move any mails
 *
 * @uses $USER
 * @param object $options
 */
function email_print_movefolder_button($options) {

    global $CFG, $USER;

    $courseid = null;
    if ( $options->id == SITEID and $options->course != SITEID) {
        $courseid = $options->course;
    } else {
        $courseid = $options->id;
    }

    // TODO: Changed this function, now cases are had:
    // 1.- Inbox folder: Only can move to subfolders inbox and trash folder.
    // 2.- Sendbox and draft folder: Only can move on this subfolders.
    // 3.- Trash folder: Can move any folder.
    if ( isset($options->folderid) ) {
        // Get folder.
        $folderbe = email_get_folder($options->folderid);
    } else if ( isset($options->folderoldid) ) {
        // Get folder.
        $folderbe = email_get_folder($options->folderoldid);
    } else {
        // Inbox folder.
        $folderbe = \block_email_list\label::get_root($USER->id, EMAIL_INBOX);
    }

    if ( \block_email_list\label::is_type($folderbe, EMAIL_SENDBOX ) ) {
        // Get my sendbox folders.
        $folders = email_get_my_folders( $USER->id, $courseid, false, true, false, true );
    } else if ( \block_email_list\label::is_type($folderbe, EMAIL_DRAFT )) {
        // Get my sendbox folders.
        $folders = email_get_my_folders( $USER->id, $courseid, false, true, true, true );
    } else if ( \block_email_list\label::is_type($folderbe, EMAIL_TRASH ) ) {
        // Get my folders.
        $folders = email_get_my_folders( $USER->id, $courseid, false, false, false, false );
    } else {
        // Get my folders.
        $folders = email_get_my_folders( $USER->id, $courseid, false, true, true, false );
    }

    if ( $folders ) {
        $choose = '';

        // Get my courses.
        foreach ($folders as $key => $foldername) {
            $choose .= '<option value="'.$key.'">'.$foldername  .'</option>';
        }
    }

    echo '<select name="folderid" onchange="addAction(this)">
                    <option value="" selected="selected">' . get_string('movetofolder', 'block_email_list') . ':</option>' .
                        $choose . '
            </select>';

    // Add 2 space.
    echo '&#160;&#160;';

    // Change, now folderoldid is actual folderid.
    if ( !$options->folderid ) {
        if ( $inbox = \block_email_list\label::get_root($USER->id, EMAIL_INBOX) ) {
            echo '<input type="hidden" name="folderoldid" value="'.$inbox->id.'" />';
        }
    } else {
        echo '<input type="hidden" name="folderoldid" value="'.$options->folderid.'" />';
    }

    // Define action.
    // Add javascript for insert person/s who I've send mail.

    $javascript = '<script type="text/javascript" language="JavaScript">
                <!--
                        function addAction(form) {

                            var d = document.createElement("div");
                        d.setAttribute("id", "action");
                        var act = document.createElement("input");
                        act.setAttribute("type", "hidden");
                        act.setAttribute("name", "action");
                        act.setAttribute("id", "action");
                        act.setAttribute("value", "move2folder");
                        d.appendChild(act);
                        document.getElementById("move2folder").appendChild(d);

                            document.sendmail.submit();
                        }
                    -->
                 </script>';

    echo $javascript;
}

/**
 * This functions return number of unreaded mails.
 *
 * @uses $CFG
 * @param int $userid User ID
 * @param int $courseid Course ID
 * @param int $labelid Folder ID (Optional) When fault this param, return total number of unreaded mails
 * @return int Number of unread mails.
 * @todo Finish documenting this function
 */
function email_count_unreaded_mails($userid, $courseid, $labelid=null) {
    global $CFG, $DB;

    if ( !$labelid or $labelid <= 0 ) {
        // Get draft folder.
        if ( $label = \block_email_list\label::get_root($userid, EMAIL_INBOX) ) {
            $labelsid = $label->id;

            // Get all subfolders.
            if ( $sublabels = \block_email_list\label::get_all_sublabels($label->id) ) {
                foreach ($sublabels as $sublabel) {
                    $labelsid .= ', '.$sublabel->id;
                }
            }

            $sql = "SELECT count(*)
                                    FROM {email_mail} m
                           LEFT JOIN {email_send} s ON m.id = s.mailid
                           LEFT JOIN {email_labelmail} fm ON m.id = fm.mailid ";

            // WHERE principal clause for filter by user and course.
            $wheresql = " WHERE s.userid = $userid
                          AND s.course = $courseid
                          AND fm.labelid IN ( $labelsid )
                          AND s.readed = 0
                          AND s.sended = 1";

            return $DB->count_records_sql( $sql.$wheresql );
        } else {
            return 0;
        }
    } else {

        // Get label.
        if ( !$label = \block_email_list\label::get($labelid) ) {
            return 0;
        }

        if ( \block_email_list\label::is_type($label, EMAIL_INBOX) ) {
            // For apply order, I've writting an sql clause.
            $sql = "SELECT count(*)
                                    FROM {email_mail} m
                           LEFT JOIN {email_send} s ON m.id = s.mailid
                           LEFT JOIN {email_labelmail} fm ON m.id = fm.mailid ";

            // WHERE principal clause for filter by user and course.
            $wheresql = " WHERE s.userid = $userid
                          AND s.course = $courseid
                          AND fm.labelid = $label->id
                          AND s.readed = 0
                          AND s.sended = 1";

            return $DB->count_records_sql( $sql.$wheresql );

        } else if ( \block_email_list\label::is_type($label, EMAIL_DRAFT) ) {
            // For apply order, I've writting an sql clause.
            $sql = "SELECT count(*)
                            FROM {email_mail} m
                            LEFT JOIN {email_labelmail} fm ON m.id = fm.mailid ";

            // WHERE principal clause for filter user and course.
            $wheresql = " WHERE m.userid = $userid
                          AND m.course = $courseid
                          AND fm.labelid = $label->id";

            return $DB->count_records_sql( $sql.$wheresql );

        } else {
            return 0;
        }
    }
}

/**
 * This funcions build an URL for an options.
 *
 * @param object $options
 * @param boolean $form If return form
 * @param boolean $arrayinput If name of hidden input is array.
 * @param string $nameinput If is arrayinput, pass name of this.
 * @return string URL or Hidden input's
 * @todo Finish documenting this function
 */
function email_build_url($options, $form=false, $arrayinput=false, $nameinput=null) {
    $url = '';

    // Build url part options.
    if ($options) {
        // Index of part url.
        $i = 0;

        foreach ($options as $name => $value) {
            // If not first param.
            if ( !$form ) {
                if ($i != 0) {
                    $url .= '&amp;' .$name .'='. $value;
                } else {
                    // If first param.
                    $url .= $name .'='. $value;
                }
                // Increment index.
                $i++;
            } else {

                if ( $arrayinput ) {
                    $url .= '<input type="hidden" name="'.$nameinput.'[]" value="'.$value.'" /> ';
                } else {
                    $url .= '<input type="hidden" name="'.$name.'" value="'.$value.'" /> ';
                }
            }
        }
    }

    return $url;
}


/**
 * This functions return id's of object.
 *
 * @param object $ids Mail
 * @return string String of ids
 * @todo Finish documenting this function
 */
function email_get_ids($ids) {
    $identities = array();

    if ( $ids ) {
        foreach ($ids as $id) {
            $identities[] = $id->id;
        }
    }

    // Character alfanumeric, becase optional_param clean anothers tags.
    $strids = implode('a', $identities);

    return $strids;
}

/**
 * This functions return next or previous mail.
 *
 * @param int $mailid Mail
 * @param string $mails Id's of mails
 * @param boolean True when next, false when previous
 * @return int Next or Previous mail
 * @todo Finish documenting this function
 */
function email_get_nextprevmail($mailid, $mails, $nextorprevious) {
    // To array.
    // Character alfanumeric, becase optional_param clean anothers tags.
    $mailsids = explode('a', $mails);

    if ( $mailsids ) {
        $prev = 0;
        $next = false;
        foreach ($mailsids as $mail) {
            if ( $next ) {
                return $mail; // Return next "record".
            }
            if ( $mail == $mailid ) {
                if ($nextorprevious) {
                    $next = true;
                } else {
                    return $prev; // Return previous "record".
                }
            }
            $prev = $mail;
        }
    }

    return false;
}

/**
 * This function return user who writting an mail.
 *
 * @param int $mailid Mail ID
 * @return object User record
 * @todo Finish documenting this function
 */
function email_get_user($mailid) {
    global $DB;

    // Get mail record.
    if ( !$mail = $DB->get_record('email_mail', 'id', $mailid)) {
        error('failgetmail', 'block_email_list');
    }

    // Return user record.
    return $DB->get_record('user', 'id', $mail->userid);
}

/**
 * This function return if userid have aviability or not to associate folder to courses.
 *
 * @param int $userid User Id.
 * @return boolean True or false if have aviability
 */
function email_have_asociated_folders($userid) {
    global $CFG, $USER, $DB;

    if ( empty($userid) ) {
        $userid = $USER->id;
    }

    if ( $CFG->email_marriedfolders2courses ) {
        if ( $preferences = $DB->get_record('email_preference', 'userid', $userid) ) {
            if ($preferences->marriedfolders2courses) {
                return true;
            }
        }
    }

    return false;
}

/**
 * This function return special fullname.
 *
 * @param object $user User
 * @return string Full name
 */
function email_fullname($user, $override=false) {
    // Drop all semicolon apears. (Js errors when select contacts).
    return str_replace(',', '', fullname($user, $override));
}

/**
 * Prints the print emails button
 *
 * Idaho State University & MoodleRooms contrib - Thanks!
 *
 * @param int $courseid Course Id
 *
 * @return void
 **/
function email_print_preview_button($courseid) {
    // Action is handled weird, put in a dummy hidden element.
    // And then change its name to action when our button has.
    // Been clicked.
    echo '<span id="print_preview" class="print_preview">';
    email_print_to_popup_window('button',
        '/blocks/email_list/email/print.php?courseid=' . $courseid . '&amp;mailids=',
        get_string('printemails', 'block_email_list'),
        get_string('printemails', 'block_email_list')
    );
    echo '</span>';
}

/**
 * Need redefine element_to_popup_window because before get email ids for print. Modify all.
 *
 */
function email_print_to_popup_window($type=null, $url=null, $linkname=null, $title=null, $return=false) {

    if (is_null($url)) {
        debugging('You must give the url to display in the popup. URL is missing - can\'t create popup window.', DEBUG_DEVELOPER);
    }

    global $CFG;

    // Add some sane default options for popup windows.
    $options = 'menubar=0,location=0,scrollbars,resizable,width=500,height=400';

    $name = 'popup';

    // Get some default string, using the localized version of legacy defaults.
    if (is_null($linkname) || $linkname === '') {
        $linkname = get_string('clickhere');
    }
    if (!$title) {
        $title = get_string('popupwindowname');
    }

    $fullscreen = 0; // Must be passed to openpopup.
    $element = '';

    $jscode = ' if (document.sendmail) { var ids = get_for_print_multiple_emails(document.sendmail.mail); }';

    switch ($type) {
        case 'button' :
            $element = '<input type="button" name="'. $name .'" title="'. $title .'" value="'. $linkname .'" '.
                       "onclick=\" $jscode if(ids !='' ) {
                       return openpopup('$url'+ids, '$name', '$options', $fullscreen); } else { alert('".
                       addslashes(get_string('nochoosemail', 'block_email_list'))."'); } \" />\n";
            break;
        case 'link' :
            // Some log url entries contain _SERVER[HTTP_REFERRER] in which case wwwroot is already there.
            if ( !(strpos($url, $CFG->wwwroot) === false) ) {
                $url = substr($url, strlen($CFG->wwwroot));
            }
            $element = '<a title="'. s(strip_tags($title)) .'" href="'. $CFG->wwwroot . $url .'" '.
                       "onclick=\"this.target='$name'; $jscode if (ids !=''){
                       return openpopup('$url'+ids, '$name', '$options', $fullscreen);
                       } else { alert('". addslashes(get_string('nochoosemail', 'block_email_list'))."'); } \">$linkname</a>";
            break;
        default :
            print_error('undefinedelement', 'block_email_list');
            break;
    }

    if ($return) {
        return $element;
    } else {
        echo $element;
    }
}

/**
 * This function manage value for what mails show perpage.
 *
 * @uses $SESSION
 */
function email_manage_mailsperpage() {
    global $SESSION;

    if ( ! empty( $_POST['perpage'] ) and is_int($_POST['perpage']) ) {
        $SESSION->email_mailsperpage = $_POST['perpage'];
        echo 'Change for: '.$_POST['perpage'];
    } else {
        $SESSION->email_mailsperpage = 10; // Default value.
    }
}

// TODO: Drop function when Moodle 1.7 is unsuported version.
if ( !function_exists('groups_print_course_menu') ) {
    /**
     * Print group menu selector for course level.
     * @param object $course course object
     * @param string $urlroot return address
     * @param boolean $return return as string instead of printing
     * @return mixed void or string depending on $return param
     */
    function groups_print_course_menu($course, $urlroot, $return=false) {
        global $CFG, $USER, $SESSION;

        if ( !$groupmode = $course->groupmode ) {
            if ($return) {
                return '';
            } else {
                return;
            }
        }

        $context = get_context_instance(CONTEXT_COURSE, $course->id);
        if ($groupmode == VISIBLEGROUPS or has_capability('moodle/site:accessallgroups', $context)) {
            $allowedgroups = groups_get_all_groups($course->id, 0);
            // Detect changes related to groups and fix active group.
            if (!empty($SESSION->activegroup[$course->id][VISIBLEGROUPS][0])) {
                if (!array_key_exists($SESSION->activegroup[$course->id][VISIBLEGROUPS][0], $allowedgroups)) {
                    // Active does not exist anymore.
                    unset($SESSION->activegroup[$course->id][VISIBLEGROUPS][0]);
                }
            }
            if (!empty($SESSION->activegroup[$course->id]['aag'][0])) {
                if (!array_key_exists($SESSION->activegroup[$course->id]['aag'][0], $allowedgroups)) {
                    // Active group does not exist anymore.
                    unset($SESSION->activegroup[$course->id]['aag'][0]);
                }
            }

        } else {
            $allowedgroups = groups_get_all_groups($course->id, $USER->id);
            // Detect changes related to groups and fix active group.
            if (isset($SESSION->activegroup[$course->id][SEPARATEGROUPS][0])) {
                if ($SESSION->activegroup[$course->id][SEPARATEGROUPS][0] == 0) {
                    if ($allowedgroups) {
                        // Somebody must have assigned at least one group, we can select it now - yay!.
                        unset($SESSION->activegroup[$course->id][SEPARATEGROUPS][0]);
                    }
                } else {
                    if (!array_key_exists($SESSION->activegroup[$course->id][SEPARATEGROUPS][0], $allowedgroups)) {
                        // Active group not allowed or does not exist anymore.
                        unset($SESSION->activegroup[$course->id][SEPARATEGROUPS][0]);
                    }
                }
            }
        }

        $activegroup = groups_get_course_group($course, true);

        $groupsmenu = array();
        if ( !$allowedgroups or $groupmode == VISIBLEGROUPS or has_capability('moodle/site:accessallgroups', $context) ) {
            $groupsmenu[0] = get_string('allparticipants');
        }

        if ($allowedgroups) {
            foreach ($allowedgroups as $group) {
                $groupsmenu[$group->id] = format_string($group->name);
            }
        }

        if ($groupmode == VISIBLEGROUPS) {
            $grouplabel = get_string('groupsvisible');
        } else {
            $grouplabel = get_string('groupsseparate');
        }

        if (count($groupsmenu) == 1) {
            $groupname = reset($groupsmenu);
            $output = $grouplabel.': '.$groupname;
        } else {
            $output = popup_form($urlroot.'&amp;group=',
                        $groupsmenu,
                        'selectgroup',
                        $activegroup,
                        '',
                        '',
                        '',
                        true,
                        'self',
                        $grouplabel
            );
        }

        $output = '<div class="groupselector">'.$output.'</div>';

        if ($return) {
            return $output;
        } else {
            echo $output;
        }
    }
}

if ( !function_exists('groups_get_all_groups')) {
    /**
     * Gets array of all groups in a specified course.
     * @param int $courseid The id of the course.
     * @param mixed $userid optional user id or array of ids, returns only groups of the user.
     * @param int $groupingid optional returns only groups in the specified grouping.
     * @return array | false Returns an array of the group objects or false if no records
     * or an error occurred. (userid field returned if array in $userid)
     */
    function groups_get_all_groups($courseid, $userid=0, $groupingid=0, $fields='g.*') {
        global $CFG, $DB;

        // Groupings are ignored when not enabled.
        if (empty($CFG->enablegroupings)) {
            $groupingid = 0;
        }

        if (empty($userid)) {
            $userfrom  = "";
            $userwhere = "";

        } else if (is_array($userid)) {
            $userids = implode(',', $userid);
            $userfrom  = ", {groups_members} gm";
            $userwhere = "AND g.id = gm.groupid AND gm.userid IN ($userids)";

        } else {
            $userfrom  = ", {groups_members} gm";
            $userwhere = "AND g.id = gm.groupid AND gm.userid = '$userid'";
        }

        if (!empty($groupingid)) {
            $groupingfrom  = ", {groupings_groups} gg";
            $groupingwhere = "AND g.id = gg.groupid AND gg.groupingid = '$groupingid'";
        } else {
            $groupingfrom  = "";
            $groupingwhere = "";
        }

        return $DB->get_records_sql("SELECT $fields
                                  FROM {groups} g $userfrom $groupingfrom
                                 WHERE g.courseid = $courseid $userwhere $groupingwhere
                              ORDER BY name ASC");
    }
}

if ( !function_exists('groups_get_course_group')) {
    /**
     * Returns group active in course, changes the group by default if 'group' page param present
     *
     * @param object $course course bject
     * @param boolean $update change active group if group param submitted
     * @return mixed false if groups not used, int if groups used, 0 means all groups (access must be verified in SEPARATE mode)
     */
    function groups_get_course_group($course, $update=false) {
        global $CFG, $USER, $SESSION;

        if (!$groupmode = $course->groupmode) {
            // NOGROUPS used.
            return false;
        }

        // Init activegroup array.
        if (!array_key_exists('activegroup', $SESSION)) {
            $SESSION->activegroup = array();
        }
        if (!array_key_exists($course->id, $SESSION->activegroup)) {
            $SESSION->activegroup[$course->id] = array( SEPARATEGROUPS => array(),
                                                     VISIBLEGROUPS => array(),
                                                     'aag' => array());
        }

        $context = get_context_instance(CONTEXT_COURSE, $course->id);
        if (has_capability('moodle/site:accessallgroups', $context)) {
            $groupmode = 'aag';
        }

        // Grouping used the first time - add first user group as default.
        if ( !array_key_exists(0, $SESSION->activegroup[$course->id][$groupmode]) ) {
            if ($groupmode == 'aag') {
                $SESSION->activegroup[$course->id][$groupmode][0] = 0; // All groups by default if user has accessallgroups.

            } else if ($usergroups = groups_get_all_groups($course->id, $USER->id, 0)) {
                $fistgroup = reset($usergroups);
                $SESSION->activegroup[$course->id][$groupmode][0] = $fistgroup->id;

            } else {
                // This happen when user not assigned into group in SEPARATEGROUPS mode or groups do not exist yet.
                // Mod authors must add extra checks for this when SEPARATEGROUPS mode used (such as when posting to forum).
                $SESSION->activegroup[$course->id][$groupmode][0] = 0;
            }
        }

        // Set new active group if requested.
        $changegroup = optional_param('group', -1, PARAM_INT);
        if ($update and $changegroup != -1) {

            if ($changegroup == 0) {
                // Do not allow changing to all groups without accessallgroups capability.
                if ($groupmode == VISIBLEGROUPS or $groupmode == 'aag') {
                    $SESSION->activegroup[$course->id][$groupmode][0] = 0;
                }

            } else {
                // First make list of allowed groups.
                if ($groupmode == VISIBLEGROUPS or $groupmode == 'aag') {
                    $allowedgroups = groups_get_all_groups($course->id, 0, 0);
                } else {
                    $allowedgroups = groups_get_all_groups($course->id, $USER->id, 0);
                }

                if ($allowedgroups and array_key_exists($changegroup, $allowedgroups)) {
                    $SESSION->activegroup[$course->id][$groupmode][0] = $changegroup;
                }
            }
        }
        return $SESSION->activegroup[$course->id][$groupmode][0];
    }
}