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

require_login();
if (isguestuser()) {
    print_error('guestsarenotallowed', '', $returnurl); //FIXME need more security than this
}

//require_login($course);
//require_capability('enrol/manual:config', $context);


//$PAGE->set_url('/blocks/sits/view.php', array('courseid'=>$course->id));
//$PAGE->requires->js('/enrol/sits/js/sits_block.js', true); //commented for debug, stuck in manually, plays better with Firebug
$PAGE->set_pagelayout('popup');
$PAGE->requires->css('/blocks/sits/gui/css/samis_user_interface.css');

$plugin = enrol_get_plugin('sits');
$context = get_context_instance(CONTEXT_SYSTEM); //This can't be right, but it does work...need the context for this block?
$PAGE->set_context($context);
$PAGE->set_title(get_string('manage_mappings', 'enrol_sits'));
$PAGE->set_heading(' '); //Set to a space in order to display the logo in 'popup' layout. al412.

echo $OUTPUT->header();
if(true){ //access to insert here?>
    <body class="yui-skin-sam">
    <div id="canvas">
        <!--<div class="bath-header">
             <div class="logo-box"><a href="http://www.bath.ac.uk/"> <img
                src="http://www.bath.ac.uk/graphics/logos/logo-hp-trans.gif"
                alt="University of Bath" /> </a>
            </div>  
            <div id="header" class=" clearfix">
                <h1 class="headermain"><?php print get_string('admin_interface','block_sits')?></h1>
            </div>
        </div> -->
        <div id = "container">
            <div id = "period_container" class="admin_box">
                <div id = "period_code_div">
                <div class = "admin_instruction">
                    <p><b>Period Alterations</b></p>
                    <p>The table below is used to change the start and end dates for period slots.<br/>  
                    This affects only Moodle, it makes no changes to SITS.  For the SAMIS-defined slots, go to <a href="http://www.bath.ac.uk/catalogues/information/staff/period-slot-dates" target="_blank">SAMIS Period Slot Dates for the 2011/12 Academic Session</a>.</p>
                    Flagging an existing Alteration to Revert will remove it and all mappings with that period will sync with SITS.</p>
                    <p><b>Please note changing period start and end dates may result in significant changes to enrollments.</b></p>
                </div>                
                    <table id = "period_code_table">
                    </table>
                </div>
                <div id = "period_code_controls" class="admin_controls">
                    <input type="submit" id = "period_code_add" value="Add Alteration" onClick="sits_block.add_period_alteration()"/>
                    <input type="submit" id = "period_code_save" value="Update Periods" onClick="sits_block.period_save()"/>
                </div>
                <div id="period_code_load" class="admin_controls" style="display: none;">
                    <img class="liloader" src="./images/liloader.gif" alt="Loading" style="float: left;">
                    <div id=period_code_load" style="float: left;">Updating period codes for all mappings - please wait</div>
                </div>
            </div>
             <div id = "sync_reset" class="admin_box">
                <div class = "admin_instruction">
                    <p><b>Full Sync Reset</b></p>
                    <p>If a fatal error occurs during a Full Sync, the flag indicating a full sync is in progress does not switch back.<br/>
                    It is important to understand why and, if necessary, fix the problem before trying again.<br/></p>
                    <p><b>The button below will reset the flag after such an incident, allowing a Full Sync once again.<br/></b></p>
                    
                </div>
                <div id = "reset_button" class="admin_controls">            
                <input type="submit" value = "Reset Full Sync Flag" onclick="sits_block.reset_sync_flag()">
                </div>
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
    <script>YAHOO.util.Event.onDOMReady(sits_block.admin_init);</script>
    </body>
    </html> 
<?php
echo $OUTPUT->footer();
}else{
    die('This interface is for administrators only');
}
?>