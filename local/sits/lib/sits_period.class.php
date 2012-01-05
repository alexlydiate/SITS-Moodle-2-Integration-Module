<?php
/** 
 * Defines a period of time as represent by a SITS period code
 * @package    local
 * @subpackage sits
 * @copyright  2011 University of Bath
 * @author     Alex Lydiate {@link http://alexlydiate.co.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sits_period {

    public $code; //SITS period code string
    public $academic_year; //SITS academic year string
    public $start; //DateTime object instantiated from $start_date
    public $end; //DateTime object instantiated from $end_date
     
    public function __construct($code, $academic_year, $start_date, $end_date){
        $this->code = $code;
        $this->academic_year = $academic_year;
        $this->start = new DateTime($start_date);
        if($this->start === false){
            return false;
        }
        $this->end = new DateTime($end_date);
        if($this->end === false){
            return false;
        }
    }
}

class period_alteration extends sits_period{
    
    public $id; //Integer, id of period alteration, if it exists, null otherwise
    public $revert; //Boolean, whether the period will be reverted on the next update
    
public function __construct($code, $academic_year, $start_date, $end_date, $revert, $id = null){
        $this->code = $code;
        $this->academic_year = $academic_year;
        $this->start = new DateTime($start_date);
        if($this->start === false){
            return false;
        }
        $this->end = new DateTime($end_date);
        if($this->end === false){
            return false;
        }
        $this->id = $id;
        $this->revert = $revert;
    }
}