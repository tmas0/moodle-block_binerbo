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

namespace block_binerbo;

defined('MOODLE_INTERNAL') || die();

class email_base {
    /**
     * eMail Id.
     * @var int $id eMail id
     */
    public $id      = null;

    /**
     * User Id for the writer of email.
     * @var int $userid Writer.
     */
    public $userid;

    /**
     * Course Id to which it belongs email.
     * @var int $course Course id.
     */
    public $course;

    /**
     * Date of create email.
     * @var int $timecreated TimeStamp
     */
    public $timecreated;

    /**
     * Subject.
     * @var string $subject Subject of email
     */
    public $subject;

    /**
     * Body.
     * @var text $body Body of email
     */
    public $body;

    /**
     * Attachments.
     * @var array $attachments Attachments
     */
    public $attachments = null;

    /**
     * User who mail has send by type to.
     * @var array $to To
     */
    public $to = array();

    /**
     * User who mail has send by type cc.
     * @var array $cc CC
     */
    public $cc = array();

    /**
     * User who mail has send by type bcc.
     * @var array $bcc BCC
     */
    public $bcc = array();

    /**
     * The class constructor.
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Set subject.
     *
     * @param string $subject Subject
     */
    public function set_subject( $subject ) {

        if ( !empty( $subject ) ) {
            // Clean text.
            $this->subject = clean_text($subject);
        } else {
            // Display error.
            print_error(get_string('nosubject', 'block_binerbo'));
        }
    }

    /**
     * Set body.
     *
     * @param text $body Body
     */
    public function set_body( $body='' ) {

        if ( empty($body) ) {
            $this->body = ''; // Define one value.
        } else {
            $context = get_context_instance(CONTEXT_COURSE, $this->course);
            trusttext_after_edit($body, $context);
            $this->body = $body;
        }
    }

    /**
     * This function add attahcments to the mail.
     *
     * @param array $attachments Attachments
     */
    public function set_attachments( $attachments ) {
        if ( !empty( $attachments) ) {
            $this->attachments = $attachments;
            return true;
        }
        return false;
    }

    /**
     * Set users send type to.
     *
     * @param array To
     */
    public function set_sendusersbyto( $to=array() ) {

        if ( is_array($to) ) {
            $this->to = $to;
        } else {
            // In all other case .. mark as empty.
            $this->to = array();
        }
    }

    /**
     * Set users send type cc.
     *
     * @param array CC
     */
    public function set_sendusersbycc( $cc=array() ) {

        if ( is_array($cc) ) {
            $this->cc = $cc;
        } else {
            // In all other case .. mark as empty.
            $this->cc = array();
        }
    }

    /**
     * Set users send type bcc.
     *
     * @param array BCC
     */
    public function set_sendusersbybcc( $bcc=array() ) {

        if ( is_array($bcc) ) {
            $this->bcc = $bcc;
        } else {
            // In all other case .. mark as empty.
            $this->bcc = array();
        }
    }

    /**
     * This function insert new record on email_mail.
     */
    public function insert_mail_record() {
        global $DB;

        $mail = new object();

        $mail->userid = $this->userid;
        $mail->course = $this->course;
        $mail->subject = $this->subject;
        $mail->body = $this->body;
        $mail->timecreated = $this->timecreated;

        if ( !$this->id = $DB->insert_record('binerbo_mail', $mail) ) {
            print_error('failinsertrecord',
                'block_binerbo',
                $CFG->wwwroot . '/blocks/binerbo/email/index.php?id=' . $this->course);
        }
    }

