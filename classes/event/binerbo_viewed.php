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
 * Viewed email.
 *
 * @package    email
 * @copyright  2015 Toni Mas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_binerbo\event;

defined('MOODLE_INTERNAL') || die();

/**
 * eMail viewed event class.
 *
 * @package    email
 * @copyright  2015 Toni Mas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class binerbo_viewed extends \core\event\base {
    /**
     * Init method
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'binerbo_mail';
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventviewedok', 'block_binerbo');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' view email";
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url("/block/binerbo/dashboard.php",
                array('id' => $this->contextinstanceid,
                      'mailid' => $this->objectid));
    }

    /**
     * Return the legacy event log data.
     *
     * @return array|null
     */
    public function get_legacy_logdata() {
        return array($this->courseid, 'binerbo_mail', 'sent email',
            "block/binerbo/dashboard.php?id={$this->contextinstanceid}",
            $this->objectid, $this->contextinstanceid);
    }

    /**
     * Legacy event data if get_legacy_eventname() is not empty.
     *
     * @return stdClass
     */
    protected function get_legacy_eventdata() {
        $attempt = $this->get_record_snapshot('binerbo_mail', $this->objectid);
        $legacyeventdata = new \stdClass();
        $legacyeventdata->component = 'block_binerbo';
        $legacyeventdata->attemptid = $this->objectid;
        $legacyeventdata->timestamp = $attempt->timecreated;
        $legacyeventdata->userid = $this->relateduserid;
        $legacyeventdata->mailid = $attempt->maild;
        $legacyeventdata->courseid = $this->courseid;

        return $legacyeventdata;
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();
        // Make sure this class is never used without proper object details.
        if (!$this->contextlevel === CONTEXT_MODULE) {
            throw new \coding_exception('Context level must be CONTEXT_MODULE.');
        }
    }
}