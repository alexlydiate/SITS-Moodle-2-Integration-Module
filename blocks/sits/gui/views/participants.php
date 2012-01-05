<?PHP // $Id: index.php,v 1.194.2.24 2010/06/17 21:02:36 mudrd8mz Exp $

// Lists all the users within a given course along with relevant mapping info
// A big old hatchet job of the normal Moodle participants script, to be thoroughly reviewed

global $CFG, $USER, $COURSE;

require_once('../../../../config.php');
require_once($CFG->libdir.'/tablelib.php');

define('USER_SMALL_CLASS', 20);   // Below this is considered small
define('USER_LARGE_CLASS', 200);  // Above this is considered large
define('DEFAULT_PAGE_SIZE', 20);
define('SHOW_ALL_PAGE_SIZE', 5000);

$page         = optional_param('page', 0, PARAM_INT);                     // which page to show
$perpage      = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);  // how many per page
$mode         = optional_param('mode', NULL);                             // '0' for less details, '1' for more
$accesssince  = optional_param('accesssince',0,PARAM_INT);                // filter by last access. -1 = never
$search       = optional_param('search','',PARAM_CLEAN);
$roleid       = optional_param('roleid', 0, PARAM_INT);                   // optional roleid, -1 means all site users on frontpage

$courseid     = optional_param('id', 0, PARAM_INT);

 
if (! $course = get_record('course', 'id', $courseid)) {
    error("Course ID is incorrect");
}
if (! $context = get_context_instance(CONTEXT_COURSE, $course->id)) {
    error("Context ID is incorrect");
}

// not needed anymore
unset($courseid);

require_login($course);

$sitecontext = get_context_instance(CONTEXT_SYSTEM);
$frontpagectx = get_context_instance(CONTEXT_COURSE, SITEID);

if ($context->id != $frontpagectx->id) {
    require_capability('moodle/course:viewparticipants', $context);
} else {
    require_capability('moodle/site:viewparticipants', $sitecontext);
    // override the default on frontpage
    $roleid = optional_param('roleid', -1, PARAM_INT);
}

/// front page course is different
$rolenames = array();
$avoidroles = array();

if ($roles = get_roles_used_in_context($context, true)) {
    // We should ONLY allow roles with moodle/course:view because otherwise we get little niggly issues
    // like MDL-8093
    // We should further exclude "admin" users (those with "doanything" at site level) because
    // Otherwise they appear in every participant list

    $canviewroles    = get_roles_with_capability('moodle/course:view', CAP_ALLOW, $context);
    $doanythingroles = get_roles_with_capability('moodle/site:doanything', CAP_ALLOW, $sitecontext);

    if ($context->id == $frontpagectx->id) {
        //we want admins listed on frontpage too
        foreach ($doanythingroles as $dar) {
            $canviewroles[$dar->id] = $dar;
        }
        $doanythingroles = array();
    }

    foreach ($roles as $role) {
        if (!isset($canviewroles[$role->id])) {   // Avoid this role (eg course creator)
            $avoidroles[] = $role->id;
            unset($roles[$role->id]);
            continue;
        }
        if (isset($doanythingroles[$role->id])) {   // Avoid this role (ie admin)
            $avoidroles[] = $role->id;
            unset($roles[$role->id]);
            continue;
        }
        $rolenames[$role->id] = strip_tags(role_get_name($role, $context));   // Used in menus etc later on
    }
}

if ($context->id == $frontpagectx->id and $CFG->defaultfrontpageroleid) {
    // default frontpage role is assigned to all site users
    unset($rolenames[$CFG->defaultfrontpageroleid]);
}

// no roles to display yet?
// frontpage course is an exception, on the front page course we should display all users
if (empty($rolenames) && $context->id != $frontpagectx->id) {
    if (has_capability('moodle/role:assign', $context)) {
        redirect($CFG->wwwroot.'/'.$CFG->admin.'/roles/assign.php?contextid='.$context->id);
    } else {
        error ('No participants found for this course');
    }
}

add_to_log($course->id, 'user', 'view all', 'index.php?id='.$course->id, '');

$bulkoperations = has_capability('moodle/course:bulkmessaging', $context);

$countries = get_list_of_countries();

$strnever = get_string('never');

