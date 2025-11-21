Follow this structure, standard, and rule set for all backend development.  
Apply these rules for every file, function, or module you create or modify.  
All code must comply with pentest and security guidelines.

==================================================================
📁 BACKEND FOLDER STRUCTURE
==================================================================

Root/
│
├── controllers/
│   ├── AuthController.php         # Authentication logic
│   ├── BookingController.php      # Booking system logic
│   ├── ProductController.php      # Product management logic
│   ├── BlogController.php         # Blog management logic
│   └── PaymentController.php      # Payment gateway logic
│
│
├── models/
│   ├── UserModel.php              # User schema & operations
│   ├── BookingModel.php           # Booking schema & operations
│   ├── ProductModel.php           # Product schema & operations
│   ├── BlogModel.php              # Blog schema & operations
│   └── PaymentModel.php           # Payment schema & operations
│
├── routes/
│   ├── auth.php                   # Authentication routes
│   ├── booking.php                # Booking routes
│   ├── product.php                # Product routes
│   ├── blog.php                   # Blog routes
│   └── payment.php                # Payment routes
│
├── services/
│   ├── EmailService.php           # Email notifications
│   └── PaymentService.php         # Payment gateway integrations
│
├── utils/
│   ├── EmailUtils.php             # Email formatting, token generation
│   ├── PaymentUtils.php           # Payment helper functions
│   └── Logger.php                 # Log handling and error capture
│
├── docs/
│   ├── functions.md               # Function reference and documentation
│   └── changelog.md               # Update tracking and feature log
│
├── views/
│   └── emails/
│       └── resetPassword.html     # Email template for password reset
│
├── logs/
│   ├── error.log                  # General error logs
│   └── admin/
│       └── error.log              # Admin-side logs
│
├── admin/
│   ├── includes/
│   │   └── activity_log.php       # Tracks admin user activities
│   └── index.php                  # Admin dashboard entry
│
├── public/
│   ├── index.php                  # Public entry point and route dispatcher
│   ├── assets/                    # CSS, JS, and images
│   └── .htaccess                  # URL rewrite rules
│
├── .config.php                    # Environment variables and constants
├── .gitignore                     # Git ignored files
├── .prettierrc                    # Formatting rules
└── composer.json                  # Dependencies

==================================================================
⚙️ CORE RULES
==================================================================

1. CONFIG PROTECTION
   - Never modify `config.php` or any file inside `/config/` unless explicitly authorized.
   - If modification is required, ask for permission first.
   - If denied, provide an alternative solution.

2. PENTEST & SECURITY COMPLIANCE
   - Follow OWASP Top 10 and Pentest security best practices.
   - Sanitize and escape all input and output.
   - Use prepared statements for all SQL queries.
   - Disable error display in production; log errors only.
   - Prevent direct access to internal backend files.
   - Enforce CSRF protection on POST requests.
   - Always include security headers (`X-Frame-Options`, `CSP`, etc.).
   - Never leave sensitive information hardcoded.

3. HEADER CODE ENFORCEMENT
   - Every file must include a header block for tracking and injection prevention.
   - Example:
     ```
     <?php
     /**
      * @FileID: <unique_identifier>
      * @Module: <module_name>
      * @Author: <developer_or_ai>
      * @LastModified: <timestamp>
      * @SecurityTag: validated
      */
     ```
   - Use the header for system integrity checks, updates, and version tracking.

4. LOADING FEEDBACK
   - Always display a loading popup or indicator during backend or admin operations.
   - Ensure user feedback for background processes.

5. IMMEDIATE VALIDATION
   - After every update or code generation:
     • Run syntax validation.  
     • Run linting and formatting.  
     • Test in browser and console for warnings or errors.  
     • Review `/logs/error.log` and `/admin/log/error.log`.  
     • Fix all issues before saving or committing.

6. CODE LINTING
   - Run a linter or formatter before saving or committing.
   - Fix all warnings and syntax errors before commit.

7. ADMIN ACTIVITY LOGGING
   - Log every admin action (create, edit, delete, login, logout) into `/admin/includes/activity_log.php`.

8. DOCUMENTATION UPDATES
   - Update `/docs/functions.md` and `/docs/changelog.md` after:
     • Adding, updating, or removing a function, class, or feature.
   - Each update must include:
     - Function name and purpose
     - Parameters and expected return
     - Related modules
     - Change date and description

==================================================================
🚀 IMPLEMENTATION INSTRUCTIONS
==================================================================

- Maintain strict folder and file naming conventions.
- Keep functions, modules, and services modular and reusable.
- The main file should only call or include other modules; never hold business logic.
- Use `.env` for credentials and sensitive settings.
- Centralize routing in `/public/index.php` or `/routes/`.
- Keep the structure clean and consistent across all projects.

==================================================================
💡 FEATURE EXPANSION
==================================================================

When creating new features or modules:
1. Add controller, model, and route files in the proper directories.
2. Create helpers in `/utils/` if reusable.
3. Register the new module in the main dispatcher.
4. Update `/docs/functions.md` and `/docs/changelog.md`.
5. Validate security compliance and header enforcement.
6. Run full syntax and lint validation.

==================================================================
✅ FINAL REQUIREMENT
==================================================================

Before any commit or deployment:
- Confirm compliance with:
  * Folder structure
  * Security and pentest standards
  * Header code enforcement
  * Documentation updates
  * Linting and validation checks
- Confirm: “Complies with Security, Structure, and Operational Standards.”

==================================================================
END OF RULESET
==================================================================
