<?php
session_start();
// 1. This includes the definition of the User class
require_once '../classes/users.php'; 

// 2. We can now safely instantiate the User object
$user = new User(); 
$message = '';

// --- FIX: Store previous input values to pre-fill the form on error ---
$firstName = '';
$lastName = '';
$email = '';

// Redirect if already logged in (safeguard)
if (isset($_SESSION['user_id'])) {
    header("Location: findTutor.php"); 
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    // --- 1. Sanitize Input and Capture Values ---
    // Use the null coalescing operator (??) for safety
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // --- 2. Backend Validation (This part is already robust) ---
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
    
    // --- 3. Process or Display Errors ---
    if (!empty($errors)) {
        // Concatenate errors into a single message
        $message = implode("<br>", $errors);
    } else {
        // --- 4. SUCCESS: Attempt Registration ---
        // Final sanitization of visible fields before database insertion
        $user->email = htmlspecialchars($email); 
        $user->password = $password; // Hashing should happen inside $user->registerUser()
        $user->firstName = htmlspecialchars($firstName);
        $user->lastName = htmlspecialchars($lastName);
    
        if ($user->registerUser()) 
        {
            // Success: Redirect to login page
            header("Location: login.php?success=1"); 
            exit();
        } 
        else 
        {
            // Failure: Typically means email is already in use (assuming logic in users.php)
            $message = "Registration failed. This email might already be in use.";
        }
    }
}
// --- Note: If the request is GET, the $firstName, $lastName, $email variables remain empty ('') ---
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
<body class="centered-page"> <div class="form-container">
        <h2>Create an Account</h2>
        <?php 
            // Only show the error message if the form was POSTed and failed validation
            if ($message && $_SERVER['REQUEST_METHOD'] === 'POST') {
                echo "<p class='alert error'>$message</p>";
            }
        ?>
        <form method="POST">
            <label for="firstName">First Name</label>
            <input type="text" name="firstName" id="firstName" placeholder="First Name" value="<?php echo htmlspecialchars($firstName); ?>"> 
            
            <label for="lastName">Last Name</label>
            <input type="text" name="lastName" id="lastName" placeholder="Last Name" value="<?php echo htmlspecialchars($lastName); ?>">
            
            <label for="email">Email</label>
            <input type="email" name="email" id="email" placeholder="email@example.com" value="<?php echo htmlspecialchars($email); ?>">
            
            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Create a password">

            <button type="submit" class="cta-button" style="margin-top: 20px;">Register & Continue</button>
        </form>
        <p style="margin-top: 15px;">Already have an account? <a href="login.php">Log In</a></p>
    </div>
</body>
</html>