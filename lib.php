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
 * ENVF Parcoursup course format. Very similar to the Topics format.
 *
 * @package     format_envfpsup
 * @copyright   2020 Laurent David - CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/course/format/lib.php');

/**
 * Main class for the ENVF course format
 *
 * @package     format_envfpsup
 * @copyright   2020 Laurent David - CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_envfpsup extends format_base {

    /**
     * Returns true if this course format uses sections
     *
     * This function may be called without specifying the course id
     * i.e. in {@link course_format_uses_sections()}
     *
     * Developers, note that if course format does use sections there should be defined a language
     * string with the name 'sectionname' defining what the section relates to in the format, i.e.
     * $string['sectionname'] = 'Section';
     * or
     * $string['sectionname'] = 'Week';
     *
     * @return bool
     */
    public function uses_sections() {
        return true; // This also enables the drag and drop for activities.
    }

    /**
     * Returns the information about the ajax support in the given source format
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    /**
     * Custom action after section has been moved in AJAX mode
     *
     * Used in course/rest.php
     *
     * @return array This will be passed in ajax respose
     */
    public function ajax_section_move() {
        global $PAGE;
        $titles = array();
        $course = $this->get_course();
        $modinfo = get_fast_modinfo($course);
        $renderer = $this->get_renderer($PAGE);
        if ($renderer && ($sections = $modinfo->get_section_info_all())) {
            foreach ($sections as $number => $section) {
                $titles[$number] = $renderer->section_title($section, $course);
            }
        }
        return array('sectiontitles' => $titles, 'action' => 'move');
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * Use section name is specified by user. Otherwise use default ("Topic #")
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);
        if ((string) $section->name !== '') {
            return format_string($section->name, true,
                array('context' => context_course::instance($this->courseid)));
        } else {
            return $this->get_default_section_name($section);
        }
    }

    /**
     * Whether this format allows to delete sections
     *
     * Do not call this function directly, instead use {@link course_can_delete_section()}
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section) {
        return true;
    }

    /**
     * Loads all the activities of section > 0 into the navigation
     *
     * This method is called from {@link global_navigation::load_course_sections()}
     *
     * By default the method {@link global_navigation::load_generic_course_sections()} is called
     *
     * When overwriting please note that navigationlib relies on using the correct values for
     * arguments $type and $key in {@link navigation_node::add()}
     *
     * Example of code creating a section node:
     * $sectionnode = $node->add($sectionname, $url, navigation_node::TYPE_SECTION, null, $section->id);
     * $sectionnode->nodetype = navigation_node::NODETYPE_BRANCH;
     *
     * Example of code creating an activity node:
     * $activitynode = $sectionnode->add($activityname, $action, navigation_node::TYPE_ACTIVITY, null, $activity->id, $icon);
     * if (global_navigation::module_extends_navigation($activity->modname)) {
     *     $activitynode->nodetype = navigation_node::NODETYPE_BRANCH;
     * } else {
     *     $activitynode->nodetype = navigation_node::NODETYPE_LEAF;
     * }
     *
     * Also note that if $navigation->includesectionnum is not null, the section with this relative
     * number needs is expected to be loaded
     *
     * @param global_navigation $navigation
     * @param navigation_node $node The course node within the navigation
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function extend_course_navigation($navigation, navigation_node $node) {
        // Remove the course list from the flat nav bar.
        $mycourses = $navigation->get('mycourses', navigation_node::TYPE_ROOTNODE);
        $mycourses->showinflatnavigation = false;
        foreach ($mycourses->children as $allmycourses) {
            $allmycourses->showinflatnavigation = false;
        }

        if ($course = $this->get_course()) {
            global $CFG;
            require_once($CFG->dirroot . '/course/lib.php');
            foreach ($navigation->children as $children) {
                $children->remove();
            }
            $navigation->add_node($node);
            $modinfo = get_fast_modinfo($course);
            $sections = $modinfo->get_section_info_all();

            foreach ($sections as $key => $section) {
                if ($key == 0) {
                    continue; // Skip section 0.
                }
                // Clone and unset summary to prevent $SESSION bloat (MDL-31802).
                $sections[$key] = clone($section);
                unset($sections[$key]->summary);
                $sections[$key]->hasactivites = false;
                if (!array_key_exists($section->section, $modinfo->sections)) {
                    continue;
                }
                foreach ($modinfo->sections[$section->section] as $cmid) {
                    $cm = $modinfo->cms[$cmid];
                    if ($cm->icon) {
                        $icon = new pix_icon($cm->icon, get_string('modulename', $cm->name), $cm->iconcomponent);
                    } else {
                        $icon = new pix_icon('icon', get_string('modulename', $cm->modname), $cm->modname);
                    }

                    // Prepare the default name and url for the node.
                    $url = $cm->url;
                    $activitydisplay = false;
                    $activitynodetype = navigation_node::TYPE_ACTIVITY;
                    if (!$url) {
                        $activityurl = null;
                    } else {
                        $activityurl = $url->out();
                        $activitydisplay = $cm->uservisible ? true : false;
                        // Display the activity only for editing teacher in the menu
                        // If not we check access.
                        if (global_navigation::module_extends_navigation($cm->modname)) {
                            $activitynodetype = navigation_node::NODETYPE_BRANCH;
                        }
                    }
                    $activityname = format_string($cm->name, true, array('context' => context_module::instance($cm->id)));
                    $action = new moodle_url($activityurl);

                    if ($activitydisplay) {
                        $node->add($activityname, $action, $activitynodetype, null, $cm->id, $icon);
                    }
                }
            }
        }
    }

    /**
     * Definitions of the additional options that this course format uses for course
     *
     * Course display must be defined
     * - coursedisplay
     *
     * @param bool $foreditform
     * @return array of options
     */
    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;
        if ($courseformatoptions === false) {
            $courseconfig = get_config('moodlecourse');
            $courseformatoptions = array(
                'coursedisplay' => array(
                    'label' => new lang_string('coursedisplay'),
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            COURSE_DISPLAY_SINGLEPAGE => new lang_string('coursedisplay_single'),
                            COURSE_DISPLAY_MULTIPAGE => new lang_string('coursedisplay_multi')
                        )
                    ),
                    'help' => 'coursedisplay',
                    'help_component' => 'moodle'
                ),
            );
        }
        return $courseformatoptions;
    }
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return \core\output\inplace_editable
 */
function format_envfpsup_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            array($itemid, 'envfpsup'), MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}

