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
 * Backup class for eMail.
 *
 * @package     email
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/blocks/binerbo/backup/moodle2/backup_binerbo_stepslib.php'); // We have structure steps.
require_once($CFG->dirroot . '/blocks/binerbo/backup/moodle2/backup_binerbo_settingslib.php'); // Because it exists (optional).


// Add by adrian.castillo CAM-1741.
class backup_binerbo_block_task extends backup_block_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
          $this->add_step(new backup_binerbo_block_structure_step('binerbo_structure', 'binerbo.xml'));
    }

    public function get_fileareas() {
    }

    public function get_configdata_encoded_attributes() {
    }

    static public function encode_content_links($content) {
        return $content; // No special encoding of links.
    }
}