    /**
     * This function update record on email_mail.
     */
    public function update_mail_record() {
        global $DB;

        $mail = new object();

        if ( $this->oldmailid <= 0 ) {
            print_error('failupdaterecord',
                'block_binerbo',
                $CFG->wwwroot . '/blocks/binerbo/dashboard.php?id=' . $this->course);
        }

        $mail->id = $this->oldmailid;
        $mail->userid = $this->userid;
        $mail->course = $this->course;
        $mail->subject = $this->subject;
        $mail->body = $this->body;
        $mail->timecreated = time();

        if ( !$DB->update_record('binerbo_mail', $mail) ) {
            print_error('failupdaterecord',
                'block_binerbo',
                $CFG->wwwroot . '/blocks/binerbo/dashboard.php?id=' . $this->course);
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
        return null; // This method sould be implemented by the derived class.
    }

    /**
     * This function save this eMail and respective users.
     * Active the corresponding flag to user sent on draft.
     * Add new mail in table.
     *
     */
    public function save() {
        return null; // This method sould be implemented by the derived class.
    }

    /**
     * This function remove this eMail. If email does in TRASH folder, drop of BBDD, else move to TRASH folder.
     */
    public function remove($userid, $courseid, $folderid, $silent=false) {
        return null;
    }

    /**
     * This function mark eMail as answered.
     *
     * @param int $mailid Mark as read external eMail
     * @param int $userid User Id
     * @param int $courseid Course Id
     * @param boolean $silent Display success.
     */
    public function mark2answered($userid, $courseid, $mailid=0, $silent=false) {
        global $DB;

        // Status.
        $success = true;

        if ( $mailid > 0 ) {
            // Mark answered.
            if ( !$DB->set_field('binerbo_sent', 'answered', 1, 'mailid', $mailid, 'userid', $userid, 'course', $courseid)) {
                $success = false;
            }
        } else if ($this->id > 0 ) {
            // Mark answered.
            if ( !$DB->set_field('binerbo_sent', 'answered', 1, 'mailid', $this->id, 'userid', $userid, 'course', $courseid)) {
                $success = false;
            }
        } else {
            $success = false;
        }

        if ( !$silent && !$success ) {
            notify(get_string('failmarkanswered', 'block_binerbo'));
        }

        return $success;
    }

    /**
     * This function mark mails to read.
     *
     * @param int $userid User Id
     * @param int $courseid Course Id
     * @param boolean $silent Show or not show messages
     * @return boolean Success/Fail
     * @todo Finish documenting this function
     **/
    public function mark2read($userid, $course, $silent=false) {
        global $DB;

        $success = true;

        // Mark as read if eMail Id exist.
        if ( $this->id > 0 ) {
            if ( !$DB->set_field('binerbo_sent', 'readed', 1, 'mailid', $this->id, 'userid', $userid, 'course', $course)) {
                $success = false;
            }
        } else {
            $success = false;
        }

        if ( $success ) {
            if ( !$silent ) {
                // Display success.
                notify(get_string('toreadok', 'block_binerbo'), 'notifysuccess');
            }

            return true;
        } else {
            if ( !$silent ) {
                notify(get_string('failmarkreaded', 'block_binerbo'));
            }

            return false;
        }
    }

    /**
     * This function mark mails to unread.
     *
     * @param int $userid User Id
     * @param int $courseid Course Id
     * @param boolean $silent Show or not show messages
     * @return boolean Success/Fail
     **/
    public function mark2unread($userid, $course, $silent=false) {
        global $DB;

        $success = true;

        // Mark as unread if eMail Id exist.
        if ( $this->id > 0 ) {
            if ( !$DB->set_field('binerbo_sent', 'readed', 0, 'mailid', $this->id, 'userid', $userid, 'course', $course)) {
                $success = false;
            }
        } else {
            $success = false;
        }

        // Display success.
        if ( $success ) {
            if ( !$silent ) {
                // Display success.
                notify(get_string('tounreadok', 'block_binerbo'), 'notifysuccess');
            }

            return true;
        } else {
            if ( !$silent ) {
                notify(get_string('failmarkunreaded', 'block_binerbo'));
            }

            return false;
        }
    }

    /**
     * This function insert reference mail <-> folder. There apply filters.
     *
     * @param int $userid User Id
     * @param string $foldername Folder name
     * @return object Contain all users object send mails
     * @todo Finish documenting this function
     **/
    public function reference_mail_folder($userid, $foldername) {
        global $DB;

        $foldermail = new stdClass();

        $foldermail->mailid = $this->id;

        $folder = \block_binerbo\label::get_root($userid, $foldername);

        $foldermail->folderid = $folder->id;

        // Insert into inbox user.
        if ( !$DB->insert_record('binerbo_foldermail', $foldermail) ) {
            return false;
        }

        return true;
    }

    /**
     * This function add new files into mailid.
     *
     * @uses $CFG
     * @param $attachments Is an array get to $_FILES
     * @return string Array of all name attachments upload
     */

    public function add_attachments() {
        global $CFG, $DB;

        // Note: $attachments is an array, who it's 5 sub-array in here.
        // name, type, tmp_name. size, error who have an arrays.

        // Prevent errors.
        if ( empty($this->oldattachments) and
                ( empty($this->attachments) or
                    ( isset($this->attachments['FILE_0']['error']) and $this->attachments['FILE_0']['error'] == 4)
                )
            ) {
            return true;
        }

        // Get course for upload manager.
        if ( !$course = $DB->get_record('course', array('id' => $this->course)) ) {
            return '';
        }

        require_once($CFG->dirroot.'/lib/uploadlib.php');

        // Get directory for save this attachments.
        $dir = $this->get_file_area();

        // Now, delete old corresponding files.
        if ( !empty( $this->oldattachments) ) {
            // Working in same email.
            if ( $this->type != EMAIL_FORWARD and $this->type != EMAIL_REPLY and $this->type != EMAIL_REPLYALL ) {
                // Necessary library for this function.
                include_once($CFG->dirroot.'/lib/filelib.php');

                // Get files of mail.
                if ($files = get_directory_list($dir)) {

                    // Process all attachments.
                    foreach ($files as $file) {
                        // Get path of file.
                        $attach = $this->get_file_area_name() . '/' .$file;

                        $attachments[] = $attach;
                    }
                }

                if ( $diff = array_diff($attachments, $this->oldattachments) ) {
                    foreach ($diff as $attachment) {
                        unlink($CFG->dataroot.'/'.$attachment); // Drop file.
                    }
                }

            } else if ( $this->type === EMAIL_FORWARD ) {   // Copy $this->oldattachments in this new email.
                foreach ($this->oldattachments as $attachment) {
                    copy($CFG->dataroot . '/' . $attachment, $this->get_file_area() . '/' . basename($attachment));
                }
            }
        }

        if ( !empty($this->attachments) or
                ( isset($this->attachments['FILE_0']['error']) and $this->attachments['FILE_0']['error'] != 4)
            ) {
            // Now, processing all attachments.
            $um = new upload_manager(null, false, false, $course, false, 0, true, true);

            if ( !$um->process_file_uploads($dir) ) {
                // Empty file upload. Error solve in latest version of moodle.
                // Warning! Only comprove first mail. Bug of uploadlib.php.
                $message = get_string('uploaderror', 'assignment');
                $message .= '<br />';
                $message .= $um->get_errors();
                print_simple_box($message, '', '', '', '', 'errorbox');
                print_continue($CFG->wwwroot . '/blocks/binerbo/dashboard.php?id=' . $course->id);
                print_footer();
                die;
            }
        }

        return true;
    }

    /**
     * This functions create upload directory if it's necessary.
     * and return path it.
     *
     * @return string Return the upload file path.
     **/
    public function get_file_area() {
        // First, showing if have path to save mails.
        if ( !$name = $this->get_file_area_name() ) {
            return false;
        }

        return make_upload_directory( $name );
    }

    /**
     * This function return upload attachment path.
     *
     * @return string Path on save upload files
     * @todo Finish documenting this function
     **/
    public function get_file_area_name() {
        global $DB;

        // Get mail.
        if ( !$mail = $DB->get_record('binerbo_mail', array('id' => $this->id)) ) {
            return false;
        }

        return "$this->course/email/$this->userid/$this->id";
    }

    /**
     * This functions return attachments of mail.
     *
     * @uses $CFG
     * @return array All attachments
     * @todo Finish documenting this function
     */
    public function get_attachments() {
        global $CFG;

        // Necessary library for this function.
        include_once($CFG->dirroot.'/lib/filelib.php');

        // Get attachments mail path.
        $basedir = $this->get_file_area();
        $attachment = new stdClass();

        // Get files of mail.
        if ($files = get_directory_list($basedir)) {

            // Process all attachments.
            foreach ($files as $file) {
                // Get path of file.
                $attachment->path = $this->get_file_area_name();
                $attachment->name = $file;

                $attachments[] = (PHP_VERSION < 5) ? $attachment : clone($attachment);  // Thanks Ann.
            }
        }

        return $attachments;
    }
}