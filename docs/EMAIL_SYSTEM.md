# Email System Documentation - eJawatan MPHS

## Overview
This document provides comprehensive information about the email functionality in the eJawatan MPHS application.

## Email Configuration

### Configuration File Location
All email settings are stored in `config.php` (root directory).

### SMTP Settings
The application supports the following SMTP configuration keys:

```php
// Primary keys (used by MailSender)
$config['smtp_host'] = 'smtp.gmail.com';
$config['smtp_port'] = 587;
$config['smtp_secure'] = 'tls';  // 'tls' or 'ssl' or 'none'
$config['smtp_auth'] = true;
$config['smtp_username'] = 'your-email@example.com';
$config['smtp_password'] = 'your-password';
$config['admin_email'] = 'admin@example.com';
$config['noreply_email'] = 'noreply@example.com';

// These are automatically mapped from smtp_* keys
$config['mail_host'] = $config['smtp_host'];
$config['mail_port'] = $config['smtp_port'];
$config['mail_username'] = $config['smtp_username'];
$config['mail_password'] = $config['smtp_password'];
$config['mail_encryption'] = $config['smtp_secure'];
```

### Gmail SMTP Configuration
If using Gmail SMTP, you need to:

1. **Enable 2-Step Verification** on your Google Account
2. **Generate an App Password**:
   - Go to Google Account Settings → Security
   - Under "Signing in to Google", select "App passwords"
   - Generate a new app password for "Mail"
   - Use this 16-character password in `smtp_password`

3. **Update config.php**:
```php
$config['smtp_host'] = 'smtp.gmail.com';
$config['smtp_port'] = 587;
$config['smtp_secure'] = 'tls';
$config['smtp_username'] = 'your-email@gmail.com';
$config['smtp_password'] = 'your-16-char-app-password';
```

## Email Components

### 1. MailSender Class
**Location**: `includes/MailSender.php`

**Purpose**: Core email sending class that handles SMTP communication using PHPMailer.

**Key Features**:
- Automatic fallback from SMTP to PHP mail() if SMTP fails
- Support for both `smtp_*` and `mail_*` config keys
- Comprehensive error logging
- HTML email support
- Test email functionality

**Methods**:
- `send($to, $subject, $message, $headers = [])` - Send an email
- `sendTest($to, $subject = null, $message = null)` - Send a test email
- `sendEmail($to, $subject, $html_body, $text_body = null, $headers = [])` - Alias for send()

### 2. NotificationService Class
**Location**: `includes/NotificationService.php`

**Purpose**: Handles application submission notifications (email + SMS).

**Key Features**:
- Sends confirmation email when applicant submits application
- Includes application reference, job details, and status tracking link
- Professional HTML email template
- Error handling with detailed logging

**Methods**:
- `sendApplicationSubmissionNotification($application_id)` - Send notification for new application

**Email Template Includes**:
- Applicant name and application reference
- Job title and grade code
- Application date
- Link to check application status
- Contact information

### 3. StatusEmailService Class
**Location**: `includes/StatusEmailService.php`

**Purpose**: Sends emails when application status is updated.

**Key Features**:
- Customizable email templates per status
- Template variable replacement (e.g., {APPLICANT_NAME}, {STATUS_NAME})
- Supports custom email subject and body from database

**Methods**:
- `send(array $application, array $status, string $notes)` - Send status update email

**Template Variables**:
- `{APPLICANT_NAME}` - Applicant's full name
- `{APPLICATION_REFERENCE}` - Application reference number
- `{STATUS_NAME}` - Status name
- `{STATUS_CODE}` - Status code
- `{JOB_TITLE}` - Job title
- `{KOD_GRED}` - Grade code
- `{NOTES}` - Additional notes
- `{BASE_URL}` - Application base URL

## Email Types

### 1. Application Submission Confirmation
**Triggered**: When applicant submits a job application
**Sent by**: `NotificationService::sendApplicationSubmissionNotification()`
**Template**: HTML email with MPHS branding
**Contains**:
- Confirmation message
- Application reference number
- Job details
- Status tracking link
- Important information and contact details

### 2. Status Update Notification
**Triggered**: When admin updates application status (if status has email template configured)
**Sent by**: `StatusEmailService::send()`
**Template**: Customizable per status type
**Contains**:
- Status change notification
- Application details
- Custom notes from admin
- Next steps (if applicable)

### 3. Password Reset Email
**Triggered**: When admin requests password reset
**Sent by**: `admin/forgot-password.php`
**Contains**:
- Password reset link with token
- Token expiration time
- Security instructions

### 4. Test Emails
**Triggered**: Manually from admin panel
**Sent by**: `MailSender::sendTest()`
**Contains**:
- Test confirmation message
- SMTP configuration details
- Timestamp

## Testing Email Functionality

### Comprehensive Email Testing Tool
**Location**: `admin/test-email-comprehensive.php`

**Features**:
1. **Configuration Display**
   - Shows current SMTP settings
   - PHPMailer status check
   - Password status (hidden for security)

2. **Test Types**:
   - **Basic Email Test**: Simple SMTP connectivity test
   - **Application Notification Test**: Tests the full application submission email
   - **Status Update Email Test**: Tests status change notification
   - **Run All Tests**: Executes all tests sequentially

3. **Test Results**:
   - Color-coded success/failure indicators
   - Detailed error messages
   - Recommendations for troubleshooting

### How to Use the Testing Tool

