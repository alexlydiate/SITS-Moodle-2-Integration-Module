<?php
/**
 * @package    blocks
 * @subpackage sits
 * @copyright  2011 University of Bath
 * @author     Alex Lydiate {@link http://alexlydiate.co.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../config.php');
GLOBAL $CFG;

require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/lib/grouplib.php');
require_once($CFG->dirroot . '/enrol/sits/lib.php');  //This used to require samis_management...not no more!
require_once($CFG->dirroot . '/local/sits/config/sits_config.php');
require_once('./samis_interface_logic.php');//This is where the business end of the PHP is now residing

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
$PAGE->set_pagelayout('popup');
$PAGE->requires->css('/blocks/sits/gui/css/samis_user_interface.css');

$plugin = enrol_get_plugin('sits');


$PAGE->set_title(get_string('link_cohorts', 'block_sits'));
$PAGE->set_heading(' '); //Set to a space in order to display the logo in 'popup' layout. Hack. al412.

echo $OUTPUT->header();?>

<!--  <body> -->
<div id="canvas">
	<div id="header" class=" clearfix">
	<h1><?php print get_string('link_cohorts', 'block_sits')?></h1>
	</div>
	
	<div> 
			<div id="course_filter" class="filter">
				<input id="course_search_input" type="text" onkeyup="sits_block.filterCourses(this.value)"></input>
				<span id = "filter_message">Filter by course name or idnumber</span>
				<input type="submit" style="float: right;" value="Close Cohorts and Groups Interface" onclick="sits_block.exit();" />
			</div>
	</div>
	<div id="cohorts">
		<br/>
		<div id="course_search"></div>
		<div id="courses">
		<?php
			if($course_is_tutor_on){
				$template = file_get_contents($CFG->dirroot . '/blocks/sits/gui/map.tpl.txt');
				foreach($course_is_tutor_on as $cur_course){
					echo sprintf($template, $cur_course->id, $cur_course->fullname, $cur_course->idnumber, $cur_course->shortname);
				}
			}
		?>
		</div>
	</div>
</div>
<!-- JS - to be called into <head> after dev - plays better with Firebug like this-->
<!-- YUI Base Dependency -->
<script src="./js/yui/yahoo-min.js"
    type="text/javascript"></script>
<!-- YUI Used for Custom Events and event listener bindings -->
<script src="./js/yui/event-min.js"
    type="text/javascript"></script>
<!-- YUI AJAX connection -->
<script
    src="./js/yui/connection-min.js"
    type="text/javascript"></script>
<!-- YUI DOM Source file -->
<script src="./js/yui/dom-min.js"></script>
<!-- YUI Element, depends on DOM -->
<script
    src="./js/yui/element-min.js"></script>
<script src="./js/sits_block.js" type="text/javascript"></script>
<!--  end JS -->

<script>YAHOO.util.Event.onDOMReady(sits_block.user_init);</script>
<br/>
<!--  </body>
</html>-->

<?php echo $OUTPUT->footer(); ?>
