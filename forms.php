<?php
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// This file is part of Moodle - http://moodle.org/                      //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//                                                                       //
// Moodle is free software: you can redistribute it and/or modify        //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation, either version 3 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// Moodle is distributed in the hope that it will be useful,             //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details.                          //
//                                                                       //
// You should have received a copy of the GNU General Public License     //
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.       //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Forms for Lesson Completion Report
 *
 * @package    report_lessoncompletion
 * @author     Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Justin Hunt  http://poodll.com
 */

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/user/selector/lib.php');

define('RLCR_FORMAT_HTML',0);
define('RLCR_FORMAT_PDF',1);
define('RLCR_FORMAT_EXCEL',2);
define('RLCR_ACTIVITY_LESSON','lesson');

class report_lessoncompletion_search_user_form extends moodleform {
	
    public function definition() {
        global $CFG, $USER, $OUTPUT;
        $strrequired = get_string('required');
        $mform = & $this->_form;
       
       //if admin, display a selectors so we can update contributor, site and sitecourseid
		$selector = new report_lessoncompletion_user_selector('userid', array());
		$selectorhtml = get_string('username', 'report_lessoncompletion');
		$selectorhtml .= $selector->display(true);
		
		$mform->addElement('static','userselector','',$selectorhtml);
		
		$mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
		
		$mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);
		
		$mform->addElement('hidden', 'action', 'dosearchuser');
        $mform->setType('action', PARAM_TEXT);
		$mform->addElement('submit', 'searchbutton',  get_string('dosearch_label', 'report_lessoncompletion'));
    }
}
/*
 * This class displays either all the Moodle users allowed to use a service,
 * either all the other Moodle users.
 */
class report_lessoncompletion_user_selector extends user_selector_base {

   /** @var boolean Whether the conrol should allow selection of many users, or just one. */
    protected $multiselect = false;
    /** @var int The height this control should have, in rows. */
    protected $rows = 15;
	
	protected $usecourse=false;

    public function __construct($name, $options) {
		global $COURSE;
		
		//if we arrive via ajax, the course context is lost
		//we have to add it in get_options() (.. see below)
		//and check for it here
		if(!array_key_exists('usecourse',$options)){
			$this->usecourse=$COURSE;
			$options['usecourse'] = $COURSE;
		}else{
			$this->usecourse=$options['usecourse'];
		}
		
        parent::__construct($name, $options);
    }
    
	 /**
     * Find users and return them
     * @global object $DB
     * @param <type> $search
     * @return array
     */
	public function find_users($search) {
		//get coruse context
		$context = context_course::instance($this->usecourse->id);
		
		//check if we have the capability to use the report (we arrive via ajax sometimes)
		if(!has_capability('report/lessoncompletion:view', $context)){
			return $this->fetch_empty_result();
		}
		
		//check if we are in group mode. If no, show all. If yes, check our group permissions
		$groupmode = groups_get_course_groupmode($this->usecourse);
		if($groupmode==NOGROUPS){
			return $this->find_all_users($search);
		}else{
			if(has_capability('moodle/site:accessallgroups', $context)){
				return $this->find_all_users($search);
			}else{
				return $this->find_mygroup_users($search);
			}
		}
	}
	
	 /**
     * Return a no users set (ie empty)
     * @return array
     */
	private function fetch_empty_result(){
		return array();
	}
	
      /**
     * Find all users and return them
     * @global object $DB
     * @param <type> $search
     * @return array
     */
    private function find_all_users($search) {
        global $DB;
        //by default wherecondition retrieves all users except the deleted, not
        //confirmed and guest
        list($wherecondition, $params) = $this->search_sql($search, 'u');


        $fields      = 'SELECT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(1)';

            $sql = " FROM {user} u
                 WHERE $wherecondition
                       AND u.deleted = 0 AND NOT (u.auth='webservice') ";
 
       

        list($sort, $sortparams) = users_order_by_sql('u', $search, $this->accesscontext);
        $order = ' ORDER BY ' . $sort;

        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > $this->maxusersperpage) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $availableusers = $DB->get_records_sql($fields . $sql . $order, array_merge($params, $sortparams));
		
		//if we have no users, return an empty result
        if (empty($availableusers)) {
            return $this->fetch_empty_result();
        }

		//return good data
        $groupname = get_string('availableusers', 'report_lessoncompletion');
        return array($groupname => $availableusers);
    }
	
	 /**
     * Find users in the same group and return them
     * @global object $DB $USER
     * @param <type> $search
     * @return array
     */
    private function find_mygroup_users($search) {
        global $DB, $USER;
		
		$ret=array();
        //by default wherecondition retrieves all users except the deleted, not
        //confirmed and guest
        list($wherecondition, $params) = $this->search_sql($search, 'u');


        $fields      = 'SELECT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(1)';
		$sql_template = " FROM {user} u
				INNER JOIN {groups_members} gu
				ON u.id = gu.userid
                 WHERE $wherecondition
					   AND gu.groupid in (@@GROUPIDS@@) 
                       AND u.deleted = 0 AND NOT (u.auth='webservice') "; 
		list($sort, $sortparams) = users_order_by_sql('u', $search, $this->accesscontext);
		$order = ' ORDER BY ' . $sort;

		//get group info
		$groups = groups_get_user_groups($this->usecourse->id, $USER->id);
		$groupids=array();
		foreach($groups[0] as $groupid){
			$groupids[]=$groupid;
		}
		
		//if we are not in any groups, exit
		if(empty($groupids)){return $this->fetch_empty_result();}
		
		//prepare SQL to get user count
		$gidstring = implode(',',$groupids);
		$sql = str_replace('@@GROUPIDS@@',$gidstring, $sql_template);
	
		 if (!$this->is_validating()) {
				$potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
				if ($potentialmemberscount > $this->maxusersperpage) {
					return $this->too_many_results($search, $potentialmemberscount);
				}
			}
		
		//Get a result set per group
		foreach($groupids as $groupid){
			$sql = str_replace('@@GROUPIDS@@',$groupid, $sql_template);
			$gusers = $DB->get_records_sql($fields . $sql . $order, array_merge($params, $sortparams));
			$ret[groups_get_group_name($groupid)]=$gusers;
		}
		return $ret;
	}
    
     /**
     * This options are automatically used by the AJAX search
     * @global object $CFG
     * @return object options pass to the constructor when AJAX search call a new selector
     */
    protected function get_options() {
        global $CFG,$COURSE;
        $options = parent::get_options();
		$options['usecourse']=$COURSE;

        //need to be set, otherwise
        // the /user/selector/search.php
        //will fail to find this user_selector class
		$options['file'] = '/report/lessoncompletion/forms.php'; 
        return $options;
    }
}