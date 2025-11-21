/**
 * @FileID: DOC_DEV_001
 * @Module: Documentation
 * @Author: Nefi
 * @LastModified: 2025-11-21
 * @SecurityTag: validated
 */

# Developer Guide: MPHS Job Application System

## 1. Code Structure

```
/
├── admin/              # Admin panel files
├── assets/             # CSS, JS, Images
├── controllers/        # Logic controllers
├── docs/               # Documentation
├── functions/          # Helper functions
├── includes/           # Core includes (DB, Config, Helpers)
├── logs/               # Application logs
├── models/             # Database models
├── modules/            # Standalone modules (Upload, Validator)
├── public/             # Public facing assets/scripts
├── uploads/            # User uploads (Gitignored)
├── index.php           # Entry point
└── config.php          # Configuration
```

## 2. Development Workflow

1.  **Setup**: Copy `config-sample.php` to `config.php` and set DB credentials.
2.  **Database**: Run `scripts/init-db.php` to set up the schema.
3.  **Serve**: Use a local server (e.g., XAMPP, Laragon) or `php -S localhost:8000`.

## 3. Adding a New Form Section

To add a new section to the application form:

1.  **Create View**: Create a new file in `application_section/` (e.g., `06-new-section.php`).
    *   Ensure it checks `defined('APP_SECURE') or die();`.
2.  **Include**: Add the include statement in `job-application-full.php`.
3.  **Update Schema**: Add necessary columns to `includes/schema.php` and run migration.
4.  **Update Processor**: Add field handling in `process-application.php` or `controllers/ApplicationSaveController.php`.

## 4. API / AJAX Endpoints

### Check Duplicate
*   **URL**: `ajax-check-duplicate.php`
*   **Method**: `POST`
*   **Params**: `nric`, `job_id`
*   **Response**: JSON `{ status: 'success'|'error', duplicate: bool }`

### Validate File
*   **URL**: `ajax-validate-file.php`
*   **Method**: `POST`
*   **Params**: `file` (Multipart)
*   **Response**: JSON `{ valid: bool, message: string }`

## 5. Logging

Use the `LogManager` class for logging.

```php
require_once 'includes/LogManager.php';
LogManager::info('Application submitted', ['app_id' => 123]);
LogManager::error('Upload failed', ['error' => $e->getMessage()]);
```

Logs are stored in `logs/app.log` and `logs/error.log`.
