<?php
// Load environment variables
require_once '../classes/envLoader.php';
EnvLoader::load(__DIR__ . '/../.env');

// Secure session configuration
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    // 'cookie_secure' => true, // Uncomment when using HTTPS
    'use_strict_mode' => true
]);

// Get email from session if available
$userEmail = $_SESSION['pending_verification_email'] ?? 'your email';

// Clear the session variable
if (isset($_SESSION['pending_verification_email'])) {
    unset($_SESSION['pending_verification_email']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Your Email | PeerMentor</title>
    <link href="../styles/authPages.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
</head>

<body class="auth-page">
    <div class="auth-container">
        <div class="auth-icon animated">📧</div>
        <h1>Check Your Email!</h1>
        <p class="auth-subtitle">We've sent a verification link to:</p>

        <div class="email-display">
            <?php echo htmlspecialchars($userEmail); ?>
        </div>

        <div class="info-box">
            <strong>📋 Next Steps:</strong>
            <ul>
                <li>Open your email inbox</li>
                <li>Look for an email from PeerMentor Team</li>
                <li>Click the verification link in the email</li>
                <li>You'll be redirected to log in!</li>
            </ul>
        </div>

        <div class="alert info" style="margin-top: 24px;">
            The verification link will <strong>expire in 24 hours</strong>, so please verify soon!
        </div>

        <div class="btn-group">
            <a href="../index.php" class="btn btn-primary">← Back to Home</a>
            <a href="register.php" class="btn btn-secondary">Back to Registration</a>
        </div>

        <p class="note" style="margin-top: 24px;">
            <strong>💡 Tip:</strong> If you don't see the email, check your spam/junk folder.<br>
            Still having trouble? Contact support for help.
        </p>
    </div>
</body>

</html>