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
    protected $rows = 5;

    public function __construct($name, $options) {
        parent::__construct($name, $options);
    }
    
      /**
     * Find allowed or not allowed users of a service (depend of $this->displayallowedusers)
     * @global object $DB
     * @param <type> $search
     * @return array
     */
    public function find_users($search) {
        global $DB, $COURSE;
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

        if (empty($availableusers)) {
            return array();
        }


    
        $groupname = get_string('availableusers', 'report_lessoncompletion');
      

        return array($groupname => $availableusers);
    }
    
     /**
     * This options are automatically used by the AJAX search
     * @global object $CFG
     * @return object options pass to the constructor when AJAX search call a new selector
     */
    protected function get_options() {
        global $CFG;
        $options = parent::get_options();
        //need to be set, otherwise
        // the /user/selector/search.php
        //will fail to find this user_selector class
		$options['file'] = '/report/lessoncompletion/forms.php'; 
        return $options;
    }
}