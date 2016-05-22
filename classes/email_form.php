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
 * Class of form to send new mail
 *
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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot.'/blocks/binerbo/lib.php');

class block_binerbo_email_form extends moodleform {

    /**
     * Returns the options array to use in filemanager for email attachments
     *
     * @param stdClass $email
     * @return array
     */
    public static function attachment_options() {
        global $COURSE, $PAGE, $CFG;
        $maxbytes = get_user_max_upload_file_size($PAGE->context, $CFG->maxbytes, $COURSE->maxbytes);
        return array(
            'subdirs' => 0,
            'maxbytes' => $maxbytes,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'accepted_types' => '*',
            'return_types' => FILE_INTERNAL
        );
    }

    /**
     * Returns the options array to use in email text editor
     *
     * @param context_module $context
     * @param int $mailid Email id, use null when adding new email
     * @return array
     */
    public static function editor_options($context, $mailid) {
        global $COURSE, $PAGE, $CFG;
        // TODO: add max files and max size support.
        $maxbytes = get_user_max_upload_file_size($PAGE->context, $CFG->maxbytes, $COURSE->maxbytes);
        return array(
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $maxbytes,
            'trusttext' => true,
            'return_types' => FILE_INTERNAL | FILE_EXTERNAL,
            'subdirs' => file_area_contains_subdirs($context, 'block_binerbo', 'post', $mailid)
        );
    }

    // Define the form.
    public function definition () {
        global $CFG, $COURSE, $PAGE;

        // Get customdata.
        $oldmail = $this->_customdata['oldmail'];
        $action = $this->_customdata['action'];
        $context = $this->_customdata['context'];

        $mform =& $this->_form;

        // Print the required moodle fields first.
        $mform->addElement('header', 'moodle', get_string('mail', 'block_binerbo'));

        // Mail to.
        $users = get_enrolled_users($context, '', 0, 'u.id, u.firstname, u.lastname');
        $options = array();
        foreach ($users as $user) {
            $options[$user->id] = $user->firstname . ' ' . $user->lastname;
        }
        $mform->addElement('autocomplete', 'sentto', get_string('for', 'block_binerbo'), $options, array('multiple' => 'multiple'));
        $mform->addElement('autocomplete', 'sentcc', get_string('cc', 'block_binerbo'), $options, array('multiple' => 'multiple'));
        $mform->addElement(
            'autocomplete',
            'sentbcc',
            get_string('bcc', 'block_binerbo'),
            $options,
            array('multiple' => 'multiple')
        );

        $mform->addElement('text',
            'subject',
            get_string('subject', 'block_binerbo'),
            'size="48"'
        );
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', get_string('nosubject', 'block_binerbo'), 'required', null, 'client');
        $mform->addRule('subject', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('editor',
            'body',
            get_string('body', 'block_binerbo'),
            null,
            self::editor_options($context, (empty($oldmail) ? null : $oldmail))
        );
        $mform->setType('body', PARAM_RAW);
        $mform->setDefault('body', '');

        $mform->addElement('filemanager', 'FILE', get_string('attachment', 'block_binerbo'), null, self::attachment_options());

        // Add old attachments.
        if ( isset($oldmail->id) ) {
            if ( $oldmail->id > 0 ) {
                $email = new eMail();
                $email->set_email($oldmail);

                if ( $email->has_attachments() ) {
                    // Get mail attachments.
                    $attachments = $email->get_attachments();
                    if ( $attachments ) {
                        $i = 0;
                        foreach ($attachments as $attachment) {
                            $mform->addElement('checkbox',
                                'oldattachment' . $i . 'ck',
                                get_string('attachment', 'block_binerbo'),
                                $attachment->name
                            );
                            $mform->setDefault('oldattachment'.$i.'ck', true);
                            $mform->addElement('hidden',
                                'oldattachment' . $i,
                                "$attachment->path/$attachment->name"
                            );
                            $i++;
                        }
                    }
                }
            }
        }

        // Patch. Thanks.
        // TODO: Add all inputs files who added by user.
        foreach ($_FILES as $key => $value) {
            if ( substr($key, 0, strlen($key) - 1) == 'FILE_' && !$mform->elementExists($key) ) {
                $mform->addElement('file', $key, '', 'value="' . $value . '"');
            }
        }

        // Add some extra hidden fields.
        if ( isset($oldmail->id) ) {
            $mform->addElement('hidden', 'id', $oldmail->id);
        } else {
            $mform->addElement('hidden', 'id');
        }
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'course', $COURSE->id);
        $mform->setType('course', PARAM_INT);
        $mform->addElement('hidden', 'action', $action);
        $mform->setType('action', PARAM_TEXT);
        $mform->addElement('hidden', 'to');
        $mform->setType('to', PARAM_INT);
        $mform->addElement('hidden', 'cc');
        $mform->setType('cc', PARAM_INT);
        $mform->addElement('hidden', 'bcc');
        $mform->setType('bcc', PARAM_INT);

        if (isset($oldmail->id) ) {
            $mform->addElement('hidden', 'oldmailid', $oldmail->id);
        }

        // Add 3 buttons (Send, Draft, Cancel).
        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'sent', get_string('sent', 'block_binerbo'));
        $buttonarray[] = $mform->createElement('submit', 'draft', get_string('savedraft', 'block_binerbo'));
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    public function validation($data, $files) {
        $error = array();

        // Get form.
        $mform =& $this->_form;

        if ( !( isset($data['to']) or isset($data['cc']) or isset($data['bcc']) )  and empty($data['draft']) ) {
            $error['nameto'] = get_string('nosenders', 'block_binerbo');
            $error['namecc'] = get_string('nosenders', 'block_binerbo');
            $error['namebcc'] = get_string('nosenders', 'block_binerbo');
        }

        // TODO: Add all inputs files who added by user.
        foreach ($_FILES as $key => $value) {
            if ( substr($key, 0, strlen($key) - 1) == 'FILE_' && !$mform->elementExists($key)) {
                $mform->addElement('file', $key, '', 'value="'.$value.'"');
            }
        }
        return (count($error) == 0) ? true : $error;
    }
}