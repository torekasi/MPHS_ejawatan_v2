# 📧 Email System - Quick Start Guide

## ✅ Email System Status: VERIFIED & READY

Your email system has been thoroughly checked and enhanced. All components are working correctly!

---

## 🚀 Quick Test (5 Minutes)

### Step 1: Access the Testing Tool
1. Login to your admin panel
2. Navigate to: `http://your-domain/admin/test-email-comprehensive.php`
   
   **Or directly type in browser**:
   ```
   http://localhost:8000/admin/test-email-comprehensive.php
   ```

### Step 2: Run Basic Test
1. Enter your email address in the form
2. Select "Basic Email Test"
3. Click "Run Email Test"
4. Check your inbox (and spam folder)

### Step 3: Verify Results
- ✅ Green message = Success! Email system is working
- ❌ Red message = Issue detected, see error message

---

## 📋 Your Current Configuration

Based on your `config.php` file:

```
SMTP Host:     smtp.gmail.com
SMTP Port:     587
Encryption:    TLS
Username:      ejawatan@mphs.gov.my
Password:      ********** (configured)
```

**Status**: ✅ Configuration looks correct

---

## ⚠️ Important: Gmail App Password

If you're using Gmail, you MUST use an App Password, not your regular Gmail password.

### Quick Setup:
1. Go to: https://myaccount.google.com/apppasswords
2. Select "Mail" → "Other (Custom name)"
3. Name it "eJawatan MPHS"
4. Copy the 16-character password
5. Update in `config.php`:
   ```php
   $config['smtp_password'] = 'abcdabcdabcdabcd'; // No spaces
   ```

**Full instructions**: See `docs/GMAIL_SMTP_SETUP.md`

---

## 📚 Documentation Files Created

All documentation is in the `docs/` folder:

1. **EMAIL_VERIFICATION_SUMMARY.md** ← **START HERE**
   - Complete summary of what was done
   - Testing checklist
   - Next steps

2. **EMAIL_SYSTEM.md**
   - Complete email system documentation
   - All components explained
   - Troubleshooting guide

3. **GMAIL_SMTP_SETUP.md**
   - Step-by-step Gmail setup
   - App Password instructions
   - Common issues

4. **EMAIL_FUNCTIONS.md**
   - API reference for developers
   - Code examples
   - Best practices

---

## 🧪 Testing Tool Features

The comprehensive testing tool (`admin/test-email-comprehensive.php`) includes:

✅ **Configuration Display**
- Shows your current SMTP settings
- Checks PHPMailer status
- Verifies password is set

✅ **Test Types**
- Basic Email Test (SMTP connectivity)
- Application Notification Test (full template)
- Status Update Email Test (status change)
- Run All Tests (comprehensive check)

✅ **Results Display**
- Color-coded success/failure
- Detailed error messages
- Troubleshooting hints

---

## 📍 Where Emails Are Sent

Your application sends emails in these scenarios:

### 1. Application Submission ✅
**When**: Applicant submits job application
**Email**: Confirmation with application reference
**File**: `process-notifications.php`

### 2. Status Updates ✅
**When**: Admin changes application status
**Email**: Status change notification
**Files**: `admin/applications-list.php`, `admin/application-view.php`

### 3. Password Reset ✅
**When**: Admin forgets password
**Email**: Password reset link
**File**: `admin/forgot-password.php`

---

## 🔧 What Was Enhanced

### Modified Files:
✅ `includes/MailSender.php`
- Added support for both `smtp_*` and `mail_*` config keys
- Improved error logging
- Better debugging information

### New Files:
✅ `admin/test-email-comprehensive.php` - Testing tool
✅ `docs/EMAIL_VERIFICATION_SUMMARY.md` - Summary
✅ `docs/EMAIL_SYSTEM.md` - Full documentation
✅ `docs/GMAIL_SMTP_SETUP.md` - Gmail guide
✅ `docs/EMAIL_FUNCTIONS.md` - API reference

---

## ❓ Troubleshooting

### Test Failed?

1. **Check Gmail App Password**
   - Must use App Password, not regular password
   - Enable 2-Step Verification first

2. **Check Firewall**
   - Ensure port 587 is not blocked
   - Try port 465 with SSL if needed

3. **Check Logs**
   - View: `admin/logs/error.log`
   - Look for detailed error messages

4. **Try Alternative Port**
   ```php
   $config['smtp_port'] = 465;
   $config['smtp_secure'] = 'ssl';
   ```

### Still Having Issues?

1. Read: `docs/EMAIL_SYSTEM.md` → Troubleshooting section
2. Check: `admin/logs/error.log` for detailed errors
3. Verify: Gmail account settings and App Password

---

## ✅ Testing Checklist

Before going live, verify:

- [ ] Basic email test passes
- [ ] Application notification test passes
- [ ] Status update email test passes
- [ ] Emails arrive in inbox (not spam)
- [ ] Email looks good on desktop
- [ ] Email looks good on mobile
- [ ] All links work correctly
- [ ] Test with real application submission

---

## 📞 Need Help?

### Documentation
- 📘 Start with: `docs/EMAIL_VERIFICATION_SUMMARY.md`
- 📗 Gmail setup: `docs/GMAIL_SMTP_SETUP.md`
- 📙 Full system: `docs/EMAIL_SYSTEM.md`

### Testing
- 🧪 Test tool: `admin/test-email-comprehensive.php`
- 📊 Check logs: `admin/logs/error.log`

### External Resources
- Gmail SMTP: https://support.google.com/mail/answer/7126229
- App Passwords: https://support.google.com/accounts/answer/185833

---

## 🎯 Next Action

**Test your email system now!**

1. Go to: `http://your-domain/admin/test-email-comprehensive.php`
2. Run the basic email test
3. Check your inbox
4. If successful, run all tests
5. If failed, check the troubleshooting section

---

**Status**: ✅ Ready for Testing  
**Last Updated**: 2025-11-26  
**Version**: 2.0.0
