<?php
require_once 'init.php';
$pageTitle = "Setup Tutor Profile";

require_once '../classes/users.php';
require_once '../classes/tutorProfiles.php';
require_once '../classes/tutorCourses.php';
require_once '../classes/courses.php';
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

$currentUserID = $_SESSION['user_id'];
$message = '';
$courseManager = new Course();
$tutorProfile = new TutorProfile();
$tutorCourseManager = new TutorCourse();
$userManager = new User();

$allCourses = $courseManager->getAllCourses();

// Default values
$initialBio = '';
$initialRate = '';
$initialAvailability = '';
$existingCourseIDs = [];

// Load existing profile
$existingProfile = $tutorProfile->getProfile($currentUserID);

if ($existingProfile) {
    $initialBio = $existingProfile['tutorBio'];
    $initialRate = $existingProfile['hourlyRate'];
    $initialAvailability = $existingProfile['availabilityDetails'];

    $existingCourses = $tutorCourseManager->getAllCoursesTaughtByTutor($currentUserID, true);
    $existingCourseIDs = array_column($existingCourses, 'courseID');
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tutorBio = trim($_POST['tutorBio'] ?? '');
    $hourlyRate = filter_input(INPUT_POST, 'hourlyRate', FILTER_VALIDATE_FLOAT);
    $availabilityDetails = trim($_POST['availabilityDetails'] ?? '');
    $selectedCourseIDs = $_POST['courseIDs'] ?? [];

    $errors = [];

    if (empty($tutorBio) || strlen($tutorBio) < 10)
        $errors[] = "Tutor Bio must be at least 10 characters.";
    if ($hourlyRate === false || $hourlyRate < 0)
        $errors[] = "Hourly Rate must be a valid, non-negative number.";
    if (empty($availabilityDetails))
        $errors[] = "Availability Details are required.";
    if (empty($selectedCourseIDs))
        $errors[] = "You must select at least one course to teach.";

    if (empty($errors)) {
        $tutorProfile->userID = $currentUserID;
        $tutorProfile->tutorBio = htmlspecialchars($tutorBio);
        $tutorProfile->hourlyRate = $hourlyRate;
        $tutorProfile->availabilityDetails = htmlspecialchars($availabilityDetails);

        $profileSaved = $tutorProfile->saveProfile();
        $coursesSaved = $tutorCourseManager->saveCourses($currentUserID, $selectedCourseIDs);

        if ($profileSaved && $coursesSaved) {
            $userManager->toggleRole($currentUserID, 'tutor', 1);
            $_SESSION['isTutorNow'] = 1;

            $message = "Your Tutor Profile has been successfully saved and ACTIVATED! You can now receive requests.";
            header("Location: setupTutorProfile.php?msg=" . urlencode($message));
            exit();
        } else {
            $message = "An error occurred while saving your profile or courses.";
        }
    } else {
        $message = "Validation Error: " . implode(' | ', $errors);
        $initialBio = $tutorBio;
        $initialRate = $_POST['hourlyRate'] ?? '';
        $initialAvailability = $availabilityDetails;
        $existingCourseIDs = $selectedCourseIDs;
    }
}

if (isset($_GET['msg'])) {
    $message = htmlspecialchars($_GET['msg']);
}

ob_start();
?>
<div class="welcome-section">
    <h1>Setup Tutor Profile</h1>
    <p>Complete your profile to start receiving tutoring requests from students!</p>
    <?php if ($message): ?>
        <p class="alert <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
            <?php echo $message; ?>
        </p>
    <?php endif; ?>
</div>

