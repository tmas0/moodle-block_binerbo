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
 * This page prints all search's.
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
require_once($CFG->dirroot.'/blocks/email_list/email/lib.php');         // The eMail library funcions.

// Advanced search form.
require_once($CFG->dirroot.'/blocks/email_list/email/advanced_search_form.php');

$courseid   = optional_param('courseid', SITEID, PARAM_INT);    // Course ID.
$folderid   = optional_param('folderid', 0, PARAM_INT);         // Folder ID.
$filterid   = optional_param('filterid', 0, PARAM_INT);         // Filter ID.

$page       = optional_param('page', 0, PARAM_INT);             // Which page to show.
$perpage    = optional_param('perpage', 10, PARAM_INT);         // How many per page.

// Search words
$search     = optional_param('words', '', PARAM_TEXT);          // Text to search.
$action     = optional_param('action', 0, PARAM_INT);           // Action.


// If defined course to view.
if ( !$course = $DB->get_record('course', array('id' => $courseid)) ) {
    print_error('invalidcourseid', 'block_email_list');
}

require_login($course->id, false); // No autologin guest.

if ($course->id == SITEID) {
    $context = context_system::instance();   // SYSTEM context.
} else {
    $context = context_course::instance($course->id);   // Course context.
}

// Get renderer.
$renderer = $PAGE->get_renderer('block_email_list');

// Set default page parameters.
$PAGE->set_pagelayout('incourse');
$PAGE->set_context($context);
$PAGE->set_url('/blocks/email_list/email/search.php',
    array(
        'courseid' => $course->id
    )
);

// Add log for one course.
add_to_log($courseid, 'email', 'search', 'view.php?id='.$courseid, 'View all mails of '.$course->shortname);


// Print the page header.

$preferencesbutton = email_get_preferences_button($courseid);

$stremail  = get_string('name', 'block_email_list');
$PAGE->set_title($course->shortname . ': ' . $stremail);

if ( $search == get_string('searchtext', 'block_email_list') or $search == '' ) {
    $strsearch = get_string('advancedsearch', 'search');
} else {
    $strsearch = get_string('search', 'search');
}

// Print the page header.
echo $renderer->header();

// Options for new mail and new folder.
$options = new stdClass();
$options->id = $courseid;
$options->folderid = $folderid;
$options->filterid = $filterid;

// Print the main part of the page.
email_printblocks($USER->id,
    $courseid,
    ($search == get_string('searchtext', 'block_email_list') or $search == '') ? true : false
);

// Print middle table.
$PAGE->set_heading($strsearch);

// Create advanced search form.
$advancedsearch = new advanced_search_form();

