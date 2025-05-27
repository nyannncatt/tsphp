<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['admin']);
require_once '../config/database.php';

// Get login statistics for the last 7 days by role
$login_query = "
    SELECT 
        DATE(ua.created_at) as date,
        u.role,
        COUNT(*) as count
    FROM user_activity ua
    JOIN users u ON ua.user_id = u.id
    WHERE ua.action = 'login'
    AND ua.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
    AND u.role IN ('student', 'parent')
    GROUP BY DATE(ua.created_at), u.role
    ORDER BY date, u.role";
$login_result = $conn->query($login_query);

$login_stats = [
    'labels' => [],
    'student_values' => [],
    'parent_values' => []
];

$dates = [];
$student_data = [];
$parent_data = [];

while ($row = $login_result->fetch_assoc()) {
    $date = date('M d', strtotime($row['date']));
    if (!in_array($date, $dates)) {
        $dates[] = $date;
    }
    if ($row['role'] === 'student') {
        $student_data[$date] = (int)$row['count'];
    } else {
        $parent_data[$date] = (int)$row['count'];
    }
}

// Fill in missing dates with zeros
foreach ($dates as $date) {
    $login_stats['labels'][] = $date;
    $login_stats['student_values'][] = $student_data[$date] ?? 0;
    $login_stats['parent_values'][] = $parent_data[$date] ?? 0;
}

// Get incident statistics by role
$incident_query = "
    SELECT 
        i.type,
        u.role,
        COUNT(*) as count
    FROM incidents i
    JOIN users u ON i.user_id = u.id
    WHERE u.role IN ('student', 'parent')
    GROUP BY i.type, u.role";
$incident_result = $conn->query($incident_query);

$incident_stats = [
    'labels' => [],
    'student_values' => [],
    'parent_values' => []
];

$types = [];
$student_incidents = [];
$parent_incidents = [];

while ($row = $incident_result->fetch_assoc()) {
    $type = ucfirst(str_replace('_', ' ', $row['type']));
    if (!in_array($type, $types)) {
        $types[] = $type;
    }
    if ($row['role'] === 'student') {
        $student_incidents[$type] = (int)$row['count'];
    } else {
        $parent_incidents[$type] = (int)$row['count'];
    }
}

foreach ($types as $type) {
    $incident_stats['labels'][] = $type;
    $incident_stats['student_values'][] = $student_incidents[$type] ?? 0;
    $incident_stats['parent_values'][] = $parent_incidents[$type] ?? 0;
}

// Get restricted access attempts by role
$restricted_query = "
    SELECT 
        DATE(sl.created_at) as date,
        u.role,
        COUNT(*) as count
    FROM system_logs sl
    JOIN users u ON sl.user_id = u.id
    WHERE sl.action = 'restricted_access'
    AND sl.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
    AND u.role IN ('student', 'parent')
    GROUP BY DATE(sl.created_at), u.role
    ORDER BY date, u.role";
$restricted_result = $conn->query($restricted_query);

$restricted_stats = [
    'labels' => [],
    'student_values' => [],
    'parent_values' => []
];

$dates = [];
$student_restricted = [];
$parent_restricted = [];

while ($row = $restricted_result->fetch_assoc()) {
    $date = date('M d', strtotime($row['date']));
    if (!in_array($date, $dates)) {
        $dates[] = $date;
    }
    if ($row['role'] === 'student') {
        $student_restricted[$date] = (int)$row['count'];
    } else {
        $parent_restricted[$date] = (int)$row['count'];
    }
}

// Fill in missing dates with zeros
foreach ($dates as $date) {
    $restricted_stats['labels'][] = $date;
    $restricted_stats['student_values'][] = $student_restricted[$date] ?? 0;
    $restricted_stats['parent_values'][] = $parent_restricted[$date] ?? 0;
}

// Get user activity by hour and role
$activity_query = "
    SELECT 
        HOUR(ua.created_at) as hour,
        u.role,
        COUNT(DISTINCT ua.user_id) as active_users
    FROM user_activity ua
    JOIN users u ON ua.user_id = u.id
    WHERE ua.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
    AND u.role IN ('student', 'parent')
    GROUP BY HOUR(ua.created_at), u.role
    ORDER BY hour, u.role";
$activity_result = $conn->query($activity_query);

$activity_stats = [
    'labels' => [],
    'student_values' => [],
    'parent_values' => []
];

$hours = [];
$student_activity = [];
$parent_activity = [];

while ($row = $activity_result->fetch_assoc()) {
    $hour = sprintf('%02d:00', $row['hour']);
    if (!in_array($hour, $hours)) {
        $hours[] = $hour;
    }
    if ($row['role'] === 'student') {
        $student_activity[$hour] = (int)$row['active_users'];
    } else {
        $parent_activity[$hour] = (int)$row['active_users'];
    }
}

foreach ($hours as $hour) {
    $activity_stats['labels'][] = $hour;
    $activity_stats['student_values'][] = $student_activity[$hour] ?? 0;
    $activity_stats['parent_values'][] = $parent_activity[$hour] ?? 0;
}

// Return all statistics as JSON
header('Content-Type: application/json');
echo json_encode([
    'login_stats' => $login_stats,
    'incident_stats' => $incident_stats,
    'restricted_stats' => $restricted_stats,
    'activity_stats' => $activity_stats
]); 