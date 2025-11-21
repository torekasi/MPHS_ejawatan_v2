# Changelog

## 2025-11-22 – Education Section Enhancement & Documentation Standards

### Added
- **Education Section Enhancement:**
  - Added `sijil_tambahan` column to `application_education` table for additional certificate uploads
  - Created SQL migration script (`tools/add-sijil-tambahan-column.sql`) for database schema update
  - Added "Sijil Tambahan" upload field in education form (`application_section/pendidikan.php`)
  - Added sijil_tambahan support in `modules/preview/DataFetcher.php`

### Changed
- **Education Form Layout:**
  - Modified education section layout - "Nama Institusi" field now spans full width for improved UX
  - Reorganized field arrangement in education entries for better visual flow
- **Backend Updates:**
  - Updated `ApplicationSaveController::saveEducation()` to handle `sijil_tambahan` field
  - Updated `PreviewDataFetcher::getEducation()` to fetch and map sijil_tambahan data
- **Code Standardization:**
  - Standardized all `@Author` tags from "AI Assistant" to "Nefi" across multiple files

### Documentation
- Created comprehensive documentation update requirements (`docs/DOCUMENTATION_REQUIREMENTS.md`)
- Created education section enhancement summary (`docs/education-section-enhancement.md`)
- Established mandatory documentation update workflow for all code changes
- Defined standards for function documentation, changelog, and technical documentation


## 2025-11-10 – Session Timeout & Edit Verification
- Added a session-based timeout to `job-application-full.php` with a client-side countdown bar and auto-redirect to the main homepage upon expiry. Default timeout is 30 minutes.
- Enforced server-side timeout in `controllers/ApplicationController.php` to block stale submissions.
- Implemented an edit verification gate in `job-application-full.php` requiring session verification via `semak-status.php` or `verify-email-for-edit.php` before editing/resuming an application.
 - Edit link on `application-status.php` now points to `job-application-full.php?edit=1&edit_token=...` to ensure consolidated edit mode.
 - `job-application-full.php` now merges health data from `application_health` and maps upload path keys (`*_path`) to legacy keys used by section templates for consistent prefill and display of previously uploaded files.
 - `job-application-full.php` accepts and validates short-lived `edit_token` against `user_sessions` (≤12 hours) and sets `$_SESSION['edit_application_verified']` when valid.
- Files: `job-application-full.php`, `controllers/ApplicationController.php`, docs updated in `docs/functions.md`.
- Rationale: Strengthen session integrity, improve user feedback, and ensure only verified users can edit applications.

## 2025-11-10 – Declarations UI Update
- Removed the "Nyatakan (jika Ya)" textarea for the question `Adakah anda mempunyai pertalian keluarga dengan mana-mana kakitangan MPHS?`.
- Rationale: The free-text explanation is not required; only the specific field `Nama Kakitangan Berkaitan (jika ada)` should be shown when "Ya" is selected.
- Impact: Streamlines the declarations section and avoids redundant input while preserving conditional visibility for the staff name field.
- Files: `application_section/05-declarations-references.php` (conditional rendering update), docs updated in `docs/functions.md`.

## 2025-11-09 – Maklumat Pasangan Toggle
- Ditambah seksyen `Maklumat Pasangan` dalam `application_section/02-personal-info.php`, disembunyikan secara lalai dan dipaparkan apabila `taraf_perkahwinan = Berkahwin`.
- `nama_pasangan` dan `telefon_pasangan` ditetapkan sebagai wajib (required) hanya untuk status berkahwin; medan lain kekal pilihan.
- Tujuan: Mengumpul maklumat pasangan secara bersyarat untuk mempercepat pengisian dan mengurangkan ralat.
- Berkaitan: Medan sejarah di `job-application-1.php`; penyimpan `save-application-part1.php` dan `save-application-with-token.php`; paparan `preview-application.php` dan `admin/application-view.php`.

