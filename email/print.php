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
 * Print preview for emails
 *
 * @version $Id: print.php,v 1.4 2008/09/06 09:11:17 tmas Exp $
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot.'/blocks/email_list/email/lib.php');

$courseid = required_param('courseid', PARAM_INT);
$mailids  = required_param('mailids', PARAM_SEQUENCE);
$mailids  = explode(',', $mailids);

if ( !$course = $DB->get_record('course', array('id' => $courseid)) ) {
    print_error('invalidcourseid', 'block_email_list');
}

require_login($course->id, false); // No autologin guest.

// Get renderer.
$renderer = $PAGE->get_renderer('block_email_list');

if ($course->id == SITEID) {
    $context = context_system::instance();   // SYSTEM context.
} else {
    $context = context_course::instance($course->id);   // Course context.
}

if ( !$course->visible and has_capability('moodle/legacy:student', $context, $USER->id, false) ) {
    print_error('courseavailablenot', 'moodle');
}

// Set default page parameters.
$PAGE->set_pagelayout('incourse');
$PAGE->set_context($context);
$PAGE->set_url('/blocks/email_list/email/print.php',
    array(
        'courseid' => $courseid,
        'mailids' => $mailids
    )
);

$PAGE->set_title($course->shortname . ': ' . get_string('printpreview', 'block_email_list');

$options           = new stdClass();
$options->course   = $course->id;
$options->folderid = 0;
$baseurl           = email_build_url($options);

// Print the page header.
echo $renderer->header();

foreach ($mailids as $mailid) {
    $email = new eMail();
    $email->set_email((int) $mailid);
    $email->display($course->id, 0, false, false, $baseurl, $USER, false);
}

echo '<script type="text/javascript">
<!--

var da = (document.all) ? 1 : 0;
var pr = (window.print) ? 1 : 0;
var mac = (navigator.userAgent.indexOf("Mac") != -1);

if (window.addEventListener) {
    window.addEventListener(\'load\', printWin, false);
} else if (window.attachEvent) {
    window.attachEvent(\'onload\', printWin);
} else if (window.onload != null) {
    var oldOnLoad = window.onload;
    window.onload = function(e)
    {
        oldOnLoad(e);
        printWin();
    };
} else {
    window.onload = printWin;
}

function printWin()
{
    if (pr) {
        // NS4+, IE5+
        window.print();
    } else if (!mac) {
        // IE3 and IE4 on PC
        VBprintWin();
    } else {
        // everything else
        handle_error();
    }
}

window.onerror = handle_error;
window.onafterprint = function() {window.close()}

function handle_error()
{
    window.alert(\'El navegador no admite esta opción de impresión. Presione Control/Comando + P para imprimir.\');
    return true;
}

if (!pr && !mac) {
    if (da) {
        // This must be IE4 or greater
        wbvers = "8856F961-340A-11D0-A96B-00C04FD705A2";
    } else {
        // this must be IE3.x
        wbvers = "EAB22AC3-30C1-11CF-A7EB-0000C05BAE0B";
    }

    document.write("<OBJECT ID=\"WB\" WIDTH=\"0\" HEIGHT=\"0\" CLASSID=\"CLSID:");
    document.write(wbvers + "\"> </OBJECT>");
}

// -->
</script>';

echo $renderer->footer();
