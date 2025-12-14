# 🚀 Deployment Guide - Hostinger

This guide will help you deploy the PeerMentor Student Peer System to Hostinger hosting.

## 📋 Prerequisites

Before deploying, ensure you have:
- Hostinger account with PHP hosting plan
- MySQL database created in Hostinger control panel
- Database credentials (host, name, username, password)
- Gmail account with App Password for email notifications
- Your domain name (or Hostinger subdomain)

---

## 🗂️ Step 1: Prepare Your Files

### 1.1 Upload Project Files

1. **Connect via FTP/File Manager**
   - Log into Hostinger control panel
   - Navigate to File Manager or use FTP client (FileZilla)

2. **Upload to public_html**
   - Upload ALL project files to `public_html` directory
   - Ensure the structure looks like this:
     ```
     public_html/
     ├── index.php
     ├── .htaccess
     ├── production.env (you'll create this)
     ├── classes/
     ├── pages/
     ├── styles/
     └── vendor/
     ```

3. **Set Proper Permissions**
   - Directories: `755`
   - Files: `644`
   - `.env` file: `600` (most secure, if supported)

---

## 🗄️ Step 2: Database Setup

### 2.1 Create Database in Hostinger

1. Go to **Hostinger Control Panel → Databases → MySQL Databases**
2. Click **Create New Database**
3. Note down:
   - Database name (usually has a prefix like `u123456_dbname`)
   - Database username (usually matches the prefix)
   - Database password

### 2.2 Create Database User (if needed)

1. Create a new MySQL user if required
2. Grant **ALL PRIVILEGES** to this user for your database
3. Save the credentials securely

### 2.3 Import Database Schema

You'll need to create the necessary database tables. If you have an SQL dump:

1. Go to **phpMyAdmin** in Hostinger control panel
2. Select your database
3. Click **Import**
4. Upload your `.sql` file containing the database schema
5. Click **Go** to execute

---

## ⚙️ Step 3: Environment Configuration

### 3.1 Create .env File

1. **Copy production.env to .env**
   ```bash
   # In File Manager or via SSH
   cp production.env .env
   ```

2. **Edit .env file** with your actual credentials:

   ```env
   # Database Settings
   DB_HOST=localhost
   DB_NAME=u123456_php_peer          # Your actual database name
   DB_USER=u123456_php_peer          # Your actual database user
   DB_PASS=YourActualDatabasePassword # Your actual password

   # Application Settings
   APP_URL=https://yourdomain.com     # Your actual domain
   APP_NAME=PeerMentor Connect
   APP_ENV=production

   # Email (SMTP) Configuration
   SMTP_HOST=smtp.gmail.com
   SMTP_PORT=587
   SMTP_USERNAME=your-email@gmail.com
   SMTP_PASSWORD=your-app-password    # Gmail App Password
   SMTP_FROM_EMAIL=your-email@gmail.com
   SMTP_FROM_NAME=PeerMentor Team
   ```

### 3.2 Set File Permissions for .env

```bash
chmod 600 .env
```

This ensures only the server can read your credentials.

---

## 📧 Step 4: Gmail SMTP Setup

### 4.1 Enable 2-Step Verification