## 2025-11-09 – Personal Info UX Tweaks
- Changed `Jantina` input from radio buttons to a dropdown in `application_section/02-personal-info.php` to standardize selection UI.
- Implemented auto-copy behavior for "Sama seperti alamat tetap" that copies permanent address fields to correspondence address and disables them while checked.
- Arranged driving license checkboxes into a responsive grid (2 columns on small, 3 on medium+) for clearer layout.
- Purpose: Improve usability and reduce input effort while keeping data entry consistent.
- Layout: Made `Nama Penuh` span the full row and moved `Nombor IC` to the next row before other personal info fields for clearer flow.
 - Moved `Email` and `Nombor Telefon` into the "Maklumat peribadi" section to keep contact info together with personal details.
 - Grouped `Email` with `Taraf Perkahwinan` and `Nombor Telefon` under a 3-column grid to keep contact and status fields aligned in one row.
 - Wrapped the "Maklumat peribadi" fields within a bordered, rounded container box to improve visual grouping and consistency with other sections.
 - Restricted `Umur` and `Tempoh Bermastautin Di Selangor` to 2 digits only (`maxlength=2`, numeric pattern).
 - Moved `Nombor Surat Beranak` to appear immediately after `Nombor IC`.
 - Removed NRIC help text string (`Format: YYMMDD-SS-#### (contoh: 800101-14-1234)`), retaining strict client-side pattern.
 - Relocated the `Maklumat Pasangan` section to below `Tempoh Bermastautin Di Selangor` while keeping conditional display when married.
 - Driving License layout updated: checkboxes arranged to 4 columns per row on medium+ screens for clearer scanning.
 - Driving License behavior updated: selecting `Tiada` disables and clears all other options; selecting any other option disables and clears `Tiada`. Initialization honors pre-filled states.
 - Personal Info layout updated: `Nombor Telefon`, `Tarikh Lahir`, dan `Umur` kini berada dalam baris yang sama (grid 3 kolum) untuk aliran pengisian yang lebih logik.
 - Personal Info layout updated: `Bangsa`, `Negeri Kelahiran`, `Warganegara`, dan `Tempoh Bermastautin Di Selangor` kini berada dalam baris yang sama (grid 4 kolum) untuk menyusun maklumat demografi dan status secara serentak.

## 2025-11-09 – Uploads Grid & Preview
- Reworked `01-uploads.php` layout to a 3-column grid for passport photo, IC copy, and birth certificate copy.
- Added immediate client-side preview with 200px height below each input (image thumbnail or embedded PDF).
- Purpose: Improve clarity and speed of verification before submission.

## 2025-11-09 – UI Copy Update
- Renamed agreement section title from "Pengesahan & Pengakuan" to "Pengakuan" in `application_section/00-agreement.php`.
- Updated consolidated form navigation label to "Pengakuan" (anchor `#agreement`) in `job-application-full.php`.
- Purpose: Clarify wording and keep navigation consistent with section content.

## 2025-11-09 – Address Section Restructure
- Unified address UI into single "Alamat" section containing two containers: "Alamat Tetap" and "Alamat Surat-Menyurat".
- Placed the "Sama seperti alamat tetap" checkbox between the two containers.
- Switched `alamat_*` fields from multi-line textareas to single-line inputs for concise entry.
- Moved license upload alongside expiry date within the "Maklumat Lesen Memandu" container.

## 2025-11-09 – Docs Consolidation
- Added `docs/Documentation.md` containing user-facing application guidance.
- Added `docs/Technical.md` consolidating architecture, modules, schema, and operations.
- Updated `docs/functions.md` to index consolidated docs.
- Removed redundant and legacy docs from `docs/` to reduce noise and centralize references.

## 2025-11-09 – Consolidated Job Application Form
- Added `job-application-full.php` consolidating `job-application-1.php` to `job-application-4.php` into one secure form.
- Added modular section files in `application_section/`:
  - `00-agreement.php` – Agreement & Confirmation (first)
  - `01-uploads.php` – Document Uploads
  - `02-personal-info.php` – Personal Information (combined)
  - `03-health-oku.php` – Health & OKU
  - `pendidikan.php` – Language & Computer Skills (dynamic rows)
  - `05-declarations-references.php` – Declarations & References
