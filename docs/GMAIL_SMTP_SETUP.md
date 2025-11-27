# Gmail SMTP Setup Guide for eJawatan MPHS

## Quick Setup Steps

### Step 1: Enable 2-Step Verification
1. Go to your Google Account: https://myaccount.google.com/
2. Click on **Security** in the left sidebar
3. Under "Signing in to Google", click **2-Step Verification**
4. Follow the prompts to enable 2-Step Verification
5. You'll need your phone to receive verification codes

### Step 2: Generate App Password
1. After enabling 2-Step Verification, go back to **Security**
2. Under "Signing in to Google", click **App passwords**
   - If you don't see this option, make sure 2-Step Verification is enabled
3. At the bottom, click **Select app** and choose **Mail**
4. Click **Select device** and choose **Other (Custom name)**
5. Enter a name like "eJawatan MPHS"
6. Click **Generate**
7. Google will display a 16-character password
8. **IMPORTANT**: Copy this password immediately (you won't see it again)

### Step 3: Update config.php
1. Open `config.php` in the root directory of your application
2. Update the email configuration section:

```php
// Email Configuration
$config['smtp_host'] = 'smtp.gmail.com';
$config['smtp_port'] = 587;
$config['smtp_secure'] = 'tls';  // Use TLS encryption
$config['smtp_auth'] = true;
$config['smtp_username'] = 'ejawatan@mphs.gov.my';  // Your Gmail address
$config['smtp_password'] = 'xxxx xxxx xxxx xxxx';   // 16-character App Password (remove spaces)
$config['admin_email'] = 'ejawatan@mphs.gov.my';
$config['noreply_email'] = 'ejawatan@mphs.gov.my';
```

**Important Notes**:
- Remove all spaces from the 16-character App Password
- Use your full Gmail address as the username
- Keep the password secure and never commit it to version control

### Step 4: Test Email Configuration
1. Login to the admin panel
2. Navigate to: `http://localhost:8000/admin/test-email-comprehensive.php`
3. Enter your email address
4. Select "Basic Email Test"
5. Click "Run Email Test"
6. Check your inbox (and spam folder) for the test email

## Gmail SMTP Settings Reference

| Setting | Value |
|---------|-------|
| SMTP Host | smtp.gmail.com |
| SMTP Port | 587 (TLS) or 465 (SSL) |
| Encryption | TLS (recommended) or SSL |
| Authentication | Required |
| Username | Your full Gmail address |
| Password | 16-character App Password |

## Recommended Configuration

```php
// Recommended Gmail SMTP Configuration
$config['smtp_host'] = 'smtp.gmail.com';
$config['smtp_port'] = 587;                    // Use TLS port
$config['smtp_secure'] = 'tls';                // TLS encryption
$config['smtp_auth'] = true;                   // Authentication required
$config['smtp_username'] = 'your@gmail.com';   // Your Gmail address
$config['smtp_password'] = 'xxxxxxxxxxxxxxxx'; // App Password (no spaces)
$config['admin_email'] = 'your@gmail.com';
$config['noreply_email'] = 'your@gmail.com';
```

## Troubleshooting

### Issue: "Authentication failed"
**Solution**: 
- Verify you're using the App Password, not your regular Gmail password
- Make sure there are no spaces in the App Password
- Confirm 2-Step Verification is enabled

### Issue: "Could not connect to SMTP host"
**Solution**:
- Check your internet connection
- Verify firewall allows outbound connections on port 587
- Try using port 465 with SSL instead:
  ```php
  $config['smtp_port'] = 465;
  $config['smtp_secure'] = 'ssl';
  ```

### Issue: "SMTP timeout"
**Solution**:
- Increase timeout in config:
  ```php
  $config['mail_timeout'] = 60; // 60 seconds
  ```
- Check if your hosting provider blocks SMTP connections
- Contact your hosting provider to allow SMTP on port 587

### Issue: Emails going to spam
**Solution**:
- This is normal for the first few emails
- Ask recipients to mark emails as "Not Spam"
- Consider using a custom domain email instead of Gmail
- Set up SPF and DKIM records for your domain

## Security Best Practices

1. **Never Share App Passwords**
   - Treat App Passwords like regular passwords
   - Each application should have its own App Password
   - Revoke App Passwords you're no longer using

2. **Protect config.php**
   - Never commit `config.php` to version control
   - Ensure `config.php` is in `.gitignore`
   - Set proper file permissions (644 or 600)

3. **Regular Security Checks**
   - Review active App Passwords monthly
   - Revoke unused App Passwords
   - Monitor Google Account activity

4. **Backup Configuration**
   - Keep a secure backup of your configuration
   - Document which App Password is used for which application
   - Store backups in a secure location

## Alternative: Using Google Workspace

If you're using Google Workspace (formerly G Suite), you have additional options:

### Option 1: SMTP Relay Service
```php
$config['smtp_host'] = 'smtp-relay.gmail.com';
$config['smtp_port'] = 587;
$config['smtp_secure'] = 'tls';
// May not require authentication depending on your setup
```

### Option 2: Gmail API
- More complex but more reliable
- Requires OAuth 2.0 setup
- Better for high-volume email sending

## Testing Checklist

Before going to production, test the following:

- [ ] Basic test email sends successfully
- [ ] Application notification email sends successfully
- [ ] Status update email sends successfully
- [ ] Emails arrive in inbox (not spam)
- [ ] Email formatting looks correct
- [ ] Links in emails work correctly
- [ ] Unsubscribe link works (if applicable)
- [ ] Test with multiple email providers (Gmail, Outlook, Yahoo)
- [ ] Check email on mobile devices
- [ ] Verify sender name displays correctly

## Gmail Sending Limits

Be aware of Gmail's sending limits:

| Account Type | Daily Limit |
|--------------|-------------|
| Free Gmail | 500 emails/day |
| Google Workspace | 2,000 emails/day |

**Recommendations**:
- Monitor your daily email volume
- Implement rate limiting if needed
- Consider using a dedicated email service for high volume
- Use batch processing for bulk emails

## Support Resources

- **Google Account Help**: https://support.google.com/accounts
- **Gmail SMTP Documentation**: https://support.google.com/mail/answer/7126229
- **App Passwords Help**: https://support.google.com/accounts/answer/185833
- **2-Step Verification**: https://support.google.com/accounts/answer/185839

## Quick Reference Commands

### Test SMTP Connection (Windows PowerShell)
```powershell
Test-NetConnection -ComputerName smtp.gmail.com -Port 587
```

### Check if Port is Open (Windows Command Prompt)
```cmd
telnet smtp.gmail.com 587
```

### View Email Logs
```powershell
# View last 50 lines of error log
Get-Content admin\logs\error.log -Tail 50
```

---

**Last Updated**: 2025-11-26  
**For Support**: Contact system administrator  
**Email**: admin@mphs.gov.my
