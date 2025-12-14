<?php
session_start();
require_once '../classes/courses.php';
require_once '../classes/tutorCourses.php';
require_once '../classes/enrollments.php';
require_once '../classes/users.php';
require_once '../classes/tutorProfiles.php'; 
require_once '../classes/csrf.php';

// Generate CSRF token
$csrfToken = CSRF::generateToken();

// Validate CSRF for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!CSRF::validateToken($token)) {
        die('Security validation failed. Please try again.');
    }
}

// Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Initialization
$courseManager = new Course();
$tutorCourseManager = new TutorCourse();
$enrollmentManager = new Enrollment(); 
$tutorProfileManager = new TutorProfile(); 

$allCourses = $courseManager->getAllCourses();
$tutorsList = [];
$selectedCourseID = filter_input(INPUT_GET, 'course', FILTER_VALIDATE_INT);
$courseName = 'Any Subject'; 
$message = ''; 
$currentStudentUserID = $_SESSION['user_id'];
$isTutorNow = $_SESSION['isTutorNow'] ?? 0; 
$userFirstName = $_SESSION['first_name'] ?? 'Student'; 
$pageTitle = "Find a Tutor";

if (isset($_GET['msg'])) {
    $message = htmlspecialchars($_GET['msg']);
}

$hasExistingProfile = $tutorProfileManager->getProfile($currentStudentUserID) !== false;

// Fetch Tutors
if ($selectedCourseID) {
    $tutorsList = $tutorCourseManager->findTutorsByCourse($selectedCourseID);
    $courseName = $courseManager->getCourseNameByID($selectedCourseID);
} else {
    $tutorsList = $tutorProfileManager->getAllActiveTutors();
}

// Handle Session Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_session') {
    $enrollmentManager->studentUserID = $currentStudentUserID;
    $enrollmentManager->tutorUserID = filter_input(INPUT_POST, 'tutorUserID', FILTER_VALIDATE_INT);
    $enrollmentManager->courseID = filter_input(INPUT_POST, 'courseID', FILTER_VALIDATE_INT);
    $enrollmentManager->sessionDetails = trim($_POST['sessionDetails'] ?? '');

    if ($enrollmentManager->tutorUserID && $enrollmentManager->courseID && !empty($enrollmentManager->sessionDetails)) {
        if ($enrollmentManager->requestSession()) {
             $message = "Your session request has been successfully sent to the tutor!";
        } else {
             $message = "Error: Could not submit session request.";
        }
    } else {
        $message = "Error: Invalid input for session request.";
    }

    header("Location: findTutor.php?msg=" . urlencode($message));
    exit();
}

ob_start();
?>

