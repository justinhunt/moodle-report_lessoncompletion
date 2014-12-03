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
		return "";
		//return $this->output->heading($sectiontitle, 4);
	}
	
	public function render_exportbuttons_html($course,$selecteduser){
		$pdf = new single_button(
			new moodle_url('/report/lessoncompletion/index.php',array('id'=>$course->id, 'userid'=>$selecteduser->id, 'format'=>RLCR_FORMAT_PDF, 'action'=>'doexport')),
			get_string('exportpdf','report_lessoncompletion'), 'get');
		
		$excel = new single_button(
			new moodle_url('/report/lessoncompletion/index.php',array('id'=>$course->id, 'userid'=>$selecteduser->id, 'format'=>RLCR_FORMAT_EXCEL, 'action'=>'doexport')), 
			get_string('exportexcel','report_lessoncompletion'), 'get');

		return html_writer::div( $this->render($pdf) . $this->render($excel),'report_lessoncompletion_listbuttons');
	}
	
	public function render_continuebuttons_html($course){
		$backtocourse = new single_button(
			new moodle_url('/course/view.php',array('id'=>$course->id)), 
			get_string('backtocourse','report_lessoncompletion'), 'get');
		
		$selectanother = new single_button(
			new moodle_url('/report/lessoncompletion/index.php',array('id'=>$course->id)), 
			get_string('selectanother','report_lessoncompletion'), 'get');
			
		return html_writer::div($this->render($backtocourse) . $this->render($selectanother),'report_lessoncompletion_listbuttons');
	}

	public function render_section_html($sectiontitle, $head, $rows) {
		global $CFG;
		if(empty($rows)){
			return $this->render_empty_section_html($sectiontitle);
		}
		
		//set up our attributes
		$tableattributes = array('class'=>'generaltable report_lessoncompletion_table');
		$headrow_attributes = array('class'=>'report_lessoncompletion_headrow');
		$desccell_attributes = array('class'=>'report_lessoncompletion_desccell');
		$datecell_attributes = array('class'=>'report_lessoncompletion_datecell');
		
		$htmltable = new html_table();
		$htmltable->attributes = $tableattributes;
		
		
		$htr = new html_table_row();
		$htr->attributes = $headrow_attributes;
		foreach($head as $headcell){
			$htr->cells[]=new html_table_cell($headcell);
		}
		$htmltable->data[]=$htr;
		
		foreach($rows as $row){
			$htr = new html_table_row();
			//set up descrption cell
			$desccell = new html_table_cell($row->description);
			$desccell->attributes =$desccell_attributes ;
			//set up date cell
			$datecell = new html_table_cell($row->date);
			$datecell->attributes =$datecell_attributes ;
			//add to row
			$htr->cells[]=$desccell;
			$htr->cells[]=$datecell;
			$htmltable->data[]=$htr;
		}
		$html = $this->output->heading($sectiontitle, 4);
		$html .= html_writer::table($htmltable);
		return $html;
		
	}
}
