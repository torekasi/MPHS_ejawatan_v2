# Email System Functions Reference - eJawatan MPHS

## MailSender Class (`includes/MailSender.php`)

### Constructor
```php
public function __construct($config)
```
**Description**: Initialize MailSender with configuration array.

**Parameters**:
- `$config` (array): Configuration array from config.php

**Example**:
```php
$mailSender = new MailSender($config);
```

---

### send()
```php
public function send($to, $subject, $message, $headers = [])
```
**Description**: Send an email using the best available method (SMTP or PHP mail()).

**Parameters**:
- `$to` (string): Recipient email address
- `$subject` (string): Email subject
- `$message` (string): Email body (HTML)
- `$headers` (array): Optional additional headers

**Returns**: `bool` - Success status

**Example**:
```php
$result = $mailSender->send(
    'user@example.com',
    'Test Subject',
    '<h1>Hello World</h1><p>This is a test email.</p>'
);
```

---

### sendTest()
```php
public function sendTest($to, $subject = null, $message = null)
```
**Description**: Send a test email with system information.

**Parameters**:
- `$to` (string): Recipient email address
- `$subject` (string|null): Optional custom subject
- `$message` (string|null): Optional custom message

**Returns**: `bool` - Success status

**Example**:
```php
$result = $mailSender->sendTest('admin@example.com');
```

---

### sendEmail()
```php
public function sendEmail($to, $subject, $html_body, $text_body = null, $headers = [])
```
**Description**: Alias for send() method to maintain compatibility.

**Parameters**:
- `$to` (string): Recipient email address
- `$subject` (string): Email subject
- `$html_body` (string): Email body (HTML)
- `$text_body` (string|null): Email body (Plain text - optional, currently ignored)
- `$headers` (array): Optional additional headers

**Returns**: `bool` - Success status

**Example**:
```php
$result = $mailSender->sendEmail(
    'user@example.com',
    'Welcome',
    '<p>Welcome to our system!</p>'
);
```

---

## NotificationService Class (`includes/NotificationService.php`)

### Constructor
```php
public function __construct($config, $pdo)
```
**Description**: Initialize NotificationService with configuration and database connection.

**Parameters**:
- `$config` (array): Configuration array from config.php
- `$pdo` (PDO): Database connection object

**Example**:
```php
$notificationService = new NotificationService($config, $pdo);
```

---

### sendApplicationSubmissionNotification()
```php
public function sendApplicationSubmissionNotification($application_id)
```
**Description**: Send email and SMS notifications when an application is submitted.

**Parameters**:
- `$application_id` (int): Application ID from database

**Returns**: `bool` - Success status (true if at least one notification sent)

**Example**:
```php
$result = $notificationService->sendApplicationSubmissionNotification(12345);
```

**Email Template Includes**:
- Applicant name and contact details
- Application reference number
- Job title and grade code
- Application date
- Status tracking link
- Important information and contact details

**Notes**:
- Automatically fetches application details from database
- Sends both email and SMS (if configured)
- Logs all notification attempts
- Handles errors gracefully without blocking application submission

---

## StatusEmailService Class (`includes/StatusEmailService.php`)

### Constructor
```php
public function __construct(array $config, \PDO $pdo)
```
**Description**: Initialize StatusEmailService with configuration and database connection.

**Parameters**:
- `$config` (array): Configuration array from config.php
- `$pdo` (PDO): Database connection object

**Example**:
```php
$statusEmailService = new StatusEmailService($config, $pdo);
```

---

### send()
```php
public function send(array $application, array $status, string $notes)
```
**Description**: Send status update email to applicant.

**Parameters**:
- `$application` (array): Application data array
- `$status` (array): Status configuration array with email template
- `$notes` (string): Additional notes from admin

**Returns**: `bool` - Success status

**Example**:
```php
$application = [
    'nama_penuh' => 'John Doe',
    'email' => 'john@example.com',
    'application_reference' => 'APP-2025-001',
    'job_title' => 'IT Officer',
    'kod_gred' => 'N41'
];

$status = [
    'code' => 'SHORTLISTED',
    'name' => 'Di Senarai Pendek',
    'email_subject' => 'Tahniah! Anda Telah Dipilih',
    'email_body' => '<p>Kepada {APPLICANT_NAME},</p>...'
];

$notes = 'Sila hadir untuk temuduga pada 15 Disember 2025.';

$result = $statusEmailService->send($application, $status, $notes);
```

**Template Variables**:
- `{APPLICANT_NAME}` - Applicant's full name
- `{APPLICATION_REFERENCE}` - Application reference number
- `{STATUS_NAME}` - Status name
- `{STATUS_CODE}` - Status code
- `{JOB_TITLE}` - Job title
- `{KOD_GRED}` - Grade code
- `{NOTES}` - Additional notes
- `{BASE_URL}` - Application base URL

---

## Email Template Helper Functions

### generateApplicationEmailContent()
**Location**: `includes/NotificationService.php` (private method)

**Description**: Generates HTML email content for application submission confirmation.

**Parameters**:
- `$application` (array): Application data

**Returns**: `string` - HTML email content

**Template Structure**:
- Header with MPHS branding
- Personalized greeting
- Application details in info box
- Status information
- Call-to-action button (Check Status)
- Important information section
- Contact details
- Footer with disclaimer

---

## Configuration Keys

