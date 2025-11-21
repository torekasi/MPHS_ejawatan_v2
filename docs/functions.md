# Functions and Modules Reference

Date: 2025-11-16

See Also:
- Consolidated user-facing docs: `docs/Documentation.md`
- Consolidated technical index: `docs/Technical.md`

This document describes the consolidated job application form and its modular section files introduced to replace `job-application-1.php` to `job-application-4.php`.

## job-application-full.php
- Purpose: Consolidated single-page job application form that loads modular sections and handles CSRF, session, and pre-fill logic.
- Inputs:
  - Method: `POST`
  - Fields: All fields defined across Part 1–Part 4 (personal info, health/OKU, language & computer skills, declarations, references).
  - CSRF token: `csrf_token`
- Returns:
  - On success: Persists the submitted application data via the existing save handlers in the project (as wired by current routes/controllers) and returns a success flow to the user.
  - On error: Displays validation errors with a security-compliant loading/feedback overlay and logs into `/logs/error.log`.
- Related Modules: `application_section/00-agreement.php`, `application_section/01-uploads.php`, `application_section/02-personal-info.php`, `application_section/03-health-oku.php`, `application_section/pendidikan.php`, `application_section/05-declarations-references.php`.
- Security: Security headers, CSRF protection, input sanitization guidance, and direct-access prevention for section files.
 - Prefill: Merges health data from `application_health` into `$application` and maps upload path keys (`gambar_passport_path`, `salinan_ic_path`, `salinan_surat_beranak_path`) to legacy keys expected by section templates to display existing filenames in edit mode.

### Feature: Session Timeout & Auto-Redirect (2025-11-10)
- Purpose: Limit active session for form completion and protect against stale data submission.
- Behavior:
  - Client-side countdown bar shows remaining time; default 30 minutes.
  - On expiry, disables inputs, shows overlay, and redirects to `index.php`.
  - Server-side enforcement rejects submissions beyond the timeout with a safe redirect.
- Inputs: `$_SESSION['form_start_time']`, `$_SESSION['form_timeout_seconds']` (initialized on page load).
- Related: `controllers/ApplicationController::handleSaveFull` (server enforcement).

### Feature: Edit Verification Gate (2025-11-10)
- Purpose: Ensure only verified users can edit/resume existing applications.
- Behavior:
  - If `edit=1` or `app_id`/`ref` present, requires session verification.
  - Accepts either `$_SESSION['verified_application_id/ref']` (from semak/verify flow) or `$_SESSION['edit_application_verified']` (multi-page flow).
  - Also accepts a short-lived `edit_token` from the status page; validates against `user_sessions` (≤12 hours) and sets `$_SESSION['edit_application_verified']` on success.
- If not verified, sets a flash error and redirects to `semak-status.php`.
- Inputs: Query `edit`, `app_id`, `ref`; session markers listed above.
- Related: `verify-email-for-edit.php`, `semak-status.php`, `edit-application.php`, `job-Application-2.php`.

### Document Upload System Fixes (2025-11-16)
- Purpose: Fix document upload functionality that was not storing file paths in database
- Issues Fixed:
  - Database column references causing SQLSTATE[42S22] errors in DataFetcher.php
  - Missing document path columns in application_application_main table
  - Upload system not updating main application table with file paths
  - Missing columns in application_extracurricular table (peringkat, tahap, salinan_sijil_filename)
- Files Modified:
  - `controllers/ApplicationSaveController.php`: Added updateMainApplicationDocuments() method
  - `modules/preview/DataFetcher.php`: Fixed column references for extracurricular and professional bodies
  - `fix-upload-columns.sql`: SQL script to add missing database columns
  - `test-upload.php`: Test script to verify upload functionality
- Directory Structure: `/uploads/applications/<year>/<application_reference>/`
- Related: `modules/FileUploader.php`, `modules/FileUploaderImplementation.php`

## application_section/00-agreement.php
- Purpose: Display the full agreement text and capture applicant confirmation at the very beginning.
- Inputs: `pengisytiharan_pengesahan` (required checkbox).
- Returns: Renders agreement content sourced primarily from admin-managed page content (`page_content.content_key = 'pengistiharan_terms'`), with fallbacks to `getActiveAcknowledgment()`, job-specific `declaration_text`, or default text; ensures user consents before proceeding.
- Related: `job-application-full.php`, `includes/bootstrap.php` (DB helper), `admin/page-content.php` (content management).

