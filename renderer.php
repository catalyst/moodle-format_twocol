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
 * Renderer for outputting the twocol course format.
 *
 * @package     format_twocol
 * @copyright   2019 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/format/renderer.php');

/**
 * Basic renderer for twocol format.
 *
 * @package     format_twocol
 * @copyright   2019 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_twocol_renderer extends format_section_renderer_base {

    /**
     * Constructor method, calls the parent constructor
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);

        // Since format_twocol_renderer::section_edit_control_items() only displays the 'Highlight' control when editing mode is on
        // we need to be sure that the link 'Turn editing mode on' is available
        // for a user who does not have any other managing capability.
        $page->set_other_editing_capability('moodle/course:setcurrentsection');
    }

    /**
     * Generate the starting container html for a list of sections
     * @return string HTML to output.
     */
    protected function start_section_list() : string {
        return html_writer::start_tag('ul', array('class' => 'twocol'));
    }

    /**
     * Generate the closing container html for a list of sections
     * @return string HTML to output.
     */
    protected function end_section_list() :string {
        return html_writer::end_tag('ul');
    }

    /**
     * Generate the title for this section page
     * @return string the page title
     */
    protected function page_title() : string {
        return get_string('topicoutline');
    }

    private function get_completion_count(\stdClass $course) : array {
        $completioninfo = new completion_info($course);
        $userprogress = $completioninfo->get_progress_all();
        error_log('here');
        foreach ($userprogress as $user) {
            error_log(print_r($user, true));
        }

        return array();
    }

    /**
     * Prints the course summary.
     *
     * @param \stdClass $course
     */
    public function print_course_summary($course) : void {
        global $OUTPUT;

        $modinfo = get_fast_modinfo($course);
        $thissection = $modinfo->get_section_info(0);
        $displaysection = 0;
        $courseformatoptions = course_get_format($course)->get_format_options();

        $templatecontext = new \stdClass();
        $templatecontext->rightcontent = $this->section_right_content($thissection, $course, true);
        $templatecontext->summaryname = get_section_name($course, $thissection);
        $templatecontext->summary = $this->format_summary_text($thissection);
        $templatecontext->mods = $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
        $templatecontext->modcontrol = $this->courserenderer->course_section_add_cm_control($course, 0, $displaysection);
        $templatecontext->courseimage = $OUTPUT->get_generated_image_for_id($course->id);

        $coursecompletion = \core_completion\progress::get_course_progress_percentage($course);
        if (!is_null($coursecompletion)) {
            $templatecontext->hasprogress = true;
            $templatecontext->progress = round($coursecompletion);
        } else {
            $templatecontext->hasprogress = false;
        }

        $templatecontext->sections = $this->get_section_info($course);

        if (!empty($courseformatoptions['detailsheading'])) {
            $templatecontext->detailsheading = $courseformatoptions['detailsheading'];
        }

        if (!empty($courseformatoptions['resourcesheading'])) {
            $templatecontext->resourcesheading = $courseformatoptions['resourcesheading'];
        }

        if (!empty($courseformatoptions['sectionheading1'])) {
            $templatecontext->sectionheading1 = $courseformatoptions['sectionheading1'];
            $templatecontext->sectionicon1 = $courseformatoptions['sectionicon1'];
            $templatecontext->sectiontext1 = $courseformatoptions['sectiontext1']['text'];
        }

        if (!empty($courseformatoptions['sectionheading2'])) {
            $templatecontext->sectionheading2 = $courseformatoptions['sectionheading2'];
            $templatecontext->sectionicon2 = $courseformatoptions['sectionicon2'];
            $templatecontext->sectiontext2 = $courseformatoptions['sectiontext2']['text'];
        }

        if (!empty($courseformatoptions['sectionheading3'])) {
            $templatecontext->sectionheading3 = $courseformatoptions['sectionheading3'];
            $templatecontext->sectionicon3 = $courseformatoptions['sectionicon3'];
            $templatecontext->sectiontext3 = $courseformatoptions['sectiontext3']['text'];
        }

        if (!empty($courseformatoptions['sectionheading4'])) {
            $templatecontext->sectionheading4 = $courseformatoptions['sectionheading4'];
            $templatecontext->sectionicon4 = $courseformatoptions['sectionicon4'];
            $templatecontext->sectiontext4 = $courseformatoptions['sectiontext4']['text'];
        }

        if (!empty($courseformatoptions['sectionheading5'])) {
            $templatecontext->sectionheading5 = $courseformatoptions['sectionheading5'];
            $templatecontext->sectionicon5 = $courseformatoptions['sectionicon5'];
            $templatecontext->sectiontext5 = $courseformatoptions['sectiontext5']['text'];
        }

        echo $this->render_from_template('format_twocol/course_summary', $templatecontext);
    }

    /**
     * Output the html for a single section page .
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     * @param int $displaysection The section number in the course which is being displayed
     */
    public function print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection) {
        global $OUTPUT;

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();
        $completioninfo = new completion_info($course);

        // Can we view the section in question?
        if (!($sectioninfo = $modinfo->get_section_info($displaysection)) || !$sectioninfo->uservisible) {
            // This section doesn't exist or is not available for the user.
            // We actually already check this in course/view.php but just in case exit from this function as well.
            print_error('unknowncoursesection', 'error', course_get_url($course),
                format_string($course->fullname));
        }

        // The requested section page.
        $thissection = $modinfo->get_section_info($displaysection);

        $sectioncompletion = $this->get_section_completion($thissection, $course);

        // Title with section navigation links.
        $sectionnavlinks = $this->get_nav_links($course, $modinfo->get_section_info_all(), $displaysection);

        // Title attributes.
        $classes = 'sectionname';
        if (!$thissection->visible) {
            $classes .= ' dimmed_text';
        }

        $templatecontext = new \stdClass();
        $templatecontext->courseimage = $OUTPUT->get_generated_image_for_id($course->id);
        $templatecontext->courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
        $templatecontext->clipboard = $this->course_activity_clipboard($course, $displaysection); // Copy activity clipboard.
        $templatecontext->navlinkprevious = $sectionnavlinks['previous'];
        $templatecontext->navlinknext = $sectionnavlinks['next'];
        $templatecontext->sectionname = $this->section_title_without_link($thissection, $course);
        $templatecontext->sectionnameclasses = $classes;
        $templatecontext->sectionheader = $this->section_header($thissection, $course, true, $displaysection);
        $templatecontext->helpicon = $completioninfo->display_help_icon();
        $templatecontext->sectioncmlist = $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
        $templatecontext->sectioncmcontrol = $this->courserenderer->course_section_add_cm_control(
            $course, $displaysection, $displaysection);
        $templatecontext->navselection = $this->section_nav_selection($course, $sections, $displaysection);
        $templatecontext->hastotal = $sectioncompletion->hastotal;
        $templatecontext->percent = $sectioncompletion->percent;

        echo $this->render_from_template('format_twocol/course_topic', $templatecontext);
    }

    /**
     * Output the html for a multiple section page
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $PAGE;

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        $context = context_course::instance($course->id);
        // Title with completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();
        echo $this->output->heading($this->page_title(), 2, 'accesshide');

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, 0);

        // Now the list of sections..
        echo $this->start_section_list();
        $numsections = course_get_format($course)->get_last_section_number();

        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            if ($section == 0) {
                continue;
            }
            if ($section > $numsections) {
                // Activities inside this section are 'orphaned', this section will be printed as 'stealth' below.
                continue;
            }
            // Show the section if the user is permitted to access it, OR if it's not available
            // but there is some available info text which explains the reason & should display,
            // OR it is hidden but the course has a setting to display hidden sections as unavilable.
            $showsection = $thissection->uservisible ||
            ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo)) ||
            (!$thissection->visible && !$course->hiddensections);
            if (!$showsection) {
                continue;
            }

            if (!$PAGE->user_is_editing() && $course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
                // Display section summary only.
                echo $this->section_summary($thissection, $course, null);
            } else {
                echo $this->section_header($thissection, $course, false, 0);
                if ($thissection->uservisible) {
                    echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                    echo $this->courserenderer->course_section_add_cm_control($course, $section, 0);
                }
                echo $this->section_footer();
            }
        }

        if ($PAGE->user_is_editing() and has_capability('moodle/course:update', $context)) {
            // Print stealth sections if present.
            foreach ($modinfo->get_section_info_all() as $section => $thissection) {
                if ($section <= $numsections or empty($modinfo->sections[$section])) {
                    // This is not stealth section or it is empty.
                    continue;
                }
                echo $this->stealth_section_header($section);
                echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                echo $this->stealth_section_footer();
            }

            echo $this->end_section_list();

            echo $this->change_number_sections($course, 0);
        } else {
            echo $this->end_section_list();
        }

    }

    /**
     * Get section info for UI display.
     *
     * @param stdClass $course The course entry from DB
     * @return array $sections array of section names and ids.
     */
    private function get_section_info(\stdClass $course) : array {
        $modinfo = get_fast_modinfo($course);
        $numsections = course_get_format($course)->get_last_section_number();
        $sections = array();

        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            if ($section == 0) {
                continue;
            }
            if ($section > $numsections) {
                // Activities inside this section are 'orphaned', this section will be printed as 'stealth' below.
                continue;
            }
            // Show the section if the user is permitted to access it, OR if it's not available
            // but there is some available info text which explains the reason & should display,
            // OR it is hidden but the course has a setting to display hidden sections as unavilable.
            $showsection = $thissection->uservisible ||
            ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo)) ||
            (!$thissection->visible && !$course->hiddensections);
            if (!$showsection) {
                continue;
            }

            $sections[] = array(
                'url' => new moodle_url('/course/view.php', array('id' => $course->id, 'section' => $section)),
                'name' => get_section_name($course, $thissection),
                'completion' => $this->get_section_completion($thissection, $course)
            );
        }

        return $sections;
    }

    /**
     * Get section activity completion information.
     *
     * @param \section_info $section
     * @param \stdClass $course
     * @return \stdClass
     */
    private function get_section_completion(\section_info $section, \stdClass $course) : \stdClass {
        $total = 0;
        $complete = 0;
        $cancomplete = isloggedin() && !isguestuser();
        $completioninfo = new completion_info($course);
        $modinfo = get_fast_modinfo($course);

        $completion = new \stdClass();
        $completion->hastotal = false;
        $completion->percent = 0;

        if (empty($modinfo->sections[$section->section])) {
            return $completion;
        }

        foreach ($modinfo->sections[$section->section] as $cmid) {
            $thismod = $modinfo->cms[$cmid];

            if ($thismod->uservisible) {
                if ($cancomplete && $completioninfo->is_enabled($thismod) != COMPLETION_TRACKING_NONE) {
                    $total++;
                    $completiondata = $completioninfo->get_data($thismod, true);
                    if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                        $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                            $complete++;
                    }
                }
            }
        }

        $completion->total = $total;
        $completion->complete = $complete;

        if ($total > 0) {
            $completion->hastotal = true;
            $completion->percent = round((($complete / $total) * 100), 0);
        }

        return $completion;
    }

    /**
     * Generate next/previous section links for naviation
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param int $sectionno The section number in the course which is being displayed
     * @return array associative array with previous and next section link
     */
    protected function get_nav_links($course, $sections, $sectionno) {
        // FIXME: This is really evil and should by using the navigation API.
        $course = course_get_format($course)->get_course();
        $canviewhidden = has_capability('moodle/course:viewhiddensections', context_course::instance($course->id))
        or !$course->hiddensections;

        $links = array('previous' => '', 'next' => '');
        $back = $sectionno - 1;
        while ($back > 0 and empty($links['previous'])) {
            if ($canviewhidden || $sections[$back]->uservisible) {
                $params = array();
                if (!$sections[$back]->visible) {
                    $params = array('class' => 'dimmed_text');
                }
                $previouslink = html_writer::tag('span', $this->output->larrow(), array('class' => 'larrow'));
                $previouslink .= get_section_name($course, $sections[$back]);
                $links['previous'] = html_writer::link(course_get_url($course, $back), $previouslink, $params);
            }
            $back--;
        }

        $forward = $sectionno + 1;
        $numsections = course_get_format($course)->get_last_section_number();
        while ($forward <= $numsections and empty($links['next'])) {
            if ($canviewhidden || $sections[$forward]->uservisible) {
                $params = array();
                if (!$sections[$forward]->visible) {
                    $params = array('class' => 'dimmed_text');
                }
                $nextlink = get_section_name($course, $sections[$forward]);
                $nextlink .= html_writer::tag('span', $this->output->rarrow(), array('class' => 'rarrow'));
                $links['next'] = html_writer::link(course_get_url($course, $forward), $nextlink, $params);
            }
            $forward++;
        }

        return $links;
    }


}
