<?php
session_start();
// Path is relative to the current file (assuming users.php is in '../classes/')
require_once '../classes/users.php'; 

// Define the message variable for status/error messages
$message = '';

// Initialize $loggedInUser to prevent "Undefined variable" warning in the HTML section
$loggedInUser = null; 

// Check if a success message from registration exists
if (isset($_GET['success'])) {
    $message = "Registration successful! Please log in.";
}

// 🐛 FIX APPLIED HERE: Simplify logic to always redirect to findTutor.php
if (isset($_SESSION['user_id'])) {
    header("Location: findTutor.php"); 
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Sanitize and capture input
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // 2. BACKEND VALIDATION: Check for empty fields
    if (empty($email) || empty($password)) {
        $message = "Email and password are required to log in.";
    } 
    else {
        // 3. Attempt Login
        $user = new User();
        $loggedInUser = $user->login($email, $password);

        if ($loggedInUser) {
            // 4. Session Setup
            $_SESSION['user_id'] = $loggedInUser['userID'];
            $_SESSION['email'] = $loggedInUser['email'];
            $_SESSION['first_name'] = $loggedInUser['firstName'];
            $_SESSION['last_name'] = $loggedInUser['lastName'];
            $_SESSION['isTutorNow'] = $loggedInUser['isTutorNow']; 

            // ✅ SUCCESS REDIRECT: Go to findTutor.php
            header("Location: findTutor.php");
            exit();

        } else {
            $message = "Invalid email or password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In | PeerMentor</title>
    <link href="../styles/styles.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    </head>
<body class="centered-page"> 
    <div class="form-container">
        <h2>Account Login</h2>
        <?php 
            // Display the success message from registration
            if (isset($_GET['success'])) {
                echo "<p class='alert success'>" . htmlspecialchars($message) . "</p>";
            }
            // Display login failure messages (from POST handler logic above)
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$loggedInUser) {
                 echo "<p class='alert error'>$message</p>";
            }
        ?>
        <form method="POST">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" placeholder="email@example.com" > 
            
            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Enter your password" > 
            
            <button type="submit" class="primary-button" style="margin-top: 20px;">Log In</button>
        </form>
        <p style="margin-top: 15px;"><a href="register.php">Need an account? Register here.</a></p>
    </div>
</body>
</html>