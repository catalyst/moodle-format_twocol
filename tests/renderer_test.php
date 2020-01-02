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
    public function test_get_completion_counts() {
        global $PAGE, $CFG;
        $CFG->enablecompletion = true;
        $renderer = $PAGE->get_renderer('format_twocol');
        $completionauto = array('completion' => COMPLETION_TRACKING_AUTOMATIC);

        // Set up the course.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => true));
        $this->getDataGenerator()->create_module('page', array('course' => $course->id), $completionauto);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);
        $this->getDataGenerator()->enrol_user($user4->id, $course->id);

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('format_twocol_renderer', 'get_completion_counts');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($renderer, $course); // Get result of invoked method.

        // No users should have started yet.
        $this->assertEquals(4, $proxy['notstarted']);
        $this->assertEquals(0, $proxy['inprogress']);
        $this->assertEquals(0, $proxy['complete']);

        $compl = new completion_completion(['userid' => $user1->id, 'course' => $course->id]);
        $compl->mark_inprogress();
        $compl = new completion_completion(['userid' => $user2->id, 'course' => $course->id]);
        $compl->mark_complete(1577779980);

        // Should have one user complete and one user in progress.
        $proxy = $method->invoke($renderer, $course); // Get result of invoked method.
        $this->assertEquals(2, $proxy['notstarted']);
        $this->assertEquals(1, $proxy['inprogress']);
        $this->assertEquals(1, $proxy['complete']);

        $compl = new completion_completion(['userid' => $user3->id, 'course' => $course->id]);
        $compl->mark_inprogress();
        $compl = new completion_completion(['userid' => $user4->id, 'course' => $course->id]);
        $compl->mark_inprogress();
        $compl = new completion_completion(['userid' => $user1->id, 'course' => $course->id]);
        $compl->mark_complete(1577779980);

        // Should have two users complete and two user in progress.
        $proxy = $method->invoke($renderer, $course); // Get result of invoked method.
        $this->assertEquals(0, $proxy['notstarted']);
        $this->assertEquals(2, $proxy['inprogress']);
        $this->assertEquals(2, $proxy['complete']);

    }
}