1. Go to [Google Account Security](https://myaccount.google.com/security)
2. Enable **2-Step Verification** if not already enabled

### 4.2 Generate App Password

1. Go to [App Passwords](https://myaccount.google.com/apppasswords)
2. Select **Mail** and **Other (Custom name)**
3. Name it "PeerMentor Hostinger"
4. Click **Generate**
5. Copy the 16-character password (remove spaces)
6. Use this as `SMTP_PASSWORD` in your `.env` file

---

## 🔒 Step 5: Security Verification

### 5.1 Test .env Protection

Try accessing these URLs in your browser:
- `https://yourdomain.com/.env` → Should show **403 Forbidden**
- `https://yourdomain.com/production.env` → Should show **403 Forbidden**

If you can see the file contents, **STOP** and fix your `.htaccess` rules immediately.

### 5.2 Verify HTTPS Force

Visit `http://yourdomain.com` (without the 's') and confirm it redirects to `https://`.

---

## ✅ Step 6: Test Your Deployment

### 6.1 Basic Access Test

1. Visit `https://yourdomain.com`
2. You should see the landing page
3. Try clicking **Get Started** or **Sign In**

### 6.2 Registration Test

1. Click **Get Started Free**
2. Fill out the registration form with a **real email address**
3. Submit the form
4. Check your email for the verification link
5. Click the verification link
6. Confirm you can log in

### 6.3 Email Verification Test

If emails aren't sending:

1. Check error logs in Hostinger: **Control Panel → Advanced → Error Logs**
2. Common issues:
   - Wrong Gmail App Password
   - 2-Step Verification not enabled
   - Firewall blocking port 587
   - `SMTP_FROM_EMAIL` doesn't match `SMTP_USERNAME`

---

## 🐛 Troubleshooting

### Database Connection Failed

**Symptoms**: "Database connection failed" error on homepage

**Solutions**:
1. Verify database credentials in `.env`
2. Ensure database user has ALL PRIVILEGES
3. Check if Hostinger prefix is included in DB_NAME and DB_USER
4. Try `DB_HOST=127.0.0.1` instead of `localhost`

### Emails Not Sending

**Symptoms**: Users not receiving verification emails

**Solutions**:
1. Verify Gmail App Password is correct (no spaces)
2. Ensure 2-Step Verification is enabled
3. Check `SMTP_FROM_EMAIL` matches `SMTP_USERNAME`
4. Review error logs for SMTP errors
5. Test if port 587 is accessible from Hostinger

### 404 Errors on Pages

**Symptoms**: Links to pages show 404 Not Found

**Solutions**:
1. Verify all files uploaded correctly
2. Check `.htaccess` is in the `public_html` root
3. Ensure mod_rewrite is enabled (usually is on Hostinger)
4. Clear browser cache and try again

### Session Issues

**Symptoms**: Users logged out frequently or can't stay logged in

**Solutions**:
1. Ensure `cookie_secure` is enabled (already configured in `pages/init.php`)
2. Verify you're using HTTPS throughout the site
3. Check if Hostinger has session.save_path configured correctly

---

## 📝 Post-Deployment Checklist

- [ ] Database created and credentials configured in `.env`
- [ ] All project files uploaded to `public_html`
- [ ] File permissions set correctly (755 for dirs, 644 for files)
- [ ] `.env` file created with production values
- [ ] `.env` and `production.env` return 403 when accessed via browser
- [ ] HTTPS force is working (HTTP redirects to HTTPS)
- [ ] Gmail App Password generated and configured
- [ ] Test registration completes successfully
- [ ] Verification emails are being received
- [ ] Users can log in after email verification
- [ ] All main pages are accessible (no 404s)
- [ ] Error logs reviewed (no critical errors)

---

## 🔐 Security Best Practices

1. **Never commit .env to Git** - It's already in `.gitignore`
2. **Use strong database passwords** - At least 16 characters, random
3. **Keep vendor/ updated** - Run `composer update` periodically
4. **Monitor error logs** - Check Hostinger error logs weekly
5. **Regular backups** - Use Hostinger's backup feature monthly
6. **Update APP_ENV** - Ensure it's set to `production` not `development`

---

## 📞 Support Resources

- **Hostinger Support**: https://www.hostinger.com/tutorials
- **Hostinger Help Center**: Available 24/7 via live chat
- **PHPMailer Docs**: https://github.com/PHPMailer/PHPMailer
- **Gmail App Passwords**: https://support.google.com/accounts/answer/185833

---

## 🎉 Success!

Once all checks pass, your PeerMentor Student Peer System is live and ready to help students connect and learn!

**Remember**: Keep your `.env` file secure and never share your credentials.
