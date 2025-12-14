<?php
session_start();
require_once '../classes/users.php'; 
require_once '../classes/csrf.php';

$user = new User(); 
$message = '';
$firstName = '';
$lastName = '';
$email = '';

// Generate CSRF token
$csrfToken = CSRF::generateToken();

// Redirect if already logged in
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
        // Sanitize Input and Capture Values
        $firstName = trim($_POST['firstName'] ?? '');
        $lastName = trim($_POST['lastName'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Backend Validation
        $errors = [];

        if (empty($firstName)) {
            $errors[] = "First Name is required.";
        } elseif (strlen($firstName) > 50) {
            $errors[] = "First Name cannot be longer than 50 characters.";
        }
        
        if (empty($lastName)) {
            $errors[] = "Last Name is required.";
        } elseif (strlen($lastName) > 50) {
            $errors[] = "Last Name cannot be longer than 50 characters.";
        }

        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }
        
        if (empty($password)) {
            $errors[] = "Password is required.";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long.";
        }
        
        // Process or Display Errors
        if (!empty($errors)) {
            $message = implode("<br>", $errors);
        } else {
            // Attempt Registration
            $user->email = htmlspecialchars($email); 
            $user->password = $password;
            $user->firstName = htmlspecialchars($firstName);
            $user->lastName = htmlspecialchars($lastName);
        
            if ($user->registerUser()) {
                header("Location: login.php?success=1"); 
                exit();
            } else {
                $message = "Registration failed. This email might already be in use.";
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
    <title>Register | PeerMentor</title>
    <link href="../styles/styles.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
</head>
<body class="centered-page">
    <div class="form-container">
        <h2>Create an Account</h2>
        <?php 
            if ($message && $_SERVER['REQUEST_METHOD'] === 'POST') {
                echo "<p class='alert error'>$message</p>";
            }
        ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            
            <label for="firstName">First Name</label>
            <input type="text" name="firstName" id="firstName" placeholder="First Name" value="<?php echo htmlspecialchars($firstName); ?>" required> 
            
            <label for="lastName">Last Name</label>
            <input type="text" name="lastName" id="lastName" placeholder="Last Name" value="<?php echo htmlspecialchars($lastName); ?>" required>
            
            <label for="email">Email</label>
            <input type="email" name="email" id="email" placeholder="email@example.com" value="<?php echo htmlspecialchars($email); ?>" required>
            
            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Create a password" required>

            <button type="submit" class="cta-button" style="margin-top: 20px;">Register & Continue</button>
        </form>
        <p style="margin-top: 15px;">Already have an account? <a href="login.php">Log In</a></p>
    </div>
</body>
</html>