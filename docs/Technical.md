# Technical Documentation

This document consolidates technical architecture, schema, modules, and operational guidance.

## Architecture Overview
- PHP application with modular sections included by `job-application-full.php` (and multi-page variants).
- Server-side processing in `process-application.php` and `process-job-application.php` using `includes/bootstrap.php`, `ErrorHandler.php`, `LogManager.php`.
- Database schema centrally defined in `includes/schema.php` and migration helpers (`setup-schema.php`, `scripts/init-db.php`).

## Data Model (Key Tables)
- `application_application_main`: Main application record, references job, payment, and applicant details.
- `application_language_skills`, `application_computer_skills`, `application_work_experience`, `application_bodies`, `application_spm`, etc.
- Declarations and references fields aligned to canonical names documented in the form and processors.

## Application Statuses
Statuses are stored in `application_statuses` (master) and changes are tracked in `application_status_history`. The listing page shows the latest status per application.

| Code              | Name (Malay)                 | Description                               |
|-------------------|------------------------------|-------------------------------------------|
| `PENDING`         | Permohonan Diterima          | Permohonan diterima dan direkod           |
| `SCREENING`       | Sedang Ditapis               | Permohonan sedang ditapis                 |
| `TEST_INTERVIEW`  | Dipanggil Ujian / Temu Duga  | Dipanggil ujian atau temu duga            |
| `AWAITING_RESULT` | Menunggu Keputusan           | Keputusan sedang ditunggu                 |
| `PASSED_INTERVIEW`| Lulus Temu Duga              | Lulus temu duga                           |
| `OFFER_APPOINTMENT`| Tawaran Pelantikan          | Dalam proses tawaran pelantikan           |
| `APPOINTED`       | Dilantik                     | Pelantikan disahkan                       |

## Module Index
- `modules/DuplicateValidator.php`: Prevents duplicate applications for the same job by IC.
- `modules/FileUploader.php`: Encapsulates file handling; used by processing scripts.
- `modules/preview/DataFetcher.php`: Accessors for preview rendering.

## Processing Endpoints
- `process-application.php`: Orchestrates save for full-form submission; handles uploads and CSRF tokens.
- `process-job-application.php`: Alternative save endpoint; creates uploads dirs and validates payload.
- Part saves: `save-application-part1.php` through `save-application-part4.php` for multi-page flow.

## Security & Compliance
- OWASP-aligned: prepared statements, sanitation, escaping, CSRF, and secure headers (X-Frame-Options, CSP).
- Disable error display in production; use `includes/ErrorHandler.php` and logs in `logs/`.
- Prevent direct access to internal backend files (`APP_SECURE` guards on sections).

## Token & Edit Flow
- Token issuance and email via `MailSender.php`, templates in `includes/email-templates` and `EditLinkEmailTemplate.php`.
- Edit verification via `verify-email-for-edit.php`; resume form with `edit-application.php`.

## Payment System
- `payment-form.php` → `payment-process.php` → `payment-callback.php` → `payment-thank-you.php`.
- Status checks: `payment-status-check.php`; migrations in `includes/payment-migration.sql`, `includes/run-payment-migration.php`.

## File Uploads
- `includes/FileUploadHandler.php` integrates with processors; client-side validation via `ajax-validate-file.php`.
- Storage paths: `uploads/applications/`; ensure permissions and whitelisted MIME types.

## Preview Rendering
- Section components in `functions/preview/*_section.php` render sanitized values; use uppercase where applicable.

## Migrations & SQL References
- SQL snippets retained in `docs/` for manual fixes and historical context have been consolidated; prefer `setup-schema.php`, `simple-migration.php`, and dedicated migrate scripts (`migrate-*`).
- For legacy table updates, see `generate-migration-sql.php` and `fix-database-structure.php`.

## Logging & Admin
- `includes/LogManager.php` and `includes/ErrorHandler.php` capture operational events.
- Admin tools under `admin/` for job management and application review.

## Testing & Diagnostics (Production Guidance)
- Use browser devtools for UI; sanitize uploads; inspect `logs/` for errors.
- Avoid enabling error display in production; use dedicated debug pages only in dev.