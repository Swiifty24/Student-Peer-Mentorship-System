<?php
// pages/addCourse.php 

// Path is relative to the current file (assuming courses.php is in '../classes/')
require_once '../classes/courses.php';

header('Content-Type: application/json');

// Security check: Must be a logged-in user
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Get and sanitize input
$courseName = trim($_POST['newCourseName'] ?? '');
$subjectArea = trim($_POST['newSubjectArea'] ?? '');

if (empty($courseName) || empty($subjectArea)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Course Name and Subject Area are required.']);
    exit();
}

$courseManager = new Course();

// The addCourse method handles the database insertion (check courses.php)
if ($courseManager->addCourse($courseName, $subjectArea)) {
    
    echo json_encode([
        'success' => true, 
        'message' => 'New course added successfully! Please click "Activate Tutor Profile & Start Teaching" to refresh the full course list and select it.',
    ]);

} else {
    // If addCourse failed (e.g., database error, duplicate key), log the error and inform the user.
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Failed to save the new course to the database.']);
}

exit();
?>