$datestring->year  = get_string('year');
$datestring->years = get_string('years');
$datestring->day   = get_string('day');
$datestring->days  = get_string('days');
$datestring->hour  = get_string('hour');
$datestring->hours = get_string('hours');
$datestring->min   = get_string('min');
$datestring->mins  = get_string('mins');
$datestring->sec   = get_string('sec');
$datestring->secs  = get_string('secs');

if ($mode !== NULL) {
    $SESSION->userindexmode = $fullmode = ($mode == 1);
} else if (isset($SESSION->userindexmode)) {
    $fullmode = $SESSION->userindexmode;
} else {
    $fullmode = false;
}

/// Check to see if groups are being used in this course
/// and if so, set $currentgroup to reflect the current group

$groupmode    = groups_get_course_groupmode($course);   // Groups are being used
$currentgroup = groups_get_course_group($course, true);

if (!$currentgroup) {      // To make some other functions work better later
    $currentgroup  = NULL;
}

$isseparategroups = ($course->groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context));

if ($isseparategroups and (!$currentgroup) ) {
    $navlinks = array();
    $navlinks[] = array('name' => get_string('participants'), 'link' => null, 'type' => 'misc');
    $navigation = build_navigation($navlinks);

    print_header("$course->shortname: ".get_string('participants'), $course->fullname, $navigation, "", "", true, "&nbsp;", navmenu($course));
    print_heading(get_string("notingroup"));
    print_footer($course);
    exit;
}

// Should use this variable so that we don't break stuff every time a variable is added or changed.
$baseurl = $CFG->wwwroot.'/blocks/sits/gui/views/participants.php?contextid='.$context->id.'&amp;roleid='.$roleid.'&amp;id='.$course->id.'&amp;perpage='.$perpage.'&amp;accesssince='.$accesssince.'&amp;search='.s($search);

/// Print headers

//$navlinks = array();
//$navlinks[] = array('name' => get_string('participants'), 'link' => null, 'type' => 'misc');
//$navigation = build_navigation($navlinks);

print_header();

/// setting up tags
if ($course->id == SITEID) {
    $filtertype = 'site';
} else if ($course->id && !$currentgroup) {
    $filtertype = 'course';
    $filterselect = $course->id;
} else {
    $filtertype = 'group';
    $filterselect = $currentgroup;
}
$currenttab = 'participants';
$user = $USER;

//require_once($CFG->dirroot .'/user/tabs.php');


/// Get the hidden field list
if (has_capability('moodle/course:viewhiddenuserfields', $context)) {
    $hiddenfields = array();  // teachers and admins are allowed to see everything
} else {
    $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
}

if (isset($hiddenfields['unenrol_date'])) {
    // do not allow access since filtering
    $accesssince = 0;
}

/// Print settings and things in a table across the top

echo '<table class="controls" cellspacing="0"><tr>';

/// Print my course menus
if ($mycourses = get_my_courses($USER->id)) {
    echo '<td class="left">';
    $courselist = array();
    foreach ($mycourses as $mycourse) {
        $courselist[$mycourse->id] = format_string($mycourse->shortname);
    }
    if (has_capability('moodle/site:viewparticipants', $sitecontext)) {
        unset($courselist[SITEID]);
        $courselist = array(SITEID => format_string($SITE->shortname)) + $courselist;
    }
    popup_form($CFG->wwwroot.'/blocks/sits/gui/views/participants.php?roleid='.$roleid.'&amp;sifirst=&amp;silast=&amp;id=',
    $courselist, 'courseform', $course->id, '', '', '', false, 'self', get_string('mycourses'));
    echo '</td>';
}

echo '<td class="left">';
groups_print_course_menu($course, $baseurl);
echo '</td>';


/// Define a table showing a list of users in the current role selection

$tablecolumns = array('userpic', 'fullname');
$tableheaders = array(get_string('userpic'), get_string('fullname'));
if (!isset($hiddenfields['mapped_cohort'])) {
    $tablecolumns[] = 'mapped_cohort';
    $tableheaders[] = 'Mapped Cohort';
}

if (!isset($hiddenfields['unenrol_type'])) {
    $tablecolumns[] = 'unenrol_method';
    $tableheaders[] = 'Unenrol Method';
}

if (!isset($hiddenfields['default_map'])) {
    $tablecolumns[] = 'default_map';
    $tableheaders[] = 'Default';
}

