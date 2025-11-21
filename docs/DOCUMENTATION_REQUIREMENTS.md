# Documentation Update Requirements

## Mandatory Documentation Updates

Every time code is updated, modified, or new features are added, the following documentation MUST be updated:

### 1. Function Documentation (`/docs/functions.md`)
**When to update:**
- Adding a new function, method, or class
- Modifying function parameters or return values
- Changing function behavior or logic
- Deprecating or removing functions

**What to include:**
- Function/method name and signature
- Purpose and description
- Parameters (name, type, description)
- Return value (type, description)
- Usage examples
- Related modules/files
- Date of last modification
- Author

**Example:**
```markdown
### saveEducation()
**File:** `controllers/ApplicationSaveController.php`
**Purpose:** Saves education data to the database
**Parameters:**
- `$postData` (array): POST data containing education entries
- `$application_id` (int): Application ID
- `$application_reference` (string): Application reference number
**Returns:** void
**Last Modified:** 2025-11-22
**Author:** Nefi
```

---

### 2. Changelog (`/docs/changelog.md`)
**When to update:**
- Every code change, no matter how small
- Bug fixes
- New features
- Performance improvements
- Breaking changes

**Format:**
```markdown
## [Date: YYYY-MM-DD]

### Added
- Description of new features

### Changed
- Description of modifications to existing features

### Fixed
- Description of bug fixes

### Removed
- Description of removed features

### Security
- Security-related changes
```

**Example:**
```markdown
## [Date: 2025-11-22]

### Added
- Added `sijil_tambahan` field to education section for additional certificate uploads
- Created SQL migration script for database schema update

### Changed
- Modified education form layout - institution name now spans full width
- Updated ApplicationSaveController to handle additional certificate field
- Updated DataFetcher to retrieve sijil_tambahan from database

### Fixed
- N/A
```

---

### 3. Technical Documentation (`/docs/technical/`)
**When to update:**
- Architecture changes
- Database schema modifications
- API endpoint changes
- Integration updates
- Deployment process changes

**What to document:**
- System architecture diagrams
- Database schema (ERD)
- API specifications
- Integration points
- Deployment procedures
- Environment configurations

**File structure:**
```
/docs/technical/
├── architecture.md       # System architecture overview
├── database-schema.md    # Complete database schema documentation
├── api-endpoints.md      # API documentation
├── integrations.md       # Third-party integrations
└── deployment.md         # Deployment procedures
```

---

## Documentation Update Workflow

### Step-by-Step Process:

1. **Before coding:**
   - Review existing documentation
   - Plan what documentation will need updates

2. **During coding:**
   - Add inline code comments
   - Note breaking changes
   - Document complex logic

3. **After coding (MANDATORY):**
   - Update `/docs/functions.md` with new/modified functions
   - Add entry to `/docs/changelog.md` with date and changes
   - Update technical documentation if architecture/schema changed
   - Update README.md if user-facing changes
   - Create migration scripts for database changes

4. **Before committing:**
   - Verify all documentation is updated
   - Check for broken links in documentation
   - Ensure examples are accurate

---

## Documentation Standards

### General Rules:
- Use clear, concise language
- Include code examples where applicable
- Keep documentation up-to-date with code
- Use proper markdown formatting
- Include dates for all changes
- Attribute changes to authors

### File Headers:
Every documentation file should have:
```markdown
# [Document Title]

**Last Updated:** YYYY-MM-DD  
**Author:** [Name]  
**Status:** [Active/Deprecated/Draft]

## Table of Contents
- [Section 1](#section-1)
- [Section 2](#section-2)
```

---

## Enforcement

### Automated Checks:
- Pre-commit hooks should verify documentation updates
- CI/CD pipeline should check for changelog entries
- Code reviews must verify documentation completeness

### Manual Review:
- Every pull request must include documentation updates
- Documentation changes should be reviewed alongside code
- Missing documentation is grounds for PR rejection

---

## Documentation Locations

| Type | Location | Purpose |
|------|----------|---------|
| Function Reference | `/docs/functions.md` | Complete function/method documentation |
| Changelog | `/docs/changelog.md` | Chronological list of all changes |
| Technical Docs | `/docs/technical/` | Architecture, database, API specs |
| User Guides | `/docs/guides/` | End-user documentation |
| API Docs | `/docs/api/` | API endpoint documentation |
| README | `/README.md` | Project overview and quick start |

---

## Templates

### Function Documentation Template:
```markdown
### [FunctionName]
**File:** `path/to/file.php`  
**Purpose:** Brief description  
**Parameters:**
- `$param1` (type): Description
- `$param2` (type): Description

**Returns:** Return type and description  
**Example:**
```php
// Usage example
$result = functionName($param1, $param2);
```
**Last Modified:** YYYY-MM-DD  
**Author:** Name
```

### Changelog Entry Template:
```markdown
## [Date: YYYY-MM-DD]

### Added
- Feature description

### Changed
- Modification description

### Fixed
- Bug fix description
```

---

**Remember:** Documentation is code. Treat it with the same care and attention as your source code.
