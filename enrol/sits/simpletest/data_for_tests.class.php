<?php
/*
* @package    enrol
* @subpackage sits
* @copyright  2011 University of Bath
* @author     Alex Lydiate {@link http://alexlydiate.co.uk}
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
require_once($CFG->dirroot . '/blocks/sits/lib/cohort.class.php');
require_once($CFG->dirroot . '/blocks/sits/lib/mapping.class.php');

define('LIVE_SERVER_NAME', 'moodle.bath.ac.uk');

class data_for_tests {
	
    public $test_username = 'al412';
    public $new_username = 'hinkley_point_powerstation';
    public $test_course_id = 1000;
    public $false_course_id = 99999999999999999;
    public $test_mod_code = 'MN20208';
    public $test_period = 'S1';
    public $test_acyear = '2010/1';
    
    public $test_cohort; //cohort object, a the relevant data for a valid Moodle cohort;
    public $test_mapping;

    public function __construct(){
        $now = new DateTime();
        $this->test_cohort = new module_cohort($this->test_mod_code, $this->test_period, $this->test_acyear);
        $this->test_mapping = new mapping($this->test_course_id, $this->test_cohort, $now, $now->add(new DateInterval('P1Y')));
    }
}