if (!isset($hiddenfields['unenrol_date'])) {
    $tablecolumns[] = 'lastaccess';
    $tableheaders[] = 'Unenrol Date';
}

if ($course->enrolperiod) {
    $tablecolumns[] = 'timeend';
    $tableheaders[] = get_string('enrolmentend');
}

/*if ($bulkoperations) {
    $tablecolumns[] = '';
    $tableheaders[] = get_string('select');
}*/

$table = new flexible_table('user-index-participants-'.$course->id);

$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);
$table->define_baseurl($baseurl);

$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'participants');
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


// we are looking for all users with this role assigned in this context or higher
if ($usercontexts = get_parent_contexts($context)) {
    $listofcontexts = '('.implode(',', $usercontexts).')';
} else {
    $listofcontexts = '('.$sitecontext->id.')'; // must be site
}
if ($roleid > 0) {
    $selectrole = " AND r.roleid = $roleid ";
} else {
    $selectrole = " ";
}

if ($context->id != $frontpagectx->id) {
    $select = 'SELECT DISTINCT u.id, u.username, u.firstname, u.lastname,
                      u.email, r.enrol, u.country, u.picture,
                      u.lang, u.timezone, u.emailstop, u.maildisplay, u.imagealt, m.end_date, m.default_map,
                      m.sits_code, m.acyear, m.period_code, m.year_group, m.specified, m.manual,
                      COALESCE(ul.timeaccess, 0) AS lastaccess,
                      r.hidden,
                      ctx.id AS ctxid, ctx.path AS ctxpath,
                      ctx.depth AS ctxdepth, ctx.contextlevel AS ctxlevel ';
    $select .= $course->enrolperiod?', r.timeend ':'';
} else {
    if ($roleid >= 0) {
        $select = 'SELECT u.id, u.username, u.firstname, u.lastname,
                          u.email, r.enrol, u.country, u.picture,
                          u.lang, u.timezone, u.emailstop, u.maildisplay, u.imagealt, m.end_date, m.default_map,
                          m.sits_code, m.acyear, m.period_code, m.year_group, m.specified, m.manual,
                          u.lastaccess, r.hidden,
                          ctx.id AS ctxid, ctx.path AS ctxpath,
                          ctx.depth AS ctxdepth, ctx.contextlevel AS ctxlevel ';
    } else {
        $select = 'SELECT u.id, u.username, u.firstname, u.lastname,
                          u.email, r.enrol, u.country, u.picture,
                          u.lang, u.timezone, u.emailstop, u.maildisplay, u.imagealt, m.end_date, m.default_map,
                          m.sits_code, m.acyear, m.period_code, m.year_group, m.specified, m.manual,
                          u.lastaccess,
                          ctx.id AS ctxid, ctx.path AS ctxpath,
                          ctx.depth AS ctxdepth, ctx.contextlevel AS ctxlevel ';
    }
}

if ($context->id != $frontpagectx->id or $roleid >= 0) {
    $from   = "FROM {$CFG->prefix}user u
                LEFT OUTER JOIN {$CFG->prefix}context ctx
                    ON (u.id=ctx.instanceid AND ctx.contextlevel = ".CONTEXT_USER.")
                JOIN {$CFG->prefix}role_assignments r
                    ON u.id=r.userid
                LEFT JOIN {$CFG->prefix}sits_mappings m
                    ON r.enrol=m.id
                LEFT OUTER JOIN {$CFG->prefix}user_lastaccess ul
                    ON (r.userid=ul.userid and ul.courseid = $course->id) ";
} else {
    // on frontpage and we want all registered users
    $from = "FROM {$CFG->prefix}user u
                LEFT OUTER JOIN {$CFG->prefix}context ctx
                    ON (u.id=ctx.instanceid AND ctx.contextlevel = ".CONTEXT_USER.") ";
}

$hiddensql = has_capability('moodle/role:viewhiddenassigns', $context)? '':' AND r.hidden = 0 ';

// exclude users with roles we are avoiding
if ($avoidroles) {
    $adminroles = 'AND r.roleid NOT IN (';
    $adminroles .= implode(',', $avoidroles);
    $adminroles .= ')';
} else {
    $adminroles = '';
}