<div class="welcome-section">
    <h1>Welcome, <?php echo htmlspecialchars($userFirstName); ?>!</h1>
    <p>Find the right peer mentor in <strong><?php echo htmlspecialchars($courseName); ?></strong> to help you succeed.</p>
    <?php if ($message): ?>
        <p class="alert <?php echo strpos($message, 'Error') === 0 ? 'error' : 'success'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <div class="tutor-role-links" style="margin-top: 20px;">
        <?php if ($isTutorNow): ?>
            <a href="toggleRole.php?role=tutor&status=0" class="tertiary-button danger-button">Deactivate Tutor Role</a>
            <a href="tutorRequests.php" class="primary-button">View My Tutor Requests</a>
        <?php else: ?>
            <?php if ($hasExistingProfile): ?>
                <a href="toggleRole.php?role=tutor&status=1" class="primary-button">Reactivate Tutor Role</a>
            <?php else: ?>
                 <a href="setupTutorProfile.php" class="primary-button">Become a Tutor / Set Up Profile</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="search-filter-section">
    <form method="GET" action="findTutor.php">
        <label for="course-filter">Filter Tutors by Course:</label>
        <select name="course" id="course-filter" onchange="this.form.submit()">
            <option value="">-- All Courses --</option>
            <?php foreach ($allCourses as $course): ?>
                <option 
                    value="<?php echo htmlspecialchars($course['courseID']); ?>"
                    <?php if ($selectedCourseID == $course['courseID']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars("{$course['courseName']} ({$course['subjectArea']})"); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <noscript><button type="submit" class="primary-button">Filter</button></noscript>
    </form>
</div>

<div class="tutor-list-grid">
    <?php if (empty($tutorsList)): ?>
        <p class="no-results-message">No active tutors found <?php echo $selectedCourseID ? "for <strong>" . htmlspecialchars($courseName) . "</strong>" : ""; ?>.</p>
    <?php else: ?>
        <?php foreach ($tutorsList as $tutor): 
            $tutorCourses = $tutorCourseManager->getAllCoursesTaughtByTutor($tutor['userID']);
        ?>
            <div class="tutor-card" data-tutor-id="<?php echo htmlspecialchars($tutor['userID']); ?>">
                <div class="card-header">
                    <h3><?php echo htmlspecialchars($tutor['firstName'] . ' ' . $tutor['lastName']); ?></h3>
                    <span class="rate-tag">$<?php echo htmlspecialchars(number_format($tutor['hourlyRate'], 2)); ?>/hr</span>
                </div>
                <p class="bio"><?php echo htmlspecialchars($tutor['tutorBio']); ?></p>
                
                <div class="details">
                    <p><strong>Teaches:</strong> <?php echo htmlspecialchars(implode(', ', $tutorCourses)); ?></p>
                    <p><strong>Availability:</strong> <?php echo htmlspecialchars($tutor['availabilityDetails']); ?></p>
                </div>

                <button 
                    class="primary-button request-session-btn" 
                    data-tutor-id="<?php echo htmlspecialchars($tutor['userID']); ?>"
                    data-tutor-name="<?php echo htmlspecialchars("{$tutor['firstName']} {$tutor['lastName']}"); ?>"
                    data-courses='<?php echo json_encode($tutorCourseManager->getTutorCoursesWithSubjectArea($tutor['userID']) ?? []); ?>'
                    data-preselect-course="<?php echo htmlspecialchars($selectedCourseID ?? ''); ?>"
                >
                    Request Session
                </button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div id="requestModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeRequestModal">&times;</span>
        <h3>Request Session with <span id="tutorNameModal"></span></h3>
        <p id="modalMessage" class="alert success" style="display:none;"></p>
        <form method="POST" id="requestForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="request_session">
            <input type="hidden" name="tutorUserID" id="tutorUserIDInput">
            
            <label for="courseID">Select Course</label>
            <select name="courseID" id="courseID" required></select>

            <label for="sessionDetails">Session Details (Topic, Questions, Times)</label>
            <textarea name="sessionDetails" id="sessionDetails" rows="4" placeholder="Briefly describe what you need help with (e.g., 'Reviewing pointers for CS 101' or 'Help with PathFit Module 3')." required></textarea>
            
            <button type="submit" class="cta-button" style="margin-top: 20px;">Send Request</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('requestModal');
        const tutorNameSpan = document.getElementById('tutorNameModal');
        const tutorUserIDInput = document.getElementById('tutorUserIDInput');
        const courseSelect = document.getElementById('courseID');
        const closeModal = document.getElementById('closeRequestModal');

        // Function to open the modal
        document.querySelectorAll('.request-session-btn').forEach(button => {
            button.addEventListener('click', function() {
                const tutorID = this.getAttribute('data-tutor-id');
                const tutorName = this.getAttribute('data-tutor-name');
                const coursesJSON = this.getAttribute('data-courses');
                const preselectedCourseID = this.getAttribute('data-preselect-course');
                
                tutorNameSpan.textContent = tutorName;
                tutorUserIDInput.value = tutorID;

                courseSelect.innerHTML = '';
                const availableCourses = coursesJSON ? JSON.parse(coursesJSON) : [];
                
                if (availableCourses.length === 0) {
                    courseSelect.innerHTML = '<option value="" disabled>No courses available for this tutor.</option>';
                    document.getElementById('requestForm').querySelector('button[type="submit"]').disabled = true;
                } else {
                    availableCourses.forEach(course => {
                        const option = document.createElement('option');
                        option.value = course.courseID;
                        option.textContent = `${course.courseName} (${course.subjectArea})`;
                        
                        if (preselectedCourseID && course.courseID == preselectedCourseID) {
                            option.selected = true;
                        }
                        courseSelect.appendChild(option);
                    });
                    document.getElementById('requestForm').querySelector('button[type="submit"]').disabled = false;
                }

                document.getElementById('modalMessage').style.display = 'none';
                document.getElementById('sessionDetails').value = '';
                modal.style.display = "flex";
            });
        });

        // Close modal
        closeModal.addEventListener('click', function() {
            modal.style.display = 'none';
        });

        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        });
    });
</script>

<?php
$pageContent = ob_get_clean(); 
include 'template.php'; 
?>