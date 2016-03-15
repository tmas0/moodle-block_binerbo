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
 * Parent class for labels.
 *
 * @package     email
 * @copyright   2015 Toni Mas <antoni.mas@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_email_list;

defined('MOODLE_INTERNAL') || die();

class label {

    /**
     * Constructor.
     */
    public function __construct() {
        // Nothing to do.
    }

    /**
     * This functions created news labels.
     *
     * @param object $label Fields of new label
     * @param int $parentlabel Parent label
     * @return boolean Success/Fail
     */
    public function create($label, $parentlabel) {
        global $DB;

        // Add actual time.
        $label->timecreated = time();

        // Make sure course field is not null. Thanks Ann.
        if ( !isset( $label->course) ) {
            $label->course = 0;
        }

        // Insert record.
        if ( !$label->id = $DB->insert_record('email_label', $label) ) {
            return false;
        }

        // Prepare sublabel.
        $sublabel = new stdClass();
        $sublabel->labelparentid = $parentlabel;
        $sublabel->labelchildid  = $label->id;

        // Insert record reference.
        if (! $DB->insert_record('email_sublabel', $sublabel)) {
            return false;
        }

        add_to_log($label->userid, "email", "add sublabel", "$label->name");

        return true;
    }

    /**
     * Get label.
     *
     * @param int $labelid The label identifier.
     * @return Label.
     */
    public static function get($labelid) {
        global $DB;

        $label = $DB->get_record('email_label', array('id' => $labelid));

        if ( isset($label->isparenttype) ) {
            // Only change in parent labels.
            if ( ! is_null($label->isparenttype) ) {
                // If is parent ... return language name.
                if ( self::is_type($label, EMAIL_INBOX) ) {
                    $label->name = get_string('inbox', 'block_email_list');
                }

                if ( self::is_type($label, EMAIL_SENDBOX) ) {
                    $label->name = get_string('sendbox', 'block_email_list');
                }

                if ( self::is_type($label, EMAIL_TRASH) ) {
                    $label->name = get_string('trash', 'block_email_list');
                }

                if ( self::is_type($label, EMAIL_DRAFT) ) {
                    $label->name = get_string('draft', 'block_email_list');
                }
            }
        }
        return $label;
    }

    /**
     * This function return success/fail if label corresponding with this type.
     *
     * @param object $label Label Object
     * @param string $type Type label
     * @return boolean Success/Fail
     * @todo Finish documenting this function
     */
    public static function is_type($label, $type) {

        if ( isset($label->isparenttype) && $label->isparenttype ) {
            return ($type == $label->isparenttype);
        } else {

            // Get first parent.
            $parentlabel = self::get_parent($label);

            if ( !isset($parentlabel->isparenttype) ) {
                return false;
            }

            // Return value.
            return ( $parentlabel->isparenttype == $type );
        }
    }

    /**
     * This function return label parent.
     *
     * @param object $label Label
     * @return object Contain parent label
     * @todo Finish documenting this function
     */
    public static function get_parent($label) {
        global $DB;

        if ( !$label ) {
            return false;
        }

        if ( is_int($label) ) {
            if ( !$sublabel = $DB->get_record('email_sublabel', array('labelchildid' => $label)) ) {
                return false;
            }
        } else {
            if ( !$sublabel = $DB->get_record('email_sublabel', array('labelchildid' => $label->id)) ) {
                return false;
            }
        }

        return $DB->get_record('email_label', array('id' => $sublabel->labelparentid));
    }

    /**
     * This function return label parent with it.
     *
     * @uses $USER
     * @param int $userid User ID
     * @param string $label Folder
     * @return object Contain parent label
     * @todo Finish documenting this function
     */
    public static function get_root($userid, $label) {
        global $USER, $DB;

        if ( empty($userid) ) {
            $userid = $USER->id;
        }

        self::create_parents($userid);

        $rootlabel = new \stdClass();

        if ( $userid > 0 and !empty($userid) ) {
            if ( $label == EMAIL_INBOX ) {
                $params = array('userid' => $userid, 'isparenttype' => EMAIL_INBOX);
                $rootlabel = $DB->get_record('email_label', $params);
                $rootlabel->name = get_string('inbox', 'block_email_list');
                return $rootlabel;
            }

            if ( $label == EMAIL_SENDBOX ) {
                $params = array('userid' => $userid, 'isparenttype' => EMAIL_SENDBOX);
                $rootlabel = $DB->get_record('email_label', $params);
                $rootlabel->name = get_string('sendbox', 'block_email_list');
                return $rootlabel;
            }

            if ( $label == EMAIL_TRASH ) {
                $params = array('userid' => $userid, 'isparenttype' => EMAIL_TRASH);
                $rootlabel = $DB->get_record('email_label', $params);
                $rootlabel->name = get_string('trash', 'block_email_list');
                return $rootlabel;
            }

            if ( $label == EMAIL_DRAFT ) {
                $params = array('userid' => $userid, 'isparenttype' => EMAIL_DRAFT);
                $rootlabel = $DB->get_record('email_label', $params);
                $rootlabel->name = get_string('draft', 'block_email_list');
                return $rootlabel;
            }
        }

        return $rootlabel;
    }

