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
 * Unit tesst for format_twocol renderer class.
 *
 * @package    format_twocol
 * @copyright  2019 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot. '/course/format/twocol/renderer.php');

/**
 * Unit tesst for format_twocol renderer class.
 *
 * @package    format_twocol
 * @copyright  2019 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      format_twocol
 */
class format_twocol_renderer_testcase extends advanced_testcase {

    /**
     * @var array Fixtures used in this test.
     */
    public $fixture;

    public function setUp() {
        $this->resetAfterTest();
     }

    /**
     * Test getting user completion counts.
     */
    public function test_get_completion_count() {
        global $PAGE;
        $renderer = $PAGE->get_renderer('format_twocol');

        $course = $this->getDataGenerator()->create_course();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('format_twocol_renderer', 'get_completion_count');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($renderer, $course); // Get result of invoked method.

    }
}
