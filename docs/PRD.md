# Product Requirements Document (PRD)

**Last Updated:** 2025-11-22
**Status:** Active

---

## [Completed] Candidate Filtering UI Enhancements

### 1. Goal
Enhance the usability and functionality of the Candidate Filtering System in the Admin Panel. This includes adding "Negeri Kelahiran" as a filtering criterion, optimizing the "Lesen Memandu" UI, and improving the overall layout of the job creation/editing forms.

### 2. User Stories
- As an **Admin**, I want to filter candidates by their "Negeri Kelahiran" so that I can prioritize locals or specific demographics.
- As an **Admin**, I want the "Lesen Memandu" section to be collapsible so that the form is less cluttered when licenses are not a requirement.
- As an **Admin**, I want the filtering criteria section to be at the bottom of the form and organized in a 3-column grid for better readability and flow.

### 3. Technical Requirements
- **Database:** No schema changes required (using existing `job_requirements` JSON column).
- **Frontend:**
  - **Job Create/Edit:**
    - Add "Negeri Kelahiran" dropdown (Standard Malaysian states + "Bukan Malaysia").
    - Implement JavaScript toggle for "Lesen Memandu" section.
    - Move "Kriteria Penapisan Calon" container to the bottom of the form.
    - Update CSS classes for 3-column grid layout (`md:grid-cols-3`).
  - **Application List:**
    - Update filtering logic to parse and compare `negeri_kelahiran`.

### 4. Implementation Plan
#### [MODIFY] `admin/job-create.php` & `admin/job-edit.php`
- **UI Layout:**
  - Move the entire "Kriteria Penapisan Calon" block to the end of the form (before the submit button).
  - Change grid classes from `md:grid-cols-2` to `md:grid-cols-3`.
- **New Field:**
  - Add "Negeri Kelahiran" dropdown.
- **Interactivity:**
  - Add a "Show/Hide" toggle button for the License section.
  - Default state: Collapsed (or Expanded if data exists in Edit mode).

#### [MODIFY] `admin/applications-list.php`
- **Logic:**
  - Retrieve `negeri_kelahiran` from `job_requirements`.
  - Compare with applicant's `negeri_kelahiran` (case-insensitive).
  - Include in "Ideal Candidate" validation.

### 5. Verification
- **Create Job:** Verify layout changes and new field.
- **Save/Edit:** Ensure `negeri_kelahiran` is saved and retrieved correctly.
- **Filtering:** Verify that an applicant with the matching state is marked as "Ideal".
