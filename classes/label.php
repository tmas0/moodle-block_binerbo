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
     * @param int $parentfolder Parent label
     * @return boolean Success/Fail
     */
    public function newlabel($label, $parentlabel) {
        global $DB;

        // Add actual time.
        $label->timecreated = time();

        // Make sure course field is not null. Thanks Ann.
        if ( !isset( $folder->course) ) {
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
}