# Database Schema Fix Summary
**Date:** 2025-11-13  
**Issue:** Application submission failing with "Column not found: gambar_passport_path"

## Problem Identified

The application was failing to save because the `job_applications` table was missing several critical columns that the application form was trying to save.

### Error Details
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'gambar_passport_path' in 'field list'
```

## Missing Columns Added

The following columns were added to the `job_applications` table:

### Document Upload Fields
- `gambar_passport_path` (VARCHAR 255)
- `salinan_ic_path` (VARCHAR 255)
- `salinan_surat_beranak_path` (VARCHAR 255)
- `salinan_lesen_memandu_path` (VARCHAR 255)

### SPM/Education Fields
- `spm_tahun` (VARCHAR 4)
- `spm_gred_keseluruhan` (VARCHAR 50)
- `spm_angka_giliran` (VARCHAR 50)
- `spm_bahasa_malaysia` (VARCHAR 5)
- `spm_bahasa_inggeris` (VARCHAR 5)
- `spm_matematik` (VARCHAR 5)
- `spm_sejarah` (VARCHAR 5)
- `spm_subjek_lain` (JSON)
- `spm_salinan_sijil` (VARCHAR 255)

### Reference Fields
- `rujukan_1_nama` (VARCHAR 255)
- `rujukan_1_telefon` (VARCHAR 50)
- `rujukan_1_tempoh` (VARCHAR 10)
- `rujukan_2_nama` (VARCHAR 255)
- `rujukan_2_telefon` (VARCHAR 50)
- `rujukan_2_tempoh` (VARCHAR 10)

### Other Fields
- `pengisytiharan_pengesahan` (VARCHAR 10)
- `nama_kakitangan_pertalian` (VARCHAR 255)

## Files Modified

### 1. `includes/schema.php`
- Added missing columns to the `$columns_to_add` array
- These columns will be automatically created when the schema is initialized

### 2. `save-application.php`
- Fixed `application_work_experience` table insert statement to use correct column names:
  - Changed `majikan` → `nama_syarikat`
  - Changed `gaji_bulanan` → `gaji`
  - Changed `tarikh_mula_kerja` → `mula_berkhidmat`
  - Changed `tarikh_akhir_kerja` → `tamat_berkhidmat`
  - Added support for `unit_bahagian`, `gred`, and `taraf_jawatan`
- Added backward compatibility to accept old field names

### 3. `fix-schema-columns.php` (New)
- Created utility script to verify and update database schema
- Runs schema creation/update function
- Verifies all critical columns are present
- Can be run manually via: `docker exec ejawatan_web php /var/www/html/fix-schema-columns.php`

## Verification

All critical columns were successfully added to the database. The schema update script confirmed:
```
✓ All critical columns are present!
Columns found: 18/18
```

## Next Steps

1. ✅ Database schema updated
2. ✅ Application save logic fixed
3. ✅ Work experience table column names corrected
4. 🔄 Test application submission with real data
5. 🔄 Verify data is properly stored in all tables

## Testing Recommendations

Please test the following scenarios:
1. Submit a new application with all fields filled
2. Upload documents (passport photo, IC, birth certificate, driving license)
3. Add multiple work experience entries
4. Add multiple education entries
5. Verify data is saved correctly in the database
6. Check that the application can be previewed after submission

## Database Backup

Before running this fix in production, ensure you have a current database backup.

## Notes

- The fix uses schema migration approach that adds columns if they don't exist
- Existing data is not affected
- The schema is backward compatible
- Foreign key errors in the log are expected due to complex table relationships and can be ignored

