<?php
/**
 * Script to migrate mappings from the SITS/Moodle 1.9 block to this
 * @package    enrol
 * @subpackage sits
 * @copyright  2011 University of Bath
 * @author     Alex Lydiate {@link http://alexlydiate.co.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
print "\n\n";
$root = '/home/al412/repositories/Moodle/branches/Moodle2';

define('CLI_SCRIPT', true);
require_once($root . '/config.php');
require_once($CFG->dirroot . '/local/sits/lib/mapping.class.php');
require_once($CFG->dirroot . '/local/sits/lib/cohort.class.php');



$SITS_sync = enrol_get_plugin('sits');
GLOBAL $DB;

$mf_dbtype = 'mysql';
$mf_dbhost = 'localhost';
$mf_dbname = 'moodle9_live';
$mf_user = 'swede';
$mf_password = 'sw3d3';

$dbh_mf = pdo_connect($mf_dbtype,
$mf_dbhost,
$mf_dbname,
$mf_user,
$mf_password);
 
if(!$dbh_mf){
    exit('Could not establish connection to the migrate-from database');
}

$created = 0;
$failed = 0;
$no_course = array();

$sql = 'select * from mdl5_sits_mappings';

$old_map_stm = $dbh_mf->prepare($sql);
$old_map_stm->execute();

while($row = $old_map_stm->fetchObject()){
        
	if($row->type == 'module'){
	try{
            $cohort = new module_cohort($row->sits_code, $row->period_code, $row->acyear);
        }catch(InvalidArgumentException $e){
            print 'mod cohort exception: ' . $e->getMessage() . "\n";
        }
    }else{
        try{
            $cohort = new program_cohort($row->sits_code, $row->year_group, $row->acyear);
        }catch(InvalidArgumentException $e){
            print 'prog cohort exception: ' . $e->getMessage() . "\n";
        }
    }

    $course = $DB->get_record('course', array('idnumber' => $row->sits_code));

    if(is_object($course)){
		try{
	    	$mapping = new mapping($course->id, 
	    							$cohort, 
	    							new DateTime($row->start_date), 
	    							new DateTime($row->end_date), 
	    							$row->manual, 
	    							$row->default_map, 
	    							$id = null, 
	    							$row->specified, 
	    							$row->active = null);
	    }catch(Exception $e){
	        print 'Failed to create automatic mapping for ' . $course->id . ' to cohort :';
	        print_r($cohort);
	        print $e->message;
	    }

	    if($SITS_sync->create_mapping($mapping)){
	        $created++;
	    }else{
	        $failed++;
	    }
    }else{
        $no_course[] = $row->sits_code;
    }
}

print 'done';

function pdo_connect(&$dbtype, &$dbhost, &$dbname, &$dbuser, &$dbpass){
    $connect_string = '%s:host=%s;dbname=%s';
    return new PDO(sprintf($connect_string, $dbtype, $dbhost, $dbname), $dbuser, $dbpass);
}
?>