## application_section/01-uploads.php
- Purpose: Collect required identification documents before personal information.
- Fixed: Document upload functionality now properly stores file paths in database columns (`gambar_passport_path`, `salinan_ic_path`, `salinan_surat_beranak_path`) and creates proper directory structure `/uploads/applications/<year>/<app-ref>/`.
- Inputs: `salinan_lesen_memandu`, `gambar_passport`, `salinan_ic`, `salinan_surat_beranak`.
- Returns: Renders file inputs; data saved via existing upload handlers.
- Related: `process-application.php`, `save-application-part1.php`, `includes/schema.php`.
 - UX Updates (2025-11-09): Layout disusun kepada grid 3 kolum pada baris yang sama untuk `gambar_passport`, `salinan_ic`, dan `salinan_surat_beranak`. Pratonton fail segera ditunjukkan di bawah setiap input dengan tinggi 200px (imej dipaparkan sebagai thumbnail, PDF di-embed pada tinggi yang sama).

## application_section/02-personal-info.php
- Purpose: Personal information fields.
- Layout: Address sections are unified under "Alamat" with two containers: "Alamat Tetap" and "Alamat Surat-Menyurat". The checkbox "Sama seperti alamat tetap" sits between them to copy values when applicable.
- Inputs: `nama_penuh`, `nombor_ic`, `email`, `nombor_telefon`, `alamat_tetap`, `poskod_tetap`, `bandar_tetap`, `negeri_tetap`, `alamat_surat`, `poskod_surat`, `bandar_surat`, `negeri_surat`, `alamat_surat_sama`.
- UX: Address fields use single-line inputs (full-width) instead of textareas.
- UX Updates (2025-11-09):
  - `Jantina` now uses a dropdown with options `Lelaki` and `Perempuan`.
  - When `alamat_surat_sama` is checked, permanent address fields auto-copy into correspondence address and those inputs are disabled until unchecked.
  - Driving license checkboxes are presented in a responsive grid (2–4 columns), now 4 columns per baris pada skrin sederhana/besar untuk ketelusan pilihan yang lebih baik.
  - Rule: Jika `Tiada` dipilih, semua pilihan lesen lain akan dinyahpilih dan dinyahaktif; jika mana-mana pilihan selain `Tiada` dipilih, `Tiada` akan dinyahpilih dan dinyahaktif. Logik ini diinisialisasi pada muat halaman untuk konsistensi pra-isi.
  - Layout: `Nombor Telefon`, `Tarikh Lahir`, dan `Umur` kini dikumpulkan pada baris yang sama (grid 3 kolum) untuk memudahkan semakan maklumat hubungan dan umur.
  - Layout: `Bangsa` kini berada dalam baris yang sama dengan `Negeri Kelahiran`, `Warganegara`, dan `Tempoh Bermastautin Di Selangor` (grid 4 kolum) untuk aliran pengisian yang lebih logik.
  - Layout: Baris maklumat asas kini menggunakan grid 3 kolum; `Email` dikelompokkan bersama `Taraf Perkahwinan` dan `Nombor Telefon` pada baris yang sama untuk memudahkan semakan maklumat hubungan.
  - `Umur` and `Tempoh Bermastautin Di Selangor` dihadkan kepada 2 digit sahaja (pattern `^[0-9]{1,2}$`, `maxlength=2`, inputmode numeric).
  - `Nombor Surat Beranak` dialihkan untuk muncul betul-betul selepas `Nombor IC`.
  - Teks bantuan format NRIC dibuang untuk mengurangkan kekeliruan; kekal `pattern` dan `placeholder`.
  - Seksyen `Maklumat Pasangan` dipindahkan ke bawah medan `Tempoh Bermastautin Di Selangor` dan kekal muncul bersyarat apabila `taraf_perkahwinan = Berkahwin`.
- Returns: Renders form inputs; data consumed by `job-application-full.php` submission.
- Related: Consolidated page.
 - Layout Update (2025-11-09): `Email` dan `Nombor Telefon` dipindahkan ke dalam seksyen "Maklumat peribadi" (di bawah blok info asas) untuk aliran pengisian yang lebih jelas.
 - Layout Update (2025-11-09): Medan dalam seksyen "Maklumat peribadi" kini dibalut kotak kontena berbingkai (border, rounded, padding) untuk persembahan yang lebih kemas dan konsisten dengan seksyen lain.

