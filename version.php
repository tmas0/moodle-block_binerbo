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
 * Control version for eMail.
 *
 * @package 	email
 * @copyright   2015 Toni Mas <antoni.mas@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   	= 2015051600;        // The current plugin version.
$plugin->requires  	= 2014051200;        // Requires this Moodle version.
$plugin->cron 		= 1;
$plugin->release 	= 'Version 2.x';
$plugin->component 	= 'block_email_list'; // Full name of the plugin (used for diagnostics).