    /**
     * This function created, if no exist, the initial labels
     * who are Inbox, Sendbox, Trash and Draft
     *
     * @param int $userid User ID
     * @return boolean Success/Fail If Success return object which id's
     * @todo Finish documenting this function
     */
    public static function create_parents($userid) {
        global $DB;

        $labels = new \stdClass();
        $label = new \stdClass();

        $label->timecreated = time();
        $label->userid  = $userid;
        $label->name    = addslashes(get_string('inbox', 'block_email_list'));
        $label->isparenttype = EMAIL_INBOX; // Be careful if you change this field.

        // Labels is an object who contain id's of created labels.

        // Insert inbox if no exist.
        $params = array('userid' => $userid, 'isparenttype' => EMAIL_INBOX);
        if ( $DB->count_records('email_label', $params) == 0 ) {
            if ( !$labels->inboxid = $DB->insert_record('email_label', $label)) {
                return false;
            }
        }

        // Insert draft if no exist.
        $label->name = addslashes(get_string('draft', 'block_email_list'));
        $label->isparenttype = EMAIL_DRAFT; // Be careful if you change this field.

        $params = array('userid' => $userid, 'isparenttype' => EMAIL_DRAFT);
        if ( $DB->count_records('email_label', $params) == 0 ) {
            if ( !$labels->trashid = $DB->insert_record('email_label', $label) ) {
                return false;
            }
        }

        // Insert sendbox if no exits.
        $label->name = addslashes(get_string('sendbox', 'block_email_list'));
        $label->isparenttype = EMAIL_SENDBOX; // Be careful if you change this field.

        $params = array('userid' => $userid, 'isparenttype' => EMAIL_SENDBOX);
        if ( $DB->count_records('email_label', $params) == 0 ) {
            if ( !$labels->sendboxid = $DB->insert_record('email_label', $label) ) {
                return false;
            }
        }

        // Insert trash if no exits.
        $label->name = addslashes(get_string('trash', 'block_email_list'));
        $label->isparenttype = EMAIL_TRASH; // Be careful if you change this field.

        $params = array('userid' => $userid, 'isparenttype' => EMAIL_TRASH);
        if ( $DB->count_records('email_label', $params) == 0) {
            if ( !$labels->trashid = $DB->insert_record('email_label', $label) ) {
                return false;
            }
        }

        return $labels;
    }

    /**
     * This fuctions return all sublabels with one label (one level), if it've.
     *
     * @uses $USER, $COURSE
     * @param int $labelid Parent label
     * @param int $courseid Course ID.
     * @param boolean $admin Admin labels
     * @return array Contain all sublabels
     * @todo Finish documenting this function
     */
    public static function get_sublabels($labelid, $courseid = null, $admin = false) {
        global $USER, $DB;

        // Get childs for this parent.
        $childs = $DB->get_records('email_sublabel', array('labelparentid' => $labelid));

        $sublabels = array();

        // If have childs.
        if ( $childs ) {

            // Save child label in array.
            foreach ($childs as $child) {

                if ( is_null($courseid) or !email_have_asociated_folders($USER->id) ) {
                    $sublabels[] = $DB->get_record('email_label', array('id' => $child->labelchildid));
                } else {
                    if ( $label = $DB->get_record('email_label', array('id' => $child->labelchildid, 'course' => $courseid)) ) {
                        $sublabels[] = $label;
                    } else if ( $label = $DB->get_record('email_label', array('id' => $child->labelchildid, 'course' => '0')) ) {
                        $sublabels[] = $label; // Add general label's.
                    }
                }
            }
        } else {
            // If no childs, return false.
            return false;
        }

        // Return sublabels.
        return $sublabels;
    }
}