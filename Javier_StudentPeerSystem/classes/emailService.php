<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class EmailService
{
    private $mailer;
    private $fromEmail;
    private $fromName;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);

        // Load configuration from environment or use defaults
        $smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $smtpPort = getenv('SMTP_PORT') ?: 587;
        $smtpUsername = getenv('SMTP_USERNAME') ?: '';
        $smtpPassword = getenv('SMTP_PASSWORD') ?: '';
        $this->fromEmail = getenv('SMTP_FROM_EMAIL') ?: 'noreply@peermentor.com';
        $this->fromName = getenv('SMTP_FROM_NAME') ?: 'PeerMentor Team';

        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $smtpHost;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $smtpUsername;
            $this->mailer->Password = $smtpPassword;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $smtpPort;

            // Enable debugging (set to 0 in production)
            // 0 = off, 1 = client messages, 2 = client and server messages
            $this->mailer->SMTPDebug = getenv('APP_ENV') === 'development' ? 2 : 0;
            $this->mailer->Debugoutput = function ($str, $level) {
                error_log("SMTP Debug: $str");
            };

            // Set from address
            $this->mailer->setFrom($this->fromEmail, $this->fromName);

            // Character set
            $this->mailer->CharSet = 'UTF-8';
        } catch (Exception $e) {
            error_log("EmailService initialization error: " . $e->getMessage());
        }
    }

    /**
     * Send verification email to new user
     * @param string $toEmail Recipient email
     * @param string $toName Recipient name
     * @param string $verificationToken Token for verification
     * @return bool Success status
     */
    public function sendVerificationEmail($toEmail, $toName, $verificationToken)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail, $toName);

            // Get app URL from environment or use default
            $appUrl = getenv('APP_URL') ?: 'http://localhost/Javier_StudentPeerSystem';
            $verificationLink = $appUrl . '/pages/emailVerification.php?token=' . urlencode($verificationToken);

            // Email subject
            $this->mailer->Subject = 'Verify Your Email - PeerMentor';

            // Email body (HTML)
            $this->mailer->isHTML(true);
            $this->mailer->Body = $this->getVerificationEmailTemplate($toName, $verificationLink);

            // Plain text alternative
            $this->mailer->AltBody = "Hello $toName,\n\n"
                . "Thank you for registering with PeerMentor!\n\n"
                . "Please verify your email address by clicking the link below:\n"
                . "$verificationLink\n\n"
                . "This link will expire in 24 hours.\n\n"
                . "If you didn't create this account, please ignore this email.\n\n"
                . "Best regards,\n"
                . "PeerMentor Team";

            $result = $this->mailer->send();

            if ($result) {
                error_log("Verification email sent to: $toEmail");
            }

            return $result;
        } catch (Exception $e) {
            error_log("Email sending error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Get HTML template for verification email
     * @param string $userName User's name
     * @param string $verificationLink Verification URL
     * @return string HTML email content
     */
    private function getVerificationEmailTemplate($userName, $verificationLink)
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: bold;">PeerMentor</h1>
                            <p style="color: #ffffff; margin: 10px 0 0 0; font-size: 16px;">Connect. Learn. Succeed.</p>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #333; margin: 0 0 20px 0; font-size: 24px;">Hello, {$userName}! 👋</h2>
                            <p style="color: #666; line-height: 1.6; margin: 0 0 20px 0; font-size: 16px;">
                                Thank you for registering with <strong>PeerMentor</strong>! We're excited to have you join our community of learners and tutors.
                            </p>
                            <p style="color: #666; line-height: 1.6; margin: 0 0 30px 0; font-size: 16px;">
                                To get started, please verify your email address by clicking the button below:
                            </p>
                            <!-- Button -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="{$verificationLink}" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; padding: 15px 40px; border-radius: 50px; font-size: 16px; font-weight: bold; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);">
                                            Verify Email Address
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="color: #999; line-height: 1.6; margin: 20px 0 0 0; font-size: 14px; text-align: center;">
                                Or copy and paste this link into your browser:<br>
                                <a href="{$verificationLink}" style="color: #667eea; word-break: break-all;">{$verificationLink}</a>
                            </p>
                            <div style="background-color: #fff3cd; border-left: 4px solid #f0ad4e; padding: 15px; margin: 30px 0;">
                                <p style="color: #856404; margin: 0; font-size: 14px;">
                                    <strong>⏰ Important:</strong> This verification link will expire in 24 hours.
                                </p>
                            </div>
                            <p style="color: #999; line-height: 1.6; margin: 20px 0 0 0; font-size: 14px;">
                                If you didn't create this account, please ignore this email or contact us if you have concerns.
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e9ecef;">
                            <p style="color: #6c757d; margin: 0 0 10px 0; font-size: 14px;">
                                Best regards,<br>
                                <strong>The PeerMentor Team</strong>
                            </p>
                            <p style="color: #adb5bd; margin: 10px 0 0 0; font-size: 12px;">
                                © 2024 PeerMentor. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Send test email
     * @param string $toEmail Test recipient email
     * @return bool Success status
     */
    public function sendTestEmail($toEmail)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail);
            $this->mailer->Subject = 'Test Email - PeerMentor';
            $this->mailer->isHTML(true);
            $this->mailer->Body = '<h1>Test Email</h1><p>If you receive this, your email service is configured correctly!</p>';
            $this->mailer->AltBody = 'Test Email - If you receive this, your email service is configured correctly!';

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Test email error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Send new session request notification to tutor
     * @param string $tutorEmail Tutor's email
     * @param string $tutorName Tutor's full name
     * @param string $studentName Student's full name
     * @param string $courseName Course name
     * @param string $sessionDetails Session details
     * @return bool Success status
     */
    public function sendNewSessionRequestEmail($tutorEmail, $tutorName, $studentName, $courseName, $sessionDetails)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($tutorEmail, $tutorName);

            $appUrl = getenv('APP_URL') ?: 'http://localhost/Javier_StudentPeerSystem';
            $requestsLink = $appUrl . '/pages/tutorRequests.php';

            $this->mailer->Subject = 'New Tutoring Request - PeerMentor';
            $this->mailer->isHTML(true);
            $this->mailer->Body = $this->getSessionRequestEmailTemplate($tutorName, $studentName, $courseName, $sessionDetails, $requestsLink);
            $this->mailer->AltBody = "Hello $tutorName,\n\nYou have a new tutoring request from $studentName for $courseName.\n\nSession Details: $sessionDetails\n\nView and manage your requests at: $requestsLink\n\nBest regards,\nPeerMentor Team";

            $result = $this->mailer->send();
            if ($result) {
                error_log("Session request email sent to: $tutorEmail");
            }
            return $result;
        } catch (Exception $e) {
            error_log("Session request email error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Send session confirmed notification to student
     * @param string $studentEmail Student's email
     * @param string $studentName Student's full name
     * @param string $tutorName Tutor's full name
     * @param string $courseName Course name
     * @return bool Success status
     */
    public function sendSessionConfirmedEmail($studentEmail, $studentName, $tutorName, $courseName)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($studentEmail, $studentName);

            $this->mailer->Subject = 'Tutoring Session Confirmed! - PeerMentor';
            $this->mailer->isHTML(true);
            $this->mailer->Body = $this->getSessionConfirmedEmailTemplate($studentName, $tutorName, $courseName);
            $this->mailer->AltBody = "Hello $studentName,\n\nGreat news! Your tutoring request for $courseName with $tutorName has been confirmed!\n\nYour tutor will reach out to you soon to finalize the session details.\n\nBest regards,\nPeerMentor Team";

            $result = $this->mailer->send();
            if ($result) {
                error_log("Session confirmed email sent to: $studentEmail");
            }
            return $result;
        } catch (Exception $e) {
            error_log("Session confirmed email error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Send session declined notification to student
     * @param string $studentEmail Student's email
     * @param string $studentName Student's full name
     * @param string $tutorName Tutor's full name
     * @param string $courseName Course name
     * @return bool Success status
     */
    public function sendSessionDeclinedEmail($studentEmail, $studentName, $tutorName, $courseName)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($studentEmail, $studentName);

            $appUrl = getenv('APP_URL') ?: 'http://localhost/Javier_StudentPeerSystem';
            $findTutorLink = $appUrl . '/pages/findTutor.php';

            $this->mailer->Subject = 'Tutoring Request Update - PeerMentor';
            $this->mailer->isHTML(true);
            $this->mailer->Body = $this->getSessionDeclinedEmailTemplate($studentName, $tutorName, $courseName, $findTutorLink);
            $this->mailer->AltBody = "Hello $studentName,\n\nUnfortunately, $tutorName is unable to accept your tutoring request for $courseName at this time.\n\nDon't worry! You can find other available tutors at: $findTutorLink\n\nBest regards,\nPeerMentor Team";

            $result = $this->mailer->send();
            if ($result) {
                error_log("Session declined email sent to: $studentEmail");
            }
            return $result;
        } catch (Exception $e) {
            error_log("Session declined email error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Send session completed notification to student
     * @param string $studentEmail Student's email
     * @param string $studentName Student's full name
     * @param string $tutorName Tutor's full name
     * @param string $courseName Course name
     * @return bool Success status
     */
    public function sendSessionCompletedEmail($studentEmail, $studentName, $tutorName, $courseName)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($studentEmail, $studentName);

            $this->mailer->Subject = 'Session Completed - PeerMentor';
            $this->mailer->isHTML(true);
            $this->mailer->Body = $this->getSessionCompletedEmailTemplate($studentName, $tutorName, $courseName);
            $this->mailer->AltBody = "Hello $studentName,\n\nYour tutoring session for $courseName with $tutorName has been completed!\n\nWe hope it was helpful. Feel free to request another session anytime.\n\nBest regards,\nPeerMentor Team";

            $result = $this->mailer->send();
            if ($result) {
                error_log("Session completed email sent to: $studentEmail");
            }
            return $result;
        } catch (Exception $e) {
            error_log("Session completed email error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Get HTML template for new session request email
     */
    private function getSessionRequestEmailTemplate($tutorName, $studentName, $courseName, $sessionDetails, $requestsLink)
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Tutoring Request</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: bold;">PeerMentor</h1>
                            <p style="color: #ffffff; margin: 10px 0 0 0; font-size: 16px;">New Tutoring Request</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #333; margin: 0 0 20px 0; font-size: 24px;">Hello, {$tutorName}! 👋</h2>
                            <p style="color: #666; line-height: 1.6; margin: 0 0 20px 0; font-size: 16px;">
                                You have a new tutoring request from <strong>{$studentName}</strong>!
                            </p>
                            <div style="background-color: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin: 20px 0; border-radius: 5px;">
                                <p style="margin: 0 0 10px 0; color: #333;"><strong>📚 Course:</strong> {$courseName}</p>
                                <p style="margin: 0; color: #666;"><strong>📝 Details:</strong> {$sessionDetails}</p>
                            </div>
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="{$requestsLink}" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; padding: 15px 40px; border-radius: 50px; font-size: 16px; font-weight: bold; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);">
                                            View Request
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="color: #999; line-height: 1.6; margin: 20px 0 0 0; font-size: 14px;">
                                Please respond to this request as soon as possible to help {$studentName} succeed!
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e9ecef;">
                            <p style="color: #6c757d; margin: 0 0 10px 0; font-size: 14px;">
                                Best regards,<br>
                                <strong>The PeerMentor Team</strong>
                            </p>
                            <p style="color: #adb5bd; margin: 10px 0 0 0; font-size: 12px;">
                                © 2024 PeerMentor. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Get HTML template for session confirmed email
     */
    private function getSessionConfirmedEmailTemplate($studentName, $tutorName, $courseName)
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Confirmed</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: bold;">✅ Confirmed!</h1>
                            <p style="color: #ffffff; margin: 10px 0 0 0; font-size: 16px;">Your tutoring session is ready</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #333; margin: 0 0 20px 0; font-size: 24px;">Great news, {$studentName}! 🎉</h2>
                            <p style="color: #666; line-height: 1.6; margin: 0 0 20px 0; font-size: 16px;">
                                Your tutoring request for <strong>{$courseName}</strong> has been confirmed by <strong>{$tutorName}</strong>!
                            </p>
                            <div style="background-color: #d4edda; border-left: 4px solid #28a745; padding: 20px; margin: 20px 0; border-radius: 5px;">
                                <p style="margin: 0; color: #155724; font-size: 14px;">
                                    <strong>📌 Next Steps:</strong> Your tutor will reach out to you soon to finalize the session details and schedule.
                                </p>
                            </div>
                            <p style="color: #666; line-height: 1.6; margin: 20px 0 0 0; font-size: 16px;">
                                Get ready to learn and succeed! If you have any questions, feel free to check your notifications.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e9ecef;">
                            <p style="color: #6c757d; margin: 0 0 10px 0; font-size: 14px;">
                                Best regards,<br>
                                <strong>The PeerMentor Team</strong>
                            </p>
                            <p style="color: #adb5bd; margin: 10px 0 0 0; font-size: 12px;">
                                © 2024 PeerMentor. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Get HTML template for session declined email
     */
    private function getSessionDeclinedEmailTemplate($studentName, $tutorName, $courseName, $findTutorLink)
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Update</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: bold;">Request Update</h1>
                            <p style="color: #ffffff; margin: 10px 0 0 0; font-size: 16px;">About your tutoring session</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #333; margin: 0 0 20px 0; font-size: 24px;">Hello, {$studentName}</h2>
                            <p style="color: #666; line-height: 1.6; margin: 0 0 20px 0; font-size: 16px;">
                                Unfortunately, <strong>{$tutorName}</strong> is unable to accept your tutoring request for <strong>{$courseName}</strong> at this time.
                            </p>
                            <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; margin: 20px 0; border-radius: 5px;">
                                <p style="margin: 0; color: #856404; font-size: 14px;">
                                    <strong>💡 Don't worry!</strong> There are many other talented tutors available who can help you succeed.
                                </p>
                            </div>
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="{$findTutorLink}" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; padding: 15px 40px; border-radius: 50px; font-size: 16px; font-weight: bold; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);">
                                            Find Another Tutor
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e9ecef;">
                            <p style="color: #6c757d; margin: 0 0 10px 0; font-size: 14px;">
                                Best regards,<br>
                                <strong>The PeerMentor Team</strong>
                            </p>
                            <p style="color: #adb5bd; margin: 10px 0 0 0; font-size: 12px;">
                                © 2024 PeerMentor. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Get HTML template for session completed email
     */
    private function getSessionCompletedEmailTemplate($studentName, $tutorName, $courseName)
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Completed</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: bold;">🎓 Session Complete!</h1>
                            <p style="color: #ffffff; margin: 10px 0 0 0; font-size: 16px;">Great job on your learning journey</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #333; margin: 0 0 20px 0; font-size: 24px;">Congratulations, {$studentName}! 🌟</h2>
                            <p style="color: #666; line-height: 1.6; margin: 0 0 20px 0; font-size: 16px;">
                                Your tutoring session for <strong>{$courseName}</strong> with <strong>{$tutorName}</strong> has been completed!
                            </p>
                            <div style="background-color: #d1ecf1; border-left: 4px solid #17a2b8; padding: 20px; margin: 20px 0; border-radius: 5px;">
                                <p style="margin: 0; color: #0c5460; font-size: 14px;">
                                    <strong>📈 Keep learning!</strong> Feel free to request another session anytime you need help.
                                </p>
                            </div>
                            <p style="color: #666; line-height: 1.6; margin: 20px 0 0 0; font-size: 16px;">
                                We hope this session was helpful for your academic success. Keep up the great work!
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e9ecef;">
                            <p style="color: #6c757d; margin: 0 0 10px 0; font-size: 14px;">
                                Best regards,<br>
                                <strong>The PeerMentor Team</strong>
                            </p>
                            <p style="color: #adb5bd; margin: 10px 0 0 0; font-size: 12px;">
                                © 2024 PeerMentor. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}

