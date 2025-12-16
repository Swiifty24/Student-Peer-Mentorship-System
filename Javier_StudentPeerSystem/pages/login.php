<?php
require_once 'init.php';

require_once '../classes/users.php';
require_once '../classes/csrf.php';
require_once '../classes/rateLimiter.php';

$message = '';
$loggedInUser = null;

// Generate CSRF token
$csrfToken = CSRF::generateToken();

// Check for success messages from URL
if (isset($_GET['msg'])) {
    $message = htmlspecialchars($_GET['msg'], ENT_QUOTES, 'UTF-8');
} elseif (isset($_GET['success'])) {
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
        // Rate limiting check
        $userIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!RateLimiter::checkLimit($userIP, 'login', 5, 300)) {
            $waitTime = RateLimiter::getWaitTime($userIP, 'login', 300);
            $message = "Too many login attempts. Please try again in " . ceil($waitTime / 60) . " minutes.";
        } else {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $message = "Email and password are required to log in.";
            } else {
                $user = new User();
                $loggedInUser = $user->login($email, $password);

                if ($loggedInUser) {
                    // Check if email is verified
                    if (isset($loggedInUser['isEmailVerified']) && $loggedInUser['isEmailVerified'] == 0) {
                        $message = "Please verify your email address before logging in. Check your inbox for the verification link.";
                    } else {
                        // Clear rate limit on successful login
                        RateLimiter::clearLimit($userIP, 'login');

                        // Regenerate session ID to prevent session fixation
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = $loggedInUser['userID'];
                        $_SESSION['email'] = $loggedInUser['email'];
                        $_SESSION['first_name'] = $loggedInUser['firstName'];
                        $_SESSION['last_name'] = $loggedInUser['lastName'];
                        $_SESSION['isTutorNow'] = $loggedInUser['isTutorNow'] ?? 0;

                        header("Location: findTutor.php");
                        exit();
                    }
                } else {
                    $message = "Invalid email or password. Please try again.";
                }
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
    <link href="../styles/authPages.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
</head>

<body class="auth-page">
    <div class="auth-container">
        <div class="auth-icon">🎓</div>
        <h2>Welcome Back!</h2>
        <p class="auth-subtitle">Log in to continue your learning journey</p>

        <?php if (!empty($message)): ?>
            <div
                class="alert <?php echo (isset($_GET['success']) || strpos($message, 'successful') !== false) ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

            <label for="email">Email Address</label>
            <input type="email" name="email" id="email" placeholder="your.email@example.com" required autofocus>

            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Enter your password" required>

            <button type="submit" class="btn btn-primary">Log In</button>
        </form>

        <p class="auth-link">
            Don't have an account? <a href="register.php">Sign up for free</a>
        </p>

        <p class="auth-link">
            <a href="../index.php">← Back to Home</a>
        </p>
    </div>
</body>

</html>