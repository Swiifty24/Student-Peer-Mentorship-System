<?php
// pages/tutorRequests.php - FINAL REVISION (Removed internal links)

session_start();
require_once '../classes/users.php';
require_once '../classes/enrollments.php';
require_once '../classes/tutorProfiles.php'; 
require_once '../classes/courses.php'; 

// --- 1. Security Check ---
if (!isset($_SESSION['user_id']) || !($_SESSION['isTutorNow'] ?? false)) {
    header("Location: findTutor.php?msg=" . urlencode("You must activate your tutor profile to view your requests.")); 
    exit();
}

// --- 2. Initialization and Setup ---
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

// --- 3. Handle POST Actions (Confirm/Cancel/Complete) ---
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
                $message = "Request ID {$enrollmentID} successfully marked as **{$newStatus}**.";
            } else {
                $message = "Error: Could not update the request status.";
            }
        }
    } else {
        $message = "Error: Invalid Request ID.";
    }

    // Redirect to clear the POST data
    header("Location: tutorRequests.php?msg=" . urlencode($message));
    exit();
}

// --- 4. Fetch Requests for the Tutor ---
$requests = $enrollmentManager->getRequestsByTutor($currentUserID);

// Start output buffering for the template
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
                
                <?php if ($request['status'] == 0): // Only show buttons for 'Requested' status ?>
                    <div class="action-buttons">
                        <form method="POST" style="display: inline-block;">
                            <input type="hidden" name="enrollmentID" value="<?php echo htmlspecialchars($request['enrollmentID']); ?>">
                            <input type="hidden" name="action" value="confirm">
                            <button type="submit" class="primary-button small-button">Confirm</button>
                        </form>
                        
                        <form method="POST" style="display: inline-block;">
                            <input type="hidden" name="enrollmentID" value="<?php echo htmlspecialchars($request['enrollmentID']); ?>">
                            <input type="hidden" name="action" value="cancel">
                            <button type="submit" class="tertiary-button danger-button small-button">Decline</button>
                        </form>
                    </div>
                <?php endif; ?>
                
                <?php if ($request['status'] == 1): // Show 'Mark as Complete' for Confirmed requests ?>
                    <div class="action-buttons">
                        <form method="POST">
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
$pageContent = ob_get_clean(); // End output buffering and capture content
include 'template.php'; // Correct inclusion of the template at the end
?>