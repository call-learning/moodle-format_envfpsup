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
 * Renderer for outputting the envfpsup course format.
 *
 * @package     format_envfpsup
 * @copyright   2020 Laurent David - CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/format/renderer.php');
require_once($CFG->dirroot.'/course/format/topics/renderer.php');

/**
 * Basic renderer for envfpsup format.
 *
 * @package     format_envfpsup
 * @copyright   2020 Laurent David - CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_envfpsup_renderer extends  format_section_renderer_base {
    // $title  = $this->render(new pix_icon('t/right', '', 'core')) . $title;
    protected function start_section_list() {
        return html_writer::start_tag('ul', array('class' => 'envfpsup'));
    }

    protected function end_section_list() {
        return html_writer::end_tag('ul');
    }

    protected function page_title() {
        return get_string('envfpsupoutline', 'format_envfpsup');
    }
}
