# Testing File Naming Convention - Implementation Summary

## ✅ Completed

Successfully added testing file naming convention to project documentation and rules.

---

## 📋 New Rules Added

### **MANDATORY Testing File Naming Standard**

**All testing, verification, and debugging files MUST use the `test_` prefix.**

---

## 📝 Naming Format

```
test_<filename>.<extension>
```

### **Examples:**

✅ **Correct:**
- `test_database_connection.php`
- `test_email_service.php`
- `test_payment_gateway.php`
- `test_file_upload.php`
- `test_recaptcha.php`
- `test_api_endpoint.php`

❌ **Incorrect:**
- `database_test.php`
- `email-test.php`
- `testing_payment.php`
- `debug.php` (use `debug_` prefix for debug files)

---

## 📂 File Organization

### **Where to Place Test Files:**

1. **`/tests/`** - Unit and integration tests
   - Permanent test suites
   - Automated testing scripts

2. **`/tools/`** - Utility and verification scripts
   - Database verification
   - Schema validation
   - Migration testing

3. **Root directory** - Quick debugging scripts (temporary only)
   - Must be removed before committing
   - For local development only

---

## 🛡️ Git Protection

### **Updated `.gitignore`**

Added automatic exclusion for test files:

```gitignore
# Testing and debugging files
test_*.php
test_*.js
test_*.html
test_*.sql
debug_*.php
debug_*.log
```

**What this means:**
- ✅ Test files won't be accidentally committed
- ✅ Sensitive test data stays local
- ✅ Cleaner repository
- ⚠️ If you NEED to commit a test file, use `git add -f test_file.php`

---

## 📐 Test File Structure Template

```php
<?php
/**
 * @FileID: test_<identifier>
 * @Module: Testing
 * @Author: [Your Name]
 * @Purpose: [What this test verifies]
 * @LastModified: YYYY-MM-DD
 * @SecurityTag: testing
 */

// Test configuration
define('TEST_MODE', true);

// Your test code here
echo "Testing: [Feature Name]\n";

// Test logic
try {
    // Test implementation
    echo "✅ Test passed\n";
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
}
```

---

## ✅ DO's and ❌ DON'Ts

### **DO:**
1. ✅ Use `test_` prefix for ALL testing files
2. ✅ Place test files in appropriate directories
3. ✅ Add sensitive test files to `.gitignore`
4. ✅ Document permanent test files in `/docs/testing.md`
5. ✅ Archive old test files in `/tests/archive/`

### **DON'T:**
1. ❌ Commit test files to production branches without review
2. ❌ Leave test files in production deployments
3. ❌ Use random naming for test files
4. ❌ Store sensitive data in test files
5. ❌ Mix test code with production code

---

## 🗂️ Other File Naming Conventions

### **Production Files:**
- Descriptive, lowercase with hyphens: `application-status.php`
- Controllers: `<Name>Controller.php` (PascalCase)
- Models: `<Name>Model.php` (PascalCase)
- Views: `<name>-view.php` (lowercase with hyphens)

### **Testing Files:**
- Always prefix with `test_`: `test_feature_name.php`

### **Debug Files:**
- Always prefix with `debug_`: `debug_output.log`

### **Utility Scripts:**
- Place in `/tools/` directory
- Use descriptive names: `update-schema.php`, `fix-database.php`

### **Migration Scripts:**
- Place in `/tools/` or `/migrations/`
- Use date prefix: `2025-11-22_add_sijil_tambahan.sql`
- Or descriptive names: `add-sijil-tambahan-column.sql`

---

## 📄 Files Updated

1. ✅ `docs/DOCUMENTATION_REQUIREMENTS.md` - Added testing section
2. ✅ `.gitignore` - Added test file exclusions
3. ✅ `docs/changelog.md` - Documented the change

---

## 🔄 Git Status

**Branch:** `dev`

**Commit:**
```
dfee3bf - docs: add testing file naming convention and update gitignore
```

**Pushed to:** `origin/dev`

---

## 🎯 Benefits

1. **Consistency** - All test files follow the same naming pattern
2. **Safety** - Automatic gitignore prevents accidental commits
3. **Organization** - Easy to identify test vs production files
4. **Cleanup** - Simple to find and remove test files
5. **Security** - Test files with sensitive data stay local

---

## 📚 Documentation Location

Full documentation available at:
- **`docs/DOCUMENTATION_REQUIREMENTS.md`** - Complete testing standards
- Section: "Testing and File Naming Conventions"

---

## 🚀 Next Steps

1. **Review** existing test files and rename if needed
2. **Follow** the convention for all new test files
3. **Clean up** any old test files not following the convention
4. **Document** permanent test files in `/docs/testing.md`

---

**Created:** 2025-11-22  
**Author:** Nefi  
**Status:** Active  
**Branch:** dev
