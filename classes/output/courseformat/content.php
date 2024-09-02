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
 * Contains the default content output class.
 *
 * @package     format_twocol
 * @copyright   2022 Tomo Tsuyuki <tomotsuyuki@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_twocol\output\courseformat;

use completion_completion;
use completion_info;
use context_course;
use core_courseformat\output\local\content as content_base;
use html_writer;
use moodle_url;
use renderer_base;
use stdClass;
use url_select;

/**
 * Base class to render a course content.
 *
 * @package     format_twocol
 * @copyright   2022 Tomo Tsuyuki <tomotsuyuki@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content extends content_base {

    /**
     * Override template definition.
     *
     * @param renderer_base $renderer The renderer requesting the template name
     * @return string
     */
    public function get_template_name(renderer_base $renderer): string {
        return 'format_twocol/local/content';
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(renderer_base $output): stdClass {
        global $PAGE;

        $data = new stdClass();

        $format = $this->format;
        $displaysection = $format->get_sectionnum();
        if (!empty($displaysection)) {
            $data->singlesectionpage = $this->print_single_section_page($output);
        } else {
            $data->coursesummary = $this->print_course_summary($output);
            if ($PAGE->user_is_editing()) {
                $data->multiplesectionpage = $this->print_multiple_section_page($output);
            }
        }

        return $data;

    }

    /**
     * Get counts of user completion states for the course.
     *
     * @param completion_info $completioninfo
     * @param stdClass $course
     * @return array $completioncounts
     */
    private function get_completion_counts(completion_info $completioninfo, stdClass $course) : array {
        $completioncounts = array(
            'complete' => 0,
            'inprogress' => 0,
            'notstarted' => 0
        );

        $trackedusers = $completioninfo->get_tracked_users();

        foreach ($trackedusers as $trackeduser) {
            $params = array(
                'userid'    => $trackeduser->id,
                'course'  => $course->id
            );

            $ccompletion = new completion_completion($params);
            if ($ccompletion->timecompleted > 0) {
                $completioncounts['complete'] ++;
            } else if ($ccompletion->timestarted > 0) {
                $completioncounts['inprogress'] ++;
            } else {
                $completioncounts['notstarted'] ++;
            }

        }

        return $completioncounts;
    }

    /**
     * Get the course image or course patten for the given course.
     *
     * @param stdClass $course
     * @param renderer_base $output
     * @param int $imagenum The course image num to get.
     * @return string $courseimage
     */
    private function get_course_image_or_pattern(stdClass $course, renderer_base $output, int $imagenum): string {
        // First try to get a custom header image.
        $courseimageid = implode('_', [$course->id, $imagenum]);
        $courseimageobj = \cache::make('format_twocol', 'header_course_image');
        $courseimage = $courseimageobj->get($courseimageid);;

        // Then try to get the default course image.
        if (!$courseimage) {
            $courseimage = \core_course\external\course_summary_exporter::get_course_image($course);
        }

        // If all else fails just get a generated image.
        if (!$courseimage) {
            $courseimage = $output->get_generated_image_for_id($course->id);
        }

        return $courseimage;
    }

    /**
     * Prints the course summary.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function print_course_summary(renderer_base $output): stdClass {
        $format = $this->format;
        $course = $format->get_course();

        $config = get_config('format_twocol');
        $modinfo = get_fast_modinfo($course);
        $thissection = $modinfo->get_section_info(0);
        $displaysection = 0;
        $courseformatoptions = course_get_format($course)->get_format_options();
        $completioninfo = new completion_info($course);
        $imagenum = isset($courseformatoptions['headerimage']) ? $courseformatoptions['headerimage'] : 1;
        $headerbackcolor = isset($courseformatoptions['headerbackcolor']) ? $courseformatoptions['headerbackcolor'] : '#FFFFFF';

        $templatecontext = new stdClass();

        if ($format->show_editor()) {
            $controlmenuclass = $format->get_output_classname('content\\section\\controlmenu');
            $controlmenu = new $controlmenuclass($format, $thissection);
            $templatecontext->controlmenu = $controlmenu->export_for_template($output);
        }
        $templatecontext->summaryname = get_section_name($course, $thissection);

        $summaryclass = $format->get_output_classname('content\\section\\summary');
        $summary = new $summaryclass($format, $thissection);
        $templatecontext->summary = $summary->export_for_template($output);
        $cmlistclass = $format->get_output_classname('content\\section\\cmlist');
        $cmlist = new $cmlistclass($format, $thissection);
        $templatecontext->mods = $cmlist->export_for_template($output);
        $templatecontext->modcontrol = $output->course_section_add_cm_control($course, 0, $displaysection);
        $templatecontext->progresstitle = get_string('progresstitle:course', 'format_twocol');
        $templatecontext->headerbackcolor = $headerbackcolor;

        if ($imagenum !== 0) {
            $imagenum--;
            $templatecontext->courseimage = $this->get_course_image_or_pattern($course, $output, $imagenum);
        }

        $coursecompletion = \core_completion\progress::get_course_progress_percentage($course);
        if (!is_null($coursecompletion)) {
            $templatecontext->hasprogress = true;
            $templatecontext->progress = round($coursecompletion);
        } else {
            $templatecontext->hasprogress = false;
        }

        $templatecontext->sections = $this->get_section_info($course);

        if (!empty($courseformatoptions['detailsheading'])) {
            $templatecontext->detailsheading = format_text($courseformatoptions['detailsheading'], FORMAT_HTML);
        }

        if (!empty($courseformatoptions['resourcesheading'])) {
            $templatecontext->resourcesheading = format_text($courseformatoptions['resourcesheading'], FORMAT_HTML);
        }

        $templatecontext->headerimageformat = format_text($courseformatoptions['headerimageformat'], FORMAT_HTML);

        if (!empty($courseformatoptions['sectionheading1'])) {
            $templatecontext->sectionheading1 = format_text($courseformatoptions['sectionheading1'], FORMAT_HTML);
            $templatecontext->sectionicon1 = $courseformatoptions['sectionicon1'];
            $sectionsummary = json_decode($courseformatoptions['sectiontext1'], true);
            $templatecontext->sectiontext1 = format_text($sectionsummary['text'], FORMAT_HTML);
        }

        if (!empty($courseformatoptions['sectionheading2'])) {
            $templatecontext->sectionheading2 = format_text($courseformatoptions['sectionheading2'], FORMAT_HTML);
            $templatecontext->sectionicon2 = $courseformatoptions['sectionicon2'];
            $sectionsummary = json_decode($courseformatoptions['sectiontext2'], true);
            $templatecontext->sectiontext2 = format_text($sectionsummary['text'], FORMAT_HTML);
        }

        if (!empty($courseformatoptions['sectionheading3'])) {
            $templatecontext->sectionheading3 = format_text($courseformatoptions['sectionheading3'], FORMAT_HTML);
            $templatecontext->sectionicon3 = $courseformatoptions['sectionicon3'];
            $sectionsummary = json_decode($courseformatoptions['sectiontext3'], true);
            $templatecontext->sectiontext3 = format_text($sectionsummary['text'], FORMAT_HTML);
        }

        if (!empty($courseformatoptions['sectionheading4'])) {
            $templatecontext->sectionheading4 = format_text($courseformatoptions['sectionheading4'], FORMAT_HTML);
            $templatecontext->sectionicon4 = $courseformatoptions['sectionicon4'];
            $sectionsummary = json_decode($courseformatoptions['sectiontext4'], true);
            $templatecontext->sectiontext4 = format_text($sectionsummary['text'], FORMAT_HTML);
        }

        if (!empty($courseformatoptions['sectionheading5'])) {
            $templatecontext->sectionheading5 = format_text($courseformatoptions['sectionheading5'], FORMAT_HTML);
            $templatecontext->sectionicon5 = $courseformatoptions['sectionicon5'];
            $sectionsummary = json_decode($courseformatoptions['sectiontext5'], true);
            $templatecontext->sectiontext5 = format_text($sectionsummary['text'], FORMAT_HTML);
        }

        if (has_capability('format/completionstats:view', context_course::instance($course->id))
            && !empty($courseformatoptions['completionstatus'])
            && $completioninfo->has_criteria()) {
            $templatecontext->completioncounts = $this->get_completion_counts($completioninfo, $course);
            $templatecontext->completionurl = new moodle_url('/report/completion/index.php', array('course' => $course->id));
        } else {
            $templatecontext->completioncounts = false;
        }

        if (has_capability('moodle/course:update', context_course::instance($course->id))
            && !empty($courseformatoptions['completionstatus'])
            && !$completioninfo->has_criteria()
            && $config->completionnag) {

            $url = new moodle_url('/course/completion.php', array('id' => $course->id));
            $messsage = get_string('nocompletion', 'format_twocol', $url->raw_out());
            $templatecontext->nocompletioncriteria = \core\notification::warning($messsage);
        }

        // Finally, check which order the columns will be displayed in.
        if (!empty($courseformatoptions['reversedisplay'])) {
            $templatecontext->reversedisplay = 1;
        }
        return $templatecontext;
    }

    /**
     * Output the html for a single section page .
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function print_single_section_page(renderer_base $output): stdClass {
        $format = $this->format;
        $displaysection = $format->get_sectionnum();
        $course = $format->get_course();

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();
        $courseformatoptions = course_get_format($course)->get_format_options();
        $imagenum = isset($courseformatoptions['sectionimage']) ? $courseformatoptions['sectionimage'] : 1;
        $headerbackcolor = isset($courseformatoptions['headerbackcolor']) ? $courseformatoptions['headerbackcolor'] : '#FFFFFF';

        // Can we view the section in question?
        if (!($sectioninfo = $modinfo->get_section_info($displaysection)) || !$sectioninfo->uservisible) {
            // This section doesn't exist or is not available for the user.
            // We actually already check this in course/view.php but just in case exit from this function as well.
            throw new \moodle_exception('unknowncoursesection', 'error', course_get_url($course),
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

        $templatecontext = new stdClass();
        $templatecontext->sectionimageformat = format_text($courseformatoptions['sectionimageformat'], FORMAT_HTML);
        $templatecontext->courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
        $templatecontext->navlinkprevious = $sectionnavlinks['previous'];
        $templatecontext->navlinknext = $sectionnavlinks['next'];
        $templatecontext->sectionname = $output->section_title_without_link($thissection, $course);
        $templatecontext->sectionnameclasses = $classes;
        $cmlistclass = $format->get_output_classname('content\\section\\cmlist');
        $cmlist = new $cmlistclass($format, $thissection);
        $templatecontext->sectioncmlist = $cmlist->export_for_template($output);
        $templatecontext->sectioncmcontrol = $output->course_section_add_cm_control(
            $course, $displaysection, $displaysection);
        $header = new \core_courseformat\output\local\content\section\summary($format, $thissection);
        $templatecontext->sectionheader = $output->render($header);
        $templatecontext->navselection = $this->section_nav_selection($course, null, $displaysection, $output);
        $templatecontext->hasprogress = $sectioncompletion->hastotal;
        $templatecontext->progress = $sectioncompletion->percent;
        $templatecontext->progresstitle = get_string('progresstitle:section', 'format_twocol');
        $templatecontext->headerbackcolor = $headerbackcolor;

        if ($imagenum !== 0) {
            $imagenum--;
            $templatecontext->sectionimage = $this->get_course_image_or_pattern($course, $output, $imagenum);
        }

        if ($format->show_editor()) {
            $controlmenuclass = $format->get_output_classname('content\\section\\controlmenu');
            $controlmenu = new $controlmenuclass($format, $thissection);
            $templatecontext->controlmenu = $controlmenu->export_for_template($output);
        }

        return $templatecontext;
    }

    /**
     * Output the html for a multiple section page
     *
     * @param renderer_base $output
     */
    public function print_multiple_section_page(renderer_base $output): stdClass {

        $data = parent::export_for_template($output);
        // Remove first section.
        unset($data->initialsection);

        return $data;

    }

    /**
     * Get section info for UI display.
     *
     * @param stdClass $course The course entry from DB
     * @return array $sections array of section names and ids.
     */
    private function get_section_info(stdClass $course): array {
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
     * @param stdClass $course
     * @return stdClass
     */
    private function get_section_completion(\section_info $section, stdClass $course): stdClass {
        $total = 0;
        $complete = 0;
        $cancomplete = isloggedin() && !isguestuser();
        $completioninfo = new completion_info($course);
        $modinfo = get_fast_modinfo($course);

        $completion = new stdClass();
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
        while ($back >= 0 and empty($links['previous'])) {
            if ($canviewhidden || $sections[$back]->uservisible) {
                $params = array();
                $params = array('class' => 'btn btn-outline-primary btn-small', 'role' => 'button');
                if (!$sections[$back]->visible) {
                    $params = array('' => 'disabled');
                }
                $previouslink = html_writer::tag('span', '', array('class' => 'fa fa-arrow-left fa-fw'));
                $previouslink .= get_string('previous', 'format_twocol') . ' ';
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
                $params = array('class' => 'btn btn-outline-primary btn-small', 'role' => 'button');
                if (!$sections[$forward]->visible) {
                    $params = array('' => 'disabled');
                }
                $nextlink = get_string('next', 'format_twocol') . ' ';
                $nextlink .= get_section_name($course, $sections[$forward]);
                $nextlink .= html_writer::tag('span', '', array('class' => 'fa fa-arrow-right fa-fw'));
                $links['next'] = html_writer::link(course_get_url($course, $forward), $nextlink, $params);
            }
            $forward++;
        }

        return $links;
    }

    /**
     * Generate the html for the 'Jump to' menu on a single section page.
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param int $displaysection The current displayed section number.
     * @param renderer_base $output
     *
     * @return string HTML to output.
     */
    protected function section_nav_selection($course, $sections, $displaysection, $output) {
        $o = '';
        $sectionmenu = array();
        $sectionmenu[course_get_url($course)->out(false)] = get_string('maincoursepage');
        $modinfo = get_fast_modinfo($course);
        $section = 1;
        $numsections = course_get_format($course)->get_last_section_number();
        while ($section <= $numsections) {
            $thissection = $modinfo->get_section_info($section);
            $showsection = $thissection->uservisible or !$course->hiddensections;
            if (($showsection) && ($section != $displaysection) && ($url = course_get_url($course, $section))) {
                $sectionmenu[$url->out(false)] = get_section_name($course, $section);
            }
            $section++;
        }

        $select = new url_select($sectionmenu, '', array('' => get_string('jumpto')));
        $select->class = 'jumpmenu border-primary';
        $select->formid = 'sectionmenu';
        $o .= $output->render($select);

        return $o;
    }
}
