<?php
/*
 * Handles requests from client apps, invariable the sits block
* @package    local
* @subpackage sits
* @copyright  2011 University of Bath
* @author     Alex Lydiate {@link http://alexlydiate.co.uk}
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
require_once('sits_sync.class.php');
require_once('mapping.class.php');
require_once('report.class.php');
require_once('cohort.class.php');
require_once('sits.final.class.php');
require_once('report.class.php');

GLOBAL $CFG;
require_once($CFG->dirroot.'/group/lib.php');

//Define the response code to return to the client
define('SUCCESS', 0);
define('ERROR_COHORT_FAILED_SITS_VALIDATION', 1);
define('ERROR_FAILED_TO_CREATE_MAPPING', 2);
define('ERROR_MAPPING_ALREADY_EXISTS', 3);
define('ERROR_FAILED_TO_DELETE_MAPPING', 4);
define('ERROR_FAILED_TO_RETRIEVE_MAPPING', 5);
define('ERROR_FAILED_TO_INSTANTIATE_COHORT', 6);
define('ERROR_FAILED_TO_CREATE_GROUP', 7);
define('ERROR_FAILED_TO_ADD_TO_GROUP', 8);
define('ERROR_FAILED_TO_UPDATE_PERIODS', 9);
define('ERROR_ERROR_FAILED_TO_RESET_SYNC_FLAG', 10);
define('ERROR_FAILED_TO_INSTANTIATE_MAPPING', 11);



/**
 * Handles all client requests
 * @package moodle_sits_block
 * @author Alex Lydiate <alexlydiate [at] gmail [dot] com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
class sits_client_request {

    private $op; //String
    private $xml; //XML string
    private $response; //String
    private $sits_sync; //Object
    private $content_type = null; //string

    public function __construct(&$op, &$xml, $testing = false){
        $this->op = $op;
        $this->xml = new SimpleXMLElement($xml);
        $this->sits_sync = enrol_get_plugin('sits');
        $this->generate_response();
        $this->respond();
    }

    /**
     * Echos the response
     */
    private function respond(){
        if(!is_null($this->content_type)){
            header("Content-type: " . $this->content_type);
        }
        echo $this->response;
    }

    /**
     * Switches on the operation ($this->op) and calld the appropriate response-generating function
     */
    private function generate_response(){
        // Switch on the posted operation and print the return value (a response code that is then handled client-side)
        switch($this->op){
            case 'create_map':
                $this->create_new_mapping();
                break;
            case 'batch':
                $this->batch_mapping_update();
                break;
            case 'sync':
                $this->sync();
                break;
            case 'add_user':
                $this->add_user_by_bucs_id();
                break;
            case 'sync_all':
                $this->sync_all_courses();
                break;
            case 'get_map_ids':
            case 'get_map_ids_group':
                $this->get_mappings_for_course();
                break;
            case 'get_map':
            case 'get_map_group':
                $this->get_mapping_by_id((integer)$this->xml->id);
                break;
            case 'get_groups':
                $this->get_groups();
                break;
            case 'add_to_group':
                $this->add_to_group((integer)$this->xml->group_id);
                break;
            case 'create_group':
                $this->create_group();
                break;
            case 'get_periods':
                $this->get_periods();
                break;
            case 'save_periods':
                $this->save_periods();
                break;
            case 'reset_sync_flag':
                $this->reset_sync_flag();
                break;
        }
    }

    /**
     * Given the appropriate XML from the client this function will call upon SITS_sync to sync a particular course
     * @param string$xml
     * @param object $this->sits_sync
     * @return a response code integer
     */
    private function sync(){
        $returnXML = <<<EOXML
<?xml version='1.0' standalone='yes'?>
<syncs>
</syncs>
EOXML;

        $returnXMLObj = new SimpleXMLElement($returnXML);
        foreach($this->xml->children() as $tag => $course_id){
            $returnXMLObj->addChild('course_id', $course_id);
            if($this->sync_course($course_id, &$this->sits_sync)){
                $returnXMLObj->addChild('synced', $course_id);
            }else{
                //FIXME The Moodle function get_records, used by sits_sync::sync_course,
                //returns false for both the cases of 'Met a problem' and 'Found no maps'
                //So, currently we have to accept, in terms of the GUI, that false means 'Found no maps' and not use the line:
                //$returnXMLObj->addChild('error', 'Failed to update ' . $course_id);
                $returnXMLObj->addChild('synced', $course_id);
            }
        }
        $this->content_type = 'text/xml';
        $this->response = $returnXMLObj->asXML();
    }

    /**
     * wrapper for the related sits_sync service
     * @param int $course_id
     * @param object $this->sits_sync
     * @return a response code integer
     */
    private function sync_course($course_id){
        return $this->sits_sync->sync_course($course_id);
    }

    /**
     * Given the appropriate XML from the client this function will create the requested mapping
     * @param string $xml
     * @param object $this->sits_sync
     * @return a response code integer
     */
    private function create_new_mapping(){
        //var_dump($this->xml);
        switch($this->xml->cohort->type){
            case 'module':
                try{
                    $cohort = new module_cohort((string)$this->xml->cohort->sits_code, (string)$this->xml->cohort->period_code, (string)$this->xml->cohort->acyear);
                    if(!$this->sits_sync->validate_module($cohort)){
                        $this->response = ERROR_COHORT_FAILED_SITS_VALIDATION;
                        return false;
                    }
                }catch(InvalidArgumentException $e){
                    $this->sits_sync->log_report(1, 'sits_client_request failed to instatiate module_cohort object - exception: ' . $e->getMessage());
                    $this->response = ERROR_FAILED_TO_INSTANTIATE_COHORT;
                    return false;
                }
                break;
            case 'program':
                try{
                    $cohort = new program_cohort((string)$this->xml->cohort->sits_code, (string)$this->xml->cohort->year_group, (string)$this->xml->cohort->acyear);
                    if(!$this->sits_sync->validate_program($cohort)){
                        $this->response = ERROR_COHORT_FAILED_SITS_VALIDATION;
                    }
                }catch(InvalidArgumentException $e){
                    $this->sits_sync->log_report(1, 'sits_client_request failed to instatiate program_cohort object - exception: ' . $e->getMessage());
                    $this->response = ERROR_FAILED_TO_INSTANTIATE_COHORT;
                    return false;
                }
        }
        
      	$mapping_exists = $this->sits_sync->read_mapping_for_course($cohort, (string)$this->xml->course_id);		
		
        if(is_object($mapping_exists)){
            if($mapping_exists->active){
                //Mapping already exists
                $this->response = ERROR_MAPPING_ALREADY_EXISTS;
                return false;
            }
        }

        $default = false; //Never going to create default mappings through the GUI
        
        if($cohort->type == 'module'){
            $period = $this->sits_sync->get_period_for_code($cohort->period_code, $cohort->academic_year);
        }else{
            $period = $this->sits_sync->get_period_for_code('AY', $cohort->academic_year); //AY is the default period code to the academic year, programs run on academic years
        } 
        
        switch($this->xml->unenrol){

            case 'specified':
                $manual = false;
                $start_date = $period->start;
                $end_date = new DateTime((string)$this->xml->end_date);
                $specified = true;
                break;
            case 'auto':
                $manual = false;
                $start_date = $period->start;
                $end_date = $period->end;
                $specified = false;
                break;
            case 'manual':
                $manual = true;
                $start_date = $period->start;
                $end_date = new DateTime('1970-01-01 00:00:00');
                $specified = false;
        }
        
        try{
            $mapping = new mapping($this->xml->course_id, $cohort, $start_date, $end_date, $manual, $default, $id = null, $specified);
        }catch(Exception $e){
            $this->sits_sync->log_report(1, 'sits_client_request failed to instatiate mapping object - exception: ' . $e->getMessage());
            $this->response = ERROR_FAILED_TO_INSTANTIATE_MAPPING;
            return false;
        }
        if($this->sits_sync->create_mapping($mapping)){
            $created_mapping = $this->sits_sync->read_mapping_for_course($mapping->cohort, $mapping->courseid);
            return $this->get_mapping_by_id($created_mapping->id);
        }else{
            $this->response = ERROR_FAILED_TO_CREATE_MAPPING;
            return false;
        }
    }

    /**
     * Given the appropriate XML from the client this function will delete the requested mapping
     * @param string $xml
     * @param object $this->sits_sync
     * @return a response code integer
     */
    private function deactivate_mapping(){
        $mapping = $this->sits_sync->read_mapping_from_id((integer)$this->xml->mapping_id);
        if($this->sits_sync->deactivate_mapping($mapping)){
            $this->response = SUCCESS;
        }else{
            $this->response = ERROR_FAILED_TO_DELETE_MAPPING;
        }
    }

    /**
     * Given the appropriate XML from the client this function will action each update the user has saved in their GUI
     * @param string $xml
     * @param object $this->sits_sync
     * @return a response code integer
     */
    private function batch_mapping_update(){
        $returnXML = <<<EOXML
<?xml version='1.0' standalone='yes'?>
<updates>;
</updates>
EOXML;

        $returnXMLObj = new SimpleXMLElement($returnXML);
        foreach($this->xml->children() as $courseMappingsXML){

            foreach($courseMappingsXML->attributes() as $key => $value){
                if($key == 'id'){
                    $courseid = $value;
                }
            }

            $returnXMLObj->addChild('course_id', $courseid);

            foreach($courseMappingsXML->children() as $mappingXML){
                $mapping = $this->sits_sync->read_mapping_from_id((integer)$mappingXML['id']);
                if(!is_object($mapping)){
                    $returnXMLObj->addChild('error', 'Failed to read mapping ' . $mappingXML['id']);
                }else{
                    $this->process_batch_element($mapping, $mappingXML, $returnXMLObj);
                }
            }
        }
        $this->content_type = 'text/xml';
        $this->response = $returnXMLObj->asXML();
    }

    /**
     * Given the objects by reference will process an individual element from batch xml sent from client
     * Broken out from batch_mapping_update for ease of reading
     * @param mapping object $mapping
     * @param SimpleXML object $mappingXML
     * @param SimpleXML object $returnXMLObj
     */
    private function process_batch_element(&$mapping, &$mappingXML, &$returnXMLObj){
        foreach($mappingXML->children() as $tag => $value){
            if($tag == 'delete'){
                $mapping->manual = true; 
                $mapping->specified = false;
                //This is a bit of a hack to allow this->update_mappings_for_period to play safe with 
                //reactivating sync/spec mappings in the knowledge that it wasn't a user who deactivated them
                if($this->sits_sync->deactivate_mapping($mapping)){
                    $returnXMLObj->addChild('deleted', $mapping->id);
                }else{
                    $returnXMLObj->addChild('error', 'ERROR failed to delete' . $mapping->id);
                }
            }

            if($tag == 'type'){
                switch($value){
                    case 'specified':
                        $mapping->manual = false;
                        foreach($mappingXML->children() as $tag => $value){
                            if($tag == 'end_date'){
                                $mapping->end = new DateTime($value);
                            }
                        }
                        $mapping->specified = true;
                    break;
                    case 'automatic':
                        $mapping->manual = false;
                        if($mapping->cohort->type == 'module'){
                            $period = $this->sits_sync->get_period_for_code($mapping->cohort->period_code, $mapping->cohort->academic_year);
                        }else{
                            $period = $this->sits_sync->get_period_for_code('AY', $mapping->cohort->academic_year); //AY is the default period code to the academic year, programs run on academic years
                        }
                        $mapping->end = $period->start;
                        $mapping->end = $period->end;
                        $mapping->specified = false;
                    break;
                    case 'manual':
                        $mapping->manual = true;
                        $mapping->end = $mapping->start;
                        $mapping->end = new DateTime('1970-01-01 00:00:00');
                        $mapping->specified = false;
                    break;
                }
                if($this->sits_sync->update_mapping($mapping)){
                    $returnXMLObj->addChild('updated', $mapping->id);
                }else{
                    $returnXMLObj->addChild('error', 'Failed to update ' . $mapping->id);
                }
            }
        }
    }

    /**
     * Given the appropriate XML from the client this function will add a user to Moodle
     * @param string $xml
     * @param object $this->sits_sync
     * @return boolean
     */
    private function add_user_by_bucs_id(){
		GLOBAL $DB;
        $returnXML = <<<EOXML
<?xml version='1.0' standalone='yes'?>
<added_user>
</added_user>    
EOXML;

        $returnXMLObj = new SimpleXMLElement($returnXML);
        $bucs_id = preg_replace('/[^a-zA-Z0-9-]/', '', (string)$this->xml->bucsid);  //Strip all characters except alphanumeric or '-' (for BUCS lite)
        if(!preg_match('/[A-Za-z0-9]|[A-Za-z0-9]+[-]/', $bucs_id) || strlen($bucs_id) > 12){ //Bit of crude validation, weedles out any proper nastiness
            $returnXMLObj->addChild('message',  $bucs_id . ' is not in a valid BUCS id format.');
            $this->content_type = 'text/xml';
            $this->response = $returnXMLObj->asXML();
            return false;
        }
        
        $user = $DB->get_record('user', array('username' => $bucs_id));
         
        if(is_object($user)){
            $returnXMLObj->addChild('message',  $user->username . ' is already a Moodle user.');
            $this->content_type = 'text/xml';
            $this->response = $returnXMLObj->asXML();
            return true;
        }

        if(!preg_match('/-/', $bucs_id)){ //And therefore not a BUCS Lite account
            $validate = $this->sits_sync->validate_bucs_id($bucs_id);
            if(!is_object($validate)){
                $returnXMLObj->addChild('message',  $bucs_id . ' is not a valid BUCS username. Please try again.');
                $this->content_type = 'text/xml';
                $this->response = $returnXMLObj->asXML();
                return false;
            }
        }
         
        $user = $this->sits_sync->user_by_username($bucs_id);
        
        if(is_object($user)){
            $returnXMLObj->addChild('message',  $user->username . ' has been successfully added as a Moodle user.');
            $this->content_type = 'text/xml';
            $this->response = $returnXMLObj->asXML();
            return true;
        }else{
            $returnXMLObj->addChild('message',  'Failed to add ' . $user->username . ' - please contact an adminstrator.');
            $this->content_type = 'text/xml';
            $this->response = $returnXMLObj->asXML();
            return false;
        }
    }

    /**
     * Given the appropriate XML from the client this function will sync all courses - a heavy operation.
     * @param string $xml
     * @param object $this->sits_sync
     * @return a response code integer
     */
    private function sync_all_courses(){

        GLOBAL $CFG;

        set_time_limit (0); //Cos this is going to take some time.
        $returnXML = <<<EOXML
<?xml version='1.0' standalone='yes'?>
<sync_all>
</sync_all>    
EOXML;

        $returnXMLObj = new SimpleXMLElement($returnXML);

        if($this->sits_sync->sync_all_courses()){
            $returnXMLObj->addChild('message',  'All course have been synced with SAMIS.  Please view the log for any errors.');
        }else{
            if($CFG->sits_sync_all == 1){
                $returnXMLObj->addChild('message',  'Sync All Courses is already in progress');
            }
            $returnXMLObj->addChild('message',  'The Sync All Courses action encountered a problem.  Please view the logs for errors.');
        }
        $this->content_type = 'text/xml';
        $this->response = $returnXMLObj->asXML();
    }

    /**
     * Processes the xml for the Add To Group operation and sets the response property to either a success or error code
     * @param string $group_id
     */
    private function add_to_group($group_id){
        $this->response = SUCCESS;
        foreach($this->xml->maps->children() as $map_id){
            $mapping = $this->sits_sync->read_mapping_from_id($map_id);
            if(is_object($mapping)){
                if(!$this->sits_sync->add_cohort_members_to_group($mapping->cohort, $group_id)){
                    $this->response = ERROR_FAILED_TO_ADD_TO_GROUP;
                }
            }
        }
    }

    /**
     * Processes the xml for the Create Group operation and sets the response property to either a success or error code
     * @param string
     */
    private function create_group(){
        $data = new StdClass;
        $data->courseid = $this->xml->course_id;
        $data->name = $this->xml->group_name;
        $data->description = ''; //groups_create_groups wants one, we don't take one, currently.  Perhaps we should, as an option.
        $group_id = groups_create_group($data);
        if($group_id === false){
            $this->response = ERROR_FAILED_TO_CREATE_GROUP;
        }else{
            $this->response = SUCCESS;
            $this->add_to_group($group_id);
        }
    }

    /**
     * Processes the xml for the Create Group operation and sets the response property to an XML string
     * @param string
     */
    private function get_groups(){

        $returnXML = <<<EOXML
<?xml version='1.0' standalone='yes'?>
<groups><cid>%s</cid>
EOXML;
        $returnXML = sprintf($returnXML, $this->xml->course_id);
        $groups = groups_get_all_groups($this->xml->course_id);
        if(is_array($groups)){
            foreach($groups as $id => $group){
                $returnXML .= '<group><id>' . $id . '</id><name>' . $group->name . '</name></group>';
            }
        }
        $returnXML .= '</groups>';
        $this->content_type = 'text/xml';
        $this->response = $returnXML;
    }

    /**
     * Processes the xml for the Get Mappings For Course operation and sets the response property to an XML string
     * @param string
     */
    private function get_mappings_for_course(){
        $returnXML = <<<EOXML
<?xml version='1.0' standalone='yes'?>
<maps><cid>%s</cid>
EOXML;
        $returnXML = sprintf($returnXML, $this->xml->course_id);
        $mappings = $this->sits_sync->read_mappings_for_course($this->xml->course_id);
        if(is_array($mappings)){
            foreach($mappings as $mapping){
                if($mapping->active){
                    $returnXML .= '<id>' . $mapping->id . '</id>';
                }
            }
        }
        $returnXML .= '</maps>';
        $this->content_type = 'text/xml';
        $this->response = $returnXML;
    }

    /**
     * Processes the xml for the Get Mapping By Id operation and sets the response property to an XML string on success
     * @param string
     * @return boolean
     */
    private function get_mapping_by_id($map_id){
        $returnXML = <<<EOXML
<?xml version='1.0' standalone='yes'?><mapdoc>
EOXML;
        $mapping = $this->sits_sync->read_mapping_from_id($map_id);
        if(is_object($mapping)){
            $returnXML .= $this->build_mapping_xml($mapping);
            $returnXML .= '</mapdoc>';
            $this->content_type = 'text/xml';
            $this->response = $returnXML;
            return true;
        }else{
            return false;
        }
    }

    /**
     * Helper function to build an XML response from a mapping object
     * @param mapping object $mapping
     */
    private function build_mapping_xml($mapping){

        $mappingXML = '<map>';
        $mappingXML .= '<id>%s</id>';
        $mappingXML .= '<course_id>%s</course_id>';
        $mappingXML .= '<manual>%s</manual>';
        $mappingXML .= '<default>%s</default>';
        $mappingXML .= '<specified>%s</specified>';
        $mappingXML .= '<start_date>%s</start_date>';
        $mappingXML .= '<end_date>%s</end_date>';

        switch($mapping->cohort->type){
            case 'module':
                $modMapXML = '<cohort>';
                $modMapXML .= '<type>module</type>';
                $modMapXML .= '<sits_code>%s</sits_code>';
                $modMapXML .= '<acyear>%s</acyear>';
                $modMapXML .= '<period_code>%s</period_code>';
                $modMapXML .= '</cohort>';
                $modMapXML .= '</map>';

                $xml = sprintf($mappingXML . $modMapXML, $mapping->id, $mapping->courseid,
                $mapping->manual,
                $mapping->default,
                $mapping->specified,
                $mapping->start->format('Y-m-d'),
                $mapping->end->format('Y-m-d'),
                $mapping->cohort->sits_code,
                $mapping->cohort->academic_year,
                $mapping->cohort->period_code);
                break;
            case 'program':
                $progMapXML = '<cohort>';
                $progMapXML .= '<type>program</type>';
                $progMapXML .= '<sits_code>%s</sits_code>';
                $progMapXML .= '<acyear>%s</acyear>';
                $progMapXML .= '<year_group>%s</year_group>';
                $progMapXML .= '</cohort>';
                $progMapXML .= '</map>';

                $xml = sprintf($mappingXML . $progMapXML, $mapping->id, $mapping->courseid,
                $mapping->manual,
                $mapping->default,
                $mapping->specified,
                $mapping->start->format('Y-m-d'),
                $mapping->end->format('Y-m-d'),
                $mapping->cohort->sits_code,
                $mapping->cohort->academic_year,
                $mapping->cohort->year_group);
                break;
        }
        return $xml;
    }
    
    private function get_periods(){
    	GLOBAL $DB;
    	
        $returnXML = <<<EOXML
<?xml version='1.0' standalone='yes'?><perioddoc>
EOXML;
        $periods = $DB->get_records('sits_period');
        if(is_array($periods)){
            $periodXML = '<period>';
            $periodXML .= '<id>%s</id>';            
            $periodXML .= '<code>%s</code>';
            $periodXML .= '<acyear>%s</acyear>';
            $periodXML .= '<start>%s</start>';
            $periodXML .= '<end>%s</end>';
            $periodXML .= '<revert>%s</revert>';
            $periodXML .= '</period>';
            foreach($periods as $period){
                $returnXML .= sprintf($periodXML, $period->id, 
                                                $period->period_code,
                                                $period->acyear,
                                                substr($period->start_date, 0, -9),
                                                substr($period->end_date, 0, -9),
                                                $period->revert);
            }            
        }
        $returnXML .= '</perioddoc>';
        $this->content_type = 'text/xml';
        $this->response = $returnXML;
    }
    
    private function save_periods(){
        $this->response = SUCCESS;
        foreach($this->xml->children() as $period_alter_xml){
            foreach($period_alter_xml->children() as $tag => $value){
                switch($tag){
                    case 'id':
                        $id = (int)$value;
                    break;
                    case 'code':
                        $code = (string)$value;
                    break;
                    case 'acyear':
                        $acyear = (string)$value;
                    break;
                    case 'start':
                        $start = (string)$value;
                    break;
                    case 'end':
                        $end = (string)$value;
                    break;
                    case 'revert':
                        if($value == 1){
                            $revert = true;
                        }else{
                            $revert = false;
                        }
                    break;
                    case 'new_alter':
                        if($value == 1){
                            $id = null;
                        }
                    break;
                }
            }
            $period_alteration = new period_alteration($code, $acyear, $start, $end, $revert, $id);
            if(!$this->sits_sync->alter_period($period_alteration)){
                $this->response = ERROR_FAILED_TO_UPDATE_PERIODS;
            }           
        }

        if(!$this->sits_sync->update_all_mapping_periods()){
            $this->response = ERROR_FAILED_TO_UPDATE_PERIODS;
        }
    }
    
    private function reset_sync_flag(){
        if(set_config('sits_sync_all', 0)){
            $this->response = SUCCESS;
        }else{
            $this->response = ERROR_ERROR_FAILED_TO_RESET_SYNC_FLAG;
        }
    }
}
?>
