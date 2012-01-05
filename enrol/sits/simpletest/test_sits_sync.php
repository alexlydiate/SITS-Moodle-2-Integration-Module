<?php
/*
* @package    enrol
* @subpackage sits
* @copyright  2011 University of Bath
* @author     Alex Lydiate {@link http://alexlydiate.co.uk}
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
require_once($CFG->dirroot . '/blocks/sits/lib/sits_sync.class.php');
require_once($CFG->dirroot . '/blocks/sits/lib/sits_period.class.php');
require('data_for_tests.class.php');
if($_SERVER['SERVER_NAME'] == LIVE_SERVER_NAME){
	exit('Do not try to run unit tests on the live site!');
}
class sits_sync_test extends UnitTestCase{
    private $sits_sync;
    private $data;
    
    public function setUp(){
        $this->sits_sync = new sits_sync;
        $this->data = new data_for_tests;    
    }

    public function tearDown(){
        unset($this->sits_sync);
    }
    
    public function test_user_by_username(){    	
        $userobject = $this->sits_sync->user_by_username($this->data->test_username);
        $this->assertEqual($userobject->username, $this->data->test_username);
    }

    public function test_neg_user_by_username(){
    	//Requires bathcas and related hack to be ported    	
		$this->assertFalse($this->sits_sync->user_by_username($this->data->new_username));        
    }
    
    public function test_sync_course(){
        $this->assertTrue($this->sits_sync->sync_course($this->data->test_course_id));
    }/*
    
    public function test_neg_sync_course(){
        $this->assertFalse($this->sits_sync->sync_course($this->data->false_course_id));
    }   
   
    public function test_create_mapping(){
    	GLOBAL $DB;
        $create_mapping = $this->sits_sync->create_mapping($this->data->test_mapping);
        $this->assertTrue($create_mapping);
        $new_mapping = $DB->get_record('sits_mappings', 'courseid', $this->data->test_mapping->courseid, 
                        'sits_code', $this->data->test_mapping->cohort->sits_code,
                        'period_code', $this->data->test_mapping->cohort->period_code);
            
        $this->assertEqual($new_mapping->courseid, $this->data->test_mapping->courseid);
            
        $DB->delete_records('sits_mappings', 'courseid', $this->data->test_mapping->courseid, 
                        'sits_code', $this->data->test_mapping->cohort->sits_code,
                        'period_code', $this->data->test_mapping->cohort->period_code);                           
    }
    
    public function test_neg_create_mapping(){
		//TODO
    }
      

    //Create the test mapping, alter the date, update the test mapping and assertTrue, delete test mapping

    public function test_update_mapping(){        
        $mapping_update = $this->create_and_return_test_mapping();        
        $new_start = new DateTime('1963-11-22 00:00:00');
        $mapping_update->start = $new_start;
        $this->sits_sync->update_mapping($mapping_update);   
        $new_mapping_record = $this->get_test_mapping_record();            
        $this->assertEqual($new_mapping_record->start_date, '1963-11-22 00:00:00');            
        $this->delete_test_mapping();
    }

     //As the test mapping does not exist, update_mapping should return false when passed it to update
   
    public function test_deactivate_mapping(){
        $mapping_to_deactivate = $this->create_and_return_test_mapping();
        $deactivate = $this->sits_sync->deactivate_mapping($mapping_to_deactivate);
        $this->assertTrue($deactivate);
        
        $deactivated_mapping_record = $this->get_test_mapping_record();
            
        $this->assertEqual($deactivated_mapping_record->active, 0);        
    }

    public function test_neg_deactivate_mapping(){
        $deactivate = $this->sits_sync->deactivate_mapping($this->data->test_mapping);
        $this->assertFalse($deactivate);
    }
    
    public function test_delete_mapping(){
        $mapping_to_delete = $this->create_and_return_test_mapping();
        $delete = $this->sits_sync->delete_mapping($mapping_to_delete);
        $this->assertTrue($delete);
        $deleted_mapping_record = $this->get_test_mapping_record();
        $this->assertFalse($deleted_mapping_record);        
    }
    
    public function test_neg_delete_mapping(){
        //TODO Can't think of a sensible way to test this, as delete_mappings essentially wraps Moodle's delete_records, 
        //which always returns an ADODB object except in a case of catastrophic faliure    
    }
    
    public function test_read_mappings_for_course(){
        $mapping_read = $this->create_and_return_test_mapping();
        $this->assertIsA($mapping_read, 'mapping');
        $this->delete_test_mapping();
    }
    
    public function test_neg_read_mappings_for_course(){
        $mappings = $this->sits_sync->read_mappings_for_course($this->data->false_course_id);
        $this->assertFalse($mappings);
    }
    
    public function test_read_mapping_from_id(){
        $mapping_read = $this->create_and_return_test_mapping();
        
        $mapping_read_from_id = $this->sits_sync->read_mapping_from_id($mapping_read->id);
        $this->assertEqual($mapping_read, $mapping_read_from_id);
        
        $this->delete_test_mapping();
    }
    
    public function test_neg_read_mapping_from_id(){        
        $mapping_read_from_id = $this->sits_sync->read_mapping_from_id('rubbish');
        $this->assertFalse($mapping_read_from_id);
    }
    
    public function test_read_users_for_mapping(){
        $mapping_created = $this->create_and_return_test_mapping();        
        $users = $this->sits_sync->read_users_for_mapping($mapping_created->id);
        $this->assertIsA($users, 'array');        
        $this->delete_test_mapping();
    }
    
    public function test_neg_read_users_for_mapping(){
        $users = $this->sits_sync->read_users_for_mapping('nonsense');
        $this->assertFalse($users);        
    }
    
    public function test_add_cohort_members_to_group(){
        $data->courseid = $this->data->test_mapping->courseid;
        $data->name = 'unit_test_created';
        $data->description = '';
        $test_group_id = groups_create_group($data);
        $cohort_added = $this->sits_sync->add_cohort_members_to_group($this->data->test_mapping->cohort, $test_group_id);
        $group_members_records = $DB->get_records('groups_members', 'groupid', $test_group_id);
        $this->assertIsA($group_members_records, 'array');
        groups_delete_group($test_group_id);  
    }
    
    public function test_neg_add_cohort_members_to_group(){
    	$this->assertFalse($this->sits_sync->add_cohort_members_to_group($this->data->test_mapping->cohort, "rubbish"));
    }
    
    public function test_alter_period(){
    	GLOBAL $DB;
    	$test_alteration = new period_alteration('S1', '2009/0', '1966-01-01 00:00:00', '01-02-2009 00:00:00', false);
    	$altered = $this->sits_sync->alter_period($test_alteration);
    	$test_alteration_record = $DB->get_record('sits_period', 'period_code', 'S1', 'acyear', '2009/0');
    	$this->assertTrue($test_alteration_record->start_date == '1966-01-01 00:00:00');	
    	$DB->delete_records('sits_period', 'period_code', 'S1', 'acyear', '2009/0');
    }
    
    public function test_update_all_mapping_periods(){
    	$this->assertTrue($this->sits_sync->update_all_mapping_periods());
    }
    
    public function test_remove_orphaned_mappings(){
    	GLOBAL $DB;
    	$data = new stdClass();
    	$data->courseid = $this->data->false_course_id;
    	$data->sits_code = $this->data->test_cohort->sits_code;
    	$data->acyear = $this->data->test_cohort->academic_year;
    	$data->period_code = $this->data->test_cohort->period_code;
    	$data->year_group = null;
    	$data->start_date = '1966-01-01 00:00:00';
    	$data->end_date = '1967-01-01 00:00:00';
    	$data->default_map = 0;
    	$data->type = 'module';
    	$data->manual = 0;
    	$data->specified = 0;
    	$data->active = 0;
    	
    	$false_course_record_id = $DB->insert_record('sits_mappings', $data, true);
    	
    	$data->courseid = $this->data->test_course_id;
    	$data->sits_code = "FK99999";
    	
    	$false_samis_record_id = $DB->insert_record('sits_mappings', $data, true);
    	
    	$return = $this->sits_sync->remove_orphaned_mappings();
    	
    	$this->assertTrue($return);
    	
    	if(!$return){
	    	if(is_int($false_course_record_id)){
	    		$DB->delete_records('sits_mappings', 'id', $false_course_record_id);
	    	}
	    	
	    	if(is_int($false_samis_record_id)){
	    		$DB->delete_records('sits_mappings', 'id', $false_samis_record_id);
	    	}
    	}
    }*/
    
    /////Private functions/////
    private function create_and_return_test_mapping(){
        $create_mapping = $this->sits_sync->create_mapping($this->data->test_mapping);
        //$this->assertTrue($create_mapping, 'Helper assertion leading to a separate test');
        $mappings = $this->sits_sync->read_mappings_for_course($this->data->test_mapping->courseid);
        foreach($mappings as $mapping){
            if($mapping->cohort == $this->data->test_mapping->cohort){
                    $mapping_update = $mapping;
            }       
        }
        if(is_object($mapping_update)){
            return $mapping_update;
        }else{
            return false;
        }
    }
    
    private function delete_test_mapping(){
    	GLOBAL $DB;
        return $DB->delete_records('sits_mappings', 'courseid', $this->data->test_mapping->courseid, 
                        'sits_code', $this->data->test_mapping->cohort->sits_code,
                        'period_code', $this->data->test_mapping->cohort->period_code);  
    }
    
    private function get_test_mapping_record(){
    	GLOBAL $DB;
        return $DB->get_record('sits_mappings', 'courseid', $this->data->test_mapping->courseid, 
                        'sits_code', $this->data->test_mapping->cohort->sits_code,
                        'period_code', $this->data->test_mapping->cohort->period_code);
    }
}
?>