// join on 2 conditions
// otherwise we run into the problem of having records in ul table, but not relevant course
// and user record is not pulled out

if ($context->id != $frontpagectx->id) {
    $where  = "WHERE (r.contextid = $context->id OR r.contextid in $listofcontexts)
            AND u.deleted = 0 $selectrole
            AND (ul.courseid = $course->id OR ul.courseid IS NULL)
            AND u.username != 'guest'
            $adminroles
            $hiddensql ";
            $where .= get_course_lastaccess_sql($accesssince);
} else {
    if ($roleid >= 0) {
        $where = "WHERE (r.contextid = $context->id OR r.contextid in $listofcontexts)
                AND u.deleted = 0 $selectrole
                AND u.username != 'guest'";
        $where .= get_user_lastaccess_sql($accesssince);
    } else {
        $where = "WHERE u.deleted = 0
                AND u.username != 'guest'";
        $where .= get_user_lastaccess_sql($accesssince);
    }
}
$wheresearch = '';

if (!empty($search)) {
    $LIKE = sql_ilike();
    $fullname  = sql_fullname('u.firstname','u.lastname');
    $wheresearch .= ' AND ('. $fullname .' '. $LIKE .' \'%'. $search .'%\' OR email '. $LIKE .' \'%'. $search .'%\' OR idnumber '.$LIKE.' \'%'.$search.'%\') ';

}

if ($currentgroup) {    // Displaying a group by choice
    // FIX: TODO: This will not work if $currentgroup == 0, i.e. "those not in a group"
    $from  .= 'LEFT JOIN '.$CFG->prefix.'groups_members gm ON u.id = gm.userid ';
    $where .= ' AND gm.groupid = '.$currentgroup;
}

$totalcount = count_records_sql('SELECT COUNT(distinct u.id) '.$from.$where);   // Each user could have > 1 role

if ($table->get_sql_where()) {
    $where .= ' AND '.$table->get_sql_where();
}

/// Always add r.hidden to sort in order to guarantee hiddens to "win"
/// in the resolution of duplicates later - MDL-13935
/// Only exception is frontpage that doesn't have such r.hidden info
/// because it retrieves ALL users (without role checking) - MDL-14034
if ($table->get_sql_sort()) {
    $sort = ' ORDER BY '.$table->get_sql_sort();
    if ($context->id != $frontpagectx->id or $roleid >= 0) {
        $sort .= ', u.lastname ASC';
    }
} else {
    $sort = '';
    if ($context->id != $frontpagectx->id or $roleid >= 0) {
        $sort .= ' ORDER BY u.lastname ASC';
    }
}

$matchcount = count_records_sql('SELECT COUNT(distinct u.id) '.$from.$where.$wheresearch);

$table->initialbars(true);
$table->pagesize($perpage, $matchcount);

$userlist = get_recordset_sql($select.$from.$where.$wheresearch.$sort,
$table->get_page_start(),  $table->get_page_size());

if ($context->id == $frontpagectx->id) {
    $strallsiteusers = get_string('allsiteusers', 'role');
    if ($CFG->defaultfrontpageroleid) {
        if ($fprole = get_record('role', 'id', $CFG->defaultfrontpageroleid)) {
            $fprole = role_get_name($fprole, $frontpagectx);
            $strallsiteusers = "$strallsiteusers ($fprole)";
        }
    }
    $rolenames = array(-1 => $strallsiteusers) + $rolenames;
}

