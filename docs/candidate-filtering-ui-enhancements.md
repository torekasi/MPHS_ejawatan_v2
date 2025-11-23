# Candidate Filtering UI Enhancements Walkthrough

**Date:** 2025-11-22
**Author:** Nefi
**Status:** Active

## Overview
This update enhances the user interface and functionality of the Candidate Filtering System in the Admin Panel. It introduces "Negeri Kelahiran" as a filtering criterion, improves the layout of the job creation/editing forms, and optimizes the display of license requirements.

## Changes Implemented

### 1. Admin - Job Management (`admin/job-create.php`, `admin/job-edit.php`)
- **New Field:** Added "Negeri Kelahiran" dropdown to the filtering criteria.
- **Layout Improvements:**
  - **Section Relocation:** Moved the "Kriteria Penapisan Calon" section to the bottom of the form (just before the save button) for better flow.
  - **Grid Layout:** Updated the dropdown fields to use a 3-column grid (`md:grid-cols-3`) for better screen space utilization.
  - **Collapsible Licenses:** The "Lesen Memandu" checklist is now collapsible to reduce visual clutter. It auto-expands if licenses are already selected (in Edit mode).

### 2. Admin - Application Listing (`admin/applications-list.php`)
- **Filtering Logic:** Updated the "Ideal Candidate" validation logic to include a check for `negeri_kelahiran`.
- **Code Cleanup:** Removed redundant logic blocks to improve code maintainability.

## Verification Results

### Manual Verification Steps
1.  **Create Job:**
    -   Open "Tambah Jawatan".
    -   Verify "Kriteria Penapisan Calon" is at the bottom.
    -   Verify "Lesen Memandu" is collapsed by default and expands on click.
    -   Verify "Negeri Kelahiran" dropdown exists and lists all states.
    -   Select specific criteria (e.g., Johor) and save.
2.  **Edit Job:**
    -   Edit the created job.
    -   Verify all selections are preserved.
    -   Verify "Lesen Memandu" auto-expands if licenses were selected.
3.  **Application List:**
    -   View applications for the job.
    -   Verify that only candidates matching the "Negeri Kelahiran" (and other criteria) are marked as "Ideal".
