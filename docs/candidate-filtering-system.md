# Candidate Filtering System Walkthrough

**Date:** 2025-11-22
**Author:** Nefi
**Status:** Active

## Overview
The Candidate Filtering System allows administrators to define specific requirements for job postings (e.g., driving license, gender, nationality, education, experience) and automatically highlights "Ideal Candidates" who meet these criteria in the application list.

## Changes Implemented

### 1. Database Schema Update
- **Added `job_requirements` column**: A new JSON column was added to the `job_postings` table to store structured filtering criteria.
  - **SQL**: `ALTER TABLE job_postings ADD COLUMN job_requirements JSON DEFAULT NULL`

### 2. Admin - Job Management (`admin/job-create.php`, `admin/job-edit.php`)
- **New "Requirements" Section**: Added a dedicated section in the job creation and editing forms to specify:
  - **Driving Licenses**: Multi-select checklist (A, B, B2, D, E, etc.).
  - **Gender**: Dropdown (Any, Lelaki, Perempuan).
  - **Nationality**: Dropdown (Any, Warganegara Malaysia).
  - **Minimum Years in Selangor**: Number input.
  - **Minimum Education Level**: Dropdown (SPM, STPM, Diploma, Ijazah, Master/PhD).
  - **Minimum Working Experience**: Number input (Years).
- **Data Storage**: These requirements are encoded as a JSON object and stored in the `job_requirements` column.

### 3. Admin - Application Listing (`admin/applications-list.php`)
- **Ideal Candidate Logic**: Implemented logic to compare applicant data against the job's defined requirements.
  - **License Check**: Verifies applicant holds *all* required licenses.
  - **Gender & Nationality**: Exact match verification.
  - **Years in Selangor**: Checks if applicant's tenure meets the minimum.
  - **Education Level**: Checks if applicant's highest qualification meets or exceeds the required level (using a hierarchy rank).
  - **Experience**: Calculates total years of experience from work history and compares with the minimum required.
- **UI Enhancements**:
  - **"Ideal" Badge**: A green "Ideal" badge with a star icon appears next to the names of candidates who meet **ALL** specified requirements.
  - **Filter Option**: Added a "Tunjuk Calon Ideal Sahaja" checkbox to filter the list and show only ideal candidates.

## Verification Results

### Manual Verification Steps
1.  **Create Job**: Created a test job with specific requirements (e.g., License D, Male, Diploma).
2.  **View Applications**: Checked the application list for this job.
3.  **Highlighting**: Confirmed that candidates matching all criteria are marked with the "Ideal" badge.
4.  **Filtering**: Confirmed that checking "Tunjuk Calon Ideal Sahaja" hides non-matching candidates.
5.  **Edit Job**: Modified requirements (e.g., changed Gender to Female) and verified that the "Ideal" status updated dynamically for applicants.
