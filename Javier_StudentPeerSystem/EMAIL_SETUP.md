# Email Verification Setup Guide

## Gmail SMTP Configuration

To send verification emails, you need to configure Gmail SMTP settings. Follow these steps:

### Step 1: Create Gmail App Password

1. Go to your Google Account: https://myaccount.google.com/
2. Select **Security** from the left menu
3. Enable **2-Step Verification** (if not already enabled)
4. Under "2-Step Verification", scroll down to **App passwords**
5. Click **App passwords**
6. Select app: **Mail**
7. Select device: **Other (Custom name)** → Enter "PeerMentor"
8. Click **Generate**
9. **Copy the 16-character password** (you'll need this)

### Step 2: Configure Environment Variables

Create a file: `c:\xampp\htdocs\Javier_StudentPeerSystem\.env`

```env
# Email Configuration for Gmail SMTP
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-gmail@gmail.com
SMTP_PASSWORD=your-16-char-app-password
SMTP_FROM_EMAIL=your-gmail@gmail.com
SMTP_FROM_NAME=PeerMentor Team

# Application URL (update for production)
APP_URL=http://localhost/Javier_StudentPeerSystem

# Database Configuration
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=tutor
```

**⚠️ IMPORTANT**: 
- Use your Gmail app password (NOT your regular Gmail password)
- Never commit the `.env` file to version control
- Add `.env` to your `.gitignore` file

### Step 3: Load Environment Variables in PHP

The `EmailService` class already supports environment variables. To load them from the `.env` file,  install a package or manually set them:

**Option A: Manual (Quick Test)**
Add this to the top of `pages/register.php` temporarily:
```php
putenv("SMTP_USERNAME=your-gmail@gmail.com");
putenv("SMTP_PASSWORD=your-16-char-app-password");
putenv("APP_URL=http://localhost/Javier_StudentPeerSystem");
```

**Option B: Using vlucas/phpdotenv (Recommended)**
```bash
cd c:\xampp\htdocs\Javier_StudentPeerSystem
composer require vlucas/phpdotenv
```

Then add to the top of your PHP files:
```php
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
```

### Step 4: Test Email Sending

Create `test_email.php` in the pages folder:

```php
<?php
require_once '../classes/emailService.php';

// Set environment variables
putenv("SMTP_USERNAME=your-gmail@gmail.com");
putenv("SMTP_PASSWORD=your-app-password");
putenv("APP_URL=http://localhost/Javier_StudentPeerSystem");

$emailService = new EmailService();
$result = $emailService->sendTestEmail('your-test-email@example.com');

echo $result ? '✅ Email sent successfully!' : '❌ Email failed to send - check error logs';
```

Navigate to: `http://localhost/Javier_StudentPeerSystem/pages/test_email.php`

## Troubleshooting

### Email not sending?
1. **Check error logs**: `c:\xampp\php\logs\php_error_log`
2. **Verify Gmail settings**: Make sure 2FA is enabled and app password is correct
3. **Check firewall**: Allow port 587 for SMTP
4. **Test SMTP connection**: Use telnet to verify connection to smtp.gmail.com:587

### Common Errors

**"SMTP connect() failed"**
- Check internet connection
- Verify Gmail credentials
- Check if port 587 is blocked by firewall

**"Invalid credentials"**
- Use app password, not regular password
- Make sure 2-Step Verification is enabled
- Generate a new app password

**"Could not instantiate mail function"**
- PHPMailer may not be installed
- Run: `composer install` in project directory

## Next Steps

Once email sending works:
1. ✅ Register a new test account
2. ✅ Check email inbox for verification link
3. ✅ Click verification link
4. ✅ Verify welcome notification appears
5. ✅ Try logging in with verified account

## Security Reminders

- Never hardcode credentials in PHP files
- Use environment variables for all sensitive data
- Add `.env` to `.gitignore`
- Use different Gmail account for production
- Consider using SendGrid or AWS SES for production
