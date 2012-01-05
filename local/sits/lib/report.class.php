<?php
/**
 * This doesn't do a lot right now, apart from make available to other classes the log_report function
 * It maybe that we want to do other things with the reporting in the future,
 * email notifications or whatnot, and this gives a central place to do it.
 *
 * @package    local
 * @subpackage sits
 * @copyright  2011 University of Bath
 * @author     Alex Lydiate {@link http://alexlydiate.co.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report {
    /**
     * @desc Writes a log message to $this->report array with type and unix timestamp
     * @param int $type 0 = process, 1 = warning, 2 = fatal.
     * @param string $message the message to be logged
     */
    public function log_report($type, $message, $output='log'){
        switch($type){
            case 0:
            default:
                $action = 'process';
                break;
            case 1:
                $action = 'warning';
                break;
            case 2:
                $action = 'fatal';
                break;
        }

        switch($output){
            case 'log':
            default:
                add_to_log(0, 'SITS', $action, '', $message);
                break;
            case 'mtrace':
                mtrace($action.' '.$message);
                break;
        }
    }
}