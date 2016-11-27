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
 * Genarator tests.
 *
 * @package    block_binerbo
 * @copyright  2016 Toni Mas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * Genarator tests class.
 *
 * @package    block_binerbo
 * @copyright  2016 Toni Mas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_binerbo_generator_testcase extends advanced_testcase {
	public function test_create_instance() {
		global $DB;
		$this->resetAfterTest();
		$this->setAdminUser();
		$course = $this->getDataGenerator()->create_course();
		$this->assertFalse($DB->record_exists('binerbo', array('course' => $course->id)));
	}
}