if ($roleid > 0) {
    if (!$currentrole = get_record('role','id',$roleid)) {
        error('That role does not exist');
    }
    $a->number = $totalcount;
    // MDL-12217, use course specific rolename
    if (isset($rolenames[$currentrole->id])){
        $a->role = $rolenames[$currentrole->id];
    }else{
        $a->role = $currentrole->name;//safety net
    }
    $heading = format_string(get_string('xuserswiththerole', 'role', $a));

    if ($currentgroup and $group) {
        $a->group = $group->name;
        $heading .= ' ' . format_string(get_string('ingroup', 'role', $a));
    }

    if ($accesssince) {
        $a->timeperiod = $timeoptions[$accesssince];
        $heading .= ' ' . format_string(get_string('inactiveformorethan', 'role', $a));
    }

    $heading .= ": $a->number";
    if (user_can_assign($context, $roleid)) {
        $heading .= ' <a href="'.$CFG->wwwroot.'/'.$CFG->admin.'/roles/assign.php?roleid='.$roleid.'&amp;contextid='.$context->id.'">';
        $heading .= '<img src="'.$CFG->pixpath.'/i/edit.gif" class="icon" alt="" /></a>';
    }
    print_heading($heading, 'center', 3);
} else {
    if ($course->id != SITEID && has_capability('moodle/role:assign', $context)) {
        $editlink  = ' <a href="'.$CFG->wwwroot.'/'.$CFG->admin.'/roles/assign.php?contextid='.$context->id.'">';
        $editlink .= '<img src="'.$CFG->pixpath.'/i/edit.gif" class="icon" alt="" /></a>';
    } else {
        $editlink = '';
    }
    if ($course->id == SITEID and $roleid < 0) {
        $strallparticipants = get_string('allsiteusers', 'role');
    } else {
        $strallparticipants = get_string('allparticipants');
    }
    if ($matchcount < $totalcount) {
        print_heading($strallparticipants.': '.$matchcount.'/'.$totalcount . $editlink, '', 3);
    } else {
        print '<div style="width: 700px; text-align: center; margin-bottom: 5px;"><b>';
    print $strallparticipants.': '.$matchcount . $editlink;
    print '</b></div>';
    }
}


