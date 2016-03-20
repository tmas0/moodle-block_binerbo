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
require_once($CFG->dirroot.'/blocks/email_list/email/lib.php');

class block_email_list_email_form extends moodleform {

    // Define the form.
    public function definition () {
        global $CFG, $COURSE;

        // Get customdata.
        $oldmail = $this->_customdata['oldmail'];
        $action = $this->_customdata['action'];

        $mform =& $this->_form;

        // Print the required moodle fields first.
        $mform->addElement('header', 'moodle', get_string('mail', 'block_email_list'));

        $mform->addElement('button',
            'urlcc',
            get_string('participants', 'block_email_list') . '...',
            array( 'onclick' => "this.target='participants';
                                    return openpopup('/blocks/email_list/email/participants.php?id=$COURSE->id',
                    'participants','menubar=0,location=0,scrollbars=1,resizable,width=760,height=700', 0);"
                )
        );

        // Mail to.
        if ( $CFG->email_enable_ajax ) {
            // Added to allow for YUI autocomplete styling.
            $mform->addElement('html', '<div class="yui-skin-sam">');
            $mform->addElement('textarea',
                'nameto',
                get_string('for', 'block_email_list'),
                array('rows' => '2',
                    'cols' => '65',
                    'class' => 'textareacontacts',
                    'multiple' => 'multiple')
            );

            // Stores the YUI autocomplete results.
            $mform->addElement('static', 'qResultsTo', '', '<div id="qResultsTo"></div>');
            $mform->addElement('html', '</div>');
        } else {
            $mform->addElement('textarea',
                'nameto',
                get_string('for', 'block_email_list'),
                array('rows' => '2',
                    'cols' => '65',
                    'class' => 'textareacontacts',
                    'disabled' => 'true'
                )
            );
        }

        // Mail cc.
        if ( $CFG->email_enable_ajax ) {
            // Added to allow for YUI autocomplete styling.
            $mform->addElement('html', '<div class="yui-skin-sam">');
            $mform->addElement('textarea',
                'namecc',
                get_string('cc', 'block_email_list'),
                array('rows' => '1',
                    'cols' => '65',
                    'class' => 'textareacontacts',
                    'multiple' => 'multiple'
                )
            );

            // Stores the YUI autocomplete results.
            $mform->addElement('static', 'qResultsCC', '', '<div id="qResultsCC"></div>');
            $mform->addElement('html', '</div>');
        } else {
            $mform->addElement('textarea',
                'namecc',
                get_string('cc', 'block_email_list'),
                array('rows' => '1',
                    'cols' => '65',
                    'class' => 'textareacontacts',
                    'disabled' => 'true'
                )
            );
        }

        // Mail bcc.
        if ( $CFG->email_enable_ajax ) {
            // Added to allow for YUI autocomplete styling.
            $mform->addElement('html', '<div class="yui-skin-sam">');
            $mform->addElement('textarea',
                'namebcc',
                get_string('bcc', 'block_email_list'),
                array('rows' => '1',
                    'cols' => '65',
                    'class' => 'textareacontacts',
                    'multiple' => 'multiple'
                )
            );

            // Stores the YUI autocomplete results.
            $mform->addElement('static', 'qResultsBCC', '', '<div id="qResultsBCC"></div>');
            $mform->addElement('html', '</div>');
        } else {
            $mform->addElement('textarea',
                'namebcc',
                get_string('bcc', 'block_email_list'),
                array('rows' => '1',
                    'cols' => '65',
                    'class' => 'textareacontacts',
                    'disabled' => 'true'
                )
            );
        }

        $mform->addElement('text',
            'subject',
            get_string('subject', 'block_email_list'),
            'class="emailsubject" maxlength="254" size="60"'
        );
        $mform->setDefault('subject', '');
        $mform->addRule('subject', get_string('nosubject', 'block_email_list'), 'required', null, 'client');
        $mform->setType('subject', PARAM_MULTILANG);

        $mform->addElement('htmleditor',
            'body',
            get_string('body', 'block_email_list'),
            array('rows' => '25',
                'cols' => '65'
            )
        );
        $mform->setDefault('body', '');
        $mform->setType('body', PARAM_RAW);

        $mform->addElement('filemanager', 'FILE', get_string('attachment', 'block_email_list'));

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
                                get_string('attachment', 'block_email_list'),
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
        $buttonarray[] = &$mform->createElement('submit', 'send', get_string('send', 'block_email_list'));
        $buttonarray[] = &$mform->createElement('submit', 'draft', get_string('savedraft', 'block_email_list'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    public function validation($data, $files) {
        $error = array();

        // Get form.
        $mform =& $this->_form;

        if ( !( isset($data['to']) or isset($data['cc']) or isset($data['bcc']) )  and empty($data['draft']) ) {
            $error['nameto'] = get_string('nosenders', 'block_email_list');
            $error['namecc'] = get_string('nosenders', 'block_email_list');
            $error['namebcc'] = get_string('nosenders', 'block_email_list');
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