<div class="profile-form-container">
    <form method="POST" action="setupTutorProfile.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

        <label for="tutorBio">Tutor Bio (Min 10 chars)</label>
        <textarea name="tutorBio" id="tutorBio" rows="4"
            required><?php echo htmlspecialchars($initialBio); ?></textarea>

        <label for="hourlyRate">Hourly Rate (e.g., 5.00)</label>
        <input type="number" name="hourlyRate" id="hourlyRate" min="0" step="0.01" placeholder="5.00"
            value="<?php echo htmlspecialchars($initialRate); ?>" required>

        <label for="availabilityDetails">📅 General Availability</label>
        <input type="text" name="availabilityDetails" id="availabilityDetails" placeholder="Click to select date & time"
            value="<?php echo htmlspecialchars($initialAvailability); ?>" required>
        <small style="color: #666; display: block; margin-top: 5px;">Select your available dates and times for tutoring
            sessions</small>

        <label>Courses You Can Teach
            <a href="#" id="openAddCourseModal" style="font-size: 0.9em; margin-left: 10px;">(+ Add New Course)</a>
        </label>
        <div class="course-list-checkboxes">
            <?php if (!empty($allCourses)): ?>
                <?php foreach ($allCourses as $course): ?>
                    <label class="checkbox-container">
                        <input type="checkbox" name="courseIDs[]" value="<?php echo htmlspecialchars($course['courseID']); ?>"
                            <?php if (in_array($course['courseID'], $existingCourseIDs))
                                echo 'checked'; ?>>
                        <?php echo htmlspecialchars("{$course['courseName']} ({$course['subjectArea']})"); ?>
                        <span class="checkmark"></span>
                    </label>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No courses found. Please add one using the link above.</p>
            <?php endif; ?>
        </div>

        <button type="submit" class="cta-button" style="margin-top: 30px;">
            Save & Activate Tutor Profile
        </button>

        <?php if (($_SESSION['isTutorNow'] ?? false)): ?>
            <a href="toggleRole.php?role=tutor&status=0" class="tertiary-button"
                style="display: block; text-align: center; margin-top: 15px;">Deactivate Tutor Role</a>
        <?php endif; ?>
    </form>
</div>

<div id="addCourseModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeAddCourseModal">&times;</span>
        <h3>Add New Course</h3>
        <p id="modalMessage" class="alert success" style="display:none;"></p>

        <form id="addCourseForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

            <label for="newCourseName">Course Name (e.g., General Chemistry)</label>
            <input type="text" name="newCourseName" id="newCourseName" required>

            <label for="newSubjectArea">Subject Area (e.g., Science)</label>
            <input type="text" name="newSubjectArea" id="newSubjectArea" required>

            <button type="submit" class="primary-button" style="margin-top: 20px;">Submit New Course</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('addCourseModal');
        const openBtn = document.getElementById('openAddCourseModal');
        const closeBtn = document.getElementById('closeAddCourseModal');
        const addCourseForm = document.getElementById('addCourseForm');
        const modalMessage = document.getElementById('modalMessage');

        if (openBtn) {
            openBtn.addEventListener('click', function (e) {
                e.preventDefault();
                modalMessage.style.display = 'none';
                modal.style.display = "flex";
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                modal.style.display = "none";
            });
        }

        addCourseForm.addEventListener('submit', function (event) {
            event.preventDefault();

            const submitButton = addCourseForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Submitting...';
            modalMessage.style.display = 'none';

            const formData = new FormData(addCourseForm);

            fetch('addCourses.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    modalMessage.textContent = data.message;
                    modalMessage.style.display = 'block';

                    if (data.success) {
                        modalMessage.className = 'alert success';
                        document.getElementById('newCourseName').value = '';
                        document.getElementById('newSubjectArea').value = '';

                        setTimeout(() => {
                            modal.style.display = "none";
                            location.reload(); // Reload to show new course
                        }, 1500);
                    } else {
                        modalMessage.className = 'alert error';
                    }
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    modalMessage.textContent = 'An unexpected error occurred.';
                    modalMessage.className = 'alert error';
                    modalMessage.style.display = 'block';
                })
                .finally(() => {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Submit New Course';
                });
        });

        window.addEventListener('click', function (event) {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        });

        // FLATPICKR DATE PICKER FOR AVAILABILITY
        flatpickr("#availabilityDetails", {
            enableTime: false,
            dateFormat: "F j, Y",
            minDate: "today",
            mode: "multiple",
            conjunction: ", ",
            placeholder: "Select multiple available dates"
        });
    });
</script>

<?php
$pageContent = ob_get_clean();
include 'template.php';
?>