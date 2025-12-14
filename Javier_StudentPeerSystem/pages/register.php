<?php
require_once 'init.php';

require_once '../classes/users.php';
require_once '../classes/csrf.php';
require_once '../classes/rateLimiter.php';
require_once '../classes/emailService.php';

$message = '';

// Generate CSRF token
$csrfToken = CSRF::generateToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    $token = $_POST['csrf_token'] ?? '';
    if (!CSRF::validateToken($token)) {
        $message = 'Security validation failed. Please try again.';
    } else {
        // Rate limiting check
        $userIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!RateLimiter::checkLimit($userIP, 'register', 3, 600)) {
            $waitTime = RateLimiter::getWaitTime($userIP, 'register', 600);
            $message = "Too many registration attempts. Please try again in " . ceil($waitTime / 60) . " minutes.";
        } else {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $firstName = $_POST['firstName'] ?? '';
            $lastName = $_POST['lastName'] ?? '';

            $user = new User();
            $errors = [];

            if (empty($firstName) || empty($lastName)) {
                $errors[] = "First name and last name are required.";
            }
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Please provide a valid email address.";
            }

            // Password validation
            if (strlen($password) < 8) {
                $errors[] = "Password must be at least 8 characters long.";
            } elseif (!preg_match('/[A-Z]/', $password)) {
                $errors[] = "Password must contain at least one uppercase letter.";
            } elseif (!preg_match('/[a-z]/', $password)) {
                $errors[] = "Password must contain at least one lowercase letter.";
            } elseif (!preg_match('/[0-9]/', $password)) {
                $errors[] = "Password must contain at least one number.";
            } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
                $errors[] = "Password must contain at least one special character.";
            }

            if (!empty($errors)) {
                $message = implode("<br>", $errors);
            } else {
                $user->email = htmlspecialchars($email);
                $user->password = $password;
                $user->firstName = htmlspecialchars($firstName);
                $user->lastName = htmlspecialchars($lastName);

                if ($user->registerUser()) {
                    RateLimiter::clearLimit($userIP, 'register');

                    // Generate verification token
                    $verificationToken = bin2hex(random_bytes(32));
                    $tokenExpiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

                    try {
                        require_once '../classes/database.php';
                        $database = new Database();
                        $conn = $database->connect();
                        $updateQuery = "UPDATE users SET verificationToken = :token, tokenExpiry = :expiry WHERE email = :email";
                        $stmt = $conn->prepare($updateQuery);
                        $stmt->bindParam(':token', $verificationToken);
                        $stmt->bindParam(':expiry', $tokenExpiry);
                        $stmt->bindParam(':email', $email);
                        $stmt->execute();

                        // TEMPORARY: Auto-verify users while Gmail authentication is being fixed
                        // TODO: Remove this bypass once Gmail/SendGrid is working properly
                        error_log("Auto-verifying user (Gmail auth bypass active): $email");
                        $autoVerify = "UPDATE users SET isVerified = 1, verificationToken = NULL, tokenExpiry = NULL WHERE email = :email";
                        $verifyStmt = $conn->prepare($autoVerify);
                        $verifyStmt->bindParam(':email', $email);
                        $verifyStmt->execute();

                        // Still attempt to send verification email (will fail silently with current Gmail issue)
                        $emailService = new EmailService();
                        $emailSent = $emailService->sendVerificationEmail($email, $firstName, $verificationToken);

                        if ($emailSent) {
                            error_log("Verification email sent to: $email");
                        } else {
                            error_log("Failed to send verification email to: $email (using auto-verify bypass)");
                        }
                    } catch (Exception $e) {
                        error_log("Error setting verification token: " . $e->getMessage());
                    }

                    $_SESSION['pending_verification_email'] = $email;
                    header("Location: pendingVerification.php");
                    exit();
                } else {
                    $message = "Registration failed. This email might already be in use.";
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
    <title>Register | PeerMentor</title>
    <link href="../styles/authPages.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
</head>

<body class="auth-page">
    <div class="auth-container">
        <div class="auth-icon">🎓</div>
        <h2>Join PeerMentor</h2>
        <p class="auth-subtitle">Create your free account and start learning today</p>

        <?php if (!empty($message)): ?>
            <div class="alert error">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

            <label for="firstName">First Name</label>
            <input type="text" name="firstName" id="firstName" placeholder="John" required autofocus>

            <label for="lastName">Last Name</label>
            <input type="text" name="lastName" id="lastName" placeholder="Doe" required>

            <label for="email">Email Address</label>
            <input type="email" name="email" id="email" placeholder="your.email@example.com" required>

            <label for="password">Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" placeholder="Create a strong password" required>
                <button type="button" class="password-toggle" id="togglePassword"
                    aria-label="Toggle password visibility">
                    👁️
                </button>
            </div>

            <script>
                document.getElementById('togglePassword').addEventListener('click', function () {
                    const passwordInput = document.getElementById('password');
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);

                    // Simple text toggle, can be improved with SVG icons
                    this.textContent = type === 'password' ? '👁️' : '🙈';
                });
            </script>

            <div class="note" style="margin-bottom: 20px; text-align: left;">
                <small>Password must contain:</small>
                <ul style="margin: 8px 0 0 20px;">
                    <li><small>At least 8 characters</small></li>
                    <li><small>One uppercase & one lowercase letter</small></li>
                    <li><small>One number & one special character</small></li>
                </ul>
            </div>

            <button type="submit" class="btn btn-primary">Create Account</button>
        </form>

        <p class="auth-link">
            Already have an account? <a href="login.php">Log in here</a>
        </p>

        <p class="auth-link">
            <a href="../index.php">← Back to Home</a>
        </p>
    </div>
</body>

</html>