<?PHP // Hacked together testers league table
global $CFG, $USER, $COURSE;
require_once('../../../../config.php');
require_once($CFG->libdir.'/tablelib.php');
$tablecolumns = array('Tester', 'Created', 'Updated', 'Removed', 'Reactivated', 'Total');
$tableheaders = array('Tester', 'Created', 'Updated', 'Removed', 'Reactivated', 'Total');
$table = new flexible_table('top-testers');
$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);
$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'Tester');
$table->set_attribute('class', 'generaltable generalbox');
$table->set_control_variables(array(
TABLE_VAR_SORT    => 'ssort',
TABLE_VAR_HIDE    => 'shide',
TABLE_VAR_SHOW    => 'sshow',
TABLE_VAR_IFIRST  => 'sifirst',
TABLE_VAR_ILAST   => 'silast',
TABLE_VAR_PAGE    => 'spage'
));
$table->setup();
$table->initialbars(true);
$user_ids = get_records_sql('select distinct(userid) from mdl5_sits_mappings_history');
$data = array();
foreach($user_ids as $user_id){
    if($user_id->userid != '0'){
        $user = get_record('user', 'id', $user_id->userid);
        $name = $user->lastname;
    }else{
        $name = 'The Machine';
    }
     
    $sql = <<<sql
select count(action) as cnt 
from mdl5_sits_mappings_history 
where action = %d 
and userid = %d
sql;

    $c = get_record_sql(sprintf($sql, 0, $user_id->userid));
    $u = get_record_sql(sprintf($sql, 1, $user_id->userid));
    $d = get_record_sql(sprintf($sql, 2, $user_id->userid));
    $r = get_record_sql(sprintf($sql, 3, $user_id->userid));
    $total = $c->cnt + $u->cnt + $d->cnt + $r->cnt;
    $key = $total;
    while(array_key_exists($key, $data)){
        $key + 0.01;
    }
    $data[$key] = array($name, $c->cnt, $u->cnt, $d->cnt, $r->cnt, $total);

}
arsort($data);
foreach($data as $row){
    $table->add_data($row);
}
$table->print_html();
?>