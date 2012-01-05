<?php
require_once('sits_db.abstract.class.php');
require_once('i_sits_db.interface.php');
GLOBAL $CFG;
require_once($CFG->dirroot . '/local/sits/config/sits_config.php');

/**
 * @package    local
 * @subpackage sits
 * @copyright  2011 University of Bath
 * @author     Alex Lydiate {@link http://alexlydiate.co.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sits extends sits_db {

    function __construct(&$report, $testing = false){
        //Connect to Oracle, or log error and return false
        if(!$testing){
            $this->dbh = ocilogon(SITS_DB_USER, SITS_DB_PASS, SITS_DB_NAME);
        }else{
            $this->dbh = ocilogon(SITS_TEST_DB_USER, SITS_TEST_DB_PASS, SITS_TEST_DB_NAME);
        }
        if(!$this->dbh){
            $this->report->log_report(2, 'Could not establish connection to the Oracle database');
            return false;
        }
        //Set date
        $this->date = new DateTime();
        
        //Set DateTime format
        $cursor = OCIParse($this->dbh, "ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD HH24:MI:SS'");
        OCIExecute($cursor);
        OCIFreeCursor($cursor);
        //Set report
        $this->report = $report;
        //Programs sql setters
        $this->set_sql_all_programs();
        $this->set_sql_prog_students();
        $this->set_sql_prog_tutors();
        $this->set_sql_prog_other_tutors();
        $this->set_sql_all_prog_members();
        $this->set_sql_student_prog_members();
        $this->set_sql_validate_program();
        $this->set_sql_current_period_codes();
        //Programs prepared statements
        $this->set_prog_cohort_stm();
        $this->set_prog_student_cohort_stm();
        $this->set_all_prog_cohort_stm();
        $this->set_all_programs_stm();
        $this->set_all_student_prog_cohort_stm();
        $this->set_validate_program_stm();
        $this->set_current_period_codes_stm();
        //Modules sql setters
        $this->set_sql_all_modules();
        $this->set_sql_mod_students();
        $this->set_sql_mod_tutors();
        $this->set_sql_mod_other_tutors();
        $this->set_sql_student_mod_members();
        $this->set_sql_all_mod_members();
        $this->set_sql_validate_module();
        //Modules prepared statements
        $this->set_mod_student_cohort_stm();
        $this->set_mod_cohort_stm();
        $this->set_all_modules_stm();
        $this->set_validate_module_stm();
        //Period code sql setter
        $this->set_sql_period_for_code();
        //Period code statement
        $this->set_period_for_code_stm();
        //Validate bucs id sql setter
        $this->set_sql_validate_bucs_id();
        //Validate bucs id statement
        $this->set_validate_bucs_id_stm();
        //Academic year setters
        $this->set_current_academic_year();
        $this->set_last_academic_year();
        $this->set_next_academic_year();
        //qa53
        $this->set_sql_get_spr_from_bucs_id();
        $this->set_get_spr_from_bucs_id_stm();
        $this->set_sql_insert_agreed_grade();
        $this->set_insert_agreed_grade_stm();
        $this->set_sql_insert_agreed_grade_smr();
        $this->set_insert_agreed_grade_smr_stm();
        $this->set_sql_insert_agreed_grade_smrt();
        $this->set_insert_agreed_grade_smrt_stm();

        $this->set_sql_update_agreed_grade();
        $this->set_update_agreed_grade_stm();
        $this->set_sql_update_agreed_grade_smr();
        $this->set_update_agreed_grade_smr_stm();
        $this->set_sql_update_agreed_grade_smrt();
        $this->set_update_agreed_grade_smrt_stm();
    }

    function __destruct(){
        ocilogoff($this->dbh);
    }

    //Functions implemented from declared abstracts in the sits_db class

    protected function set_validate_program_stm(){

        $this->validate_program_stm = oci_parse($this->dbh, $this->sql_validate_program);
        if($this->validate_program_stm === false){
            $this->report->log_report(2, 'set_full_sync_resultset() failed to parse query');
            return false;
        }elseif(!oci_bind_by_name($this->validate_program_stm, ':acyear', $this->academic_year, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :acyear in set_prog_cohort_query');
            return false;
        }elseif(!oci_bind_by_name($this->validate_program_stm, ':sits_code', $this->sits_code, 12, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :sits_code in set_prog_cohort_query');
            return false;
        }elseif(!oci_bind_by_name($this->validate_program_stm, ':year_group', $this->year_group, 2, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :year_group in set_prog_cohort_query');
            return false;
        }else{
            return true;
        }
    }

    protected function sets_all_programs_stm(){

        $conditions = <<<sql
AND sce.sce_ayrc = :acyear
sql;
        	
        $sql = sprintf($this->sql_all_programs, $conditions);

        $this->all_programs_stm = oci_parse($this->dbh, $sql);

        if($this->all_programs_stm() === false){
            $this->report->log_report(2, 'set_full_sync_resultset() failed to parse query');
            return false;
        }elseif(!oci_bind_by_name($this->all_programs_stm(), ':acyear', $this->academic_year, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :acyear in set_prog_cohort_query');
            return false;
        }else{
            return true;
        }
    }

    protected function set_prog_student_cohort_stm(){

        $conditions = <<<sql
AND crs.crs_code = :progcode
AND sce.sce_ayrc = :acyear
AND sce.sce_blok = :yeargroup
sql;

        $sql = sprintf($this->sql_student_prog_members, $conditions);

        $this->prog_student_cohort_stm = oci_parse($this->dbh, $sql);

        if($this->prog_student_cohort_stm === false){
            $this->report->log_report(2, 'set_full_sync_resultset() failed to parse query');
            return false;
        }elseif(!oci_bind_by_name($this->prog_student_cohort_stm, ':progcode', $this->sits_code, 16, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :progcode in set_prog_cohort_query');
            return false;
        }elseif(!oci_bind_by_name($this->prog_student_cohort_stm, ':acyear', $this->academic_year, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :acyear in set_prog_cohort_query');
            return false;
        }elseif(!oci_bind_by_name($this->prog_student_cohort_stm, ':yeargroup', $this->year_group, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :yeargroup in set_prog_cohort_query');
            return false;
        }else{
            return true;
        }
    }

    protected function set_prog_cohort_stm(){

        $conditions = <<<sql
AND crs.crs_code = :progcode
AND sce.sce_ayrc = :acyear
AND sce.sce_blok = :yeargroup
sql;

        $sql = sprintf($this->sql_all_prog_members, $conditions);

        $this->prog_cohort_stm = oci_parse($this->dbh, $sql);

        if($this->prog_cohort_stm === false){
            $this->report->log_report(2, 'set_full_sync_resultset() failed to parse query');
            return false;
        }elseif(!oci_bind_by_name($this->prog_cohort_stm, ':progcode', $this->sits_code, 16, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :progcode in set_prog_cohort_query');
            return false;
        }elseif(!oci_bind_by_name($this->prog_cohort_stm, ':acyear', $this->academic_year, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :acyear in set_prog_cohort_query');
            return false;
        }elseif(!oci_bind_by_name($this->prog_cohort_stm, ':yeargroup', $this->year_group, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :yeargroup in set_prog_cohort_query');
            return false;
        }else{
            return true;
        }
    }

    protected function set_all_student_prog_cohort_stm(){

        $conditions = <<<sql
AND crs.crs_code = :progcode
AND sce.sce_ayrc = :acyear
sql;
        	
        $sql = sprintf($this->sql_student_prog_members, $conditions);

        $this->all_student_prog_cohort_stm = oci_parse($this->dbh, $sql);

        if($this->all_prog_cohort_stm === false){
            $this->report->log_report(2, 'set_full_sync_resultset() failed to parse query');
            return false;
        }elseif(!oci_bind_by_name($this->all_student_prog_cohort_stm, ':progcode', $this->sits_code, 16, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :progcode in set_prog_cohort_query');
            return false;
        }elseif(!oci_bind_by_name($this->all_student_prog_cohort_stm, ':acyear', $this->academic_year, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :acyear in set_prog_cohort_query');
            return false;
        }else{
            return true;
        }
    }

    protected function set_all_prog_cohort_stm(){

        $conditions = <<<sql
AND crs.crs_code = :progcode
AND sce.sce_ayrc = :acyear
sql;
        	
        $sql = sprintf($this->sql_all_prog_members, $conditions);

        $this->all_prog_cohort_stm = oci_parse($this->dbh, $sql);

        if($this->all_prog_cohort_stm === false){
            $this->report->log_report(2, 'set_full_sync_resultset() failed to parse query');
            return false;
        }elseif(!oci_bind_by_name($this->all_prog_cohort_stm, ':progcode', $this->sits_code, 16, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :progcode in set_prog_cohort_query');
            return false;
        }elseif(!oci_bind_by_name($this->all_prog_cohort_stm, ':acyear', $this->academic_year, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :acyear in set_prog_cohort_query');
            return false;
        }else{
            return true;
        }
    }

    protected function set_all_modules_stm(){

        $conditions = <<<sql
AND smo.ayr_code = :acyear
sql;
        	
        $sql = sprintf($this->sql_all_modules, $conditions);

        $this->all_modules_stm = oci_parse($this->dbh, $sql);

        if($this->all_modules_stm === false){
            $this->report->log_report(2, 'set_all_modules_stm() failed to parse query');
            return false;
        }elseif(!oci_bind_by_name( $this->all_modules_stm, ':acyear', $this->academic_year, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :acyear in set_prog_cohort_query');
            return false;
        }else{
            return true;
        }
    }

    protected function set_all_programs_stm(){

        $conditions = <<<sql
AND sce.sce_ayrc = :acyear
sql;
        	
        $sql = sprintf($this->sql_all_programs, $conditions);

        $this->all_programs_stm = oci_parse($this->dbh, $sql);

        if($this->all_modules_stm === false){
            $this->report->log_report(2, 'set_all_programs_stm() failed to parse query');
            return false;
        }elseif(!oci_bind_by_name( $this->all_programs_stm, ':acyear', $this->academic_year, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :acyear in set_all_programs_stm()');
            return false;
        }else{
            return true;
        }
    }

    protected function set_validate_module_stm(){

        $this->validate_module_stm = oci_parse($this->dbh, $this->sql_validate_module);

        if($this->validate_module_stm === false){
            $this->report->log_report(2, 'set_validate_module_stm() failed to parse query');
            return false;
        }elseif(!oci_bind_by_name($this->validate_module_stm, ':sits_code', $this->sits_code, 16, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :modcode in set_validate_module_stm()');
            return false;
        }else{
            return true;
        }
    }

    protected function set_mod_student_cohort_stm(){

        $conditions = <<<sql
AND smo.mod_code = :modcode
AND smo.psl_code = :period
AND smo.AYR_CODE = :acyear
sql;

        $sql = sprintf($this->sql_student_mod_members, $conditions);

        $this->mod_student_cohort_stm = oci_parse($this->dbh, $sql);

        if($this->mod_cohort_stm === false){
            $this->report->log_report(2, 'sset_mod_cohort_stm() failed to parse query');
            return false;
        }elseif(!oci_bind_by_name($this->mod_student_cohort_stm, ':modcode', $this->sits_code, 16, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :progcode in set_mod_cohort_query');
            return false;
        }elseif(!oci_bind_by_name($this->mod_student_cohort_stm, ':acyear', $this->academic_year, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :acyear in set_mod_cohort_query');
            return false;
        }elseif(!oci_bind_by_name($this->mod_student_cohort_stm, ':period', $this->period_code, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :yeargroup in set_mod_cohort_query');
            return false;
        }else{
            return true;
        }
    }

    protected function set_mod_cohort_stm(){

        $conditions = <<<sql
AND smo.mod_code = :modcode
AND smo.psl_code = :period
AND smo.AYR_CODE = :acyear
sql;

        $sql = sprintf($this->sql_all_mod_members, $conditions);

        $this->mod_cohort_stm = oci_parse($this->dbh, $sql);

        if($this->mod_cohort_stm === false){
            $this->report->log_report(2, 'sset_mod_cohort_stm() failed to parse query');
            return false;
        }elseif(!oci_bind_by_name($this->mod_cohort_stm, ':modcode', $this->sits_code, 16, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :modcode in set_mod_cohort_query');
            return false;
        }elseif(!oci_bind_by_name($this->mod_cohort_stm, ':acyear', $this->academic_year, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :acyear in set_mod_cohort_query');
            return false;
        }elseif(!oci_bind_by_name($this->mod_cohort_stm, ':period', $this->period_code, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :yeargroup in set_mod_cohort_query');
            return false;
        }else{
            return true;
        }
    }

    protected function set_period_for_code_stm(){
        $this->period_for_code_stm = oci_parse($this->dbh, $this->sql_period_for_code);

        if($this->period_for_code_stm === false){
            $this->report->log_report(2, 'set_period_for_code_stm() failed to parse query');
            return false;
        }elseif(!oci_bind_by_name($this->period_for_code_stm, ':acyear', $this->academic_year, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :acyear in set_mod_cohort_query');
            return false;
        }elseif(!oci_bind_by_name($this->period_for_code_stm, ':period', $this->period_code, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :yeargroup in set_mod_cohort_query');
            return false;
        }else{
            return true;
        }
    }

    protected function set_validate_bucs_id_stm(){
        $this->validate_bucs_id_stm = oci_parse($this->dbh, $this->sql_validate_bucs_id);

        if($this->validate_bucs_id_stm === false){
            $this->report->log_report(2, 'set_validate_bucs_id_stm() failed to parse query');
            return false;
        }elseif(!oci_bind_by_name($this->validate_bucs_id_stm, ':username', $this->bucs_id, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :username in set_validate_bucs_id_stm');
            return false;
        }else{
            return true;
        }
    }

    protected function set_current_period_codes_stm(){
        $this->current_period_codes_stm = oci_parse($this->dbh, $this->sql_current_period_codes);

        if($this->current_period_codes_stm === false){
            $this->report->log_report(2, 'current_period_codes_stm() failed to parse query');
            return false;
        }else{
            return true;
        }

    }

    // qa53 //
    protected function set_get_spr_from_bucs_id_stm(){
        $this->get_spr_from_bucs_id_stm = oci_parse($this->dbh, $this->sql_get_spr_from_bucs_id);

        if($this->get_spr_from_bucs_id_stm === false){
            $this->report->log_report(2, 'set_get_spr_from_bucs_id_stm() failed to parse query');
            return false;
        }elseif(!oci_bind_by_name($this->get_spr_from_bucs_id_stm, ':bucs_id', $this->bucs_id, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :bucs_id in set_get_spr_from_bucs_id_stm');
            return false;
        }elseif(!oci_bind_by_name($this->get_spr_from_bucs_id_stm, ':ac_year', $this->ac_year, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :ac_year in set_get_spr_from_bucs_id_stm');
            return false;
        }else{
            return true;
        }
    }

    protected function set_insert_agreed_grade_stm(){

        $this->insert_agreed_grade_stm = oci_parse($this->dbh, $this->sql_insert_agreed_grade);

        if($this->insert_agreed_grade_stm === false){
            $this->report->log_report(2, 'set_insert_agreed_grade_stm() failed to parse query', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->insert_agreed_grade_stm, ':sits_code', $this->sits_code, 16, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :sits_code in set_insert_agreed_grade_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->insert_agreed_grade_stm, ':academic_year', $this->academic_year, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :acyear in set_insert_agreed_grade_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->insert_agreed_grade_stm, ':period_code', $this->period_code, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :period_code in set_insert_agreed_grade_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->insert_agreed_grade_stm, ':spr_code', $this->spr_code, 16, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :spr_code in set_insert_agreed_grade_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->insert_agreed_grade_stm, ':sas_agrg', $this->sas_agrg, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :sas_agrg in set_insert_agreed_grade_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->insert_agreed_grade_stm, ':mav_occur', $this->mav_occur, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :mav_occur in set_insert_agreed_grade_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->insert_agreed_grade_stm, ':map_code', $this->map_code, 16, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :map_code in set_insert_agreed_grade_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->insert_agreed_grade_stm, ':mab_seq', $this->mab_seq, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :mab_seq in set_insert_agreed_grade_stm()', 'mtrace');
            return false;

        }else{
            return true;
        }
    }

    protected function set_insert_agreed_grade_smr_stm(){

        $this->insert_agreed_grade_smr_stm = oci_parse($this->dbh, $this->sql_insert_agreed_grade_smr);

        if($this->insert_agreed_grade_smr_stm === false){
            $this->report->log_report(2, 'set_insert_agreed_grade_smr_stm() failed to parse query', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->insert_agreed_grade_smr_stm, ':sits_code', $this->sits_code, 16, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :sits_code in set_insert_agreed_grade_smr_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->insert_agreed_grade_smr_stm, ':academic_year', $this->academic_year, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :acyear in set_insert_agreed_grade_smr_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->insert_agreed_grade_smr_stm, ':period_code', $this->period_code, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :period_code in set_insert_agreed_grade_smr_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->insert_agreed_grade_smr_stm, ':spr_code', $this->spr_code, 16, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :spr_code in set_insert_agreed_grade_smr_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->insert_agreed_grade_smr_stm, ':sas_agrg', $this->sas_agrg, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :sas_agrg in set_insert_agreed_grade_smr_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->insert_agreed_grade_smr_stm, ':mav_occur', $this->mav_occur, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :mav_occur in set_insert_agreed_grade_smr_stm()', 'mtrace');
            return false;

        }else{
            return true;
        }
    }

    protected function set_insert_agreed_grade_smrt_stm(){

        $this->insert_agreed_grade_smrt_stm = oci_parse($this->dbh, $this->sql_insert_agreed_grade_smrt);

        if($this->insert_agreed_grade_smrt_stm === false){
            $this->report->log_report(2, 'set_insert_agreed_grade_smrt_stm() failed to parse query', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->insert_agreed_grade_smrt_stm, ':sits_code', $this->sits_code, 16, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :sits_code in set_insert_agreed_grade_smrt_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->insert_agreed_grade_smrt_stm, ':academic_year', $this->academic_year, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :acyear in set_insert_agreed_grade_smr_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->insert_agreed_grade_smrt_stm, ':spr_code', $this->spr_code, 16, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :spr_code in set_insert_agreed_grade_smrt_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->insert_agreed_grade_smrt_stm, ':period_code', $this->period_code, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :period_code in set_insert_agreed_grade_smrt_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->insert_agreed_grade_smrt_stm, ':mav_occur', $this->mav_occur, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :mav_occur in set_insert_agreed_grade_smrt_stm()', 'mtrace');
            return false;

        }else{
            return true;
        }
    }

    protected function set_update_agreed_grade_stm(){

        $this->update_agreed_grade_stm = oci_parse($this->dbh, $this->sql_update_agreed_grade);

        if($this->update_agreed_grade_stm === false){
            $this->report->log_report(2, 'set_update_agreed_grade_stm() failed to parse query', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->update_agreed_grade_stm, ':spr_code', $this->spr_code, 16, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :spr_code in set_update_agreed_grade_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->update_agreed_grade_stm, ':sits_code', $this->sits_code, 16, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :sits_code in set_update_agreed_grade_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->update_agreed_grade_stm, ':academic_year', $this->academic_year, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :acyear in set_update_agreed_grade_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->update_agreed_grade_stm, ':period_code', $this->period_code, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :period_code in set_update_agreed_grade_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->update_agreed_grade_stm, ':sas_agrg', $this->sas_agrg, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :sas_agrg in set_update_agreed_grade_stm()', 'mtrace');
            return false;

        }else{
            return true;
        }
    }

    protected function set_update_agreed_grade_smr_stm(){

        $this->update_agreed_grade_smr_stm = oci_parse($this->dbh, $this->sql_update_agreed_grade_smr);

        if($this->update_agreed_grade_smr_stm === false){
            $this->report->log_report(2, 'set_update_agreed_grade_smr_stm() failed to parse query', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->update_agreed_grade_smr_stm, ':spr_code', $this->spr_code, 16, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :spr_code in set_update_agreed_grade_smr_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->update_agreed_grade_smr_stm, ':sits_code', $this->sits_code, 16, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :sits_code in set_update_agreed_grade_smr_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->update_agreed_grade_smr_stm, ':academic_year', $this->academic_year, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :acyear in set_update_agreed_grade_smr_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->update_agreed_grade_smr_stm, ':period_code', $this->period_code, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :period_code in set_update_agreed_grade_smr_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->update_agreed_grade_smr_stm, ':sas_agrg', $this->sas_agrg, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :smr_agrg in set_update_agreed_grade_smr_stm()', 'mtrace');
            return false;

        }else{
            return true;
        }
    }
    protected function set_update_agreed_grade_smrt_stm(){

        $this->update_agreed_grade_smrt_stm = oci_parse($this->dbh, $this->sql_update_agreed_grade_smrt);

        if($this->update_agreed_grade_smrt_stm === false){
            $this->report->log_report(2, 'set_update_agreed_grade_smrt_stm() failed to parse query', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->update_agreed_grade_smrt_stm, ':spr_code', $this->spr_code, 16, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :spr_code in set_update_agreed_grade_smrt_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->update_agreed_grade_smrt_stm, ':sits_code', $this->sits_code, 16, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :sits_code in set_update_agreed_grade_smrt_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->update_agreed_grade_smrt_stm, ':academic_year', $this->academic_year, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :acyear in set_update_agreed_grade_smrt_stm()', 'mtrace');
            return false;
        }elseif(!oci_bind_by_name($this->update_agreed_grade_smrt_stm, ':period_code', $this->period_code, 8, SQLT_CHR)){
            $this->report->log_report(2, 'Failed to bind :period_code in set_update_agreed_grade_smrt_stm()', 'mtrace');
            return false;

        }else{
            return true;
        }
    }


    //Protected SQL setters, declared from abstract functions in sync

    protected function set_sql_all_programs(){
        $this->sql_all_programs = <<<sql
SELECT DISTINCT * FROM(
SELECT crs.crs_code "sits_code", crs.crs_name "shortname", crs.crs_titl "fullname", crs.crs_dptc "dep_code"
FROM srs_sce sce, srs_crs crs, ins_prs prs
WHERE sce.sce_crsc = crs.crs_code
AND crs.crs_prsc = prs.prs_code 
AND sce.sce_stac NOT IN ('G', 'DE', 'L', 'NS', 'T')
AND crs.crs_prsc IS NOT NULL
sql;

        $this->sql_all_programs .= ' %1$s)';
    }

    protected function set_sql_prog_students(){
        //Use sprintf() to add in the appropriate extra conditions for :acyear, :modcode and :periodcode
        $this->sql_prog_students = <<<sql
SELECT stu.stu_udf1 "username", crs.crs_code "sits_code", crs.crs_titl "fullname", dpt.dpt_code "dep_code", 1 "role"
FROM srs_sce sce, ins_stu stu, srs_crs crs, ins_dpt dpt
WHERE stu.stu_code = sce.sce_stuc 
AND sce.sce_crsc = crs.crs_code 
AND dpt.dpt_code = crs.crs_dptc 
AND stu.stu_udf1 IS NOT NULL 
AND sce.sce_stac NOT IN ('G', 'DE', 'L', 'NS', 'T')
sql;
        //this to give a vconditions placeholder; because EOT parses $variables, so you can't stick it straight in
        $this->sql_prog_students .= ' %1$s';
    }

    protected function set_sql_prog_tutors(){
        $this->sql_prog_tutors = <<<sql
SELECT prs.prs_emad "username", crs.crs_code "sits_code", crs.crs_titl "fullname", crs.crs_dptc "dep_code", 2 "role"
FROM srs_sce sce, srs_crs crs, ins_prs prs
WHERE sce.sce_crsc = crs.crs_code
AND crs.crs_prsc = prs.prs_code 
AND sce.sce_stac NOT IN ('G', 'DE', 'L', 'NS', 'T')
AND crs.crs_prsc IS NOT NULL
sql;
        //this to give a vconditions placeholder; because EOT parses $variables, so you can't stick it straight in
        $this->sql_prog_tutors .= ' %1$s';
    }

    protected function set_sql_prog_other_tutors(){
        $this->sql_prog_other_tutors = <<<sql
SELECT prs.prs_emad "username", crs.crs_code "sits_code", crs.crs_titl "fullname", crs.crs_dptc "dep_code", 2 "role"
FROM srs_crs crs, ins_prs prs, men_xon xon, srs_sce sce
WHERE sce.sce_crsc = crs.crs_code
AND crs.crs_code = SUBSTR(xon.xon_tabl,4,10)
AND SUBSTR(xon.xon_tabl,1,3) = 'EC-'
AND xon.xon_oldv = prs.prs_code
AND prs.prs_emad IS NOT NULL
AND crs.crs_dptc IS NOT NULL
sql;
        //this to give a conditions placeholder; because EOT parses $variables, so you can't stick it straight in
        $this->sql_prog_other_tutors .= ' %1$s';
    }

    protected function set_sql_validate_program(){
        $this->sql_validate_program = <<<sql
SELECT DISTINCT(sce.sce_crsc)
FROM srs_sce sce
WHERE sce.sce_crsc = :sits_code
AND sce.sce_ayrc = :acyear
AND sce.sce_blok LIKE :year_group
sql;
    }


    protected function set_sql_all_prog_members(){
        $this->sql_all_prog_members = 'SELECT DISTINCT * FROM (' . $this->sql_prog_students . ' UNION ' . $this->sql_prog_tutors . ' UNION ' . $this->sql_prog_other_tutors . ')';
    }

    protected function set_sql_student_prog_members(){
        $this->sql_student_prog_members = 'SELECT DISTINCT * FROM (' . $this->sql_prog_students . ')';
    }

    // Module SQL setters

    protected function set_sql_all_modules(){
        $this->sql_all_modules = <<<sql
SELECT DISTINCT * FROM (
SELECT smo.mod_code "sits_code", smo.psl_code "period_code", mod.mod_snam "shortname", mod.mod_name "fullname", dpt.dpt_code "dep_code"
FROM cam_smo smo, ins_mod mod, ins_dpt dpt
where smo.mod_code=mod.mod_code
AND mod.DPT_CODE=dpt.DPT_CODE
AND SUBSTR(mod.mod_code,1,2) != 'ZZ'
AND mod.prs_code IS NOT NULL
sql;

        $this->sql_all_modules .= ' %1$s)';
    }

    protected function set_sql_mod_students(){
        //Use sprintf() to add in the appropriate extra conditions for :acyear, :modcode and :periodcode
        $this->sql_mod_students = <<<sql
SELECT stu.stu_udf1 "username", smo.mod_code "sits_code", smo.psl_code "period_code", dpt.dpt_code "dep_code", 1 "role"
FROM ins_stu stu, cam_smo smo, ins_mod mod, ins_dpt dpt, srs_scj scj
where stu.stu_code=substr(smo.spr_code,1,9)
AND smo.mod_code=mod.mod_code
AND mod.DPT_CODE=dpt.DPT_CODE
AND stu.stu_code=scj.scj_stuc 
AND SUBSTR(mod.mod_code,1,2) != 'ZZ'
AND scj.scj_stac NOT IN ('G', 'DE', 'L', 'NS', 'T')
sql;
        //this to give a conditions placeholder; because EOT parses $variables, so you can't stick it straight in
        $this->sql_mod_students .= ' %1$s';
    }

    protected function set_sql_mod_tutors(){
        $this->sql_mod_tutors = <<<sql
SELECT prs.prs_emad "username", smo.mod_code "sits_code", smo.psl_code "period_code", dpt.dpt_code "dep_code", 2 "role"
FROM cam_smo smo,
ins_mod mod,
ins_dpt dpt,
ins_prs prs
where smo.mod_code=mod.mod_code
AND mod.DPT_CODE=dpt.DPT_CODE
AND mod.PRS_CODE=prs.PRS_CODE
AND SUBSTR(mod.mod_code,1,2) != 'ZZ'
sql;
        //this to give a conditions placeholder; because EOT parses $variables, so you can't stick it straight in
        $this->sql_mod_tutors .= ' %1$s';
    }

    protected function set_sql_mod_other_tutors(){
        $this->sql_mod_other_tutors = <<<sql
SELECT prs.prs_emad "username", smo.mod_code "sits_code", smo.psl_code "period_code", dpt.dpt_code "dep_code", 3 "role"
FROM cam_smo smo,
ins_mod mod,
ins_dpt dpt,
ins_prs prs,
men_xon xon
WHERE smo.mod_code=mod.mod_code
AND mod.DPT_CODE=dpt.DPT_CODE
AND xon.xon_oldv=prs.PRS_CODE
AND smo.mod_code = SUBSTR(xon.xon_tabl,4)
AND SUBSTR(xon.xon_tabl,1,3) = 'EM-'
AND SUBSTR(mod.mod_code,1,2) != 'ZZ'
sql;
        //this to give a conditions placeholder; because EOT parses $variables, so you can't stick it straight in
        $this->sql_mod_other_tutors .= ' %1$s';
    }

    protected function set_sql_validate_module(){
        $this->sql_validate_module = <<<sql
SELECT DISTINCT(mod.mod_code)
FROM ins_mod mod
WHERE mod.mod_code = :sits_code
AND SUBSTR(mod.mod_code,1,2) != 'ZZ'
sql;
    }

    protected function set_sql_all_mod_members(){
        $this->sql_all_mod_members = 'SELECT DISTINCT * FROM (' . $this->sql_mod_students . ' UNION ' . $this->sql_mod_tutors . ' UNION ' . $this->sql_mod_other_tutors . ')';
    }

    protected function set_sql_student_mod_members(){
        $this->sql_student_mod_members = 'SELECT DISTINCT * FROM (' . $this->sql_mod_students . ')';
    }

    //Other SQL

    protected function set_sql_period_for_code(){
        $this->sql_period_for_code = <<<sql
SELECT yps.yps_begd "start", yps_endd "end"
FROM ins_yps yps 
WHERE yps.yps_ayrc = :acyear
AND yps.yps_pslc = :period
sql;
    }

    protected function set_sql_validate_bucs_id(){
        $this->sql_validate_bucs_id = <<<sql
SELECT stu.stu_udf1 AS username, stu.stu_name AS name 
FROM ins_stu stu 
WHERE stu.stu_udf1 = :username
UNION
SELECT prs.prs_emad AS username, prs.prs_name AS name 
FROM ins_prs prs
WHERE prs.prs_emad = :username
sql;
    }
    //qa53

    protected function set_sql_get_spr_from_bucs_id(){
        $this->sql_get_spr_from_bucs_id = <<<sql
SELECT max(SPR_CODE) as SPR_CODE FROM INS_SPR spr
LEFT JOIN INS_STU stu ON spr.SPR_STUC = stu.STU_CODE
LEFT JOIN SRS_SCJ scj ON spr.SPR_CODE = scj.SCJ_SPRC
LEFT JOIN SRS_SCE sce on scj.SCJ_CODE = sce.SCE_SCJC
WHERE SUBSTR(SCE.SCE_CRSC,1,1) IN ('U','T','R')
and SUBSTR(SCJ.SCJ_CRSC,1,1) IN ('U','T','R')
AND stu.STU_UDF1 = :bucs_id AND sce.sce_ayrc = :ac_year
sql;
    }

    protected function set_sql_insert_agreed_grade(){
        $this->sql_insert_agreed_grade = <<<sql
INSERT INTO CAM_SAS (SPR_CODE, MOD_CODE, MAV_OCCUR, AYR_CODE, PSL_CODE, MAP_CODE, MAB_SEQ, SAS_AGRG )
VALUES (:spr_code , :sits_code , :mav_occur , :academic_year , :period_code , :map_code , :mab_seq , :sas_agrg )
sql;
    }

    protected function set_sql_insert_agreed_grade_smr(){
        $this->sql_insert_agreed_grade_smr = <<<sql
INSERT INTO INS_SMR (SPR_CODE, MOD_CODE, MAV_OCCUR, AYR_CODE, PSL_CODE, SMR_AGRG )
VALUES (:spr_code , :sits_code , :mav_occur , :academic_year , :period_code , :sas_agrg )
sql;
    }

    protected function set_sql_insert_agreed_grade_smrt(){
        $this->sql_insert_agreed_grade_smrt = <<<sql
INSERT INTO INS_SMRT (SPR_CODE, MOD_CODE, MAV_OCCUR, AYR_CODE, PSL_CODE, SMR_AGRD )
VALUES (:spr_code , :sits_code, :mav_occur, :academic_year, :period_code, sysdate)
sql;
    }

    protected function set_sql_update_agreed_grade(){
        $this->sql_update_agreed_grade = <<<sql
UPDATE CAM_SAS SET SAS_AGRG = :sas_agrg 
WHERE SPR_CODE = :spr_code
AND MOD_CODE = :sits_code
AND AYR_CODE = :academic_year
AND PSL_CODE = :period_code
sql;
    }

    protected function set_sql_update_agreed_grade_smr(){
        $this->sql_update_agreed_grade_smr = <<<sql
UPDATE INS_SMR SET SMR_AGRG = :sas_agrg 
WHERE SPR_CODE  = :spr_code
AND MOD_CODE = :sits_code
AND AYR_CODE = :academic_year
AND PSL_CODE = :period_code
sql;
    }

    protected function set_sql_update_agreed_grade_smrt(){

        $this->sql_update_agreed_grade_smrt = <<<sql
UPDATE INS_SMRT SET SMR_AGRD = sysdate
WHERE SPR_CODE  = :spr_code
AND MOD_CODE = :sits_code
AND AYR_CODE = :academic_year
AND PSL_CODE = :period_code
sql;
    }


    protected function set_sql_current_period_codes(){

        $this->sql_current_period_codes = <<<sql
SELECT yps.yps_pslc "period_code", yps.yps_ayrc "acyear", yps.yps_begd "start", yps_endd "end"
FROM ins_yps yps 
WHERE yps.yps_begd <= sysdate
AND yps_endd >= sysdate
sql;
    }
}

