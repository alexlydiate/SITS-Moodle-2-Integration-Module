<?php
/*
* @package    local
* @subpackage sits
* @copyright  2011 University of Bath
* @author     Alex Lydiate {@link http://alexlydiate.co.uk}
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
require_once('i_sits_db.interface.php');
require_once('sits_period.class.php');

abstract class sits_db implements i_sits_db {

    protected $dbh; //OCI/Oracle database handle for SITS
    protected $report; //Object of report class
    protected $sits_code; //String
    protected $academic_year; //String, in SITS format
    protected $period_code; // String, refers to SITS period code
    protected $year_group; //For programs, integer, 0 denotes to all year groups
    protected $bucs_id; // Stings, BUCS id
    //Programs sql
    protected $sql_prog_students;
    protected $sql_prog_tutors;
    protected $sql_prog_other_tutors;
    protected $sql_student_prog_members; //SQL string to get student cohort
    protected $sql_all_prog_members; //SQL string to get all of them
    protected $sql_all_programs; //SQL string to return all modules for the current academic year
    protected $sql_validate_program; //SQL string to validate a program cohort

    //Programs prepared statements
    protected $prog_student_cohort_stm;//OCI parsed and variable bound query ready to be executed;
    protected $all_progs_cohort_stm; // OCI parsed and variable bound query ready to be executed;
    protected $all_student_prog_cohort_stm;// OCI parsed and variable bound query ready to be executed;
    protected $prog_cohort_stm; //OCI parsed and variable bound query ready to be executed;
    protected $all_programs_stm;//OCI parsed and variable bound query ready to be executed;
    protected $validate_program_stm;//OCI parsed and variable bound query ready to be executed;
    //Modules sql
    protected $sql_mod_students;
    protected $sql_mod_tutors;
    protected $sql_mod_other_tutors;
    protected $sql_student_mod_members; //SQL string to get student cohort
    protected $sql_all_mod_members; //SQL string to get all of them
    protected $sql_all_modules; //SQL string to return all modules for the current academic year
    protected $sql_validate_module; //SQL string to validate a module cohort
    //Modules prepared statements
    protected $mod_student_cohort_stm;//OCI parsed and variable bound query ready to be executed;
    protected $mod_cohort_stm; //OCI parsed and variable bound query ready to be executed;
    protected $all_modules_stm;//OCI parsed and variable bound query ready to be executed;
    protected $validate_module_stm;//OCI parsed and variable bound query ready to be executed;
    //Period sql
    protected $sql_period_for_code;
    protected $sql_current_period_codes;
    //Period prepared statement
    protected $period_for_code_stm;
    protected $current_period_codes_stm;
    //Validate ID sql
    protected $sql_validate_bucs_id;
    //Validate ID statement
    protected $validate_bucs_id_stm;
    // qa53
    protected $sql_get_spr_from_bucs_id;
    protected $get_spr_from_bucs_id_stm;
    protected $sql_insert_agreed_grade;
    protected $insert_agreed_grade_stm;
    protected $sql_insert_agreed_grade_smr;
    protected $insert_agreed_grade_smr_stm;
    protected $sql_insert_agreed_grade_smrt;
    protected $insert_agreed_grade_smrt_stm;

    protected $sql_update_agreed_grade;
    protected $update_agreed_grade_stm;
    protected $sql_update_agreed_grade_smr;
    protected $update_agreed_grade_smr_stm;
    protected $sql_update_agreed_grade_smrt;
    protected $update_agreed_grade_smrt_stm;
    
    protected $current_academic_year; //current academic year string
    protected $current_academic_year_start; //DateTime object
    protected $current_academic_year_end; //DateTime object
    protected $last_academic_year; //Last academic year string
    protected $next_academic_year; //Next academic year string
    
    protected $date; //DateTime object of right now

    //Implementation of i_sits_db

    public function mod_student_members_rh(&$module_cohort){
        return $this->return_mod_members_rh($this->mod_student_cohort_stm, $module_cohort);
    }

    public function mod_members_rh(&$module_cohort){
        return $this->return_mod_members_rh($this->mod_cohort_stm, $module_cohort);
    }

    public function prog_members_rh(&$program_cohort){ //FIXME this function needs tidying up
    return $this->return_prog_members_rh($program_cohort);
    }

    public function prog_student_members_rh(&$program_cohort){
        return $this->return_prog_members_rh($program_cohort, true);
    }

    public function mods_for_academic_year($acyear){
        $this->academic_year = $acyear;
        if(!oci_execute($this->all_modules_stm)){
            $this->report->log_report(2, sprintf('Failed to execute all modules query for %s',
            $this->academic_year
            ));
            return false;
        }
        return $this->all_modules_stm;
    }

    public function progs_for_academic_year($acyear){
        $this->academic_year = $acyear;
        if(!oci_execute($this->all_programs_stm)){
            $this->report->log_report(2, sprintf('Failed to execute all programs query for %s',
            $this->academic_year
            ));
            return false;
        }
        return $this->all_programs_stm;
    }

    public function validate_module(&$module_cohort){

        $this->sits_code = $module_cohort->sits_code;
        $this->period_code = $module_cohort->period_code;
        $this->academic_year = $module_cohort->academic_year;

        if(!oci_execute($this->validate_module_stm)){
            return false;
        }

        $result = oci_fetch_object($this->validate_module_stm);

        if(is_object($result) && count($result) == 1){
            return true; // The three properties of the $module cohort return exactly one module instance from SITS
        }else{
            return false; //They don't, therefore $module cohort does not refer to a unique cohort.
            //Perhaps in the fullness of time report this fact, iterating over result?
        }
    }

    public function validate_program(&$program_cohort){

        $this->sits_code = $program_cohort->sits_code;
        $this->academic_year = $program_cohort->academic_year;
        if($program_cohort->year_group == 'All'){
            $this->year_group = '%';
        }else{
            $this->year_group = $program_cohort->year_group;
        }

        if(!oci_execute($this->validate_program_stm)){
            return false;
        }

        $result = oci_fetch_object($this->validate_program_stm);

        if(count($result) == 1){
            return true; // The three properties of the $program_cohort cohort return exactly one program instance from SITS
        }else{
            return false; //They don't, therefore $program_cohort cohort does not refer to a unique cohort.
            //Perhaps in the fullness of time report this fact, iterating over result?
        }
    }

    public function get_period_for_code($period_code, $academic_year){

        $this->period_code = $period_code;
        $this->academic_year = $academic_year;

        if(!oci_execute($this->period_for_code_stm)){
            return false;
        }

        $result = oci_fetch_object($this->period_for_code_stm);
        if(is_object($result)){
            return new sits_period($this->period_code,$this->academic_year, $result->start, $result->end);
        }else{
            return false;
        }
    }

    public function current_period_codes_rh(){
        if(!oci_execute($this->current_period_codes_stm)){
            $this->report->log_report(2, 'Failed to execute current period codes statement');
            return false;
        }
        return $this->current_period_codes_stm;
    }

    //End on implementation of i_sits_sync
    //Other services

    /**
     * Returns an object containing the SAMIS period code, acyear and the start end dates for that period if fed a valid period code
     * and academic year combination
     * @param string $bucs_id
     * @return period object containing the SAMIS period code, acyear and the start end dates for that period, or false
     */
    public function validate_bucs_id($bucs_id){

        if(!$this->validate_bucs_id_string($bucs_id)){
            return false;
        }
        $this->bucs_id = $bucs_id;
        if(!oci_execute($this->validate_bucs_id_stm)){
            return false;
        }
        return oci_fetch_object($this->validate_bucs_id_stm);
    }
    
    /**
     * 
     * Returns the current academic year SITS string
     * @return string
     */
    public function get_current_academic_year(){
    	return $this->current_academic_year;
    }
    
    /**
     * 
     * Returns the last academic year SITS string
     * @return string
     */
    public function get_last_academic_year(){
    	return $this->last_academic_year;
    }
    
    /**
     * 
     * Returns the next academic year SITS string
     * @return string
     */
    public function get_next_academic_year(){
    	return $this->next_academic_year; 
    }
    
    /**
     * 
     * Returns the current academic year start date
     * @return DateTime object
     */
    public function get_current_academic_year_start(){
    	return $this->current_academic_year_start;
    }
    
    /**
     *
     * Returns the current academic year end date
     * @return DateTime object
     */
    public function get_current_academic_year_end(){
    	return $this->current_academic_year_end;
    }
        
    /**
    * sets current academic year in the format 'yyyy/+1' style, such as 2010/1, 2011/2 and the lke
    */
    protected function set_current_academic_year(){
    	$date_array = explode('-', $this->date->format('m-Y'));
    	if(intval($date_array[0]) > 7){
    		$this->current_academic_year = strval(intval($date_array[1])) . '/' . substr(strval(intval($date_array[1]) + 1), -1);
    		$this->current_academic_year_start = new DateTime($date_array[1] . '-07-31 00:00:00');
    		$this->academic_year_end = new DateTime($date_array[1] + 1 . '-07-31 00:00:00');
    	}else{
    		$this->current_academic_year = strval(intval($date_array[1]) - 1) . '/' . substr(strval(intval($date_array[1])), -1);
    		$this->current_academic_year_start = new DateTime($date_array[1] - 1 . '-07-31 00:00:00');
    		$this->current_academic_year_end = new DateTime($date_array[1] . '-07-31 00:00:00');
    	}
    }
    
    /**
     * sets last academic year in the format 'yyyy/+1' style, such as 2010/1, 2011/2 and the lke
     */
    protected function set_last_academic_year(){
    	$date_array = explode('-', $this->date->format('m-Y'));
    	if(intval($date_array[0]) > 7){
    		$this->last_academic_year = strval(intval($date_array[1]) - 1) . '/' . substr(strval(intval($date_array[1])), -1);
    	}else{
    		$this->last_academic_year = strval(intval($date_array[1]) - 2) . '/' . substr(strval(intval($date_array[1]) - 1), -1);
    	}
    }
    
    /**
     * sets n academic year in the format 'yyyy/+1' style, such as 2010/1, 2011/2 and the lke
     */
    protected function set_next_academic_year(){
    	$date_array = explode('-', $this->date->format('m-Y'));
    	if(intval($date_array[0]) < 8){
    		$this->next_academic_year = strval(intval($date_array[1])) . '/' . substr(strval(intval($date_array[1]) + 1), -1);
    	}else{
    		$this->next_academic_year = strval(intval($date_array[1]) + 1) . '/' . substr(strval(intval($date_array[1]) + 2), -1);
    	}
    }

    public function validate_bucs_id_string($bucs_id){
        if(!ctype_alnum($bucs_id)){
            return false;
        }elseif(strlen($bucs_id) > 8){
            return false;
        }else{
            return true;
        }
    }

    public function insert_agreed_grade( &$student,&$grade,&$cohort ){

        $this->spr_code = $this->get_spr_from_bucs_id($student->username,$cohort)->SPR_CODE;
        $this->period_code = $cohort->period_code;
        $this->sits_code = $cohort->sits_code;
        $this->academic_year = $cohort->academic_year;
        $this->mark = $grade->sumgrades;
        $this->sas_agrg = $grade->sas_agrg;
        $this->smr_agrd = $grade->smr_agrd;
        $this->mav_occur = $cohort->mav_occur;
        $this->map_code = $cohort->map_code;
        $this->mab_seq = $cohort->mab_seq;
        return (oci_execute($this->insert_agreed_grade_stm)
        && oci_execute($this->insert_agreed_grade_smr_stm)
        //&& oci_execute($this->insert_agreed_grade_smrt_stm)
        );
    }
