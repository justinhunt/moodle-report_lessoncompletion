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
 * This file contains functions used by the lessoncompletion reports
 *
 * @package    report
 * @subpackage lessoncompletion
 * @copyright  2014 Justin Hunt (http://poodll.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->dirroot.'/course/lib.php');

/*
 * This class displays either all the Moodle users allowed to use a service,
 * either all the other Moodle users.
 */
class report_lessoncompletion_helper{
	function get_completions_by_user($userid){
			global $DB;
			$data = $DB->get_records_menu('course_modules_completion',array('userid'=>$userid,'completionstate'=>1),'coursemoduleid','coursemoduleid,timemodified');
			return $data;
	}
}
