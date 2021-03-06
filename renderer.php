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
 * The renderer.
 *
 * @package 	email
 * @copyright   2015 Toni Mas <antoni.mas@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * The renderer for the eMail list.
 *
 * @copyright  2015 Toni Mas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_binerbo_renderer extends plugin_renderer_base {
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
    public function showmails($userid, $order = '', $page=0, $perpage=10, $options=null, $search=false, $mailssearch=null) {
        global $CFG, $COURSE, $SESSION, $DB, $OUTPUT;

        // CONTRIB-690.
        if ( !empty( $_POST['perpage'] ) and is_numeric($_POST['perpage']) ) {
            $SESSION->binerbo_mailsperpage = $_POST['perpage'];
        } else if ( !isset($SESSION->binerbo_mailsperpage) or empty($SESSION->binerbo_mailsperpage) ) {
            $SESSION->binerbo_mailsperpage = 10; // Default value.
        }

        // Get actual course.
        if ( !$course = $DB->get_record('course', array('id' => $COURSE->id)) ) {
            print_error('invalidcourseid', 'block_binerbo');
        }

        if ($course->id == SITEID) {
            $coursecontext = context_system::instance(); // SYSTEM context.
        } else {
            $coursecontext = context_course::instance($course->id); // Course context.
        }

        $url = '';
        // Build url part options.
        if ($options) {
            $url = binerbo_build_url($options);
        }

        // Print all mails in this HTML file.

        // Should use this variable so that we don't break stuff every time a variable is added or changed.
        $baseurl = $CFG->wwwroot . '/blocks/binerbo/email/index.php?' . $url .
            '&amp;page=' . $page . '&amp;perpage=' . $perpage;

        // Print init form from send data.
        echo '<form id="sendmail" action="' . $CFG->wwwroot .
            '/blocks/binerbo/email/index.php?id=' . $course->id .
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
                $folder = \block_binerbo\label::get($options->folderid);
            } else {
                // Solve problem with select an x mails per page for maintein in this folder.
                if ( isset($options->folderoldid) && $options->folderoldid != 0 ) {
                    $options->folderid = $options->folderoldid;
                    $folder = \block_binerbo\label::get($options->folderid);
                }
            }
        }

        // If actual folder is inbox type, ... change tag showing.
        if ( $folder ) {
            if ( ( \block_binerbo\label::is_type($folder, EMAIL_INBOX) ) ) {
                $strto = get_string('from', 'block_binerbo');
            } else {
                $strto = get_string('to', 'block_binerbo');
            }
        } else {
            $strto = get_string('from', 'block_binerbo');
        }

        if ( $course->id == SITEID ) {
            $tableheaders = array('',
                                '',
                                get_string('course'),
                                get_string('subject', 'block_binerbo'),
                                $strto,
                                get_string('date', 'block_binerbo')
            );
        } else {
            $tableheaders = array('',
                                 '',
                                 get_string('subject', 'block_binerbo'),
                                 $strto,
                                 get_string('date', 'block_binerbo')
            );
        }

        $table = new html_table('list-mails-' . $userid);

        $table->head = $tableheaders;

        $table->align = array(null, 'center');
        $table->attributes['class'] = 'emailtable';

        // When no search.
        if ( !$search ) {
            // Get mails.
            $mails = \block_binerbo\email::get_user_mails($userid, $course->id, null, '', '', $options);
        } else {
            $mails = $mailssearch;
        }

        // Define long page.
        $emailcount = count($mails);
        echo $OUTPUT->paging_bar($emailcount, $page, $perpage, $baseurl);

        // Now, re-getting emails, apply pagesize (limit).
        if ( !$search) {
            // Get mails.
            $mails = \block_binerbo\email::get_user_mails($userid,
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

        $mailsids = binerbo_get_ids($mails);

        // Print all rows.
        foreach ($mails as $mail) {
            $attribute = array();
            $email = new \block_binerbo\email();
            $email->set_email($mail);

            if ( $folder ) {
                if ( \block_binerbo\label::is_type($folder, EMAIL_SENDBOX) ) {
                    $struser = $email->get_users_send(has_capability('moodle/site:viewfullnames', $coursecontext));
                } else if ( \block_binerbo\label::is_type($folder, EMAIL_INBOX) ) {

                    $struser = $email->get_fullname_writer(has_capability('moodle/site:viewfullnames', $coursecontext));
                    if ( !$email->is_readed($userid, $mail->course) ) {
                        $attribute = array( 'bgcolor' => $CFG->binerbo_table_field_color);
                    }
                } else if ( \block_binerbo\label::is_type($folder, EMAIL_TRASH) ) {
                    $struser = $email->get_fullname_writer(has_capability('moodle/site:viewfullnames', $coursecontext));

                    if ( !$email->is_readed($userid, $mail->course) ) {
                        $attribute = array( 'bgcolor' => $CFG->binerbo_table_field_color);
                    }
                } else if ( \block_binerbo\label::is_type($folder, EMAIL_DRAFT) ) {

                    $struser = $email->get_users_send(has_capability('moodle/site:viewfullnames', $coursecontext));

                    if ( !$email->is_readed($userid, $mail->course) ) {
                        $attribute = array( 'bgcolor' => $CFG->binerbo_table_field_color);
                    }
                } else {
                    $struser = $email->get_fullname_writer(has_capability('moodle/site:viewfullnames', $coursecontext));

                    if ( !$email->is_readed($userid, $mail->course) ) {
                        $attribute = array( 'bgcolor' => $CFG->binerbo_table_field_color);
                    }
                }
            } else {
                // Format user's.
                $struser = $email->get_fullname_writer(has_capability('moodle/site:viewfullnames', $coursecontext));
                if ( !$email->is_readed($userid, $mail->course) ) {
                    $attribute = array( 'bgcolor' => $CFG->binerbo_table_field_color);
                }
            }

            if ( !isset($options->folderid) ) {
                $options->folderid = 0;
            }

            if ( \block_binerbo\label::is_type($folder, EMAIL_DRAFT) ) {
                $urltosent = '<a href="' . $CFG->wwwroot .
                    '/blocks/binerbo/email/sendmail.php?id=' . $mail->id .
                    '&amp;action=' . EMAIL_EDITDRAFT . '&amp;course=' . $course->id .
                    '">' . $mail->subject . '</a>';
            } else {
                if ( $course->id == SITEID ) {
                    $urltosent = '<a href="' . $CFG->wwwroot .
                        '/blocks/binerbo/email/view.php?id=' . $mail->id .
                        '&amp;action=' . EMAIL_VIEWMAIL . '&amp;course=' . $mail->course .
                        '&amp;folderid=' . $options->folderid . '&amp;mails=' . $mailsids . '">' .
                        $mail->subject . '</a>';
                } else {
                    $urltosent = '<a href="' . $CFG->wwwroot .
                        '/blocks/binerbo/email/view.php?id=' . $mail->id .
                        '&amp;action=' . EMAIL_VIEWMAIL . '&amp;course=' .$course->id .
                        '&amp;folderid=' . $options->folderid . '&amp;mails=' . $mailsids . '">' .
                        $mail->subject . '</a>';
                }
            }

            $attachment = '';
            if ( $email->has_attachments() ) {
                $attachment = '<img src="'.$CFG->wwwroot.'/blocks/binerbo/email/images/clip.gif" alt="attachment" /> ';
            }

            $newemailicon = '<img src="'.$CFG->wwwroot.'/blocks/binerbo/email/images/icon.gif" alt="this email was read" /> ';
            if ( $email->is_readed($userid, $mail->course) ) {
                $newemailicon = '<img src="'.$CFG->wwwroot.'/blocks/binerbo/email/images/openicon.gif" alt="new email" /> ';
            }

            // Display diferent color if mail is reply or reply all.
            $extraimginfo = '';
            if ( $email->is_answered($userid, $course->id) ) {
                // Color td.
                unset($attribute);
                $attribute = array('bgcolor' => $CFG->binerbo_answered_color);

                // Adding info img.
                $extraimginfo = '<img src="'.$CFG->wwwroot.'/blocks/binerbo/email/images/answered.gif" alt="" /> ';

            }

            if ( !$coursemail = $DB->get_record('course', array('id' => $mail->course)) ) {
                print_error('invalidcourseid', 'block_binerbo');
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

        echo html_writer::table($table);

        // Print select action, if have mails.
        if ( $mails ) {
            binerbo_print_select_options($options, $SESSION->binerbo_mailsperpage);
        }

        // End form.
        echo '</form>';

        return true;
    }

    /**
     * This function return, if corresponding, preferences button.
     *
     * @uses $CFG
     * @param int $courseid Course Id.
     * @return string. Preferences button if corresponding.
     * @todo Finish documenting this function.
     */
    public function binerbo_get_preferences_button($courseid) {
        global $CFG, $OUTPUT, $PAGE;

        // Security.
        if ( empty($courseid) ) {
            $courseid = SITEID;
        }

        if ( empty($CFG->binerbo_trackbymail) and empty($CFG->binerbo_marriedfolders2courses) ) {
            return '';
        } else {
            $form = new html_form();
            $form->url = new moodle_url($link, $options);
            $form->button = new html_button();
            $form->button->text = get_string('preferences', 'block_binerbo');
            $form->button->title = get_string('preferences', 'block_binerbo');
            $form->method = 'post';
            return $OUTPUT->action_button($form);
        }
    }
}