<?php

/**
 * These cohort classes are definitions of cohorts to be mapped.  The start and end dates are adjusted from the period start and end dates
 * to reflect the adjustments found in mappings to dynamically extend these times.
 *
 * 4/12/11 - I've added validation functions for the sits_code, period_code and academic_year parameters in pattern-matching style
 * This should suffice, although there may be a case to go off to SITS to truly validate period codes and perhaps the academic years
 *
 * @author Alex Lydiate <alexlydiate [at] gmail [dot] com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 **/

/**
 * Base cohort class
 * @package    local
 * @subpackage sits
 * @copyright  2011 University of Bath
 * @author     Alex Lydiate {@link http://alexlydiate.co.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

abstract class cohort {

    public $type; //string, either 'module' or 'program'
    public $sits_code; //String, sits code
    public $academic_year; //String, academic year in SITS format

    /**
     * Will validate that an academic year string is in the appropriate SITS
     * format by way of regex
     * @param string $acyear
     * @return boolean
     */
    public function validate_acyear($acyear){
        $acyear_pat = '/^\d{4}\/\d{1}$/';
        if(preg_match($acyear_pat, $acyear)){
            return true;
        }else{
            return false;
        }
    }

    abstract public function validate_sits_code($sits_code);
}

/**
 * Base module cohort class
 * @author Alex Lydiate <a.lydiate@bath.ac.uk>
 */
abstract class gen_mod_cohort extends cohort {

    public $period_code; // string, SITS period code

    /**
     * Will check that the object's properties comprise a valid module cohort,
     * throwing an InvalidArgumentException if not
     */
    protected function validate_module(){

        if(!$this->validate_sits_code($this->sits_code)){
            throw new InvalidArgumentException('$sits_code ' . $this->sits_code . ' is invalid');
        }

        if(!$this->validate_acyear($this->academic_year)){
            throw new InvalidArgumentException('$academic_year is invalid');
        }

        if(!$this->validate_period_code($this->period_code)){
            throw new InvalidArgumentException('$period_code is invalid');
        }
    }

    /**
     * Will validate that period code string is alphanumeric and less than 5 characters
     * As new period codes can always be defined validation can't be much stricter
     * @param string $acyear
     * @return boolean
     */
    public function validate_period_code($period_code){
        if(ctype_alnum($period_code) && strlen($period_code) < 5){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Will validate that a module's sits_code is in the appropriate SITS
     * format by way of regex
     * @param string $acyear
     * @return boolean
     */
    public function validate_sits_code($sits_code){
        $modcode_pat = '/^[A-Z]{2}\d{5}$/';
        if(preg_match($modcode_pat, $sits_code)){
            return true;
        }else{
            return false;
        }
    }
}

/**
 * Program cohort class
 * @author Alex Lydiate <a.lydiate@bath.ac.uk>
 */
class program_cohort extends cohort {

    public $year_group; // string, SITS year group

    public function __construct($sits_code, $year_group, $academic_year){
        $this->type = 'program';
        $this->sits_code = $sits_code;
        $this->academic_year = $academic_year;
        $this->year_group = $year_group;

        $this->validate_program();
    }

    /**
     * Will check that the object's properties comprise a valid program cohort,
     * throwing an InvalidArgumentException if not
     */
    protected function validate_program(){
        if(!$this->validate_sits_code($this->sits_code)){
            throw new InvalidArgumentException('$sits_code ' . $this->sits_code . ' is invalid');
        }

        if(!$this->validate_acyear($this->academic_year)){
            throw new InvalidArgumentException('$academic_year is invalid');
        }

        if(!$this->validate_year_group($this->year_group)){
            throw new InvalidArgumentException('$year_group is invalid');
        }
    }

    /**
     * Validates that the given year group is numeric
     * @param int $year_group
     * @return boolean
     */
    protected function validate_year_group($year_group){
        $yeargroup_pat = '/\d/';
        if(preg_match($yeargroup_pat, $year_group)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Will validate that a program's sits_code is in the appropriate SITS
     * format by way of regex
     * @param string $acyear
     * @return boolean
     */
    public function validate_sits_code($sits_code){
        $progcode_pat = '/^[A-Z]{4}\-[A-z]{3}\d{2}$/';
        if(preg_match($progcode_pat, $sits_code)){
            return true;
        }else{
            return false;
        }
    }
}

/**
 * Main module cohort class
 * @author Alex Lydiate <a.lydiate@bath.ac.uk>
 */
class module_cohort extends gen_mod_cohort{
    public function __construct($sits_code, $period_code, $academic_year){
        $this->type = 'module';
        $this->sits_code = $sits_code;
        $this->period_code = $period_code;
        $this->academic_year = $academic_year;
        $this->validate_module();
    }
}

/**
 * Module cohort class for use by the Gradeout block
 * @author James Barrett <jb642@bath.ac.uk>
 */
class grade_module_cohort extends gen_mod_cohort {

    public $mav_occur;
    public $map_code;
    public $mab_seq;

    public function __construct($sits_code, $period_code, $academic_year, $mav_occur, $map_code, $mab_seq){
        $this->type = 'grade_module';
        $this->sits_code = $sits_code;
        $this->period_code = $period_code;
        $this->academic_year = $academic_year;
        $this->mav_occur = $mav_occur;
        $this->map_code = $map_code;
        $this->mab_seq = $mab_seq;
        $this->validate_module();
    }
}