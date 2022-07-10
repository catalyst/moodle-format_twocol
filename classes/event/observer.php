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
 * Event observers used in format_twocol.
 *
 * @package     format_twocol
 * @copyright   2022 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace format_twocol\event;

use core\event\course_updated;


defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for format_twocol.
 */
class observer {
    /**
     * Triggered via course_updated event.
     *
     * @param \core\event\course_updated $event
     */
    public static function course_updated(course_updated $event) {
        // Purge course image cache in case if course image has been updated.
        \cache::make('format_twocol', 'header_course_image')->delete($event->objectid);
        return true;
    }

}