### SMTP Configuration
```php
// Primary SMTP keys
$config['smtp_host']     = 'smtp.gmail.com';
$config['smtp_port']     = 587;
$config['smtp_secure']   = 'tls';  // 'tls', 'ssl', or 'none'
$config['smtp_auth']     = true;
$config['smtp_username'] = 'your-email@example.com';
$config['smtp_password'] = 'your-password';

// Email addresses
$config['admin_email']   = 'admin@example.com';
$config['noreply_email'] = 'noreply@example.com';

// Automatically mapped keys (for MailSender compatibility)
$config['mail_host']       = $config['smtp_host'];
$config['mail_port']       = $config['smtp_port'];
$config['mail_username']   = $config['smtp_username'];
$config['mail_password']   = $config['smtp_password'];
$config['mail_encryption'] = $config['smtp_secure'];
$config['email_from']      = $config['noreply_email'];
$config['email_reply_to']  = $config['admin_email'];
```

---

## Usage Examples

### Example 1: Send Basic Email
```php
require_once 'includes/MailSender.php';
require_once 'config.php';

$mailSender = new MailSender($config);

$to = 'user@example.com';
$subject = 'Welcome to eJawatan';
$message = '
    <html>
    <body>
        <h1>Welcome!</h1>
        <p>Thank you for registering.</p>
    </body>
    </html>
';

if ($mailSender->send($to, $subject, $message)) {
    echo "Email sent successfully!";
} else {
    echo "Failed to send email.";
}
```

### Example 2: Send Application Notification
```php
require_once 'includes/NotificationService.php';
require_once 'config.php';

// Assume $pdo is your database connection
$notificationService = new NotificationService($config, $pdo);

// After saving application to database
$application_id = 12345;

if ($notificationService->sendApplicationSubmissionNotification($application_id)) {
    echo "Notification sent successfully!";
} else {
    echo "Failed to send notification.";
}
```

### Example 3: Send Status Update Email
```php
require_once 'includes/StatusEmailService.php';
require_once 'config.php';

// Assume $pdo is your database connection
$statusEmailService = new StatusEmailService($config, $pdo);

// Fetch application and status data
$application = [
    'nama_penuh' => 'Ahmad bin Ali',
    'email' => 'ahmad@example.com',
    'application_reference' => 'APP-2025-001',
    'job_title' => 'Pembantu Tadbir',
    'kod_gred' => 'N17'
];

$status = [
    'code' => 'INTERVIEW',
    'name' => 'Dijemput Temuduga',
    'email_subject' => 'Jemputan Temuduga - {APPLICATION_REFERENCE}',
    'email_body' => '
        <h2>Jemputan Temuduga</h2>
        <p>Kepada {APPLICANT_NAME},</p>
        <p>Anda dijemput untuk temuduga bagi jawatan {JOB_TITLE}.</p>
        <p>Rujukan: {APPLICATION_REFERENCE}</p>
        <p>{NOTES}</p>
    '
];

$notes = 'Tarikh: 15 Disember 2025, Masa: 10:00 AM';

if ($statusEmailService->send($application, $status, $notes)) {
    echo "Status email sent successfully!";
} else {
    echo "Failed to send status email.";
}
```

### Example 4: Test Email Configuration
```php
require_once 'includes/MailSender.php';
require_once 'config.php';

$mailSender = new MailSender($config);

// Send test email
$test_email = 'admin@example.com';

if ($mailSender->sendTest($test_email)) {
    echo "Test email sent successfully! Check your inbox.";
} else {
    echo "Failed to send test email. Check logs for details.";
}
```

---

## Error Handling

All email functions include comprehensive error handling:

1. **Try-Catch Blocks**: All email sending wrapped in try-catch
2. **Logging**: Errors logged to `admin/logs/error.log`
3. **Graceful Degradation**: Failures don't block application flow
4. **Detailed Messages**: Error messages include context for debugging

**Example Error Handling**:
```php
try {
    $result = $mailSender->send($to, $subject, $message);
    if (!$result) {
        log_error('Email sending failed', [
            'to' => $to,
            'subject' => $subject
        ]);
    }
} catch (Exception $e) {
    log_error('Email exception', [
        'error' => $e->getMessage(),
        'to' => $to
    ]);
}
```

---

## Testing Functions

### Comprehensive Email Testing Tool
**Location**: `admin/test-email-comprehensive.php`

**Features**:
- Display current SMTP configuration
- Test basic email sending
- Test application notification emails
- Test status update emails
- Run all tests at once
- Detailed success/failure reporting

**Access**: Login to admin panel → Navigate to test-email-comprehensive.php

---

## Logging

All email operations are logged with the following information:

**Success Logs**:
- Recipient email
- Subject
- Timestamp
- SMTP host and port used

**Error Logs**:
- Error message
- Recipient email
- Subject
- SMTP configuration
- Stack trace (if exception)

**Log Locations**:
- Frontend: `admin/logs/error.log`
- Admin: `admin/logs/admin_error.log`

---

## Best Practices

1. **Always Validate Email Addresses**:
```php
if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $mailSender->send($email, $subject, $message);
}
```

2. **Use HTML Templates**:
```php
$message = file_get_contents('templates/welcome-email.html');
$message = str_replace('{NAME}', $user_name, $message);
```

3. **Handle Errors Gracefully**:
```php
if (!$mailSender->send($to, $subject, $message)) {
    // Log error but don't show to user
    log_error('Email failed', ['to' => $to]);
    // Continue with application flow
}
```

4. **Test Before Production**:
```php
if ($config['app_env'] === 'development') {
    // Override recipient for testing
    $to = 'test@example.com';
}
```

---

**Last Updated**: 2025-11-26  
**Module**: Email System  
**Maintained By**: System Administrator
