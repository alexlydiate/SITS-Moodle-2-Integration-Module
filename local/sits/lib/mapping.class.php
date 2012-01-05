<?php
/**
 * Defines a mapping of a SITS cohort to a Moodle course
 * @package moodle_sits_block
 * @author Alex Lydiate <alexlydiate [at] gmail [dot] com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
class mapping {

    public $courseid; //Moodle course id of course to which the mapping is made.
    public $cohort; //Object of Cohort class
    public $start; //DateTime object - Date for enrolments to start
    public $end; //DateTime object - Date for enrolments to end
    public $manual; //Boolean, has this cohort been manually mapped, and so should not be automatically unenrolled
    public $specified; //Bolean, the unenrol date has been specified by a user, should not be synced with SITS
    public $default; //Boolean, is this the course's current default cohort
    public $id; //Moodle table ID if available, null if not
    public $active; //Boolean, false if the mapping has been removed by a user action
     
    public function __construct($courseid, $cohort, $start, $end, $manual = false, $default = false, $id = null, $specified = false, $active = null){
        $this->courseid = $courseid;
        $this->cohort = $cohort;
        $this->start = $start;
        $this->end = $end;
        $this->manual = $manual;
        $this->default = $default;
        $this->specified = $specified;
        $this->id = $id;
        if(is_null($active)){ //active parameter not give, work it out from the others
            $now = new DateTime();
            if($this->end < $now && !$this->manual){
                //Mapping is auto or specified and has expired, make as inactive
                $this->active = false;
            }else{
                $this->active = true;
            }
        }else{
            $this->active = $active;
        }
        $this->validate();
    }
    
    private function validate(){
        if($this->specified && $this->manual){
            throw new Exception('A mapping cannot be both Specified and Manual');
        }
        if($this->default && $this->manual){
            throw new Exception('A mapping cannot be both Default and Manual');
        }
    }
}