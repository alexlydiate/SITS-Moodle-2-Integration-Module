<?php
/*
 * @package    enrol
 * @subpackage sits
 * @copyright  2011 University of Bath
 * @author     Alex Lydiate {@link http://alexlydiate.co.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG, $USER, $COURSE;

if(isset($_REQUEST['courseid'])){
    $courseid = $_REQUEST['courseid'] ? $_REQUEST['courseid'] : $_REQUEST['courseid'];
}else{
    //$courseid = '';
}
if(isset($_REQUEST['action'])){
    $action = $_REQUEST['action'];
}
if(isset($_REQUEST['group_reference'])){
    $groupid = $_REQUEST['group_reference'] ? $_REQUEST['group_reference'] : '';
}
if(isset($_REQUEST['groupname'])){
    $groupname = $_REQUEST['groupname'] ? $_REQUEST['groupname'] : '';
}

if(isset($_REQUEST['listcohorts'])){
    $selcohorts = $_REQUEST['listcohorts'];
}

if(isset($_REQUEST['groupsubmit']) OR isset($_REQUEST['gotogroups'])) //Brought to this page by a group submit action
{
    $disp = 'group';
    $courseid = $_REQUEST['grp_course'] ? $_REQUEST['grp_course'] : $_REQUEST['courseid'];
}
elseif(isset($_REQUEST['tabgroup'])) //Brought to this page by either a tab switch or from the main Moodle site.
{
    $disp = $_REQUEST['tabgroup'] ? 'group' : 'cohort'; //If tabbed to group then use group otherwise cohort is good for both other option and default load.
    $courseid = $_REQUEST['courseid'];
}

$context = get_context_instance(CONTEXT_COURSE, $courseid);


if(has_capability('moodle/course:manageactivities', $context))
{
    //Here is the business end of things
    $sits_sync = new enrol_sits_plugin();
    $user_courses = enrol_get_my_courses();
    $course_is_tutor_on = array();

    foreach($user_courses as $cur_course)
    {
        $context = get_context_instance(CONTEXT_COURSE, $cur_course->id);
        if(has_capability('moodle/course:manageactivities', $context)){
            if($cur_course->idnumber != 'AD_Staff_area_new'){ //Which is the only thing the vast swathe of code incurred by get_prohibited_courses returns
                    $course_is_tutor_on[] = $cur_course;
            }
        }
    }

    $existinggroups = groups_get_all_groups($courseid);
    if(!$existinggroups) { $existinggroups = array(); }
    if(isset($action))
    {
        $users_from_mappings = array();

        foreach($selcohorts as $mapping_id){
            $new_users_array = $sits_sync->read_users_for_mapping($mapping_id);
            if(is_array($new_users_array)){
                $users_from_mappings = array_merge($users_from_mappings, $new_users_array);
            }
        }
        switch($action){
            case 'create':

                $data->courseid = $courseid;
                $data->name = $groupname;
                $data->description = '';

                $newid = groups_create_group($data);

                if($newid)
                {
                    foreach($users_from_mappings as $userid){
                        groups_add_member($newid,$userid);
                    }
                    $result = "Your new group has been created. Please return to your course to view it, or continue to add further groups." .
    				"<br/>Please note that if the course is not yet synchronised with SAMIS, the students will not appear in that group " .
    				"until they are enrolled.";

                }
                break;
            case 'add':
                foreach($users_from_mappings as $userid)
                {
                    groups_add_member($groupid,$userid);
                }
                $result = "The cohort has been added to this group. Please return to your course to view the new members, or continue to add further groups." .
    			"<br/>Please note that membership in a group may take up to 24 hours if the student is not already enrolled on the course.";
                break;
        }
    }

    $course_and_groups = array(); //build array of course ids and groups
    foreach($course_is_tutor_on as $cur_course)
    {
        $groups = groups_get_all_groups($cur_course->id);
        $course_and_groups[$cur_course->id] = $groups;
    }

    $j_array_grp = 'var courses_and_groups = new Array();' . "\n";

    foreach($course_and_groups as $cur_key => $cur_groups)
    {

        if($cur_groups){
            $j_array_grp .= 'courses_and_groups[' . $cur_key . '] = {';
            foreach($cur_groups as $cur_group){
                $j_array_grp .=  '' . $cur_group->id . ' : "' . $cur_group->name . '", ';
            }
            $j_array_grp = rtrim($j_array_grp,',');
            $j_array_grp .= '};' . "\n";
        }else{
            $j_array_grp .= "courses_and_groups[" . $cur_key . "] = new Array('no groups');" . "\n";
        }
    }
}else{
    die('This interface is for teachers and administrators only');
}
///Functions below

function get_years($date)
{
    $start_year = 1970;
    $end_year = 2015;

    $years_html = '';

    $year = $date->format('Y');

    for($count = $start_year; $count <= $end_year; $count++)
    {
        if($count==$year)
        {
            $years_html .= '<option value="' . $count . '" selected="selected">' . $count . '</option>';
        }
        else
        {
            $years_html .= '<option value="' . $count . '">' . $count . '</option>';
        }
    }
    return $years_html;
}

function get_months($date)
{
    $months_html = '';
    $month = $date->format('n');
    $months = array('JAN','FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC');
    $count = 1;
    foreach($months as $cur_month)
    {
        if($count==$month)
        {
            $months_html .= '<option value="' . $cur_month . '" selected="selected">' . $cur_month . '</option>';
        }
        else
        {
            $months_html .= '<option value="' . $cur_month . '">' . $cur_month . '</option>';
        }
        $count++;
    }
    return $months_html;
}

function get_days($date)
{
    $days_html = '';
    $day = $date->format('j');
    for($count = 1; $count < 31; $count++)
    {
        if($count==$day)
        {
            $days_html .= '<option value="' . $count . '" selected="selected">' . $count . '</option>';
        }
        else
        {
            $days_html .= '<option value="' . $count . '">' . $count . '</option>';
        }
    }
    return $days_html;
}

function get_unenrolment_types($ui_map_type)
{

    if($ui_map_type == 'manual')
    {
        $types_html = '<option value="automatic">Synchronise with SAMIS</option>' .
            '<option value="specified">Specified Date</option>' .  
            '<option value="manual" selected="selected">Manual</option>';
    }
    elseif($ui_map_type == 'specified')
    {
        $types_html = '<option value="automatic">Synchronise with SAMIS</option>' .
            '<option value="specified" selected="selected">Specified Date</option>' .  
            '<option value="manual">Manual</option>';
    }
    else
    {
        $types_html = '<option value="automatic" selected="selected">Synchronise with SAMIS</option>' .
            '<option value="specified">Specified Date</option>' .  
            '<option value="manual">Manual</option>';
    }
    return $types_html;
}

function return_academic_year(){
    $date = date('m-Y');
    $date_array = explode('-', $date);
    if(intval($date_array[0]) >= 7){
        $academic_year = strval(intval($date_array[1])) . '/' . substr(strval(intval($date_array[1]) + 1), -1);
    }else{
        $academic_year = strval(intval($date_array[1]) - 1) . '/' . substr(strval(intval($date_array[1])), -1);
    }

    return $academic_year;
}

?>