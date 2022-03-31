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
 * A more permissive class of \moodle_url.
 *
 * @package     format_twocol
 * @copyright   2020 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace format_twocol;

defined('MOODLE_INTERNAL') || die();
/**
 * A more permissive class of \moodle_url.
 *
 * @package     format_twocol
 * @copyright   2020 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_url extends \moodle_url {

    /**
     * Returns params for url.
     *
     * @return array
     */
    public function get_params() : array {
        return $this->params;
    }

    /**
     * Returns anchor for URL.
     *
     * @return string|null
     */
    public function get_anchor() {
        return $this->anchor;
    }

}
