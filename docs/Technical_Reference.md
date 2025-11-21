/**
 * @FileID: DOC_TECH_001
 * @Module: Documentation
 * @Author: Nefi
 * @LastModified: 2025-11-21
 * @SecurityTag: validated
 */

# Technical Reference: MPHS Job Application System

## 1. System Architecture

The system is built on a **PHP** (Native) foundation with a **MySQL** database. It follows a modular architecture where the main application form aggregates smaller, functional section files.

*   **Frontend**: HTML5, CSS3 (Tailwind/Custom), JavaScript (Vanilla/jQuery for AJAX).
*   **Backend**: PHP 7.4+, PDO for database access.
*   **Database**: MySQL 5.7/8.0.

## 2. Database Schema

Key tables include:

*   `application_application_main`: The core record linking applicant, job, and status.
*   `application_personal_info`: Stores extended personal details.
*   `application_education`: Stores SPM and higher education records.
*   `application_experience`: Work history.
*   `application_documents`: References to uploaded file paths.
*   `job_postings`: Available vacancies.

*See `includes/schema.php` for the full DDL.*

## 3. Key Modules

### 3.1 Application Processing
*   `process-application.php`: Handles the full form submission.
*   `controllers/ApplicationController.php`: Logic layer for validation and orchestration.
*   `models/ApplicationModel.php`: Database interaction layer.

### 3.2 File Handling
*   `modules/FileUploader.php`: Manages file validation, renaming, and storage.
*   `includes/FileUploadHandler.php`: Integration helper for the form.
*   **Storage Path**: `uploads/applications/{YEAR}/{APP_REF}/`

### 3.3 Duplicate Prevention
*   `modules/DuplicateValidator.php`: Checks if an IC has already applied for a specific Job ID.
*   `ajax-check-duplicate.php`: Endpoint for real-time frontend validation.

## 4. Security Implementation

*   **CSRF Protection**: All forms include a `csrf_token` generated in `includes/bootstrap.php`.
*   **Input Sanitization**: All inputs are sanitized using `htmlspecialchars` and prepared statements.
*   **Session Management**: Strict session timeouts and regeneration to prevent hijacking.
*   **Direct Access Prevention**: Section files check for `APP_SECURE` constant.

## 5. Configuration

Configuration is managed in `config.php`.

```php
return [
    'db_host' => 'localhost',
    'db_name' => 'mphs_db',
    'db_user' => 'root',
    'db_pass' => 'password',
    'upload_dir' => __DIR__ . '/uploads/',
    // ...
];
```