if ( ( $search == get_string('searchtext', 'block_email_list') or $search == '' ) and ( !$advancedsearch->is_submitted() ) ) {

    if ( ! $action ) {
        notify(get_string('emptysearch', 'block_email_list'));
        notify(get_string('wantadvancedsearch', 'block_email_list'), 'notifysuccess');
    }

    // Print advanced search form.
    $advancedsearch->display();
} else if ( $advancedsearch->is_cancelled() ) {

    // Cancelled form.
    redirect($CFG->wwwroot.'/blocks/email_list/email/index.php?id='.$courseid, '', 1);

} else if ( $data = $advancedsearch->get_data()) {

    // Advanced Search by:
    // - Folders.
    // - Course.
    // - From.
    // - To.
    // - Subject.
    // - Body.
    //
    // And / Or.

    $select = 'SELECT m.*, u.firstname,u.lastname, m.userid as writer';

    $from   = ' FROM {user} u,
                 {email_mail} m,
                 {email_send} s,
                 {email_foldermail} fm';


    // FOLDERS.
    $wherefolders = '';
    if ( ! empty( $data->folders) ) {

        if ( is_array($data->folders) ) {
            $wherefolders .= ' AND ( ';
            $i = 0;
            foreach ($data->folders as $key => $folder) {
                // Select this folder.
                $wherefolders .= ($i > 0) ? " $data->connector fm.folderid = $key" : " fm.folderid = $key ";
                $i++;
            }
            $wherefolders .= ' ) ';
        }
    } else {
        print_error('nosearchfolders', 'block_email_list');
    }

    $groupby = ' GROUP BY m.id';

    // TO.
    $myto = " m.userid = $USER->id AND m.userid = u.id ";
    $searchto = '';
    if ( ! empty($data->to) ) {

        $searchtext = trim($data->to);

        if ($searchtext !== '') {   // Search for a subset of remaining users.
            $like      = $DB->sql_ilike();
            $fullname  = $DB->sql_fullname('usu.firstname', 'usu.lastname');

            $searchto = " AND ($fullname $like '%$searchtext%') ";
        }
    }


    // FROM.
    $searchfrom = '';
    if ( ! empty( $data->from ) ) {

        $searchtext = trim($data->from);

        if ($searchtext !== '') {   // Search for a subset of remaining users.
            $like      = $DB->sql_ilike();
            $fullname  = $DB->sql_fullname();

            $searchfrom = " AND ($fullname $like '%$searchtext%') ";
        }
    }

    // SUBJECT.
    $sqlsubject = '';
    if ( !empty($data->subject) ) {

        $searchstring = str_replace( "\\\"", "\"", $data->subject);
        $parser = new search_parser();
        $lexer = new search_lexer($parser);

        if ($lexer->parse($searchstring)) {
            $parsearray = $parser->get_parsed_array();

            $sqlsubject = $DB->search_generate_text_SQL($parsearray, 'm.subject', '', 'm.userid', 'u.id',
                     'u.firstname', 'u.lastname', 'm.timecreated', '');
        }
    }


    // BODY.
    $sqlbody = '';
    if ( ! empty( $data->body ) ) {

        $searchstring = str_replace( "\\\"", "\"", $data->body);
        $parser = new search_parser();
        $lexer = new search_lexer($parser);

        if ($lexer->parse($searchstring)) {
            $parsearray = $parser->get_parsed_array();

            $sqlbody = $DB->search_generate_text_SQL($parsearray, 'm.body', '', 'm.userid', 'u.id',
                     'u.firstname', 'u.lastname', 'm.timecreated', '');

            $sqlsubjectbody = (! empty($sqlsubject) ) ? " AND ( $sqlsubject $data->connector $sqlbody ) " : ' AND '.$sqlbody;
        }
    } else if (!empty($sqlsubject) ) {
        $sqlsubjectbody = ' AND '.$sqlsubject;
    } else {
        $sqlsubjectbody = '';
    }

    $sqlcourse = " AND s.course = m.course AND m.course = $courseid AND s.course = $courseid ";

    $sql = '';

    if ( !empty($data->to) ) {
        $sql = "SELECT  R1.*, usu.firstname,usu.lastname, R1.userid as writer FROM (";
    }

    $sql .= $select.$from. ' WHERE fm.mailid = m.id '.
                ' AND m.userid = u.id '. // Allways I'm searching writer ... show Select fields.
                ' AND s.mailid = m.id '. // Allways searching one mail ... apply join.
                $wherefolders.
                $sqlcourse.
                $sqlsubjectbody.
                $searchfrom.
                ' AND ( m.userid = '.$USER->id.' OR ( s.userid = '.$USER->id.' AND s.mailid = m.id) ) '.
                $groupby;

    if ( !empty($data->to) ) {
        $sql .= " ) R1, {user} usu, {email_send} s1 " .
                "WHERE R1.id = s1.mailid AND usu.id=s1.userid AND R1.course = s1.course AND s1.type <> 'bcc' $searchto";
    }

    if (! $searchmails = $DB->get_records_sql($sql) ) {
        debugging('Empty advanced search for next SQL stament: '.$sql, DEBUG_DEVELOPER);
    }

    $advancedsearch->display();

    notify(get_string('searchword', 'block_email_list'), 'notifysuccess');

    // Show mails searched.
    \block_email_list\email::showmails($USER->id, '', $page, $perpage, $options, true, $searchmails );

} else {

    // Simple search.
    $select = 'SELECT m.*, u.firstname,u.lastname, m.userid as writer';

    $from   = ' FROM {user} u,
                 {email_mail} m,
                 {email_send} s,
                 {email_foldermail} fm';


    // FOLDERS.
    $wherefolders = '';
    $folders = email_get_root_folders($USER->id, false);
    if ( !empty( $folders) ) {

        $wherefolders .= ' AND ( ';
        $i = 0;
        foreach ($folders as $folder) {
            $wherefolders .= ($i > 0) ? " OR fm.folderid = $folder->id" : " fm.folderid = $folder->id "; // Select this folder.
            $i++;

            // Now, get all subfolders it.
            $subfolders = email_get_subfolders($folder->id);

            // If subfolders.
            if ( $subfolders ) {
                foreach ($subfolders as $subfolder) {
                    // Select this folder.
                    $wherefolders .= ($i > 0) ? " OR fm.folderid = $subfolder->id" : " fm.folderid = $subfolder->id ";
                    $i++;
                }
            }
        }
        $wherefolders .= ' ) ';
    } else {
        print_error('nosearchfolders', 'block_email_list');
    }

    $groupby = ' GROUP BY m.id';

    // TO.
    $myto = " m.userid = $USER->id AND m.userid = u.id ";
    $searchto = '';
    if ( ! empty($search) ) {

        $searchtext = trim($search);

        if ($searchtext !== '') {   // Search for a subset of remaining users.
            $like      = $DB->sql_ilike();
            $fullname  = $DB->sql_fullname('usu.firstname', 'usu.lastname');

            $searchto = " AND ($fullname $like '%$searchtext%') ";
        }
    }


    // FROM.
    $searchfrom = '';
    if ( !empty( $search ) ) {
        $searchtext = trim($search);

        if ($searchtext !== '') {   // Search for a subset of remaining users.
            $like      = $DB->sql_ilike();
            $fullname  = $DB->sql_fullname();

            $searchfrom = " OR ($fullname $like '%$searchtext%') )";
        }
    }

    // SUBJECT.
    $sqlsubject = '';
    if ( ! empty( $search ) ) {

        $searchstring = str_replace( "\\\"", "\"", $search);
        $parser = new search_parser();
        $lexer = new search_lexer($parser);

        if ($lexer->parse($searchstring)) {
            $parsearray = $parser->get_parsed_array();

            $sqlsubject = $DB->search_generate_text_SQL($parsearray, 'm.subject', '', 'm.userid', 'u.id',
                     'u.firstname', 'u.lastname', 'm.timecreated', '');
        }
    }

    // BODY.
    $sqlbody = '';
    if ( !empty($search) ) {

        $searchstring = str_replace( "\\\"", "\"", $search);
        $parser = new search_parser();
        $lexer = new search_lexer($parser);

        if ($lexer->parse($searchstring)) {
            $parsearray = $parser->get_parsed_array();

            $sqlbody = $DB->search_generate_text_SQL($parsearray, 'm.body', '', 'm.userid', 'u.id',
                     'u.firstname', 'u.lastname', 'm.timecreated', '');

            $sqlsubjectbody = (! empty($sqlsubject) ) ? " AND ( $sqlsubject OR $sqlbody " : ' AND '.$sqlbody;
        }
    } else if (!empty($sqlsubject) ) {
        $sqlsubjectbody = ' AND '.$sqlsubject;
    } else {
        $sqlsubjectbody = '';
    }


    $sqlcourse = " AND s.course = m.course AND m.course = $courseid AND s.course = $courseid ";

    // README: If you can search by to, this simple search mode don't get this results, you use advanced search.
    // Only search by: Folder and ( Subject or Body or From).

    $sql = '';

    $sql .= $select.$from. ' WHERE fm.mailid = m.id '.
                ' AND m.userid = u.id '. // Allways I'm searching writer ... show Select fields.
                ' AND s.mailid = m.id '. // Allways searching one mail ... apply join.
                $wherefolders.
                $sqlcourse.
                $sqlsubjectbody.
                $searchfrom.
                ' AND ( m.userid = '.$USER->id.' OR ( s.userid = '.$USER->id.' AND s.mailid = m.id) ) '.
                $groupby;

    if (! $searchmails = $DB->get_records_sql($sql) ) {
        debugging('Empty simple search for next SQL stament: '.$sql, DEBUG_DEVELOPER);
    }

    $advancedsearch->display();

    notify(get_string('searchword', 'block_email_list'), 'notifysuccess');

    // Show mails searched.
    \block_email_list\email::showmails($USER->id, '', $page, $perpage, $options, true, $searchmails );
}

// Finish the page.
echo $renderer->footer();