- Implemented CSRF protection, session management, security headers, and loading overlay feedback.
- Wired consolidated page to include section files and support pre-filling application data.
- Documentation updated in `docs/functions.md`.
 - Agreement content source updated: `00-agreement.php` now loads admin-managed page content (`page_content.content_key = 'pengistiharan_terms'`) with secure DB access and fallbacks.

## Security and Operations
- Ensures OWASP-aligned patterns with input sanitization and prepared statements guidance.
- Prevents direct access to section files; errors are logged, not displayed.
## 2025-11-09

- Added `application_section/00-agreement.php` to display full agreement text and capture required applicant confirmation before other sections.
- Updated `application_section/02-personal-info.php` to focus on personal details, correspondence address, and driving license info; moved declaration to `00-agreement.php`. Enforced header block and security checks.
- Updated `application_section/03-health-oku.php` to include eyewear (`memakai_cermin_mata`) with conditional rabun selection, physical measurements (`berat_kg`, `tinggi_cm`), and optional OKU card upload; enforced header block and security checks.
2025-11-09
- Enhanced `application_section/03-health-oku.php` client-side logic to mirror `save-application-part2.php` validation:
  - Requires at least one `jenis_oku[]` when `pemegang_kad_oku = Ya`.
  - Requires `jenis_rabun` when `memakai_cermin_mata = Ya`.
  - Adds safe fallback for file size/type validation using `data-max-size` and `data-allowed-types`.
  - Disables and clears `Salinan Kad OKU` input when `pemegang_kad_oku = Tidak`; re-enables when `Ya`.
  - Sets `penyakit_lain_nyatakan` to required when `penyakit_lain = Ya`.
  - Adds loading overlay on submit for user feedback.
  - Maintains security header enforcement and `APP_SECURE` guard.
- Replaced `application_section/05-declarations-references.php` to render declarations with conditional "Nyatakan" fields and two reference contacts; enforced header block.
- Enhanced `job-application-full.php` inline JS to toggle `cermin_mata_field` and declarations `*_nyatakan_field` visibility.

## 2025-11-09 – Health Section Layout
- Moved `Salinan Kad OKU` into the same container as `Jenis OKU` in `application_section/03-health-oku.php`.
- Behavior: This container is hidden when `pemegang_kad_oku = Tidak`, ensuring the file input follows the same visibility rules as `Jenis OKU`.
- Updated `public/health-oku-preview.html` to reflect the same layout and toggling for visual verification.
## 2025-11-09 – Code Cleanup

- Removed test/debug scripts: `debug-application-status.php`, `debug-computer-skills.php`, `debug-edit-token.php`, `debug-form-submission.php`, `debug-form-submission-test.php`, `test-debug.php`, `test-education-save.php`, `test-file-uploads.php`, `test-payment-email-preview.php`, `test-payment-status.php`, `test-pengistiharan-job-code.php`, `test-preview-data.php`, `test-professional-bodies.php`, `test-validation.php`, `test_output.html`.
- Removed legacy pages: `job-application-backup.php`, `job-application-restore.php`.
- Removed unused asset: `assets/js/job-application-oku.js`.
- Removed backup artifacts: `admin/application-view.php.bak`, `backups/ejawatan_db (2).sql`.
- Purpose: Reduce clutter, remove non-production/testing files, and align repo with active modules.
- Updated section titles to remove numeric prefixes across `01-uploads.php`, `02-personal-info.php`, `03-health-oku.php`, `pendidikan.php`, and `05-declarations-references.php`.

