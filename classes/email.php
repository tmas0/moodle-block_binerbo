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
 * Parent class for eMail.
 *
 * @package     email
 * @copyright   2015 Toni Mas <antoni.mas@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_email_list;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/email_base.php');

class email extends \block_email_list\email_base {

    /**
     * Mark if eMail has reply, reply all or forward
     * @var string $type Reply, reply all or forward
     */
    public $type    = null;

    /**
     * Mark if eMail is save in draft
     * @var boolean $draft Is draft?
     */
    public $draft = false;


    /**
     * Old eMail Id when this send by reply or reply all message.
     */
    public $oldmailid = null;

    /**
     * Old attachments if mail have forward or draft.
     */
    public $oldattachments = array();

    /**
     * Constructor.
     */
    public function __construct() {
        // Nothing to do.
    }

    /**
     * This funcion return formated fullname of user. ALLWAYS return firstname lastname.
     */
    public function fullname($user, $override=false) {

        // Drop all semicolon apears. (Js errors when select contacts).
        return str_replace(',', '', fullname($user, $override));
    }

    /**
     * Function for define new eMail, with this data.
     *
     * @param object or int $email eMail
     */
    public function set_email($email) {

        if ( ! empty($email) ) {
            if (is_object($email) ) {
                $this->id = $email->id;
                $this->subject = $email->subject;
                $this->body = $email->body;
                $this->timecreated = $email->timecreated;
                // Get writer.
                if ( ! isset($email->writer) ) {
                    $this->userid = $email->userid;
                } else {
                    $this->userid = $email->writer;
                }
                $this->course = $email->course;
            } else if (is_int($email) ) {
                if ( $mail = $DB->get_record('email_mail', array('id' => $email)) ) {
                    $this->id = $mail->id;
                    $this->subject = $mail->subject;
                    $this->body = $mail->body;
                    $this->timecreated = $mail->timecreated;
                    $this->userid = $mail->userid;
                    $this->course = $mail->course;
                }
            }
        }

    }

    /**
     * Set Writer.
     *
     * @uses $USER
     * @param int $userid Writer
     */
    public function set_writer($userid) {
        global $USER;

        // Security issues.
        if ( isset( $USER->id ) ) {
            if ( $USER->id != $userid or !$userid) {
                // Display error.
                print_error ( 'incorrectuserid', 'block_email_list' );
                return false;
            }
        }

        // Assign userid - FIXME: If user don't exist¿?
        $this->userid = $userid;
        return true;
    }

    /**
     * Get Writer.
     */
    public function get_writer() {
        return $this->userid;
    }

    /**
     * Get full name of writer.
     */
    public function get_fullname_writer($override=false) {
        global $DB;

        if ( $user = $DB->get_record('user', array('id' => $this->userid)) ) {
            return $this->fullname($user, $override);
        } else {
            return ''; // User not found.
        }
    }

    /**
     * Get format string of all fullnames users send.
     *
     * @return string Contain user who writed mails
     * @todo Finish documenting this function
     */
    public function get_users_send($type='', $override=false) {
        global $DB;

        // Get send's.
        if ( isset($this->id) ) {
            if ( $type === 'to' or $type === 'cc' or $type === 'bcc' ) {
                $sendbox = $DB->get_records('email_send', array('mailid' => $this->id, 'type' => $type));
                if ( !$sendbox ) {
                    return false;
                }
            } else {
                $sendbox = $DB->get_records('email_send', array('mailid' => $this->id));
                if ( !$sendbox ) {
                    return false;
                }
            }

            $users = '';

            foreach ($sendbox as $sendmail) {
                // Get user.
                if ( $user = $DB->get_record('user', array('id' => $sendmail->userid)) ) {
                    $users .= $this->fullname($user, $override) .', ';
                }
            }

            // Delete 2 last characters.
            $count = strlen($users);
            $users = substr($users, 0, $count - 2);

            return $users;
        } else {
            return get_string('neverusers', 'block_email_list');
        }
    }

    /**
     * This function show if the user can read this mail.
     *
     * @param object $user User.
     * @return boolean True or false if the user can read this mail.
     * @todo Finish documenting this function
     */
    public function can_readmail($user) {
        global $DB;

        // Writer.
        if ( $this->userid == $user->id ) {
            return true;
        }

        $senders = $DB->get_records('email_send', array('mailid' => $this->id));

        if ( $senders ) {
            foreach ($senders as $sender) {
                if ( $sender->userid == $user->id ) {
                    return true;
                }
            }
        }

        return false;

    }

