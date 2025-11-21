# Application Documentation

This document consolidates user-facing guidance for the MPHS job application system.

## Overview
- Single consolidated form `job-application-full.php` and multi-page flow (`job-application-1.php` → `job-Application-2.php` → `job-application-3.php` → `job-application-4.php`).
- Saves via `process-application.php` and `process-job-application.php` to application tables defined in `includes/schema.php`.
- Preview at `preview-application.php`; status check at `application-status.php`.

## Form Sections
- Personal Info: Name, IC, email, phone, address, religion, marital status, gender, DOB, state of birth, ethnicity, nationality, Selangor residency duration, birth certificate number, driving license, uploads (IC, birth cert, license, passport photo).
- Health & OKU: Health condition radios, “penyakit_lain” with conditional textarea, OKU holder with optional `jenis_oku[]`, eyewear and rabun, physical measurements, optional OKU card upload.
- Language & Computer Skills: Dynamic rows for languages (speaking/writing) and computer skills (software + level).
- Declarations & References: Public service, relation to staff, worked at MPHS, disciplinary actions, legal conviction, bankruptcy; conditional “Nyatakan” fields; two references (name, phone, years known).

## Conditional UI Behavior
- Radios toggle visibility of: `penyakit_lain_field`, `oku_field`, `cermin_mata_field`, and `*_nyatakan_field` in declarations.
- Main toggles are implemented inline in `job-application-full.php` (event delegation on `change`).

## File Uploads
- Handled by `FileUploadHandler.php` via `process-application.php` and `process-job-application.php`.
- Accepts `JPG/JPEG/PNG/GIF/PDF`, typical max sizes 2–5MB.
- Validate client-side with `ajax-validate-file.php` and basic attributes; server-side sanitization enforced.

## Duplicate Prevention
- Uses `modules/DuplicateValidator.php` and related docs; prevents multiple applications by same IC for the same job.
- Public status check pages: `application-status.php` and `semak-status.php`.

## Payment Flow (if applicable)
- `payment-form.php`, `payment-process.php`, `payment-callback.php`, `payment-status-check.php`, `payment-thank-you.php`.
- Payment references attach to applications for edit or resume flows.

## Preview
- Modular preview renders per section in `functions/preview/health_section.php`, `skills_section.php`, `work_section.php`.
- `preview-application.php` aggregates and displays sanitized application data.

## Admin
- Admin pages let staff review and manage applications (e.g., `applications-list.php`, `application-view.php`).
- Activity logging via `admin/includes/admin_logger.php` and `includes/LogManager.php`.

## Security Practices
- CSRF tokens and secure headers; no direct access to internal modules.
- Sanitization/escaping of inputs and outputs.
- Error display disabled in production; use logs.

## Troubleshooting Quick Tips
- Verify uploads directory permissions and PHP settings for file uploads.
- Check browser console for JS warnings and network requests.
- Review `logs/` for server-side issues.