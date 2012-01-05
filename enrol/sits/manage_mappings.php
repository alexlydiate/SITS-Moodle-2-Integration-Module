<?php
/*
* @package    enrol
* @subpackage sits
* @copyright  2011 University of Bath
* @author     Alex Lydiate {@link http://alexlydiate.co.uk}
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
require_once('../../config.php');

require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/lib/grouplib.php');
require_once($CFG->dirroot . '/enrol/sits/lib.php');  
require_once($CFG->dirroot . '/local/sits/config/sits_config.php');

require_login();
if (isguestuser()) {
    print_error('guestsarenotallowed', '', $returnurl); //FIXME need more security than this
}
//Markup starts here
$courseid = required_param('courseid', PARAM_INT);

$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
$context = get_context_instance(CONTEXT_COURSE, $course->id, MUST_EXIST);

require_login($course);
require_capability('enrol/manual:config', $context);

$PAGE->set_url('/blocks/sits/view.php', array('courseid'=>$course->id));
//$PAGE->requires->js('/enrol/sits/js/sits_block.js', true); //commented for debug, stuck in manually, plays better with Firebug
$PAGE->set_pagelayout('admin');

$plugin = enrol_get_plugin('sits');

$PAGE->set_title(get_string('manage_mappings', 'enrol_sits'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
?>
<script src ="js/sits_block.js" type="text/javascript"></script>
<?php
echo $OUTPUT->heading(get_string('manage_mappings', 'enrol_sits'));

$template = file_get_contents($CFG->dirroot . '/enrol/sits/map.tpl.txt');

echo sprintf($template, $course->id, $course->fullname, $course->idnumber, $course->shortname);
?>
<script>YAHOO.util.Event.onDOMReady(sits_block.course_init('<?php echo($course->id); ?>'));</script>
<?php echo $OUTPUT->footer(); ?>