/*
 * NB update now INSERTS to SMR_T as sas1b does not populate it automatically
 * therefore the conditionally called INSERT function above will not need
 */
    public function update_agreed_grade( &$student,&$grade,&$cohort ){

        $this->spr_code = $this->get_spr_from_bucs_id($student->username,$cohort)->SPR_CODE;
        $this->period_code = $cohort->period_code;
        $this->sits_code = $cohort->sits_code;
        $this->academic_year = $cohort->academic_year;
        $this->sas_agrg = $grade->sas_agrg;
        $this->smr_agrd = $grade->smr_agrd;
        $this->mav_occur = $cohort->mav_occur;
        $this->map_code = $cohort->map_code;
        $this->mab_seq = $cohort->mab_seq;
        oci_execute($this->update_agreed_grade_stm);
        oci_execute($this->update_agreed_grade_smr_stm);
        oci_execute($this->insert_agreed_grade_smrt_stm);
        return (oci_num_rows($this->update_agreed_grade_stm)
        && oci_num_rows($this->update_agreed_grade_smr_stm)
        && oci_num_rows($this->insert_agreed_grade_smrt_stm));
    }

    //Protected functions

    /*
     * Given a valid, referenced module members statement and module cohort
     * 		 this function sets the relevant bound variables and executes
     * @param prepared Oracle statement $stm
     * @param module_sql_prog_other_tutorscohort object $module_cohort
     * @return mixed executed statement if successful, false on faliure
     */
    protected function return_mod_members_rh(&$stm, &$module_cohort){

        $this->sits_code = $module_cohort->sits_code;
        $this->period_code = $module_cohort->period_code;
        $this->academic_year = $module_cohort->academic_year;

        if(!oci_execute($stm)){
            $this->report->log_report(2, sprintf('Failed to execute module cohort query for %s:%s:%s',
            $module_cohort->sits_code,
            $module_cohort->period_code,
            $module_cohort->academic_year
            ));
            return false;
        }
        return $stm;
    }

    /*
     * Given a valid program cohort this function sets the relevant bound variables and executes a program members statement
     * @param module_cohort object $module_cohort
     * @param boolean $student if true, returns just student members, if false students, tutors and other tutors
     * @return mixed executed statement if successful, false on faliure
     */
    protected function return_prog_members_rh(&$program_cohort, $student = false){

        $this->sits_code = $program_cohort->sits_code;
        $this->year_group = $program_cohort->year_group;
        $this->academic_year = $program_cohort->academic_year;

        if($student){
            $all_years_stm = $this->all_student_prog_cohort_stm;
            $single_year_stm = $this->prog_student_cohort_stm;
        }else{
            $all_years_stm = $this->all_prog_cohort_stm;
            $single_year_stm = $this->prog_cohort_stm;
        }

        if($this->year_group == 0){ //0 denotes all year groups,
            if(!oci_execute($all_years_stm)){
                $this->report->log_report(2, sprintf('Failed to execute all program cohort query for %s:%s',
                $program_cohort->sits_code,
                $program_cohort->academic_year
                ));
                return false;
            }else{
                return $all_years_stm;
            }
        }else{
            if(!oci_execute($single_year_stm)){
                $this->report->log_report(2, sprintf('Failed to execute program cohort query for %s:%s:%s',
                $program_instance->sits_code,
                $program_instance->year_group,
                $program_instance->academic_year
                ));
                return false;
            }else{
                return $single_year_stm;
            }
        }
    }
    /**
     * returns the student number for a bucs_id
     * @param unknown_type $bucs_id
     */
    protected function get_spr_from_bucs_id($bucs_id,&$cohort){
        $this->bucs_id = $bucs_id;
        $this->ac_year = $cohort->academic_year;
        if(!oci_execute($this->get_spr_from_bucs_id_stm)){
            return false;
        }
        return oci_fetch_object($this->get_spr_from_bucs_id_stm);
    }
    
    //Programs SQL setters

    /**
     * sets the property sql_all_programs
     */
    abstract protected function set_sql_all_programs();
    /**
     * sets the property sql_prog_students
     */
    abstract protected function set_sql_prog_students();
    /**
     * sets the property sql_prog_tutors
     */
    abstract protected function set_sql_prog_tutors();
    /**
     * sets the property sql_prog_other_tutors
     */
    abstract protected function set_sql_prog_other_tutors();
    /**
     * sets the property sql_validate_program
     */
    abstract protected function set_sql_validate_program();
    /**
     * sets the property sql_all_prog_members
     */
    abstract protected function set_sql_all_prog_members();
    /**
     * sets the property sql_student_prog_members
     */
    abstract protected function set_sql_student_prog_members();

    //Programs prepared statement setters

    /**
     * sets the property prog_cohort_stm
     * @return boolean
     */
    abstract protected function set_prog_cohort_stm();
    /**
     * sets the property all_prog_cohort_stm
     * @return boolean
     */
    abstract protected function set_all_prog_cohort_stm();
    /**
     * sets the property all_prog_cohort_stm
     * @return boolean
     */
    abstract protected function set_validate_program_stm();
    /**
     * sets the property all_programs_stm
     * @return boolean
     */
    abstract protected function set_all_programs_stm();
    /**
     * sets the property prog_student_cohort_stm
     * @return boolean
     */
    abstract protected function set_prog_student_cohort_stm();
    /**
     * sets the property all_student_prog_cohort_stm
     * @return boolean
     */
    abstract protected function set_all_student_prog_cohort_stm();

    //Modules SQL setters

    /**
     * sets the property sql_all_modules
     */
    abstract protected function set_sql_all_modules();
    /**
     * sets the property sql_mod_students
     */
    abstract protected function set_sql_mod_students();
    /**
     * sets the property sql_mod_tutors
     */
    abstract protected function set_sql_mod_tutors();
    /**
     * sets the property sql_mod_other_tutors
     */
    abstract protected function set_sql_mod_other_tutors();
    /**
     * sets the property sql_validate_module
     */
    abstract protected function  set_sql_validate_module();
    /**
     * sets the property sql_all_mod_members
     */
    abstract protected function  set_sql_all_mod_members();
    /**
     * sets the property sql_student_mod_members
     */
    abstract protected function  set_sql_student_mod_members();

    //Modules prepared statement setters

    /**
     * sets the property mod_cohort_stm
     * @return boolean
     */
    abstract protected function set_mod_cohort_stm();
    /**
     * sets the property all_modules_stm
     * @return boolean
     */
    abstract protected function set_all_modules_stm();
    /**
     * sets the property validate_module_stm
     * @return boolean
     */
    abstract protected function set_validate_module_stm();
    /**
     * sets the property mod_student_cohort_stm
     * @return boolean
     */
    abstract protected function set_mod_student_cohort_stm();

    //Other sql setters
    /**
    * sets the property sql_period_for_code
    */
    abstract protected function set_sql_period_for_code();

    /**
     * sets the property sql_validate_bucs_id
     */
    abstract protected function set_sql_validate_bucs_id();

    //Other prepared statement setters

    /**
     * sets the property period_for_code_stm
     * @return boolean
     */
    abstract protected function set_period_for_code_stm();

    /**
     * sets the property set_validate_bucs_id_stm
     */
    abstract protected function set_validate_bucs_id_stm();

    abstract protected function set_sql_get_spr_from_bucs_id();
    abstract protected function set_get_spr_from_bucs_id_stm();
    abstract protected function set_sql_insert_agreed_grade();
    abstract protected function set_insert_agreed_grade_stm();
    abstract protected function set_sql_insert_agreed_grade_smr();
    abstract protected function set_insert_agreed_grade_smr_stm();
    abstract protected function set_sql_insert_agreed_grade_smrt();
    abstract protected function set_insert_agreed_grade_smrt_stm();

    abstract protected function set_sql_update_agreed_grade();
    abstract protected function set_update_agreed_grade_stm();
    abstract protected function set_sql_update_agreed_grade_smr();
    abstract protected function set_update_agreed_grade_smr_stm();
    abstract protected function set_sql_update_agreed_grade_smrt();
    abstract protected function set_update_agreed_grade_smrt_stm();
}