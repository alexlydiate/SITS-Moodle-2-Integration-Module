<?php
/*
 * @package    enrol
 * @subpackage sits
 * @copyright  2011 University of Bath
 * @author     Alex Lydiate {@link http://alexlydiate.co.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once('edit_form.php');

$courseid = required_param('courseid', PARAM_INT);

$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
$context = get_context_instance(CONTEXT_COURSE, $course->id, MUST_EXIST);

require_login($course);
/*require_capability('enrol/sits:config', $context);*/
$plugin = enrol_get_plugin('sits');
$PAGE->set_url('/enrol/sits/edit.php', array('courseid'=>$course->id));
$PAGE->set_pagelayout('admin');
$instance_id = $plugin->add_instance($course);
$return = new moodle_url('/enrol/instances.php', array('id'=>$course->id));
if(!is_null($instance_id)){
	redirect($return);
}else{
	throw coding_exception;
}



if (!enrol_is_enabled('sits')) {
    redirect($return);
}



if ($instances = $DB->get_records('enrol', array('courseid'=>$course->id, 'enrol'=>'sits'), 'id ASC')) {
    $instance = array_shift($instances);
    if ($instances) {
        // oh - we allow only one instance per course!!
        foreach ($instances as $del) {
            $plugin->delete_instance($del);
        }
    }
} else {
    require_capability('moodle/course:enrolconfig', $context);
    // no instance yet, we have to add new instance
    navigation_node::override_active_url(new moodle_url('/enrol/instances.php', array('id'=>$course->id)));
    $instance = new stdClass();
    $instance->id       = null;
    $instance->courseid = $course->id;
}

$mform = new enrol_sits_edit_form(NULL, array($instance, $plugin, $context));

if ($mform->is_cancelled()) {
    redirect($return);

} else if ($data = $mform->get_data()) {
    if ($instance->id) {
        $instance->status       = $data->status;
        $instance->enrolperiod  = $data->enrolperiod;
        $instance->roleid       = $data->roleid;
        $instance->timemodified = time();
        $DB->update_record('enrol', $instance);

    } else {
        $fields = array('status'=>$data->status, 'enrolperiod'=>$data->enrolperiod, 'roleid'=>$data->roleid);
        $plugin->add_instance($course, $fields);
    }

    redirect($return);
}

$PAGE->set_title(get_string('pluginname', 'enrol_sits'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'enrol_sits'));
$mform->display();
echo $OUTPUT->footer();