if ($bulkoperations) {
    echo '
        <script type="text/javascript">
        //<![CDATA[
        function checksubmit(form) {
            var destination = form.formaction.options[form.formaction.selectedIndex].value;
            if (destination == "" || !checkchecked(form)) {
                form.formaction.selectedIndex = 0;
                return false;
            } else {
                return true;
            }
        }

        function checkchecked(form) {
            var inputs = document.getElementsByTagName(\'INPUT\');
            var checked = false;
            inputs = filterByParent(inputs, function() {return form;});
            for(var i = 0; i < inputs.length; ++i) {
                if (inputs[i].type == \'checkbox\' && inputs[i].checked) {
                    checked = true;
                }
            }
            return checked;
        }
        //]]>
        </script>
            ';
    echo '<form action="action_redir.php" method="post" id="participantsform" onsubmit="return checksubmit(this);">';
    echo '<div  style="width: 700px;">';
    echo '<input type="hidden" name="sesskey" value="'.$USER->sesskey.'" />';
    echo '<input type="hidden" name="returnto" value="'.s(me()).'" />';
}

if ($CFG->longtimenosee > 0 && $CFG->longtimenosee < 1000 && $totalcount > 0) {
    echo '<p id="longtimenosee">('.get_string('unusedaccounts', '', $CFG->longtimenosee).')</p>';
}

if ($fullmode) {    // Print simple listing
    if ($totalcount < 1) {
        print_heading(get_string('nothingtodisplay'));
    } else {
        if ($totalcount > $perpage) {

            $firstinitial = $table->get_initial_first();
            $lastinitial  = $table->get_initial_last();
            $strall = get_string('all');
            $alpha  = explode(',', get_string('alphabet'));

            // Bar of first initials

            echo '<div class="initialbar firstinitial">'.get_string('firstname').' : ';
            if(!empty($firstinitial)) {
                echo '<a href="'.$baseurl.'&amp;sifirst=">'.$strall.'</a>';
            } else {
                echo '<strong>'.$strall.'</strong>';
            }
            foreach ($alpha as $letter) {
                if ($letter == $firstinitial) {
                    echo ' <strong>'.$letter.'</strong>';
                } else {
                    echo ' <a href="'.$baseurl.'&amp;sifirst='.$letter.'">'.$letter.'</a>';
                }
            }
            echo '</div>';

            // Bar of last initials

            echo '<div class="initialbar lastinitial">'.get_string('lastname').' : ';
            if(!empty($lastinitial)) {
                echo '<a href="'.$baseurl.'&amp;silast=">'.$strall.'</a>';
            } else {
                echo '<strong>'.$strall.'</strong>';
            }
            foreach ($alpha as $letter) {
                if ($letter == $lastinitial) {
                    echo ' <strong>'.$letter.'</strong>';
                } else {
                    echo ' <a href="'.$baseurl.'&amp;silast='.$letter.'">'.$letter.'</a>';
                }
            }
            echo '</div>';

            print_paging_bar($matchcount, intval($table->get_page_start() / $perpage), $perpage, $baseurl.'&amp;', 'spage');
        }

        if ($matchcount > 0) {
            $usersprinted = array();
            while ($user = rs_fetch_next_record($userlist)) {
                if (in_array($user->id, $usersprinted)) { /// Prevent duplicates by r.hidden - MDL-13935
                    continue;
                }
                $usersprinted[] = $user->id; /// Add new user to the array of users printed

                $user = make_context_subobj($user);
                print_user($user, $course, $bulkoperations);
            }

        } else {
            print_heading(get_string('nothingtodisplay'));
        }
    }

} else {
    $countrysort = (strpos($sort, 'country') !== false);
    $timeformat = get_string('strftimedate');


    if ($userlist)  {
        $usersprinted = array();
        while ($user = rs_fetch_next_record($userlist)) {
            if (in_array($user->id, $usersprinted)) { /// Prevent duplicates by r.hidden - MDL-13935
                continue;
            }
            $usersprinted[] = $user->id; /// Add new user to the array of users printed

            $user = make_context_subobj($user);
            if ( !empty($user->hidden) ) {
                // if the assignment is hidden, display icon
                $hidden = " <img src=\"{$CFG->pixpath}/t/show.gif\" title=\"".get_string('userhashiddenassignments', 'role')."\" alt=\"".get_string('hiddenassign')."\" class=\"hide-show-image\"/>";
            } else {
                $hidden = '';
            }

            if ($user->lastaccess) {
                $lastaccess = format_time(time() - $user->lastaccess, $datestring);
            } else {
                $lastaccess = $strnever;
            }

            if (empty($user->country)) {
                $country = '';

            } else {
                if($countrysort) {
                    $country = '('.$user->country.') '.$countries[$user->country];
                }
                else {
                    $country = $countries[$user->country];
                }
            }

            if (empty($user->context->id)) {
                $usercontext = get_context_instance(CONTEXT_USER, $user->id);
            } else {
                $usercontext = $user->context;
            }

            $contextcanviewdetails = has_capability('moodle/user:viewdetails', $context);
            $usercontextcanviewdetails = has_capability('moodle/user:viewdetails', $usercontext);

            if ($piclink = ($USER->id == $user->id || $contextcanviewdetails || $usercontextcanviewdetails)) {
                if ($usercontextcanviewdetails) {
                    $canviewfullname = has_capability('moodle/site:viewfullnames', $usercontext);
                } else {
                    $canviewfullname = has_capability('moodle/site:viewfullnames', $context);
                }
                $profilelink = '<strong><a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$course->id.'">'.fullname($user, $canviewfullname).'</a></strong>';
            } else {
                $profilelink = '<strong>'.fullname($user, has_capability('moodle/site:viewfullnames', $context)).'</strong>';
            }

            $data = array (
            print_user_picture($user, $course->id, $user->picture, false, true, $piclink),
            $profilelink . $hidden);

            if (!isset($hiddenfields['mapped_cohort'])) {
                if($user->enrol == 'manual' || $user->enrol == 'metacourse' || $user->enrol == 'internal' || $user->enrol == 'database'){
                    $data[] = get_string('enrol_not_linked', 'block_sits');
                }else{
                    if(is_null($user->period_code)){
                    if($user->year_group == 0){
                        $year_group = 'All';
                    }else{
                        $year_group = $user->year_group;
                    }
                    $data[] = $user->sits_code . ', ' . $user->acyear . ', ' . $year_group;
                    }else{
                    $data[] = $user->sits_code . ', ' . $user->acyear . ', ' . $user->period_code;    
                    }
                }
            }
            if (!isset($hiddenfields['unenrol_type'])) {
                if($user->enrol == 'manual' || $user->enrol == 'metacourse' || $user->enrol == 'internal' || $user->enrol == 'database'){
                    $data[] = 'N/A';
                }elseif($user->specified){
                    $data[] = 'Specified';
                }elseif($user->manual){
                    $data[] = 'Manual';
                }else{
                    $data[] = 'Automatic';
                }
            }
            if (!isset($hiddenfields['default_map'])) {
                if($user->enrol == 'manual' || $user->enrol == 'metacourse' || $user->enrol == 'internal' || $user->enrol == 'database'){
                    $data[] = 'N/A';
                }elseif($user->default_map){
                    $data[] = 'Yes';
                }else{
                    $data[] = 'No';
                }
            }
            if (!isset($hiddenfields['unenrol_date'])) {
                if(is_null($user->end_date) || $user->end_date == '1970-01-01 00:00:00'){
                    $data[] = 'N/A';
                }else{
                    $date = new DateTime($user->end_date);
                    $data[] = $date->format('d-m-Y');
                }
            }
            if ($course->enrolperiod) {
                if ($user->timeend) {
                    $data[] = userdate($user->timeend, $timeformat);
                } else {
                    $data[] = get_string('unlimited');
                }
            }
            /*if ($bulkoperations) {
                $data[] = '<input type="checkbox" name="user'.$user->id.'" />';
            }*/ //
            $table->add_data($data);

        }
    }

    $table->print_html();

}

if ($bulkoperations) {
    echo '<br /><div class="buttons">';
    //echo '<input type="button" onclick="checkall()" value="'.get_string('selectall').'" /> ';
    //echo '<input type="button" onclick="checknone()" value="'.get_string('deselectall').'" /> ';
    $displaylist = array();
    $displaylist['messageselect.php'] = get_string('messageselectadd');
    if (!empty($CFG->enablenotes) && has_capability('moodle/notes:manage', $context) && $context->id != $frontpagectx->id) {
        $displaylist['addnote.php'] = get_string('addnewnote', 'notes');
        $displaylist['groupaddnote.php'] = get_string('groupaddnewnote', 'notes');
    }

    if ($context->id != $frontpagectx->id) {
        $displaylist['extendenrol.php'] = get_string('extendenrol');
        $displaylist['groupextendenrol.php'] = get_string('groupextendenrol');
    }

    //helpbutton("participantswithselectedusers", get_string("withselectedusers"));
    //choose_from_menu ($displaylist, "formaction", "", get_string("withselectedusers"), "if(checksubmit(this.form))this.form.submit();", "");
    echo '<input type="hidden" name="id" value="'.$course->id.'" />';
    echo '<div id="noscriptparticipantsform" style="display: inline;">';
    echo '<input type="submit" value="'.get_string('ok').'" /></div>';
    echo '<script type="text/javascript">'.
               "\n//<![CDATA[\n".
               'document.getElementById("noscriptparticipantsform").style.display = "none";'.
               "\n//]]>\n".'</script>';
    echo '</div>';
    echo '</div>';
    echo '</form>';

}

/*if (has_capability('moodle/site:viewparticipants', $context) && $totalcount > ($perpage*3)) {
    echo '<form action="index.php" class="searchform"><div><input type="hidden" name="id" value="'.$course->id.'" />'.get_string('search').':&nbsp;'."\n";
    echo '<input type="text" name="search" value="'.s($search).'" />&nbsp;<input type="submit" value="'.get_string('search').'" /></div></form>'."\n";
}*/

$perpageurl = preg_replace('/&amp;perpage=\d*/','', $baseurl);
if ($perpage == SHOW_ALL_PAGE_SIZE) {
    echo '<div id="showall"><a href="'.$perpageurl.'&amp;perpage='.DEFAULT_PAGE_SIZE.'">'.get_string('showperpage', '', DEFAULT_PAGE_SIZE).'</a></div>';

} else if ($matchcount > 0 && $perpage < $matchcount) {
    echo '<div id="showall"><a href="'.$perpageurl.'&amp;perpage='.SHOW_ALL_PAGE_SIZE.'">'.get_string('showall', '', $matchcount).'</a></div>';
}

print_footer($course);

if ($userlist) {
    rs_close($userlist);
}


function get_course_lastaccess_sql($accesssince='') {
    if (empty($accesssince)) {
        return '';
    }
    if ($accesssince == -1) { // never
        return ' AND ul.timeaccess = 0';
    } else {
        return ' AND ul.timeaccess != 0 AND ul.timeaccess < '.$accesssince;
    }
}

function get_user_lastaccess_sql($accesssince='') {
    if (empty($accesssince)) {
        return '';
    }
    if ($accesssince == -1) { // never
        return ' AND u.lastaccess = 0';
    } else {
        return ' AND u.lastaccess != 0 AND u.lastaccess < '.$accesssince;
    }
}

?>