<?php
/**
 * Language entries for SITS Block
 * @package    blocks
 * @subpackage sits
 * @copyright  2011 University of Bath
 * @author     Alex Lydiate {@link http://alexlydiate.co.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'SAMIS';
$string['sits'] = 'SAMIS Integration';

// Bulk Restore Lang Strings
$string['link_cohorts'] = 'Manage Mappings';
$string['add_user'] = 'Add User';
$string['sits_admin'] = 'Admin Interface';
$string['sits_settings'] = 'Global Settings';
$string['sits_gui_label'] = 'Enable Block';
$string['sits_gui_desc'] = 'Disables the user interface and replaces the links in the block with the message set below.<br/>
                            If the SAMIS service is going down, switch this to Off';
$string['sits_disable_label'] = 'Disable Block Message';
$string['sits_disable_desc'] = '';
$string['sits_orphan_label'] = 'Orphaned Mappings Removal';
$string['sits_orphan_desc'] = '<b>On</b> means that all orphaned mappings will be removed before a Full Sync
                               <br/>This is will add considerable time to the duration of a full sync, but is necessary at least periodically.';
$string['sits_disable_text'] = '';
$string['sits_cron_label'] = 'Full Sync setting';
$string['config_select_desc'] = '<b>Daily</b> means a full sync runs once a day in the hour preceeding that set below<br/><b>Continuous</b> means it will keep as up to date as possible<br/><b>Off</b> means it will not run at all';
$string['config_runtime_label'] = 'Daily Sync hour';
$string['config_runtime_desc'] = 'That hour preceeding which the "Daily Sync" will run in, once daily (see above).';
$string['admin_interface'] = 'SAMIS Admin Interface';
$string['enrol_not_linked'] = 'Not mapped from SAMIS';
?>