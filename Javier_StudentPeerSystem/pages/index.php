<?php 
// Start session for consistency, although not strictly needed for this page
session_start();

// If the user is already logged in, redirect them to the appropriate primary page
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['isTutorNow'] ?? false) {
        header("Location: tutorRequests.php");
        exit();
    } else {
        header("Location: findTutor.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PeerMentor Connect | Free Tutoring</title>
    <link href="../styles/landingStyle.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
</head>
<body>
    <div class="hero-section">
        
    <div class="hero-section">
        <div class="hero-content">
            <h1>Connect. Learn. Succeed.</h1>
            <p>Your free peer-to-peer tutoring network. Find help or offer your expertise today.</p>
            
            <a href="register.php" class="btn cta-button">Get Started (Free)</a>
            
            <div style="margin-top: 20px;">
                <p style="font-size: 1em; font-weight: 400;">Already a member? 
                    <a href="login.php" style="color: #ffffff; text-decoration: underline;">Log In</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>