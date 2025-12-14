<?php
// Secure session configuration
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    // 'cookie_secure' => true, // Uncomment when using HTTPS
    'use_strict_mode' => true
]);

require_once '../classes/courses.php';
require_once '../classes/csrf.php';

header('Content-Type: application/json');

// Security check: Must be a logged-in user
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit();
}

// Validate CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!CSRF::validateToken($token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Security validation failed. Please try again.']);
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Get and sanitize input
$courseName = trim($_POST['newCourseName'] ?? '');
$subjectArea = trim($_POST['newSubjectArea'] ?? '');

if (empty($courseName) || empty($subjectArea)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Course Name and Subject Area are required.']);
    exit();
}

// Validate input length
if (strlen($courseName) > 100 || strlen($subjectArea) > 50) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Input values are too long.']);
    exit();
}

$courseManager = new Course();

if ($courseManager->addCourse($courseName, $subjectArea)) {
    echo json_encode([
        'success' => true,
        'message' => 'New course added successfully! Please refresh the page to select it.',
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save the new course to the database.']);
}

exit();