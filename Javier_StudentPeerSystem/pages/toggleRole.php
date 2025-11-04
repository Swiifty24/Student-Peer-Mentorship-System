<?php
// pages/toggleRole.php - Handles Activation (1) and Deactivation (0) of Tutor Role

session_start();
require_once '../classes/users.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['user_id'];
$user = new User();
$redirectPage = "findTutor.php";
$role = $_GET['role'] ?? null;
$status = $_GET['status'] ?? null; // '0' for deactivate, '1' for reactivate/activate

if ($role === 'tutor' && ($status === '0' || $status === '1')) 
{
    $newStatus = intval($status);

    // Call the toggleRole method in the User class (which you already have)
    if ($user->toggleRole($userID, 'tutor', $newStatus)) 
    {
        // Update the session variable immediately after success
        $_SESSION['isTutorNow'] = $newStatus;
        
        if ($newStatus === 1)
        {
            $message = "Your tutor role is now **ACTIVE**. You can view your requests.";
            $redirectPage = "tutorRequests.php";
        } 
        else 
        {
            $message = "Your tutor role has been set to **INACTIVE**. You will not receive new requests.";
            $redirectPage = "findTutor.php";
        }
    } 
    else 
    {
        $message = "Error: Could not update tutor status in the database.";
    }
} else {
    $message = "Invalid role or status parameters for role toggle.";
}

header("Location: " . $redirectPage . "?msg=" . urlencode($message));
exit();