1. **Access the Tool**:
   - Login to admin panel
   - Navigate to: `http://your-domain/admin/test-email-comprehensive.php`

2. **Run Tests**:
   - Enter your email address
   - Select test type
   - Click "Run Email Test"
   - Check your inbox (and spam folder)

3. **Verify Results**:
   - Green = Success
   - Red = Failure (check error message)
   - Review logs at `admin/logs/error.log` for details

## Troubleshooting

### Common Issues

#### 1. Emails Not Sending
**Symptoms**: Test emails fail, no emails received

**Solutions**:
- Verify SMTP credentials in `config.php`
- Check if SMTP port is correct (587 for TLS, 465 for SSL)
- Ensure firewall allows outbound SMTP connections
- For Gmail: Use App Password, not regular password
- Check `admin/logs/error.log` for detailed errors

#### 2. Emails Going to Spam
**Symptoms**: Emails received but in spam folder

**Solutions**:
- Configure SPF records for your domain
- Set up DKIM authentication
- Use a verified "From" email address
- Avoid spam trigger words in subject/body

#### 3. PHPMailer Not Found
**Symptoms**: Error "PHPMailer not available"

**Solutions**:
- Run `composer install` in project root
- Verify `vendor/autoload.php` exists
- Check PHPMailer is in `composer.json` dependencies

#### 4. SMTP Timeout
**Symptoms**: Email sending times out

**Solutions**:
- Increase timeout in config: `$config['mail_timeout'] = 60;`
- Check network connectivity to SMTP server
- Try different SMTP port (587 vs 465)
- Verify SMTP server is not blocking your IP

### Debugging Steps

1. **Enable Debug Mode**:
```php
// In config.php
$config['app_env'] = 'development';
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

2. **Check Logs**:
```bash
# Frontend logs
tail -f admin/logs/error.log

# Admin logs
tail -f admin/logs/admin_error.log
```

3. **Test SMTP Connection**:
```bash
# Using telnet (Windows)
telnet smtp.gmail.com 587

# Using PowerShell
Test-NetConnection -ComputerName smtp.gmail.com -Port 587
```

4. **Verify PHPMailer**:
```php
// In test script
var_dump(class_exists('\\PHPMailer\\PHPMailer\\PHPMailer'));
var_dump(class_exists('PHPMailer'));
```

## Email Flow Diagrams

### Application Submission Flow
```
User Submits Application
    ↓
Application Saved to Database
    ↓
NotificationService::sendApplicationSubmissionNotification()
    ↓
MailSender::send() with application template
    ↓
PHPMailer sends via SMTP
    ↓
Email delivered to applicant
    ↓
Notification logged in database
```

### Status Update Flow
```
Admin Updates Application Status
    ↓
Check if status has email template
    ↓
StatusEmailService::send()
    ↓
Replace template variables
    ↓
MailSender::send() with rendered template
    ↓
PHPMailer sends via SMTP
    ↓
Email delivered to applicant
```

## Security Considerations

1. **Password Storage**:
   - SMTP password stored in `config.php` (gitignored)
   - Never commit `config.php` to version control
   - Use environment variables in production

2. **Email Validation**:
   - All email addresses validated before sending
   - HTML content properly escaped
   - Template variables sanitized

3. **Rate Limiting**:
   - Consider implementing rate limiting for email sending
   - Prevent abuse of notification system

4. **Logging**:
   - All email attempts logged
   - Failed emails logged with error details
   - Sensitive data (passwords) never logged

## Best Practices

1. **Configuration**:
   - Use App Passwords for Gmail
   - Keep SMTP credentials secure
   - Use TLS encryption (port 587)

2. **Testing**:
   - Always test email changes in development first
   - Use the comprehensive testing tool before production
   - Test with multiple email providers (Gmail, Outlook, etc.)

3. **Monitoring**:
   - Regularly check email logs
   - Monitor delivery rates
   - Set up alerts for email failures

4. **Templates**:
   - Keep email templates professional
   - Include unsubscribe option (if applicable)
   - Test on multiple email clients
   - Ensure mobile responsiveness

## Email Template Customization

### Modifying Application Notification Template
Edit: `includes/NotificationService.php` → `generateEmailContent()` method

### Modifying Status Email Templates
1. Login to admin panel
2. Go to Settings → Application Statuses
3. Edit status and customize email template
4. Use template variables for dynamic content

### Adding New Email Types
1. Create new method in appropriate service class
2. Follow existing email template structure
3. Use `MailSender::send()` for delivery
4. Add error handling and logging
5. Test thoroughly with testing tool

## Support and Maintenance

### Regular Maintenance Tasks
- [ ] Weekly: Check email logs for errors
- [ ] Monthly: Test email functionality
- [ ] Quarterly: Review and update email templates
- [ ] Yearly: Rotate SMTP credentials

### Getting Help
- Check `admin/logs/error.log` for detailed errors
- Use the comprehensive testing tool for diagnostics
- Review this documentation for common issues
- Contact system administrator if issues persist

## Changelog

### Version 2.0.0 (2025-11-26)
- Added comprehensive email testing tool
- Enhanced MailSender with dual config key support (smtp_* and mail_*)
- Improved error logging and debugging
- Added detailed documentation
- Fixed Gmail SMTP compatibility issues

---

**Last Updated**: 2025-11-26  
**Maintained By**: System Administrator  
**Contact**: admin@mphs.gov.my