2025-11-09
- Pendidikan (Part 3) UI expansion in `application_section/pendidikan.php`:
  - Added SPM/SPV consolidated inputs: `spm_tahun`, `spm_gred_keseluruhan`, `spm_angka_giliran`, core subject gred, `spm_subjek_lain[]`, and `spm_salinan_sijil` upload.
  - Added Education repeater: `persekolahan[]` with institution, qualification, years, grade/CGPA, certificate upload.
  - Added Professional Bodies repeater: `badan_profesional[]` with member no., certificate name/date, and certificate upload.
  - Added Extracurricular repeater: `kegiatan_luar[]` with activity, role, level, year, and certificate upload.
  - Introduced client-side cloning/removal logic to correctly renumber input names and file input IDs for validation.
### 2025-11-09
- Consolidated health/OKU section spacing to a single container in `application_section/03-health-oku.php` by removing extra vertical margins (`mb-6`, `mt-6`) and inter-block spacing (`space-y-6`, `gap-6`).
- Further removed horizontal gaps between subcontainers by changing `space-x-4` to `space-x-0` and eliminating additional top margins in the OKU file input block.
- Updated `public/health-oku-preview.html` to remove row gaps (`gap: 0; margin-bottom: 0;`) for consistent preview of the single-container layout.
  - Also removed inline top margin from the OKU file input row to keep containers flush.

### 2025-11-09 – Part 3 Container Consolidation
- Merged all skill-related subsections in `application_section/pendidikan.php` into one main container:
  - `Kemahiran Bahasa`, `Kelulusan SPM/SPV`, `Maklumat Persekolahan & IPT`, `Badan Profesional`, `Kegiatan Luar`, dan `Kemahiran Komputer` kini berada dalam satu pembungkus utama.
- Removed multiple outer wrappers (`bg-white rounded-lg shadow-md overflow-hidden mb-6`) and retained a single wrapper without `mb-6` to eliminate inter-container gaps.
- Preserved internal section structure using sequential `section-title` and `p-6` blocks for clarity while maintaining compact visual flow.
- No changes to input names or dynamic row logic; add/remove functionality remains intact and indexed correctly.

### 2025-11-09 – Section Header Color Update
- Updated global `.section-title` background to green gradient in `assets/css/main.css` for consistent header styling across application sections.
- Synced preview pages to reflect green headers: `public/skills-preview.html` and `public/health-oku-preview.html`.

### 2025-11-09 – Part 3 Outer Container & Heading Title
- Added a new outer container wrapping Part 3 (`application_section/pendidikan.php`) using the same class chain as the current container.
- Renamed all header elements in Part 3 from `.section-title` to `.heading-title`.
- Added `.heading-title` style in `assets/css/main.css`, replicating `.section-title` with a solid green background for clear visual distinction.
- Updated `public/skills-preview.html` to use `.heading-title` to mirror the Part 3 markup change.

### 2025-11-09 – Header CSS Path Fix
- Updated `header.php` to use relative CSS links (`assets/css/...`) instead of absolute (`/assets/...`).
- Purpose: Ensure `assets/css/main.css` (including `.heading-title` and green headers) loads correctly across all pages regardless of base URL.
 
### 2025-11-09 – Pengalaman Kerja Section Added
- Added `Pengalaman Kerja` subsection to `application_section/pendidikan.php` with dynamic repeater rows.
- Fields: `pengalaman_kerja[index][majikan|jawatan|dari_bulan|dari_tahun|hingga_bulan|hingga_tahun|tanggungjawab]`.
- Behavior: Uses existing cloning utility; `Tambah Pengalaman` button adds rows; remove button shows when more than one entry exists.
- Styling: Header uses `.heading-title` (green background) to match updated section headers.
- Rationale: Addressed missing work experience section as requested; integrates with Part 3 UI without altering save logic.
2025-11-10
- Added `controllers/ApplicationController.php` with CSRF validation and security headers, delegating to `process-application.php` for full persistence.
- Added `routes/application.php` to centralize saving route (`action=save_full`).
- Updated `job-application-full.php` to post to the new route and include the `action` hidden field.
- Created `models/ApplicationModel.php` skeleton for future DB operations refactor.
- Rationale: Align with backend folder structure, improve security enforcement, and prepare for modular persistence.