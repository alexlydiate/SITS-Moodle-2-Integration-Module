<?php
/**
 * @package    local
 * @subpackage sits
 * @copyright  2011 University of Bath
 * @author     Alex Lydiate {@link http://alexlydiate.co.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface i_sits_sync {
    /**
     * Given a valid Moodle course id will sync all mappings related for that course
     * @param integer $course_id Moodle course id
     * @return boolean
     */
    public function sync_course($course_id);
    /**
     * Syncs all Moodle courses with SITS
     * @return boolean
     */
    public function sync_all_courses();
    /**
     * Given a valid instance of the Mapping class will create that mapping in Moodle
     * If one exists but is deactivated, it will reactivate it
     * @param mapping object $mapping
     * @return boolean
     */
    public function create_mapping(&$mapping);
    /**
     * Given a valid Moodle course id will return an array of that course's mapping objects
     * @param string $sits_code
     * @return mixed, array of mapping objects on success, or false
     */
    public function read_mappings_for_course($course_id);
    /**
     * Given a valid mapping object relating to a current mapping will update that mapping
     * @param $mapping
     * @return boolean
     */
    public function update_mapping(&$mapping);
    /**
     * Given a valid mapping object relating to a current mapping, this will deactivate that mapping;.
     * It will not delete the record
     * @param $mapping
     * @return boolean
     */
    public function deactivate_mapping(&$mapping);
    /**
     * Given a valid mapping object relating to a current mapping will delete that mapping record
     * @param $mapping
     * @return boolean
     */
    public function delete_mapping(&$mapping);
    /**
     * Given a valid cohort object and Moodle course id, returns a mapping object for that mapping if one exists, else false
     * @param cohort object $cohort
     * @param integer $courseid
     * @return Mapping object if one exists, false if not
     */
    public function read_mapping_for_course(&$cohort, $courseid);
    /**
     * Given a valid mapping id, returns a mapping object, or false on faliure
     * @param unknown_type $mapping_id
     * @return mixed, mapping object or false on faliure
     */
    public function read_mapping_from_id($mapping_id);
    /**
     * Returns an array of user ids associated with a given mapping id
     * Created as part of mimicing the old SAMIS block's Groups interface
     * @param int $mapping_id
     * @return mixed, numeric array of userid objects on success, boolean false on faliure
     */
    public function read_users_for_mapping($mapping_id);
    /**
     * Returns a Moodle user object given a BUCS username, or any username, actually.
     * If a user with that username exists already it will return that, else it'll create a user and return that
     * @param string $username
     * @return object $user or false if one can't be found or created.
     */
    public function user_by_username($username);
    /**
     * Adds the members of the given Cohort object to the Moodle group of the given id
     * @param cohort object $cohort
     * @param int $groupid
     * @return boolean
     */
    public function add_cohort_members_to_group($cohort, $groupid);
    /**
     * Adds or updates an alteration to a SITS period slot, setting either the start date, end date or both
     * to a date different from that defined in SITS
     * @param period_alteration object $period_alteration
     */
    public function alter_period(&$period_alteration);    
    
    /**
     * Updates all mappings period start and end dates
     * @return boolean
     */
    public function update_all_mapping_periods();
    
     /**
     * Cycles through and validates all mappings - if they are invalid, that is to say if either the SITS cohort or Moodle course
     * does not exist, the mapping will be deleted.
     * @return boolean
     */
    public function remove_orphaned_mappings();
    
    /**
     * Gets SITS formatted current academic year
     * @return string
     */
    public function get_current_academic_year();
}
	