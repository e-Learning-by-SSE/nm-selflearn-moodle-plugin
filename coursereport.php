<?php
/**
 * SelfLearn Progress Report - Minimalistic Professional Design
 * Shows detailed progress overview for all students in SelfLearn activities
 *
 * @package   selflearn
 * @copyright 2024 University of Hildesheim, Software Systems Engineering
 * @license   Apache License 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

require_once("../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT);
$sortby = optional_param('sortby', '0', PARAM_INT);
$sortorder = optional_param('sortorder', 'ASC', PARAM_ALPHA);
$refresh = optional_param('refresh', 0, PARAM_INT);
$page = optional_param('page', 1, PARAM_INT);
$perpage = optional_param('perpage', 25, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);

// Access + permissions
$course = get_course($id);
require_login($id);

$context = context_course::instance($id);
require_capability('moodle/grade:viewall', $context);

// Cache setup
$cache = cache::make('mod_selflearn', 'report_cache');

if ($refresh) {
    $cache->delete($id);
}

// Fetch enrolled users
$enrolled_users = get_enrolled_users($context);
$nextsortorder = ($sortorder == 'ASC') ? 'DESC' : 'ASC';

// Fetch course module information
$modinfo = get_fast_modinfo($id);
$modulename = 'selflearn';

// Get all activities
global $DB;
$courses = [];
$instances = [];
foreach ($modinfo->get_instances_of($modulename) as $cm) {
    if ($cm->uservisible) {
        $info = $DB->get_record('selflearn', ['id' => $cm->instance]);
        $instances[] = [
            'name' => $cm->name,
            'id' => $cm->id,
            'url' => new moodle_url('/mod/' . $modulename . '/view.php', ['id' => $cm->id]),
            'slug' => $info->slug
        ];
        $courses[] = ["slug" => $info->slug, "id" => $info->id];
    }
}

// Get students
$studentRoleId = 5;
$students = [];
foreach ($enrolled_users as $user) {
    if (user_has_role_assignment($user->id, $studentRoleId, $context->id)) {
        $student = new stdClass();
        $student->username = $user->username;
        $student->firstname = $user->firstname;
        $student->lastname = $user->lastname;
        $students[] = $student;
    }
}

if (empty($students)) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string("report::no_students", "selflearn"));
    echo $OUTPUT->footer();
    die();
}

if (empty($courses)) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string("report::no_activities", "selflearn"));
    echo $OUTPUT->footer();
    die();
}

// Get or compute progress data
$merged = [];
$data = $cache->get($id);

if ($data === false || $refresh) {
    $progress = selflearn_query_progress($students, $courses);

    foreach ($students as $user) {
        $combined = [
            $user->firstname,
            $user->lastname,
            $user->username
        ];

        foreach ($courses as $course) {
            $slug = $course['slug'];

            if (isset($progress[$user->username]) && isset($progress[$user->username][$slug])) {
                $value = $progress[$user->username][$slug];
                if ($value === null || $value === false || $value === "---") {                    
                    $combined[] = '---';
                } else {
                    $combined[] = intval($value);
                }
            } else {
                $combined[] = '---';
            }
        }

        if (isset($progress[$user->username]['total_average'])) {
            $avg = $progress[$user->username]['total_average'];
            $combined[] = ($avg !== null && $avg !== false) ? intval($avg) : '---';
        } else {
            $combined[] = '---';
        }

        $merged[] = $combined;
    }

    $data = new stdClass();
    $data->data = $merged;
    $data->courses = array_column($instances, 'name');
    $data->last_updated = time();

    $cache->set($id, $data);
} else {
    $merged = $data->data;
}

// Apply search filter
$filtered = $merged;
if (!empty($search)) {
    $search_lower = strtolower(trim($search));
    $filtered = array_filter($merged, function($entry) use ($search_lower) {
        $firstname = strtolower($entry[0]);
        $lastname = strtolower($entry[1]);
        $username = strtolower($entry[2]);
        
        return (strpos($firstname, $search_lower) !== false ||
                strpos($lastname, $search_lower) !== false ||
                strpos($username, $search_lower) !== false);
    });
    $filtered = array_values($filtered);
}

// Sort data
usort($filtered, function($a, $b) use ($sortby, $sortorder) {
    $index = $sortby;
    if ($index == 0 || $index == 1 || $index == 2) {
        return $sortorder == 'ASC' ? strcmp($a[$index], $b[$index]) : strcmp($b[$index], $a[$index]);
    }

    $aVal = ($a[$index] === '---' || $a[$index] === 'N/A') ? -1 : intval($a[$index]);
    $bVal = ($b[$index] === '---' || $b[$index] === 'N/A') ? -1 : intval($b[$index]);

    return $sortorder == 'ASC' ? $aVal <=> $bVal : $bVal <=> $aVal;
});

// Calculate pagination
$total_records = count($filtered);
$total_pages = ceil($total_records / $perpage);
$page = max(1, min($page, $total_pages));
$offset = ($page - 1) * $perpage;
$paginated_data = array_slice($filtered, $offset, $perpage);

// Calculate statistics
$total_students = count($filtered);
$enrolled_students = 0;
$total_progress = 0;
$progress_count = 0;

foreach ($filtered as $entry) {
    $has_any_enrollment = false;
    
    for ($i = 3; $i < count($entry) - 1; $i++) {
        if ($entry[$i] !== '---' && $entry[$i] !== 'N/A') {
            $has_any_enrollment = true;
            $progress_value = intval($entry[$i]);
            $total_progress += $progress_value;
            $progress_count++;
        }
    }

    if ($has_any_enrollment) {
        $enrolled_students++;
    }
}

$avg_progress = ($progress_count > 0) ? round($total_progress / $progress_count, 1) : 0;

// Page setup
global $PAGE, $OUTPUT;
$pagetitle = get_string("report::title", "selflearn");
$pageurl = new moodle_url('/mod/selflearn/coursereport.php', [
    'id' => $id, 
    'sortby' => $sortby, 
    'sortorder' => $sortorder,
    'page' => $page,
    'perpage' => $perpage,
    'search' => $search
]);
$PAGE->set_pagelayout('incourse');
$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_title($pagetitle);
$PAGE->set_heading(format_string($course->fullname, true, ['context' => $context]));
$PAGE->add_body_class('limitedwidth');

$PAGE->requires->css('/mod/selflearn/styles.css');

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

// Last updated info
if (isset($data->last_updated)) {
    $last_updated = userdate($data->last_updated, get_string('strftimerecent'));
    echo '<div class="alert-info">';
    echo '<p><strong>' . get_string('report::last_updated', 'selflearn') . '</strong> ' . $last_updated . '</p>';
    echo '<p>' . get_string('report::cache_info', 'selflearn') . '</p>';
    echo '</div>';
}

echo '<div class="report-wrapper">';

// Top Statistics Bar
echo '<div class="top-stats-bar">';

echo '<div class="stat-box">';
echo '<div class="stat-box-label">' . get_string('report::total_students', 'selflearn') . '</div>';
echo '<div class="stat-box-value">' . $total_students . '</div>';
echo '</div>';

echo '<div class="stat-box">';
echo '<div class="stat-box-label">' . get_string('report::enrolled_students', 'selflearn') . '</div>';
echo '<div class="stat-box-value">' . $enrolled_students . '</div>';
echo '</div>';

echo '<div class="stat-box">';
echo '<div class="stat-box-label">' . get_string('report::average_progress', 'selflearn') . '</div>';
echo '<div class="stat-box-value">' . $avg_progress . '%</div>';
echo '</div>';

echo '<div class="stat-box">';
echo '<div class="stat-box-label">' . get_string('report::activities_count', 'selflearn') . '</div>';
echo '<div class="stat-box-value">' . count($instances) . '</div>';
echo '</div>';

echo '</div>';

// Controls Section
echo '<div class="controls-section">';

echo '<div class="search-container">';
echo '<form method="get" action="' . $pageurl->out_omit_querystring() . '">';
echo '<input type="hidden" name="id" value="' . $id . '">';
echo '<input type="hidden" name="sortby" value="' . $sortby . '">';
echo '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
echo '<input type="text" name="search" class="search-input" placeholder="' . get_string('report::search_placeholder', 'selflearn') . '" value="' . s($search) . '">';
echo '</form>';
echo '</div>';

echo '<div class="action-buttons">';
$refreshurl = new moodle_url('/mod/selflearn/coursereport.php', ['id' => $id, 'refresh' => 1]);
echo html_writer::link($refreshurl, get_string('report::refresh_data', 'selflearn'), ['class' => 'btn btn-secondary']);
$exporturl = new moodle_url('/mod/selflearn/excelexport.php', ['id' => $id]);
echo html_writer::link($exporturl, get_string('report::export_btn', 'selflearn'), ['class' => 'btn btn-primary']);
echo '</div>';

echo '</div>';

// Progress table
if (empty($paginated_data)) {
    echo '<div class="no-results">';
    echo '<p>' . get_string('report::no_results', 'selflearn') . '</p>';
    echo '</div>';
} else {
    echo '<div class="table-container">';
    echo '<div class="table-responsive">';
    echo '<table class="table selflearn_table">';
    echo '<thead>';
    echo '<tr>';

    $baseParams = ['id' => $id, 'page' => $page, 'perpage' => $perpage, 'search' => $search, 'sortorder' => $nextsortorder];
    
    $firstNameUrl = new moodle_url('/mod/selflearn/coursereport.php', array_merge($baseParams, ['sortby' => 0]));
    echo '<th><a href="' . $firstNameUrl . '">' . get_string("report::first_name", "selflearn");
    if ($sortby == 0) echo ($sortorder == 'ASC') ? ' ↑' : ' ↓';
    echo '</a></th>';

    $lastNameUrl = new moodle_url('/mod/selflearn/coursereport.php', array_merge($baseParams, ['sortby' => 1]));
    echo '<th><a href="' . $lastNameUrl . '">' . get_string("report::last_name", "selflearn");
    if ($sortby == 1) echo ($sortorder == 'ASC') ? ' ↑' : ' ↓';
    echo '</a></th>';

    $usernameUrl = new moodle_url('/mod/selflearn/coursereport.php', array_merge($baseParams, ['sortby' => 2]));
    echo '<th><a href="' . $usernameUrl . '">' . get_string('report::username', 'selflearn');
    if ($sortby == 2) echo ($sortorder == 'ASC') ? ' ↑' : ' ↓';
    echo '</a></th>';

    for ($i = 0; $i < count($instances); $i++) {
        $activityUrl = new moodle_url('/mod/selflearn/coursereport.php', array_merge($baseParams, ['sortby' => ($i + 3)]));
        echo '<th><a href="' . $activityUrl . '">' . $instances[$i]["name"];
        if ($sortby == ($i + 3)) echo ($sortorder == 'ASC') ? ' ↑' : ' ↓';
        echo '</a></th>';
    }

    $avgUrl = new moodle_url('/mod/selflearn/coursereport.php', array_merge($baseParams, ['sortby' => (count($instances) + 3)]));
    echo '<th><a href="' . $avgUrl . '">' . get_string('report::average', 'selflearn');
    if ($sortby == (count($instances) + 3)) echo ($sortorder == 'ASC') ? ' ↑' : ' ↓';
    echo '</a></th>';

    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($paginated_data as $entry) {
        echo '<tr>';
        for ($i = 0; $i < count($entry); $i++) {
            $value = $entry[$i];

            if ($i >= 3) {
                if ($value === '---' || $value === 'N/A') {
                    echo '<td class="text-muted"><em>' . $value . '</em></td>';
                } else {
                    $percentage = intval($value);
                    $class = '';
                    if ($percentage >= 80) $class = 'text-success';
                    elseif ($percentage >= 60) $class = 'text-warning';
                    elseif ($percentage >= 0) $class = 'text-danger';
                    else $class = 'text-muted';

                    echo '<td class="' . $class . '"><strong>' . $percentage . '%</strong></td>';
                }
            } else {
                echo '<td>' . htmlspecialchars($value) . '</td>';
            }
        }
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</div>';

    // Bottom Section: Pagination + Legend
    echo '<div class="bottom-section">';
    
    // Pagination
    echo '<div class="pagination-wrapper">';
    
    if ($total_pages > 1) {
        echo '<div class="pagination-container">';
        
        $start = $offset + 1;
        $end = min($offset + $perpage, $total_records);
        echo '<div class="pagination-info">';
        echo get_string('report::showing_entries', 'selflearn', ['start' => $start, 'end' => $end, 'total' => $total_records]);
        echo '</div>';
        
        echo '<ul class="pagination">';
        
        $paginationParams = ['id' => $id, 'sortby' => $sortby, 'sortorder' => $sortorder, 'perpage' => $perpage, 'search' => $search];
        
        if ($page > 1) {
            $prevUrl = new moodle_url('/mod/selflearn/coursereport.php', array_merge($paginationParams, ['page' => ($page - 1)]));
            echo '<li><a href="' . $prevUrl . '">' . get_string('report::previous', 'selflearn') . '</a></li>';
        } else {
            echo '<li class="disabled"><span>' . get_string('report::previous', 'selflearn') . '</span></li>';
        }
        
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        
        if ($start_page > 1) {
            $firstUrl = new moodle_url('/mod/selflearn/coursereport.php', array_merge($paginationParams, ['page' => 1]));
            echo '<li><a href="' . $firstUrl . '">1</a></li>';
            if ($start_page > 2) {
                echo '<li class="disabled"><span>...</span></li>';
            }
        }
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            $pageNumUrl = new moodle_url('/mod/selflearn/coursereport.php', array_merge($paginationParams, ['page' => $i]));
            if ($i == $page) {
                echo '<li class="active"><span>' . $i . '</span></li>';
            } else {
                echo '<li><a href="' . $pageNumUrl . '">' . $i . '</a></li>';
            }
        }
        
        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) {
                echo '<li class="disabled"><span>...</span></li>';
            }
            $lastUrl = new moodle_url('/mod/selflearn/coursereport.php', array_merge($paginationParams, ['page' => $total_pages]));
            echo '<li><a href="' . $lastUrl . '">' . $total_pages . '</a></li>';
        }
        
        if ($page < $total_pages) {
            $nextUrl = new moodle_url('/mod/selflearn/coursereport.php', array_merge($paginationParams, ['page' => ($page + 1)]));
            echo '<li><a href="' . $nextUrl . '">' . get_string('report::next', 'selflearn') . '</a></li>';
        } else {
            echo '<li class="disabled"><span>' . get_string('report::next', 'selflearn') . '</span></li>';
        }
        
        echo '</ul>';
        echo '</div>';
    }
    
    echo '</div>';
    
    // Legend
    echo '<div class="legend-card">';
    echo '<h5>' . get_string('report::legend_title', 'selflearn') . '</h5>';
    echo '<ul>';
    echo '<li>' . get_string('report::legend_excellent', 'selflearn') . '</li>';
    echo '<li>' . get_string('report::legend_good', 'selflearn') . '</li>';
    echo '<li>' . get_string('report::legend_needs_improvement', 'selflearn') . '</li>';
    echo '<li>' . get_string('report::legend_not_enrolled', 'selflearn') . '</li>';
    echo '</ul>';
    echo '</div>';
    
    echo '</div>';
}

echo '</div>';

echo $OUTPUT->footer();