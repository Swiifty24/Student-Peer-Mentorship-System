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

require_once '../classes/database.php';
require_once '../classes/notifications.php';

$message = '';
$success = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    try {
        $database = new Database();
        $conn = $database->connect();

        // Check if token exists and is not expired
        $query = "SELECT userID, firstName, tokenExpiry FROM users 
                  WHERE verificationToken = :token AND isVerified = 0";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Check if token is expired
            if ($user['tokenExpiry'] && strtotime($user['tokenExpiry']) < time()) {
                $message = "Verification link has expired. Please request a new verification email.";
            } elseif (!$user['tokenExpiry']) {
                $message = "Invalid verification link.";
            } else {
                // Verify the user
                $updateQuery = "UPDATE users 
                               SET isVerified = 1, 
                                   verificationToken = NULL, 
                                   tokenExpiry = NULL 
                               WHERE userID = :userID";

                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bindParam(':userID', $user['userID']);

                if ($updateStmt->execute()) {
                    $success = true;
                    $message = "Email verified successfully! You can now log in to your account.";

                    // Create welcome notification
                    try {
                        $notification = new Notification();
                        $welcomeMessage = "Welcome to PeerMentor, {$user['firstName']}! Your email has been verified successfully. You can now access all features of the platform.";
                        $notification->create($user['userID'], 'account', $welcomeMessage);
                        error_log("Welcome notification created for user: " . $user['userID']);
                    } catch (Exception $e) {
                        error_log("Error creating notification: " . $e->getMessage());
                    }
                } else {
                    $message = "Error verifying email. Please try again or contact support.";
                }
            }
        } else {
            $message = "Invalid or already used verification link.";
        }

    } catch (PDOException $e) {
        $message = "Database error. Please try again later.";
        error_log("Email verification error: " . $e->getMessage());
    }
} else {
    $message = "No verification token provided.";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification | PeerMentor</title>
    <link href="../styles/authPages.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
</head>

<body class="auth-page">
    <div class="auth-container">
        <?php if ($success): ?>
            <div class="status-icon success">✅</div>
            <h1>Email Verified!</h1>
            <p class="auth-subtitle"><?php echo htmlspecialchars($message); ?></p>

            <div class="alert success" style="margin: 24px 0;">
                Your account is now active. You can start using PeerMentor immediately!
            </div>

            <a href="login.php" class="btn btn-primary">Log In Now</a>

            <p class="auth-link" style="margin-top: 20px;">
                <a href="../index.php">← Back to Home</a>
            </p>
        <?php else: ?>
            <div class="status-icon error">❌</div>
            <h1>Verification Failed</h1>
            <p class="auth-subtitle"><?php echo htmlspecialchars($message); ?></p>

            <div class="info-box" style="margin: 24px 0;">
                <strong>What to do next:</strong>
                <ul>
                    <li>Check if you clicked the most recent verification link</li>
                    <li>Links expire after 24 hours</li>
                    <li>If needed, register again to get a new link</li>
                </ul>
            </div>

            <div class="btn-group">
                <a href="register.php" class="btn btn-primary">Register Again</a>
                <a href="login.php" class="btn btn-secondary">Back to Login</a>
            </div>

            <p class="auth-link" style="margin-top: 20px;">
                <a href="../index.php">← Back to Home</a>
            </p>
        <?php endif; ?>
    </div>
</body>

</html>