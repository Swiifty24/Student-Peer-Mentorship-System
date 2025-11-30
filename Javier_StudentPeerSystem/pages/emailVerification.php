<?php
// pages/verifyEmail.php
session_start();
require_once '../classes/database.php';

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
            if (strtotime($user['tokenExpiry']) < time()) {
                $message = "Verification link has expired. Please request a new verification email.";
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
                } else {
                    $message = "Error verifying email. Please try again or contact support.";
                }
            }
        } else {
            $message = "Invalid or already used verification link.";
        }
        
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
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
    <link href="../styles/styles.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <style>
        .verification-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 40px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .success-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        
        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .btn-container {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
    </style>
</head>
<body class="centered-page">
    <div class="verification-container">
        <?php if ($success): ?>
            <div class="success-icon">✅</div>
            <h1>Email Verified!</h1>
            <p><?php echo htmlspecialchars($message); ?></p>
            <div class="btn-container">
                <a href="login.php" class="cta-button">Go to Login</a>
            </div>
        <?php else: ?>
            <div class="error-icon">❌</div>
            <h1>Verification Failed</h1>
            <p><?php echo htmlspecialchars($message); ?></p>
            <div class="btn-container">
                <a href="register.php" class="tertiary-button">Register Again</a>
                <a href="login.php" class="primary-button">Back to Login</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>