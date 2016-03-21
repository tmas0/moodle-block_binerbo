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
 * eMail list block.
 *
 * @package     email
 * @copyright   2016 Toni Mas <antoni.mas@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot .'/blocks/binerbo/lib.php');

/**
 * This block shows information about user email's
 *
 * @package     email
 * @copyright   2016 Toni Mas <antoni.mas@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_binerbo extends block_list {

    /**
     * Set the initial properties for the block.
     */
    public function init() {
        $this->title = get_string('binerbo', 'block_binerbo');
    }

    /**
     * All multiple instances of this block.
     *
     * @return bool Returns false
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Generates the content of the block and returns it.
     *
     * If the content has already been generated then the previously generated content is returned.
     *
     * @return stdClass
     */
    public function get_content() {
        global $USER, $CFG, $COURSE, $DB, $OUTPUT;

        // Get course id.
        if ( !empty($COURSE) ) {
            $this->courseid = $COURSE->id;
        }

        // If block have content, skip.
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();

        // Get context.
        $context = context_block::instance($this->instance->id);

        $emailicon = $OUTPUT->pix_icon('i/email', get_string('course'));
        $composeicon = $OUTPUT->pix_icon('i/edit', '');

        // Only show all course in principal course, others, show it.
        if ( $this->page->course->id == 1 ) {
            // Get the courses of the user.
            $mycourses = enrol_get_my_courses();
            $this->content->footer = '<br /><a href="' . $CFG->wwwroot .
                '/blocks/binerbo/dashboard.php">' . get_string('view_all', 'block_binerbo') . ' ' . $emailicon . '</a>';
        } else {

            if ( !empty($CFG->mymoodleredirect) and $COURSE->id == 1 ) {
                // Get the courses of the user.
                $mycourses = enrol_get_my_courses();
                $this->content->footer = '<br /><a href="' . $CFG->wwwroot .
                    '/blocks/binerbo/dashboard.php">' . get_string('view_all', 'block_binerbo') . ' ' . $emailicon . '</a>';
            } else {
                // Get this course.
                $course = $DB->get_record('course', array('id' => $this->page->course->id));
                $mycourses[] = $course;
                $this->content->footer = '<br /><a href="' . $CFG->wwwroot .
                    '/blocks/binerbo/dashboard.php?id=' . $course->id . '">' .
                    get_string('view_inbox', 'block_binerbo') . ' ' . $emailicon.'</a>';
                $this->content->footer .= '<br /><a href="' . $CFG->wwwroot .
                    '/blocks/binerbo/message.php?course=' . $course->id .
                    '&folderid=0&filterid=0&folderoldid=0&action=newmail">' .
                    get_string('compose', 'block_binerbo') . ' ' . $composeicon . '</a>';
            }
        }

        // Count my courses.
        $countmycourses = count($mycourses);

        // Configure item and icon for this account.
        $icon = '<img src="' . $CFG->wwwroot .
            '/blocks/binerbo/email/images/openicon.gif" height="16" width="16" alt="' .
            get_string("course") . '" />';

        $maxcourses = get_config('binerbo', 'max_number_courses');
        $number = 0;
        foreach ($mycourses as $mycourse) {
            ++$number; // Increment for first course.

            if ( $number > $maxcourses && !empty($maxcourses) ) {
                continue;
            }
            // Get the number of unread mails.
            $numberunreadmails = binerbo_count_unreaded_mails($USER->id, $mycourse->id);

            // Only show if has unreaded mails.
            if ( $numberunreadmails > 0 ) {

                $unreadmails = '<b>('.$numberunreadmails.')</b>';
                $this->content->items[] = '<a href="' . $CFG->wwwroot .
                    '/blocks/binerbo/dashboard.php?id=' . $mycourse->id . '">' . $mycourse->fullname .
                    ' ' . $unreadmails .'</a>';
                $this->content->icons[] = $icon;
            }
        }

        if ( count( $this->content->items ) == 0 ) {
            $this->content->items[] = '<div align="center">' . get_string('emptymailbox', 'block_binerbo') . '</div>';
        }

        return $this->content;
    }

    /**
     * Set the applicable formats for this block to all.
     *
     * @return array
     */
    public function applicable_formats() {
        return array('all' => true, 'mod' => false, 'tag' => false);
    }

    /**
     * Returns true if this block has global config.
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }

    /**
     * Function to be run periodically according to the moodle cron
     * This function searches for things that need to be done, such
     * as sending out mail, toggling flags etc ...
     *
     * @return boolean
     */
    public function cron() {
        $trackbymail = get_config('binerbo', 'trackbymail');

        // If no isset trackbymail, return cron.
        if ( !isset($trackbymail) ) {
            return true;
        }

        // If NOT enabled.
        if ( $trackbymail == 0 ) {
            return true;
        }

        // Get actualtime.
        $now = time();

        // Get record for mail list.
        if ( $block = $DB->get_record('block', array('name' => 'binerbo')) ) {

            if ( $now > $block->lastcron ) {

                $unreadmails = new stdClass();

                // Get users who have unread mails.
                $from = "{user} u,
                         {binerbo_sent} s,
                         {binerbo_mail} m";

                $where = " WHERE u.id = s.userid
                                AND s.mailid = m.id
                                AND m.timecreated > $block->lastcron
                                AND s.readed = 0
                                AND s.sended = 1";

                // If exist any users.
                if ( $users = $DB->get_records_sql('SELECT u.* FROM ' . $from . $where) ) {

                    // For each user ... get this unread mails, and send alert mail.
                    foreach ($users as $user) {

                        $mails = new stdClass();

                        // Preferences! Can send mail?
                        // 1.- Site allow send trackbymail.
                        // 1.1.- User doesn't define this settings -> Send mail.
                        // 1.2.- User allow trackbymail -> Send mail.
                        // 1.3.- User denied trackbymail -> Don't send mail.

                        // User can definied this preferences?
                        if ( $preferences = $DB->get_record('binerbo_preference', array('userid' => $user->id)) ) {
                            if ( $preferences->trackbymail == 0 ) {
                                continue;
                            }
                        }

                        // Get this unread mails.
                        $sql = "SELECT *
                                    FROM {binerbo_sent}
                                    WHERE readed = 0
                                        AND sended = 1
                                        AND userid = $user->id
                                    ORDER BY course";
                        $mails = $DB->get_records_sql($sql);
                        if ( $mails ) {

                            $bodyhtml = '<head>';
                            foreach ($CFG->stylesheets as $stylesheet) {
                                $bodyhtml .= '<link rel="stylesheet" type="text/css" href="'.$stylesheet.'" />'."\n";
                            }

                            $bodyhtml .= '</head>';
                            $bodyhtml .= "\n<body id=\"email\">\n\n";

                            $bodyhtml .= '<div class="content">'.get_string('listmails', 'block_binerbo').": </div>\n\n";
                            $body = get_string('listmails', 'block_binerbo')  .": \n\n";

                            $bodyhtml .= '<table border="0" cellpadding="3" cellspacing="0">';
                            $bodyhtml .= '<th class="header">' . get_string('course') . '</th>';
                            $bodyhtml .= '<th class="header">' . get_string('subject', 'block_binerbo') . '</th>';
                            $bodyhtml .= '<th class="header">' . get_string('from', 'block_binerbo') . '</th>';
                            $bodyhtml .= '<th class="header">' . get_string('date', 'block_binerbo') . '</th>';

                            // Prepare messagetext.
                            foreach ($mails as $mail) {

                                // Get folder.
                                $folder = \block_binerbo\label::get_root($mail->userid, EMAIL_SENDBOX);
                                if ( ! \block_binerbo\label::is_type($folder, EMAIL_SENDBOX) ) {
                                    continue;
                                }

                                if ( isset($mail->mailid) ) {
                                    $message = $DB->get_record('binerbo_mail', array('id' => $mail->mailid));
                                    $mailcourse = $DB->get_record('course', array('id' => $mail->course));

                                    $body .= "---------------------------------------------------------------------\n";
                                    $body .= get_string('course') . ": $mailcourse->fullname \n";
                                    $body .= get_string('subject', 'block_binerbo') . ": $message->subject \n";
                                    $body .= get_string('from', 'block_binerbo') . ": " . fullname(binerbo_get_user($message->id));
                                    $body .= " - ".userdate($message->timecreated) . "\n";
                                    $body .= "---------------------------------------------------------------------\n\n";

                                    $bodyhtml .= '<tr  class="r0">';
                                    $bodyhtml .= '<td class="cell c0">' . $mailcourse->fullname . '</td>';
                                    $bodyhtml .= '<td class="cell c0">' . $message->subject . '</td>';
                                    $bodyhtml .= '<td class="cell c0">' . fullname(binerbo_get_user($message->id)) . '</td>';
                                    $bodyhtml .= '<td class="cell c0">' . userdate($message->timecreated) . '</td>';
                                    $bodyhtml .= '</tr>';
                                }
                            }

                            $bodyhtml .= '</table>';
                            $bodyhtml .= '</body>';

                            $body .= "\n\n\n\n";

                            binerbo_to_user($user, get_string('emailalert', 'block_binerbo'),
                                            get_string('emailalert', 'block_binerbo') .
                                            ': ' . get_string('newmails', 'block_binerbo'), $body, $bodyhtml);
                        }
                    }
                }

            }

            return true;
        } else {
            mtrace('FATAL ERROR: I couldn\'t read eMail list block');
            return false;
        }
    }

    /**
     * Returns the aria role attribute that best describes this block.
     *
     * @return string
     */
    public function get_aria_role() {
        return 'application';
    }
}