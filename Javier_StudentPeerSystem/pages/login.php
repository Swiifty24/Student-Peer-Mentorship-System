<?php
session_start();
require_once '../classes/users.php'; 
require_once '../classes/csrf.php';

$message = '';
$loggedInUser = null; 

// Generate CSRF token
$csrfToken = CSRF::generateToken();

if (isset($_GET['success'])) {
    $message = "Registration successful! Please log in.";
}

if (isset($_SESSION['user_id'])) {
    header("Location: findTutor.php"); 
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    $token = $_POST['csrf_token'] ?? '';
    if (!CSRF::validateToken($token)) {
        $message = 'Security validation failed. Please try again.';
    } else {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $message = "Email and password are required to log in.";
        } else {
            $user = new User();
            $loggedInUser = $user->login($email, $password);

            if ($loggedInUser) {
                $_SESSION['user_id'] = $loggedInUser['userID'];
                $_SESSION['email'] = $loggedInUser['email'];
                $_SESSION['first_name'] = $loggedInUser['firstName'];
                $_SESSION['last_name'] = $loggedInUser['lastName'];
                $_SESSION['isTutorNow'] = $loggedInUser['isTutorNow'] ?? 0; 

                header("Location: findTutor.php");
                exit();
            } else {
                $message = "Invalid email or password. Please try again.";
            }
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
            if (!empty($message)) {
                $alertClass = (isset($_GET['success']) || strpos($message, 'successful') !== false) ? 'success' : 'error';
                echo "<p class='alert {$alertClass}'>" . htmlspecialchars($message) . "</p>";
            }
        ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            
            <label for="email">Email</label>
            <input type="email" name="email" id="email" placeholder="email@example.com" required> 
            
            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Enter your password" required> 
            
            <button type="submit" class="primary-button" style="margin-top: 20px;">Log In</button>
        </form>
        <p style="margin-top: 15px;"><a href="register.php">Need an account? Register here.</a></p>
    </div>
</body>
</html>