### Feature: Maklumat Pasangan Toggle
- Purpose: Paparkan seksyen Maklumat Pasangan hanya apabila `taraf_perkahwinan` dipilih sebagai `Berkahwin`.
- Inputs: `nama_pasangan`, `telefon_pasangan`, `status_pasangan`, `pekerjaan_pasangan`, `nama_majikan_pasangan`, `telefon_pejabat_pasangan`, `alamat_majikan_pasangan`, `bandar_majikan_pasangan`, `negeri_majikan_pasangan`, `poskod_majikan_pasangan`.
- Behavior:
  - Seksyen `Maklumat Pasangan` (dibungkus dalam `#spouse_info_section`) disembunyikan secara lalai dan akan dipaparkan apabila `#taraf_perkahwinan` berubah kepada `Berkahwin`.
  - `required` dihidupkan untuk `nama_pasangan` dan `telefon_pasangan` hanya apabila status berkahwin; medan lain kekal pilihan.
  - Status dikekalkan pada muat semula halaman untuk memastikan konsistensi UI.
- Related Modules: `preview-application.php` dan `admin/application-view.php` (paparan), `save-application-part1.php` dan `save-application-with-token.php` (penyimpanan), `includes/schema.php` (rujukan lajur).
- Change Date: 2025-11-09
- Notes: Mematuhi garis panduan keselamatan klien (tiada akses terus ke fail seksyen, patuh CSRF di halaman induk) dan tidak mendedahkan log ralat kepada pengguna.

## application_section/03-health-oku.php
- Purpose: Health information and OKU status.
- Inputs: Health flags, `other_disease` (conditional), `oku_status`, `oku_type` (conditional).
- Returns: Renders conditional inputs; data consumed by consolidated submission.
- Related: Consolidated page.
 - Layout: `Salinan Kad OKU` kini berada dalam kontena yang sama dengan `Jenis OKU` dan akan disembunyikan serentak apabila `pemegang_kad_oku = Tidak`.

## application_section/pendidikan.php
- Purpose: Language and computer skills with dynamic rows.
- Inputs:
  - `kemahiran_bahasa[index][bahasa|pertuturan|penulisan]`
  - `kemahiran_komputer[index][nama_perisian|tahap_kemahiran]`
  - `pengalaman_kerja[index][majikan|jawatan|dari_bulan|dari_tahun|hingga_bulan|hingga_tahun|tanggungjawab]`
  - SPM: `spm_tahun`, `spm_gred_keseluruhan`, `spm_angka_giliran`, `spm_bahasa_malaysia`, `spm_bahasa_inggeris`, `spm_matematik`, `spm_sejarah`, `spm_subjek_lain[index][subjek|gred]`, `spm_salinan_sijil`
  - Pendidikan: `persekolahan[index][institusi|kelayakan|dari_tahun|hingga_tahun|gred|sijil]`
  - Badan Profesional: `badan_profesional[index][nama_lembaga|no_ahli|sijil|tarikh_sijil|salinan_sijil]`
  - Kegiatan Luar: `kegiatan_luar[index][sukan_persatuan_kelab|jawatan|peringkat|tahun|salinan_sijil]`
- Returns: Renders repeater-style inputs for all Part 3 (pendidikan) subsections; data consumed by `save-application-part3.php`.
- UX Updates (2025-11-09): Ditambah paparan seksyen `Kelulusan SPM/SPV`, `Maklumat Persekolahan & IPT`, `Badan Profesional`, `Kegiatan Luar`, serta `Pengalaman Kerja` dalam fail ini. Butang `Tambah ...` disediakan untuk menduplikasi baris secara dinamik, termasuk kemaskini nama input dan ID pratonton fail.
- Related: Consolidated page.

## application_section/05-declarations-references.php
- Purpose: Self-declaration questions and references.
- Inputs: Declaration flags, `relative_name` (conditional), reference entries (`ref1_name`, `ref1_phone`, `ref1_duration`, etc.).
- Returns: Renders conditional inputs and reference sections; data consumed by consolidated submission.
- Related: Consolidated page.
 - UX Update (2025-11-10): Removed the "Nyatakan (jika Ya)" textarea specifically for `pertalian_kakitangan`. Only the conditional input `nama_kakitangan_pertalian` remains when "Ya" is selected to streamline data entry.

## Notes
- All section files are protected from direct access and rely on `job-application-full.php` for context.
- Ensure controllers/models saving logic map to the same field names used here for reliable persistence.
### Application Sections Updated (2025-11-09)

- 00-agreement.php
  - Purpose: New first section to display agreement content and collect user confirmation.
  - Inputs: `pengisytiharan_pengesahan`.
  - Returns: Integrates with consolidated submission; content sourced from DB, job, or config.

