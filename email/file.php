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
 * This script fetches files from the dataroot directory
 * Syntax:      file.php/courseid/dir/dir/dir/filename.ext
 *              file.php/courseid/dir/dir/dir/filename.ext?forcedownload=1 (download instead of inline)
 *              file.php/courseid/dir (returns index.html from dir)
 * Workaround:  file.php?file=/courseid/dir/dir/dir/filename.ext
 * Test:        file.php/testslasharguments
 *
 * TODO: Blog attachments do not have access control implemented - anybody can read them!
 *      It might be better to move the code to separate file because the access
 *      control is quite complex - see bolg/index.php
 */

require_once('../../../config.php');
require_once('../../../lib/filelib.php');
// Load eMail - Toni Mas.
require_once($CFG->dirroot.'/blocks/email_list/email/email.class.php');

if ( !isset($CFG->filelifetime) ) {
    $lifetime = 86400;     // Seconds for files to remain in caches.
} else {
    $lifetime = $CFG->filelifetime;
}

// Disable moodle specific debug messages.
disable_debugging();

$relativepath = get_file_argument('file.php');
$forcedownload = optional_param('forcedownload', 0, PARAM_BOOL);

// Relative path must start with '/', because of backup/restore!!!
if ( !$relativepath ) {
    error('No valid arguments supplied or incorrect server configuration');
} else if ( $relativepath{0} != '/' ) {
    error('No valid arguments supplied, path does not start with slash!');
}

$pathname = $CFG->dataroot.$relativepath;

// Extract relative path components.
$args = explode('/', trim($relativepath, '/'));
if ( count($args) == 0 ) { // Always at least courseid, may search for index.html in course root.
    error('No valid arguments supplied');
}

// Security: limit access to existing course subdirectories.
if ( ( $args[0] != 'blog' ) and
    ( !$course = $DB->get_record_sql("SELECT * FROM {course} WHERE id='".(int)$args[0]."'")) ) {
    error('Invalid course ID');
}

// Security: prevent access to "000" or "1 something" directories.
// Hack for blogs, needs proper security check too.
if ( ($args[0] != 'blog') and ($args[0] != $course->id) ) {
    print_error('Invalid course ID');
}

// Security: login to course if necessary.
// Note: file.php always calls require_login() with $setwantsurltome=false.
// In order to avoid messing redirects. MDL-14495.
if ( $args[0] == 'blog' ) {
    if ( empty($CFG->bloglevel) ) {
        error('Blogging is disabled!');
    } else if ( $CFG->bloglevel < BLOG_GLOBAL_LEVEL ) {
        require_login(0, true, null, false);
    } else if ( $CFG->forcelogin ) {
        require_login(0, true, null, false);
    }
} else if ( $course->id != SITEID ) {
    require_login($course->id, true, null, false);
} else if ($CFG->forcelogin) {
    $policy = $CFG->sitepolicy == $CFG->wwwroot.'/file.php'.$relativepath;
    $policyslashed = ($CFG->sitepolicy == $CFG->wwwroot . '/file.php?file=' . $relativepath);
    $test = ($policy or $policyslashed);
    if ( empty($CFG->sitepolicy) and !$test ) {
        require_login(0, true, null, false);
    }
}

// Security: only editing teachers can access backups.
if ((count($args) >= 2) and (strtolower($args[1]) == 'backupdata')) {
    if ( !has_capability('moodle/site:backup', get_context_instance(CONTEXT_COURSE, $course->id)) ) {
        print_error('Access not allowed');
    } else {
        $lifetime = 0; // Disable browser caching for backups.
    }
}

if ( is_dir($pathname) ) {
    if ( file_exists($pathname.'/index.html') ) {
        $pathname = rtrim($pathname, '/').'/index.html';
        $args[] = 'index.html';
    } else if ( file_exists($pathname.'/index.htm') ) {
        $pathname = rtrim($pathname, '/').'/index.htm';
        $args[] = 'index.htm';
    } else if ( file_exists($pathname.'/Default.htm') ) {
        $pathname = rtrim($pathname, '/').'/Default.htm';
        $args[] = 'Default.htm';
    } else {
        // Security: do not return directory node!
        not_found($course->id);
    }
}

// Security: teachers can view all assignments, students only their own.
if ( (count($args) >= 3)
    and (strtolower($args[1]) == 'moddata')
    and (strtolower($args[2]) == 'assignment') ) {

    $lifetime = 0;  // Do not cache assignments, students may reupload them.
    if ( !has_capability('mod/assignment:grade', get_context_instance(CONTEXT_COURSE, $course->id))
      and $args[4] != $USER->id ) {
        print_error('Access not allowed');
    }
}

// Security for eMail.
if ( strtolower($args[3]) == 'email') {
    // Get mail.
    $email = new eMail();
    $email->set_email($args[5]);

    if ( !$email->can_readmail($USER) ) {
        print_error('Access not allowed');
    }
}

// Security: force download of all attachments submitted by students.
if ( (count($args) >= 3 )
    and ( strtolower($args[1]) == 'moddata' )
    and ((strtolower($args[2]) == 'forum')
        or (strtolower($args[2]) == 'assignment')
        or (strtolower($args[2]) == 'data')
        or (strtolower($args[2]) == 'glossary')
        or (strtolower($args[2]) == 'wiki')
        or (strtolower($args[2]) == 'exercise')
        or (strtolower($args[2]) == 'workshop')
        )) {
    $forcedownload  = 1; // Force download of all attachments.
}
if ( $args[0] == 'blog' ) {
    $forcedownload  = 1; // Force download of all attachments.
}

// Security: some protection of hidden resource files.
// Warning: it may break backwards compatibility.
if ((!empty($CFG->preventaccesstohiddenfiles))
    and (count($args) >= 2)
    and (!(strtolower($args[1]) == 'moddata' and strtolower($args[2]) != 'resource'))
    and (!has_capability('moodle/course:viewhiddenactivities', get_context_instance(CONTEXT_COURSE, $course->id)))) {

    $rargs = $args;
    array_shift($rargs);
    $reference = implode('/', $rargs);

    $sql = "SELECT COUNT(r.id) " .
             "FROM {resource} r, " .
                  "{course_modules} cm, " .
                  "{modules} m " .
             "WHERE r.course    = '{$course->id}' " .
               "AND m.name      = 'resource' " .
               "AND cm.module   = m.id " .
               "AND cm.instance = r.id " .
               "AND cm.visible  = 0 " .
               "AND r.type      = 'file' " .
               "AND r.reference = '{$reference}'";
    if ( $DB->count_records_sql($sql) ) {
        error('Access not allowed');
    }
}

// Check that file exists.
if (!file_exists($pathname)) {
    not_found($course->id);
}

// Finally send the file.
session_write_close(); // Unlock session during fileserving.
$filename = $args[count($args) - 1];
send_file($pathname, $filename, $lifetime, $CFG->filteruploadedfiles, false, $forcedownload);

function not_found($courseid) {
    global $CFG;
    header('HTTP/1.0 404 not found');
    print_error('filenotfound', 'error', $CFG->wwwroot.'/course/view.php?id='.$courseid); // This is not displayed on IIS??
}