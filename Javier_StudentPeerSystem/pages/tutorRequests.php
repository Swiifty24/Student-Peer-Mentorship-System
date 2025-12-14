<?php
session_start();
require_once '../classes/users.php';
require_once '../classes/enrollments.php';
require_once '../classes/tutorProfiles.php'; 
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
if (!isset($_SESSION['user_id']) || !($_SESSION['isTutorNow'] ?? false)) {
    header("Location: findTutor.php?msg=" . urlencode("You must activate your tutor profile to view your requests.")); 
    exit();
}

// Initialization
$pageTitle = "My Tutoring Requests"; 
$currentUserID = $_SESSION['user_id'];
$enrollmentManager = new Enrollment();
$courseManager = new Course(); 
$userManager = new User(); 
$message = '';
$userName = $_SESSION['first_name'] ?? 'Tutor';

if (isset($_GET['msg'])) {
    $message = htmlspecialchars($_GET['msg']);
}

// Handle POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $enrollmentID = filter_input(INPUT_POST, 'enrollmentID', FILTER_VALIDATE_INT);
    
    if ($enrollmentID) {
        $newStatus = '';
        if ($_POST['action'] === 'confirm') {
            $newStatus = 'Confirmed';
        } elseif ($_POST['action'] === 'cancel') {
            $newStatus = 'Cancelled';
        } elseif ($_POST['action'] === 'complete') {
            $newStatus = 'Completed';
        }

        if (!empty($newStatus)) {
            if ($enrollmentManager->updateStatus($enrollmentID, $newStatus)) {
                $message = "Request ID {$enrollmentID} successfully marked as {$newStatus}.";
            } else {
                $message = "Error: Could not update the request status.";
            }
        }
    } else {
        $message = "Error: Invalid Request ID.";
    }

    header("Location: tutorRequests.php?msg=" . urlencode($message));
    exit();
}

// Fetch Requests
$requests = $enrollmentManager->getRequestsByTutor($currentUserID);

ob_start();
?>

<div class="welcome-section">
    <h1>Hello, <?php echo htmlspecialchars($userName); ?>!</h1>
    <p>Manage your incoming and pending session requests below. Your profile actions are available in the navigation bar.</p>
    <?php if ($message): ?>
        <p class="alert <?php echo strpos($message, 'Error') === 0 ? 'error' : 'success'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>
</div>

<div class="request-list-grid">
    <?php if (empty($requests)): ?>
        <p class="no-results-message">You have no pending tutoring requests.</p>
    <?php else: ?>
        <?php foreach ($requests as $request): 
            $studentName = $userManager->getUserFullNameByID($request['studentUserID']);
            $courseName = $courseManager->getCourseNameByID($request['courseID']);
            $statusString = $enrollmentManager->getStatusString($request['status']);
        ?>
            <div class="tutor-card request-card status-<?php echo htmlspecialchars($request['status']); ?>">
                <div class="card-header">
                    <h3>Request for: <?php echo htmlspecialchars($courseName); ?></h3>
                    <span class="status-tag <?php 
                        if ($request['status'] == 1) echo 'confirmed'; 
                        else if ($request['status'] == 2) echo 'cancelled';
                        else if ($request['status'] == 3) echo 'completed';
                        else echo 'pending';
                    ?>">
                        <?php echo htmlspecialchars($statusString); ?>
                    </span>
                </div>
                
                <p><strong>From:</strong> <?php echo htmlspecialchars($studentName); ?></p>
                <p class="details"><strong>Details:</strong> <?php echo htmlspecialchars($request['sessionDetails']); ?></p>
                <p class="meta">Requested on: <?php echo date('M d, Y', strtotime($request['requestDate'])); ?></p>
                
                <?php if ($request['status'] == 0): ?>
                    <div class="action-buttons">
                        <form method="POST" style="display: inline-block;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="enrollmentID" value="<?php echo htmlspecialchars($request['enrollmentID']); ?>">
                            <input type="hidden" name="action" value="confirm">
                            <button type="submit" class="primary-button small-button">Confirm</button>
                        </form>
                        
                        <form method="POST" style="display: inline-block;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="enrollmentID" value="<?php echo htmlspecialchars($request['enrollmentID']); ?>">
                            <input type="hidden" name="action" value="cancel">
                            <button type="submit" class="tertiary-button danger-button small-button">Decline</button>
                        </form>
                    </div>
                <?php endif; ?>
                
                <?php if ($request['status'] == 1): ?>
                    <div class="action-buttons">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="enrollmentID" value="<?php echo htmlspecialchars($request['enrollmentID']); ?>">
                            <input type="hidden" name="action" value="complete">
                            <button type="submit" class="cta-button small-button">Mark as Complete</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$pageContent = ob_get_clean();
include 'template.php';
?>