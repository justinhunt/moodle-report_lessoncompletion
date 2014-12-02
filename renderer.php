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
 * Lesson Completion report renderer.
 *
 * @package    report_lessoncompletion
 * @copyright  2014 Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for lesson completion report.
 *
 * @package    report_lessoncompletion
 * @copyright  2014 Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_lessoncompletion_renderer extends plugin_renderer_base {

	public function render_reporttitle_html($course,$username) {
		$ret = $this->output->heading(format_string($course->fullname),2);
		$ret .= $this->output->heading(get_string('reporttitle','report_lessoncompletion',$username),3);
		return $ret;
	}

	public function render_empty_section_html($sectiontitle) {
		global $CFG;
		return $this->output->heading($sectiontitle, 4);
	}
	
	public function render_continuebuttons_html($course){
		$backtocourse = new single_button(
			new moodle_url('/course/view.php',array('id'=>$course->id)), 
			get_string('backtocourse','report_lessoncompletion'), 'get');
		
		$selectanother = new single_button(
			new moodle_url('/report/lessoncompletion/index.php',array('id'=>$course->id)), 
			get_string('selectanother','report_lessoncompletion'), 'get');
			
		return $this->render($backtocourse) . $this->render($selectanother);
	}

	public function render_section_html($sectiontitle, $head, $rows) {
		global $CFG;
		if(empty($rows)){
			return $this->render_empty_section_html($sectiontitle);
		}
		$htmltable = new html_table();
		
		$htr = new html_table_row();
		foreach($head as $headcell){
			$htr->cells[]=new html_table_cell($headcell);
		}
		$htmltable->data[]=$htr;
		
		foreach($rows as $row){
			$htr = new html_table_row();
			$htr->cells[]=new html_table_cell($row->description);
			$htr->cells[]=new html_table_cell($row->date);
			$htmltable->data[]=$htr;
		}
		$html = $this->output->heading($sectiontitle, 4);
		$html .= html_writer::table($htmltable);
		return $html;
		
	}
}
