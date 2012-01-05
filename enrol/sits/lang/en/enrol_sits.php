<?php

/*
 * @package    enrol
 * @subpackage sits
 * @copyright  2011 University of Bath
 * @author     Alex Lydiate {@link http://alexlydiate.co.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['alterstatus'] = 'Alter status';
$string['altertimeend'] = 'Alter end time';
$string['altertimestart'] = 'Alter start time';
$string['assignrole'] = 'Assign role';
$string['confirmbulkdeleteenrolment'] = 'Are you sure you want to delete these users enrolments?';
$string['defaultperiod'] = 'Default enrolment duration';
$string['defaultperiod_desc'] = 'Default length of time that the enrolment is valid (in seconds). If set to zero, the enrolment duration will be unlimited by default.';
$string['defaultperiod_help'] = 'Default length of time that the enrolment is valid, starting with the moment the user is enrolled. If disabled, the enrolment duration will be unlimited by default.';
$string['deleteselectedusers'] = 'Delete selected user enrolments';
$string['editenrolment'] = 'Edit enrolment';
$string['editselectedusers'] = 'Edit selected user enrolments';
$string['enrolledincourserole'] = 'Enrolled in "{$a->course}" as "{$a->role}"';
$string['enrolusers'] = 'Enrol users';
$string['manage_mappings'] = 'Manage Mappings';
$string['pluginname'] = 'SITS';
$string['pluginname_desc'] = 'The SITS enrolments plug utilises the SITS libraries in local/sits to map and enrol SITS cohorts to Moodle courses.';

$string['sits:config'] = 'Configure SITS instances';
$string['sits:enrol'] = 'Enrol users';
$string['sits:manage'] = 'Manage user enrolments';
$string['sits:unenrol'] = 'Unenrol users from the course';
$string['sits:unenrolself'] = 'Unenrol self from the course';

$string['status'] = 'Enable SITS enrolments';
$string['status_desc'] = 'Allow course access of internally enrolled users. This should be kept enabled in most cases.';
$string['status_help'] = 'This setting determines whether users can be enrolled sitsly, via a link in the course administration settings, by a user with appropriate permissions such as a teacher.';
$string['statusenabled'] = 'Enabled';
$string['statusdisabled'] = 'Disabled';
$string['unenrol'] = 'Unenrol user';
$string['unenrolselectedusers'] = 'Unenrol selected users';
$string['unenrolselfconfirm'] = 'Do you really want to unenrol yourself from course "{$a}"?';
$string['unenroluser'] = 'Do you really want to unenrol "{$a->user}" from course "{$a->course}"?';
$string['unenrolusers'] = 'Unenrol users';
$string['wscannotenrol'] = 'Plugin instance cannot enrol a user in the course id = {$a->courseid}';
$string['wsnoinstance'] = 'SITS enrolment plugin instance doesn\'t exist or is disabled for the course (id = {$a->courseid})';
$string['wsusercannotassign'] = 'You don\'t have the permission to assign this role ({$a->roleid}) to this user ({$a->userid}) in this course({$a->courseid}).';
