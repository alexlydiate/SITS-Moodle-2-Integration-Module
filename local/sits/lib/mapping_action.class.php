<?php
/**
 * Mapping action class, defining an entry to the sits_mappings_history table
 * @package    local
 * @subpackage sits
 * @copyright  2011 University of Bath
 * @author     Alex Lydiate {@link http://alexlydiate.co.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mapping_action {

    public $map_id; //The map id of the mapping to which the action is being made
    public $userid; //Integer, Moodle user id of user making the action
    public $action; //String, the action that has been made, either 'create', 'update', 'deactivate', 'reactivate' or 'delete'
    public $method; //String, the id of the method to which the mapping has been changed, if that change has been made;
    //Either 'automatic', 'specified' or 'manual'
    public $end; //DateTime object - the changed end date, if the end date is being changed
    public $time; //DateTime object - when the action occurred
     
    public function __construct($map_id, $userid, $action, $method, $end, $time = null){

        $this->map_id = $map_id;
        $this->userid = $userid;
        $this->action = $action;
        $this->method = $method;
        $this->end = $end;
        if(is_null($time)){
            $this->time = new DateTime();
        }

        $this->validate_mapping_action_strings();
    }

    /**
     * Validates that the $action string is one of the acceptable five
     * @param string $action
     * @return boolean
     */
    private function validate_action($action){
        if($action === 'create' || 'update' || 'deactivate' || 'reactivate' || 'delete'){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Validates that the $method string is one of the acceptable three
     * @param string $method
     */
    private function validate_method($method){
        if($method === 'automatic' || 'specified' || 'manual'){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Checks that the object's action and method strings are appropriate; throws
     * an InvalidArgumentException if not.
     */
    private function validate_mapping_action_strings(){
         
        if(!$this->validate_action($this->action)){
            throw new InvalidArgumentException('$action is invalid');
        }

        if(!$this->validate_method($this->method)){
            throw new InvalidArgumentException('$method is invalid');
        }
    }
}