- 02-personal-info.php
  - Purpose: Collect comprehensive personal details, correspondence address, and driving license info. Declaration moved to `00-agreement.php`; uploads remain in `01-uploads.php`.
  - Inputs: `agama`, `taraf_perkahwinan`, `jantina`, `tarikh_lahir`, `umur`, `negeri_kelahiran`, `bangsa`, `warganegara`, `tempoh_bermastautin_selangor`, `nombor_surat_beranak`, correspondence address fields, `lesen_memandu[]`, `tarikh_tamat_lesen`.
  - Returns: Posted data saved via existing processing scripts.
  - Related: `process-application.php`, `includes/schema.php`.

- 03-health-oku.php
  - Purpose: Gather health declarations, OKU status/types, eyewear and rabun details, physical measurements, and optional OKU card upload.
  - Inputs: health radios, `penyakit_lain` with `penyakit_lain_nyatakan`, `pemegang_kad_oku` with `jenis_oku[]`, `memakai_cermin_mata` with `jenis_rabun`, `berat_kg`, `tinggi_cm`, `salinan_kad_oku`.
  - Visibility: Conditional sections `penyakit_lain_field`, `oku_field`, `cermin_mata_field` toggle via section script.
- Validation: When `pemegang_kad_oku = Ya`, at least one `jenis_oku[]` must be checked; when `memakai_cermin_mata = Ya`, `jenis_rabun` selection is required; file input validates size (`data-max-size`) and type (`data-allowed-types`) with a safe local fallback if global validator is absent.
 - Validation: When `pemegang_kad_oku = Ya`, at least one `jenis_oku[]` must be checked; when `memakai_cermin_mata = Ya`, `jenis_rabun` selection is required; file input validates size (`data-max-size`) and type (`data-allowed-types`) with a safe local fallback if global validator is absent.
 - Behavior: `Salinan Kad OKU` input is disabled and cleared automatically when `pemegang_kad_oku = Tidak`; enabled when `Ya`.
 - Behavior: `penyakit_lain_nyatakan` textarea becomes `required` only when `penyakit_lain = Ya`.
  - UX: Shows a loading overlay during form submission for feedback.
  - Related: `job-Application-2.php`, `preview-application.php`, `functions/preview/health_section.php`.

- 05-declarations-references.php
  - Purpose: Capture declarations (public service employee, relation to staff, worked at MPHS, disciplinary action, legal conviction, bankruptcy) with conditional explanations; capture two reference contacts.
  - Inputs: radios for `pekerja_perkhidmatan_awam`, `pertalian_kakitangan` (+ `nama_kakitangan_pertalian`), `pernah_bekerja_mphs`, `tindakan_tatatertib`, `kesalahan_undangundang`, `muflis`; textareas for `*_nyatakan`; reference fields `rujukan_1_*`, `rujukan_2_*`.
  - Visibility: Conditional `*_nyatakan_field` toggled by JS in `job-application-full.php`.
  - Related: `save-application-part4.php`, `process-job-application.php`, `includes/schema.php`.

- job-application-full.php (JS updates)
  - Purpose: Provide loading overlay and toggle conditional fields.
  - Functions: Event delegation on `change` toggles `penyakit_lain_field`, `oku_field`, `cermin_mata_field`, and declarations `*_nyatakan_field`.
### ApplicationController::handleSaveFull
- Purpose: Validates CSRF and delegates full application saving to the existing `process-application.php` for comprehensive persistence.
- Parameters: None (reads from `$_POST` and `$_FILES`).
- Returns: void (manages redirects internally).
- Related modules: `routes/application.php`, `job-application-full.php`, `process-application.php`.
- Change date: 2025-11-10.
- Notes: Adds security headers and basic sanitization; future refactor can move DB ops to `ApplicationModel::save`.
 - Enforcement: Rejects submissions when session timeout exceeded; requires edit verification when `edit=1`.

### routes/application.php (save_full)
- Purpose: Provide a secure route endpoint for saving the full job application.
- Parameters: `action` (POST, default `save_full`), `csrf_token`, all form fields and files.
- Returns: void (delegates to controller; redirects or emits error).
- Related modules: `controllers/ApplicationController.php`, `job-application-full.php`.
- Change date: 2025-11-10.
- Notes: Enforces method=POST and security headers.

### ApplicationModel::save (skeleton)
- Purpose: Placeholder to centralize DB operations for job applications.
- Parameters: `array $post`, `array $files`.
- Returns: void.
- Related modules: `controllers/ApplicationController.php`, `process-application.php`.
- Change date: 2025-11-10.
- Notes: Will be populated when migrating logic from `process-application.php`.