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
 * Display lesson completion reports for a course
 *
 * @package    report
 * @subpackage lessoncompletion
 * @copyright  2014 Justin Hunt (http://poodll.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/report/lessoncompletion/locallib.php');
require_once($CFG->dirroot.'/report/lessoncompletion/forms.php');

// course id
$id = required_param('id',PARAM_INT); 
//anything other than searchuser will show the repor
$action = optional_param('action','searchuser',PARAM_TEXT);
// the format to "show". Currently only html. Later csc and excel 
$format = optional_param('format',RLCR_FORMAT_HTML,PARAM_INT);
//user id to show report for
$userid =optional_param('userid',0,PARAM_INT);

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);

$PAGE->set_url('/report/lessoncompletion/index.php', array('id'=>$id));
$PAGE->set_pagelayout('report');

require_login($course);
$context = context_course::instance($course->id);
require_capability('report/lessoncompletion:view', $context);

// Trigger an activity report viewed event.
$event = \report_lessoncompletion\event\activity_report_viewed::create(array('context' => $context));
$event->trigger();

$strlessoncompletion = get_string('pluginname', 'report_lessoncompletion');
$PAGE->set_title($course->shortname .': '. $strlessoncompletion);
$PAGE->set_heading($course->fullname);
$renderer = $PAGE->get_renderer('report_lessoncompletion');
$rlc_helper = new report_lessoncompletion_helper();

//if showing our form do that
switch($action){
	case 'searchuser':
		echo $renderer->header();
		echo $renderer->heading(format_string($course->fullname));
		$searchform = new report_lessoncompletion_search_user_form();
		$data=new stdClass();
		$data->id = $id;
		$searchform->set_data($data);
		$searchform->display();
		echo $renderer->footer();
		return;
		
	case 'dosearchuser':	
	default:
		//anything other than "searchuser" means display report.
		//so we just continue on
}
	

//Check that the userid passed in is ok
$selecteduser=$DB->get_record('user',array('id'=>$userid));
if(!$selecteduser){
	print_error('that is not a real user id');
	die;
}

//get completions data
$completions = $rlc_helper->get_completions_by_user($selecteduser->id);

//get sections data
$modinfo = get_fast_modinfo($course);

//match sections data with completion data.
///fill $allsections with results to be displayed.
$haslesson=false;
$prevsectionnum=-1;
$allsections = array();
$sectiontitle ="";
$items=array();

foreach ($modinfo->sections as $sectionnum=>$section) {
	if($prevsectionnum ==-1){$prevsectionnum = $sectionnum;}
    foreach ($section as $cmid) {
        $cm = $modinfo->cms[$cmid];
		//if its a new section. set the current section's data to allsections
		//then setup the next section		
        if ($prevsectionnum != $sectionnum) {
			if($haslesson){
				$allsections[]=array($sectiontitle,$items);//$renderer->render_section($sectiontitle, $head, $items);
			}else{
				$allsections[]=array($sectiontitle,array());//$renderer->render_empty_section($sectiontitle);
			}
			$haslesson=false;
            $sectiontitle = get_section_name($course, $sectionnum);
			unset($items);
			$items=array();
            $prevsectionnum = $sectionnum;
		//if its a lesson item, store it ready for display or export
        }else{
			if($cm->modname==RLCR_ACTIVITY_LESSON && $cm->visible){
				$haslesson=true;
				$item = new stdClass();
				$item->description=$cm->name;
				if($completions && array_key_exists($cm->id,$completions)){
					$item->date=date("Y-m-d",$completions[$cm->id]);
				}else{
					$item->date= get_string('incomplete','report_lessoncompletion');
				}
				$items[]=$item;
			}//end of if 'lesson'
		}//end of if 'end of section'
	}//end of sections  inner loop
}//end of sections outer loop


//Prepare and output the all sections data we collected above
$head=array(get_string('description','report_lessoncompletion'),get_string('completiondate','report_lessoncompletion'));
switch($format){
	case RLCR_FORMAT_HTML:
		echo $renderer->header();
		echo $renderer->render_reporttitle_html($course,fullname($selecteduser));
		foreach($allsections as $thesection){
			//$thesection[0]=section title / $thesection[1]=array of lesson titles and compl. dates
			echo $renderer->render_section_html($thesection[0], $head, $thesection[1]);
		}
		echo $renderer->render_continuebuttons_html($course);
		echo $renderer->footer();
		break;
		
	case RLCR_FORMAT_CSV:
	case RLCR_FORMAT_EXCEL:
		//add renderer calls here
}