    /**
     * This functions return if mail is readed or not readed.
     *
     * @param int $userid User ID
     * @param int $courseid Course ID
     * @return boolean Success/Fail
     * @todo Finish documenting this function
     **/
    public function is_readed($userid, $courseid) {
        global $DB;

        // Get mail.
        $send = $DB->get_record('email_send', array('mailid' => $this->id,
            'userid' => $userid,
            'course' => $courseid);
        if ( !$send ) {
            return false;
        }

        // Return value.
        return $send->readed;
    }

    /**
     * This function return true or false if mail has answered.
     *
     * @param int $userid User Id.
     * @param int $courseid Course ID.
     * @return boolean Success/Fail
     * @todo Finish documenting this function
     **/
    public function is_answered($userid, $courseid) {
        global $DB;

        $params = array('mailid' => $this->id, 'userid' => $userid, 'course' => $courseid);
        $send = $DB->get_record('email_send', $params);
        if ( !$send ) {
            return false; // User Id is the writer (only apears in email_mail).
        }

        return $send->answered;
    }

    /**
     * This functions return if mail has attachments.
     *
     * @return boolean Success/Fail
     * @todo Finish documenting this function
     **/
    public function has_attachments() {

        if ( isset($this->id) ) {
            if (! $this->get_file_area_name()) {
                return false;
            }
            // Get attachments mail path.
            $basedir = $this->get_file_area();

            // Get files of mail.
            if ($files = get_directory_list($basedir)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * This functions prints attachments of mail.
     *
     * @uses $CFG
     * @param boolean $attachmentformat Print attachment formatting box (Optional)
     * @return boolean Success/Fail
     * @todo Finish documenting this function
     **/
    public function _get_format_attachments($attachmentformat=true) {

        global $CFG;

        // Necessary library for this function.
        include_once($CFG->dirroot.'/lib/filelib.php');

        // Get strings.
        $strattachment  = get_string('attachment', 'block_email_list');
        $strattachments = get_string('attachments', 'block_email_list');

        $html = '';

        // Get attachments mail path.
        $basedir = $this->get_file_area();

        // Get files of mail.
        if ($files = get_directory_list($basedir)) {

            if ( $attachmentformat ) {
                $html .= '<br /><br /><br />';
                $html .= '<hr width="80%" />';

                $result = count($files) == 1 ? $strattachment : $strattachments;

                $html .= '<b>'. $result. '</b>';

                $html .= '<br />';
                $html .= '<br />';
            }
            // Process all attachments.
            foreach ($files as $file) {
                // Get icon.
                $icon = mimeinfo('icon', $file);
                $html .= '<img border="0" src="'. $CFG->pixpath.'/f/'. $icon.'" alt="icon" height="16" width="16" />';

                $html .= '&#160;';

                // Get path of file.
                $filearea = $this->get_file_area_name();

                if ($CFG->slasharguments) {
                    $ffurl = "blocks/email_list/email/file.php/$filearea/$file";
                } else {
                    $ffurl = "blocks/email_list/email/file.php?file=/$filearea/$file";
                }

                $html .= '<a href="'.$CFG->wwwroot.'/'.$ffurl. '" target="blank">'. $file .'</a>';
                $html .= '<br />';
            }

            if ( $attachmentformat ) {
                $html .= '<br />';
            }
        }

        return $html;
    }

    /**
     * This function set old attachments.
     *
     * @param array $oldattachments Old attachments
     */
    public function set_oldattachments($oldattachments) {

        if ( is_array( $oldattachments) ) {
            $this->oldattachments = $oldattachments;
            return true;
        } else if ( is_string($oldattachments) ) {
            $this->oldattachments = array($oldattachments);
            return true;
        }

        return false;
    }

    /**
     * Set Course.
     *
     * @uses $COURSE
     * @param int $courseid Course Id
     */
    public function set_course($courseid) {
        global $COURSE;

        if ( empty( $courseid ) and isset($COURSE->id) ) {
            $this->course = $COURSE->id;
        } else if ( empty( $courseid ) ) {
            print_error('specifycourseid', 'block_email_list');
        }
        $this->course = $courseid;
        return true;
    }

    /**
     * Set type of mail.
     */
    public function set_type($type) {

        // Security control.
        if ( $type === EMAIL_REPLY or $type === EMAIL_REPLYALL or $type === EMAIL_FORWARD ) {
            $this->type = $type;
        }
    }

    /**
     * Set old mail (if exit).
     *
     * @param int $oldmailid Old mail id (Forward)
     */
    public function set_oldmailid($oldmailid) {
        // Forbidden negative id's and zeros.
        if ( $oldmailid > 0 AND $this->type != EMAIL_FORWARD ) {
            $this->oldmailid = $oldmailid;
        }
    }

    /**
     * Set mail ID (if exist).
     *
     * @param int $id Mail id (Draft)
     */
    public function set_mailid($id) {
        // Forbidden negative id's and zeros.
        if ( $id > 0 ) {
            $this->id = $id;
        }
    }

    /**
     * This function send this eMail to respective users.
     * Active the corresponding flag to user sent.
     * Add new mail in table.
     * Add all references in send table.
     *
     */
    public function send() {
        global $COURSE, $USER, $DB;

        // Mark answered old mail.
        if ( $this->type === EMAIL_REPLY or $this->type === EMAIL_REPLYALL ) {
            $this->mark2answered($USER->id, $COURSE->id, $this->oldmailid, true);
        }

        if (! $this->id or $this->type === EMAIL_FORWARD or $this->type === EMAIL_REPLY or $this->type === EMAIL_REPLYALL) {
            $this->timecreated = time();
            // Insert record.
            $this->insert_mail_record();
        } else {
            // Update record.
            $this->update_mail_record();
        }

        if (! $this->reference_mail_folder($this->userid, EMAIL_SENDBOX) ) {
            return false;
        }

        // If mail has saved in draft, delete this reference.
        if ( $folderdraft = \block_email_list\label::get_root($this->userid, EMAIL_DRAFT) ) {
            if ($foldermail = email_get_reference2foldermail($this->id, $folderdraft->id) ) {
                if (! $DB->delete_records('email_foldermail', 'id', $foldermail->id)) {
                    print_error( 'failremovingdraft', 'block_email_list');
                }
            }
        }

        // Add attachments.
        if ($this->attachments or $this->oldattachments) {
            if (! $this->add_attachments() ) {
                notify('Fail uploading attachments');
            }
        }

        // If mail already exist ... (in draft).
        if ( $this->id ) {
            // Drop all records, and insert all again.
            if (! $DB->delete_records('email_send', 'mailid', $this->id)) {
                return false;
            }
        }

        // Prepare send mail.
        $send = new stdClass();
        $send->userid   = $this->userid;
        $send->course   = $this->course;
        $send->mailid   = $this->id;
        $send->readed   = 0;
        $send->sended   = 1;
        $send->answered = 0;

        if (! empty($this->to) ) {

            // Insert mail into send table, for all senders users.
            foreach ($this->to as $userid) {

                // In this moment, create if no exist this root folders.
                \blocks_email_list\label::create_parents($userid);

                $send->userid = $userid;

                $send->type      = 'to';

                if (! $DB->insert_record('email_send', $send)) {
                    print_error('failinsertsendrecord', 'block_email_list');
                    return false;
                }

                // Add reference to corresponding user.
                if (! $this->reference_mail_folder($userid, EMAIL_INBOX) ) {
                    return false;
                }
            }
        }

        if ( !empty($this->cc) ) {

            // Insert mail into send table, for all senders users.
            foreach ($this->cc as $userid) {

                // In this moment, create if no exist this root folders.
                \blocks_email_list\label::create_parents($userid);

                $send->userid = $userid;

                $send->type      = 'cc';

                if (! $DB->insert_record('email_send', $send)) {
                    print_error('failinsertsendrecord', 'block_email_list');
                    return false;
                }

                // Add reference to corresponding user.
                if (! $this->reference_mail_folder($userid, EMAIL_INBOX) ) {
                    return false;
                }
            }
        }

        if (! empty($this->bcc) ) {

            // Insert mail into send table, for all senders users.
            foreach ($this->bcc as $userid) {

                // In this moment, create if no exist this root folders.
                \blocks_email_list\label::create_parents($userid);

                $send->userid = $userid;

                $send->type      = 'bcc';

                if ( !$DB->insert_record('email_send', $send)) {
                    print_error('failinsertsendrecord', 'block_email_list');
                    return false;
                }

                // Add reference to corresponding user.
                if ( !$this->reference_mail_folder($userid, EMAIL_INBOX) ) {
                    return false;
                }
            }
        }

        add_to_log($this->course, 'email', "add mail", 'sendmail.php', "$this->subject", 0, $this->userid);

        return $this->id;
    }

    /**
     * This functions add mail in user draft folder.
     * Add new mail in table.
     * Add all references in table send.
     *
     * @param int $mailid Old mail ID
     * @return boolean Success/Fail
     * @todo Finish documenting this function
     **/
    public function save($mailid=null) {
        global $DB;

        $this->timecreated = time();

        if ( !$mailid ) {

            $this->insert_mail_record();

            $writer = $this->userid;

            // Prepare send mail.
            $send = new stdClass();
            $send->userid = $this->userid;
            $send->course = $this->course;
            $send->mailid = $this->id;
            $send->readed = 0;
            $send->sended = 0; // Important.
            $send->answered = 0;

            if (! empty($this->to) ) {

                // Insert mail into send table, for all senders users.
                foreach ($this->to as $userid) {

                    // In this moment, create if no exist this root folders.
                    \blocks_email_list\label::create_parents($userid);

                    $send->userid = $userid;

                    $send->type      = 'to';

                    if ( !$DB->insert_record('email_send', $send)) {
                        print_error('failinsertsendrecord', 'block_email_list');
                        return false;
                    }
                }
            }

            if ( !empty($this->cc) ) {

                // Insert mail into send table, for all senders users.
                foreach ($this->cc as $userid) {

                    // In this moment, create if no exist this root folders.
                    \blocks_email_list\label::create_parents($userid);

                    $send->userid = $userid;

                    $send->type      = 'cc';

                    if (! $DB->insert_record('email_send', $send)) {
                        print_error('failinsertsendrecord', 'block_email_list');
                        return false;
                    }
                }
            }

            if (! empty($this->bcc) ) {

                // Insert mail into send table, for all senders users.
                foreach ($this->bcc as $userid) {

                    // In this moment, create if no exist this root folders.
                    \blocks_email_list\label::create_parents($userid);

                    $send->userid = $userid;

                    $send->type      = 'bcc';

                    if ( !$DB->insert_record('email_send', $send)) {
                        print_error('failinsertsendrecord', 'block_email_list');
                        return false;
                    }
                }
            }

            if ( !$this->reference_mail_folder($this->userid, EMAIL_DRAFT) ) {
                print_error('failinsertrecord', 'block_email_list');
            }

        } else {
            $this->oldmailid = $mailid;

            $this->update_mail_record();

            // Drop all records, and insert all again.
            if ( $DB->delete_records('email_send', 'mailid', $mailid)) {

                // Prepare send mail.
                $send = new stdClass();
                $send->userid = $this->userid;
                $send->course = $this->course;
                $send->mailid = $mailid;
                $send->readed = 0;
                $send->sended = 0; // Important.
                $send->answered = 0;

                // Now, I must verify the users who sended mail, in case they have changed.
                if (! empty($this->to) ) {

                    // Insert mail into send table, for all senders users.
                    foreach ($this->to as $userid) {

                        $send->userid = $userid;

                        $send->type      = 'to';

                        if ( !$DB->insert_record('email_send', $send)) {
                            print_error('failinsertsendrecord', 'block_email_list');
                            return false;
                        }
                    }
                }

                if ( !empty($this->cc) ) {

                    // Insert mail into send table, for all senders users.
                    foreach ($this->cc as $userid) {

                        $send->userid = $userid;

                        $send->type      = 'cc';

                        if ( !$DB->insert_record('email_send', $send)) {
                            print_error('failinsertsendrecord', 'block_email_list');
                            return false;
                        }
                    }
                }

                if ( !empty($this->bcc) ) {

                    // Insert mail into send table, for all senders users.
                    foreach ($this->bcc as $userid) {

                        $send->userid = $userid;

                        $send->type      = 'bcc';

                        if (! $DB->insert_record('email_send', $send)) {
                            print_error('failinsertsendrecord', 'block_email_list');
                            return false;
                        }
                    }
                }
            }
        }

        // Add attachments.
        if ($this->attachments or $this->oldattachments ) {
            if (! $this->add_attachments() ) {
                notify('Fail uploading attachments');
            }
        }

        add_to_log($this->course, 'email', "add mail in draft", 'sendmail.php', "$this->subject", 0, $this->userid);

        return $this->id;
    }

    /**
     * This function remove eMail, if this does in TRASH folder remove of BBDD else move to TRASH folder.
     *
     * @param int $userid User Id
     * @param int $courseid Course Id
     * @param int $folderid Folder Id
     * @param boolean $silent Show or not show messages
     * @return boolean Success/Fail
     * @todo Finish documenting this function
     */
    public function remove($userid, $courseid, $folderid, $silent=false) {
        global $DB;
        // First, show if folder remove or not.

        $deletemails = false;
        $success = true;

        $type = \block_email_list\label::is_type(
            get_record('email_folder', array('id' => $folderid)),
            EMAIL_TRASH
        );
        if ( $type ) {
            $deletemails = true;
        }

        // FIXME: Esborrar els attachments quan no hagi cap referència al mail.

        // If delete definity mails ...
        if ( $deletemails ) {
            // Delete reference mail.
            if (! $DB->delete_records('email_send', 'mailid', $this->id, 'userid', $userid, 'course', $courseid)) {
                    return false;
            }
        } else {
            // Get remove folder user.
            $removefolder = \block_email_list\label::get_root($userid, EMAIL_TRASH);

            // Get actual folder.
            $actualfolder = email_get_reference2foldermail($this->id, $folderid);

            if ($actualfolder) {
                // Move mails to trash.
                if (! email_move2folder($this->id, $actualfolder->id, $removefolder->id) ) {
                    $success = false;
                } else {
                    // Mark the message as read.
                    $DB->set_field('email_send', 'readed', 1, 'mailid', $this->id, 'userid', $userid, 'course', $courseid);
                }
            } else {
                $success = false;
            }
        }

        // Notify.
        if ( $success ) {
            add_to_log($this->course, 'email', 'remove mail', '', 'Remove '.$this->subject, 0, $this->userid);
            if ( ! $silent ) {
                notify( get_string('removeok', 'block_email_list'), 'notifysuccess' );
            }
            return true;
        } else {
            if ( ! $silent ) {
                notify( get_string('removefail', 'block_email_list') );
            }
            return false;
        }
    }

    /**
     * This function display an eMail.
     *
     * @uses $COURSE
     * @param
     */
    public function display($courseid, $folderid, $urlpreviousmail, $urlnextmail, $baseurl, $user, $override=false) {

        global $COURSE;

        // SECURITY. User can read this mail?
        if (! $this->can_readmail($user) ) {
            print_error('dontreadmail', 'block_email_list', $CFG->wwwroot.'/blocks/email_list/email/index.php?'.$baseurl);
        }

        // Now, mark mail as readed.
        if (! $this->mark2read($user->id, $COURSE->id, true) ) {
            print_error('failmarkreaded', 'block_email_list');
        }

        echo $this->get_html($courseid, $folderid, $urlpreviousmail, $urlnextmail, $baseurl, $override);

    }

    /**
     * This function return an HTML code for display this eMail.
     */
    public function get_html($courseid, $folderid, $urlpreviousmail, $urlnextmail, $baseurl, $override=false) {

        global $USER, $CFG, $DB;

        $html = '';

        $html .= '<table class="sitetopic" border="0" cellpadding="5" cellspacing="0" width="100%">';
        $html .= '<tr class="headermail">';
        $html .= '<td style="border-left: 1px solid black; border-top:1px solid black" width="7%" align="center">';

        // Get user picture.
        $user = $DB->get_record('user', array('id' => $this->userid));
        $html .= print_user_picture($this->userid, $this->course, $user->picture, 0, true, false);

        $html .= '</td>';

        $html .= '<td style="border-right: 1px solid black; border-top:1px solid black" align="right" colspan="2">';
        $html .= $this->subject;
        $html .= '</td>';

        $html .= '</tr>';

        $html .= '<tr>';

        $html .= '<td style="border-left: 1px solid black;
                    border-right: 1px solid black; border-top:1px solid black" align="right" colspan="3">';
        $html .= '&nbsp;&nbsp;&nbsp;';
        $html .= '<b> ' . get_string('from', 'block_email_list') . ':</b>&nbsp;';
        $html .= $this->get_fullname_writer($override);

        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr>';

        $userstosendto = $this->get_users_send('to');

        $html .= '<td style="border-right: 1px solid black;" width="80%" align="right" colspan="2">';
        $html .= '&nbsp;&nbsp;&nbsp;';

        if ( $userstosendto != '' ) {
            $html .= '<b> '. get_string('for', 'block_email_list') .':</b>&nbsp;';

            $html .= $this->get_users_send('to');
        }

        $html .= '</td>';

        $html .= '<td style="border-left: 1px solid black;" width="20%">';

        if ( $urlnextmail or $urlpreviousmail ) {
            $html .= "&nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp;";
        }

        if ( $urlpreviousmail ) {
            $html .= '<a href="view.php?'. $urlpreviousmail .'">' . get_string('previous', 'block_email_list') . '</a>';
        }

        if ( $urlnextmail ) {
            if ( $urlpreviousmail ) {
                $html .= '&nbsp;|&nbsp;';
            }
            $html .= '<a href="view.php?' . $urlnextmail .'">' . get_string('next', 'block_email_list') . '</a>';
        }

        $html .= '&nbsp;&nbsp;';
        $html .= '</td>';
        $html .= '</tr>';

        $userstosendcc = $this->get_users_send('cc');
        if ( $userstosendcc != '' ) {
            $html .= '<tr>
                        <td  style="border-left: 1px solid black;
                                border-right: 1px solid black;" align="right" colspan="3">
                                &nbsp;&nbsp;&nbsp;
                            <b> ' . get_string('cc', 'block_email_list') . ':</b>&nbsp;' . $userstosendcc . '
                        </td>
                    </tr>';
        }

        // Drop users sending by bcc if user isn't writer.
        if ( $userstosendbcc = $this->get_users_send('bcc') != '' and $USER->id != $this->userid ) {
            $html .= '<tr>
                        <td  style="border-left: 1px solid black; border-right: 1px solid black;" align="right" colspan="3">
                            &nbsp;&nbsp;&nbsp;
                            <b> ' . get_string('bcc', 'block_email_list') . ':</b>&nbsp;' . $userstosendbcc . '
                        </td>
                    </tr>';
        }

        $html .= '<tr>';

        $html .= '<td style="border-left: thin solid black; border-right: 1px solid black" width="60%" align="right" colspan="3">';
        $html .= '&nbsp;&nbsp;&nbsp;';

        $html .= '<b> '. get_string('date', 'block_email_list') . ':</b>&nbsp;';

        $html .= userdate($this->timecreated);

        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td style="border: 1px solid black" colspan="3" align="right">';
        $html .= '<br />';

        // Options for display body.
        $options = new object();
        $options->filter = true;

        $html .= format_text($this->body, FORMAT_HTML, $options );

        if ( $this->has_attachments() ) {
            $html .= $this->_get_format_attachments();
        }

        $html .= '<br />';
        $html .= '<br />';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr class="messagelinks">';
        $html .= '<td align="right" colspan="3">';

        $html .= '<a href="sendmail.php?' . $baseurl . '&amp;action='
                    . EMAIL_REPLY . '"><b>' . get_string('reply', 'block_email_list') . '</b></a>';
        $html .= ' | ';
        $html .= '<a href="sendmail.php?' . $baseurl . '&amp;action='
                    . EMAIL_REPLYALL . '"><b>' . get_string('replyall', 'block_email_list') . '</b></a>';
        $html .= ' | ';
        $html .= '<a href="sendmail.php?' . $baseurl . '&amp;action='
                    . EMAIL_FORWARD .'"><b>' . get_string('forward', 'block_email_list') . '</b></a>';
        $html .= ' | ';
        $html .= '<a href="index.php?id=' . $courseid . '&amp;mailid=' . $this->id
                    .'&amp;folderid=' . $folderid . '&amp;action=removemail"><b>'
                    . get_string('removemail', 'block_email_list') . '</b></a>';
        $html .= ' | ';

        $icon = '<img src="' . $CFG->wwwroot . '/blocks/email_list/email/images/printer.png" height="16" width="16" alt="'
                    . get_string('print', 'block_email_list') . '" />';

        $html .= email_print_to_popup_window(
                        'link',
                        '/blocks/email_list/email/print.php?courseid=' . $courseid . '&amp;mailids=' . $this->id,
                        '<b>' . get_string('print', 'block_email_list') . '</b>' . print_spacer(1, 3, false, true) . $icon,
                        get_string('print', 'block_email_list'), true);

        $html .= '</td>';

        $html .= '</tr>';
        $html .= '</table>';

        return $html;
    }

    /**
     * This function get mails.
     *
     * @uses $CFG
     * @param int $userid User ID
     * @param int $courseid Course ID
     * @param string $sort Order by ...
     * @param string $limitfrom Limit from
     * @param string $limitnum Limit num
     * @param object $options Options from get
     * @return object Contain all send mails
     * @todo Finish documenting this function
     **/
    public static function get_user_mails($userid, $courseid=null, $sort = null, $limitfrom = '', $limitnum = '', $options = null) {
        global $CFG, $DB;

        // For apply order, I've writting an sql clause.
        $sql = "SELECT m.id, m.userid as writer, m.course, m.subject, m.timecreated, m.body
                                FROM {email_mail} m
                       LEFT JOIN {email_send} s ON m.id = s.mailid ";

        // WHERE principal clause for filter userid.
        $wheresql = " WHERE s.userid = $userid
                        AND s.sended = 1";
        if ( $courseid != SITEID ) {
            // WHERE principal clause for filter courseid.
            $wheresql = " WHERE s.course = $courseid
                        AND s.sended = 1";
        }

        if ( $options ) {
            if ( isset($options->folderid ) ) {
                // Filter by folder?
                if ( $options->folderid != 0 ) {

                    // Get folder.
                    $folder = email_get_folder($options->folderid);

                    if ( \block_email_list\label::is_type($folder, EMAIL_SENDBOX) ) {
                        // ALERT!!!! Modify where sql, because now I've show my inbox ==> email_send.userid = myuserid.
                        $wheresql = " WHERE m.userid = $userid
                                        AND s.sended = 1";
                        if ( $courseid != SITEID) {
                            // WHERE principal clause for filter courseid.
                            $wheresql = " WHERE m.course = $courseid
                                            AND s.sended = 1";
                        }
                    } else if ( \block_email_list\label::is_type($folder, EMAIL_DRAFT) ) {
                        // ALERT!!!! Modify where sql, because now I've show my inbox ==> email_send.userid = myuserid.
                        $wheresql = " WHERE m.userid = $userid
                                        AND s.sended = 0";
                        if ( $courseid != SITEID) {
                            // WHERE principal clause for filter courseid.
                            $wheresql = " WHERE m.course = $courseid
                                            AND s.sended = 0";
                        }
                    }

                    $sql .= " LEFT JOIN {email_labelmail} fm ON m.id = fm.mailid ";
                    $wheresql .= " AND fm.labelid = $options->labelid ";
                    $groupby = " GROUP BY m.id";

                } else {
                    // If label == 0, I've get inbox.
                    // Get label.
                    $label = \block_email_list\label::get_root($userid, EMAIL_INBOX);
                    $sql .= " LEFT JOIN {email_labelmail} fm ON m.id = fm.mailid ";
                    $wheresql .= " AND fm.labelid = $label->id ";
                    $groupby = " GROUP BY m.id";
                }
            } else {
                // If label == 0, I've get inbox.
                // Get folder.
                $label = \block_email_list\label::get_root($userid, EMAIL_INBOX);
                $sql .= " LEFT JOIN {email_labelmail} fm ON m.id = fm.mailid ";
                $wheresql .= " AND fm.labelid = $label->id ";
                $groupby = " GROUP BY m.id";
            }
        } else {
            // If no options, I've get inbox, per default get this label.
            // Get label.
            $label = \block_email_list\label::get_root($userid, EMAIL_INBOX);
            $sql .= " LEFT JOIN {email_labelmail} fm ON m.id = fm.mailid ";
            $wheresql .= " AND fm.labelid = $label->id ";
            $groupby = " GROUP BY m.id";
        }

        if ($sort) {
            $sortsql = ' ORDER BY '.$sort;
        } else {
            $sortsql = ' ORDER BY m.timecreated';
        }

        return $DB->get_records_sql($sql.$wheresql.$groupby.$sortsql, array(), $limitfrom, $limitnum);
    }
}