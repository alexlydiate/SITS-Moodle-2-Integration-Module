<?php
/**
 * @package    local
 * @subpackage sits
 * @copyright  2011 University of Bath
 * @author     Alex Lydiate {@link http://alexlydiate.co.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface i_sits_db {
    /**
     * Returns an Oracle resource handle for all module members of a specific module instance
     * The resultset contains the following fields:
     *
     * username : string : unique identifier for the person
     * sits_code : string : unique identifier for the module
     * period_code : string : SITS period code
     * shortname : string : module's short name if available, empty if not
     * fullname : string : module's full name
     * dpt_name : string : department name
     * role : integer :  1 = student, 2 = tutor
     *
     * @param program_cohort $program_cohort
     * @return Oracle resource handle on success, or false on faliure
     */
    public function mod_members_rh(&$module_cohort);
    /**
     * Returns an Oracle resource handle for allmodule members of a specific module instance
     * The resultset contains the following fields:
     *
     * username : string : unique identifier for the person
     * sits_code : string : unique identifier for the module
     * period_code : string : SITS period code
     * shortname : string : module's short name if available, empty if not
     * fullname : string : module's full name
     * dpt_name : string : department name
     * role : integer :  1 = student, 2 = tutor
     *
     * @param module_cohort $module_cohort
     * @return Oracle resource handle on success, or false on faliure
     */
    public function mod_student_members_rh(&$module_cohort);
    /**
     * Returns an Oracle resource handle for student module members of of a specific module instance
     * The resultset contains the following fields:
     *
     * username : string : unique identifier for the person
     * sits_code : string : unique identifier for the program
     * shortname : string : program's short name if available, empty if not
     * fullname : string : program's full name
     * dpt_name : string : department name
     * role : integer :  1 = student, 2 = tutor
     *
     * @param module_cohort $module_cohort
     * @return Oracle resource handle on success, or false on faliure
     */
    public function prog_members_rh(&$program_cohort);
    /**
     * Returns an Oracle resource handle for all program members of either a specific or all year groups
     * (if $program_cohort is set to 0 all will be returned).  The resultset contains the following fields:
     *
     * username : string : unique identifier for the person
     * sits_code : string : unique identifier for the program
     * shortname : string : program's short name if available, empty if not
     * fullname : string : program's full name
     * dpt_name : string : department name
     * role : integer :  1 = student, 2 = tutor
     *
     * @param program_cohort $program_cohort
     * @return Oracle resource handle on success, or false on faliure
     */
    public function prog_student_members_rh(&$program_instance);
    /**
     * Returns an Oracle resource handle for student program members of either a specific or all year groups
     * (if $program_cohort is set to 0 all will be returned).  The resultset contains the following fields:
     *
     * username : string : unique identifier for the person
     * sits_code : string : unique identifier for the program
     * shortname : string : program's short name if available, empty if not
     * fullname : string : program's full name
     * dpt_name : string : department name
     * role : integer :  1 = student, 2 = tutor
     *
     * @param program_cohort $program_cohort
     * @return Oracle resource handle on success, or false on faliure
     */
    public function mods_for_academic_year($acyear);
    /**
     * Returns an Oracle resource handle for all program members of either a specific or all year groups
     * (if $program_cohort is set to 0 all will be returned).  The resultset contains the following fields:
     *
     * sits_code : string :
     *
     * @param string $academic_year
     * @return Oracle resource handle on success, or false on faliure
     */
    public function progs_for_academic_year($acyear);
    /**
     * Validates that a module cohort object has a corresponding, valid record in SITS
     * @param module_cohort $module_cohort
     * @return boolean
     */
    public function validate_module(&$module);
    /**
     * Validates that a module cohort object has a corresponding, valid record in SITS
     * @param module_cohort $module_cohort
     * @return boolean
     */
    public function validate_program(&$program);
    /**
     * Returns an object containing the SAMIS period code, acyear and the start end dates for that period if fed a valid period code
     * and academic year combination
     * @param string $period_code
     * @param string $academic_year
     * @return period object containing the SAMIS period code, acyear and the start end dates for that period, or false
     */
    public function get_period_for_code($period_code, $academic_year);
    /**
     * updates samis with pass (or potentially fail) for qa53 assignment
     * @param user $user
     * @param grade $grade
     * @param module_cohort $cohort
     * @return boolean
     * @author James Barrett
     */
    public function update_agreed_grade(&$user,&$grade,&$cohort);
    /**
     * inserts samis pass (or potentially fail) for qa53 assignment
     * @param user $user
     * @param grade $grade
     * @param module_cohort $cohort
     * @return boolean
     * @author James Barrett
     */
    public function insert_agreed_grade(&$user,&$grade,&$cohort);
    /**
     * Returns an Oracle resource handle for all currently active period codes.
     * The resultset contains the following fields:
     *
     * period_code : string : unique identifier for the module
     * acyear : string : SITS period code
     * start : string : Start date in DateTime loadable format
     * end : string : end date in DateTime loadable format
     *
     * @return Oracle resource handle on success, or false on faliure
     */
    public function current_period_codes_rh();
}