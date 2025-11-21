<?php
/**
 * @FileID: application_save_controller
 * @Module: Application Save Controller
 * @Author: Nefi
 * @LastModified: 2025-11-14
 * @SecurityTag: validated
 */

class ApplicationSaveController {
    private $pdo;
    private $config;
    private bool $languageSchemaPrepared = false;
    private bool $computerSchemaPrepared = false;
    private bool $spmSchemaPrepared = false;
    
    public function __construct($pdo, $config) {
        $this->pdo = $pdo;
        $this->config = $config;
    }
    
    /**
     * Save application data to application_application_main and separate tables
     */
    public function saveApplication($postData, $filesData = []) {
        $errors = [];
        
        try {
            // Ensure any schema adjustments happen outside of transactions
            $this->ensureSchemaPreconditions($postData);

            // Begin transaction
            $this->pdo->beginTransaction();
            
            // Server-side handling of alamat_surat_sama checkbox
            if (!empty($postData['alamat_surat_sama'])) {
                // Copy permanent address to correspondence address
                $postData['alamat_surat'] = $postData['alamat_tetap'] ?? '';
                $postData['bandar_surat'] = $postData['bandar_tetap'] ?? '';
                $postData['negeri_surat'] = $postData['negeri_tetap'] ?? '';
                $postData['poskod_surat'] = $postData['poskod_tetap'] ?? '';
                error_log('Server-side: Copied permanent address to correspondence address due to checkbox');
            }
            
            // Validate required fields
            $required_fields = ['nama_penuh', 'nombor_ic', 'email', 'job_id'];
            foreach ($required_fields as $field) {
                if (empty($postData[$field])) {
                    $errors[] = "Medan mandatori `{$field}` tidak diisi.";
                }
            }
            
            // Validate address fields
            if (empty($postData['alamat_tetap'])) {
                $errors[] = "Alamat tetap adalah wajib.";
            }
            if (empty($postData['alamat_surat']) && empty($postData['alamat_surat_sama'])) {
                $errors[] = "Alamat surat-menyurat adalah wajib atau pilih 'Sama seperti alamat tetap'.";
            }
            
            if (!empty($errors)) {
                throw new Exception(implode('; ', $errors));
            }
            
            $is_edit = !empty($postData['edit']);
            $application_id = !empty($postData['application_id']) ? (int)$postData['application_id'] : null;
            $application_reference = $postData['application_reference'] ?? null;
            
            // Check for duplicates (only for new applications)
            if (!$is_edit) {
                $stmt = $this->pdo->prepare('SELECT id FROM application_application_main WHERE job_id = ? AND nombor_ic = ? LIMIT 1');
                $stmt->execute([$postData['job_id'], trim($postData['nombor_ic'])]);
                if ($stmt->fetch()) {
                    throw new Exception('Permohonan untuk jawatan ini dengan NRIC tersebut sudah wujud.');
                }
            }
            
            // Prepare main application data for application_application_main table
            $main_data = $this->prepareMainApplicationData($postData);
            
            // Debug: Log what data we're trying to save
            error_log('Main application data to save: ' . json_encode(array_keys($main_data)));
            error_log('Address data: alamat_tetap=' . ($main_data['alamat_tetap'] ?? 'NULL') . ', alamat_surat=' . ($main_data['alamat_surat'] ?? 'NULL') . ', alamat_surat_sama=' . ($main_data['alamat_surat_sama'] ?? 'NULL'));
            
            // Save or update main application
            if ($is_edit && $application_id) {
                $application_reference = $this->updateMainApplication($application_id, $main_data);
            } else {
                list($application_id, $application_reference) = $this->insertMainApplication($main_data);
            }
            
            $uploadUpdates = $this->handleFileUploads($filesData, $application_id, $application_reference);
            if (is_array($uploadUpdates) && !empty($uploadUpdates)) {
                $postData = $this->mergePostData($postData, $uploadUpdates);
            }
            $this->saveSeparateTableData($postData, $application_id, $application_reference, $is_edit);
            
            // Commit transaction
            $this->pdo->commit();
            
            return [
                'success' => true,
                'application_id' => $application_id,
                'application_reference' => $application_reference
            ];
            
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('Application save error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Prepare main application data for application_application_main table
     */
    private function prepareMainApplicationData($postData) {
        // Get table columns for application_application_main
        try {
            $table_columns = array_map(fn($col) => $col['Field'], 
                $this->pdo->query("DESCRIBE application_application_main")->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            // Fallback if table doesn't exist yet - use schema-based columns
            $table_columns = [
                'id', 'job_id', 'application_reference', 'nama_penuh', 'nombor_ic', 'nombor_surat_beranak',
                'email', 'agama', 'taraf_perkahwinan', 'jantina', 'tarikh_lahir', 'umur', 'negeri_kelahiran',
                'bangsa', 'warganegara', 'tempoh_bermastautin_selangor', 'nombor_telefon', 'alamat_tetap',
                'poskod_tetap', 'bandar_tetap', 'negeri_tetap', 'alamat_surat', 'poskod_surat', 'bandar_surat',
                'negeri_surat', 'alamat_surat_sama', 'lesen_memandu_set', 'tarikh_tamat_lesen', 'nama_pasangan',
                'telefon_pasangan', 'bilangan_anak', 'status_pasangan', 'pekerjaan_pasangan', 
                'nama_majikan_pasangan', 'telefon_pejabat_pasangan', 'alamat_majikan_pasangan',
                'poskod_majikan_pasangan', 'bandar_majikan_pasangan', 'negeri_majikan_pasangan',
                'pekerja_perkhidmatan_awam', 'pekerja_perkhidmatan_awam_nyatakan', 'pertalian_kakitangan',
                'pertalian_kakitangan_nyatakan', 'pernah_bekerja_mphs', 'pernah_bekerja_mphs_nyatakan',
                'tindakan_tatatertib', 'tindakan_tatatertib_nyatakan', 'kesalahan_undangundang',
                'kesalahan_undangundang_nyatakan', 'muflis', 'muflis_nyatakan', 'pengistiharan',
                'status', 'submission_locked', 'created_at', 'updated_at', 'gambar_passport_path',
                'salinan_ic_path', 'salinan_surat_beranak_path', 'salinan_lesen_memandu_path'
            ];
        }
        
        $main_data = [];
        foreach ($table_columns as $column) {
            if (isset($postData[$column])) {
                $main_data[$column] = $postData[$column];
            }
        }
        
        // Remove columns that don't exist in application_application_main
        $exclude_columns = ['id', 'created_at', 'updated_at']; // These are handled separately
        foreach ($exclude_columns as $col) {
            unset($main_data[$col]);
        }
        
        // Set default values
        $main_data['job_id'] = (int)($postData['job_id'] ?? 0);
        $main_data['status'] = $postData['status'] ?? 'PENDING';
        $main_data['submission_locked'] = 0;
        $main_data['updated_at'] = date('Y-m-d H:i:s');
        
        // Handle license data (convert array to comma-separated for main table)
        if (isset($postData['lesen_memandu']) && is_array($postData['lesen_memandu'])) {
            $licenses = $this->formatArrayValues($postData['lesen_memandu']);
            $main_data['lesen_memandu_set'] = !empty($licenses) ? implode(',', $licenses) : null;
        } elseif (isset($postData['lesen_memandu'])) {
            $main_data['lesen_memandu_set'] = $this->formatStringValue($postData['lesen_memandu']);
        }
        
        // Handle checkbox values
        $main_data['alamat_surat_sama'] = !empty($postData['alamat_surat_sama']) ? 1 : 0;
        $main_data['pengistiharan'] = (
            !empty($postData['pengistiharan']) ||
            strtoupper((string)($postData['pengisytiharan_pengesahan'] ?? '')) === 'YA'
        ) ? 1 : 0;
        
        // Handle alamat_surat_sama logic - copy permanent address to correspondence address
        if (!empty($postData['alamat_surat_sama'])) {
            $main_data['alamat_surat'] = $postData['alamat_tetap'] ?? '';
            $main_data['bandar_surat'] = $postData['bandar_tetap'] ?? '';
            $main_data['negeri_surat'] = $postData['negeri_tetap'] ?? '';
            $main_data['poskod_surat'] = $postData['poskod_tetap'] ?? '';
        }
        
        // Ensure required address fields have values
        if (empty($main_data['alamat_surat'])) {
            if (!empty($main_data['alamat_tetap'])) {
                // Use permanent address as fallback
                $main_data['alamat_surat'] = $main_data['alamat_tetap'];
                $main_data['bandar_surat'] = $main_data['bandar_tetap'] ?? '';
                $main_data['negeri_surat'] = $main_data['negeri_tetap'] ?? '';
                $main_data['poskod_surat'] = $main_data['poskod_tetap'] ?? '';
            } else {
                // Set empty string as default to avoid NULL constraint issues
                $main_data['alamat_surat'] = '';
                $main_data['bandar_surat'] = '';
                $main_data['negeri_surat'] = '';
                $main_data['poskod_surat'] = '';
            }
        }
        
        // Ensure other required fields have default values
        $required_fields_defaults = [
            'nama_penuh' => '',
            'nombor_ic' => '',
            'email' => '',
            'alamat_tetap' => '',
            'bandar_tetap' => '',
            'negeri_tetap' => '',
            'poskod_tetap' => ''
        ];
        
        foreach ($required_fields_defaults as $field => $default) {
            if (!isset($main_data[$field]) || $main_data[$field] === null) {
                $main_data[$field] = $default;
            }
        }

        $jid = (int)($postData['job_id'] ?? 0);
        if ($jid) {
            try {
                $stmt = $this->pdo->prepare('SELECT job_code, job_title FROM job_postings WHERE id = ? LIMIT 1');
                $stmt->execute([$jid]);
                $job = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($job) {
                    $main_data['job_code'] = $job['job_code'] ?? ($postData['job_code'] ?? null);
                    $main_data['jawatan_dipohon'] = $job['job_title'] ?? ($postData['jawatan_dipohon'] ?? null);
                } else {
                    $stmt2 = $this->pdo->prepare('SELECT job_code, job_title FROM jobs WHERE id = ? LIMIT 1');
                    $stmt2->execute([$jid]);
                    $job2 = $stmt2->fetch(PDO::FETCH_ASSOC);
                    if ($job2) {
                        $main_data['job_code'] = $job2['job_code'] ?? ($postData['job_code'] ?? null);
                        $main_data['jawatan_dipohon'] = $job2['job_title'] ?? ($postData['jawatan_dipohon'] ?? null);
                    }
                }
            } catch (Throwable $e) {}
        }

        // Normalize string casing and encode arrays
        foreach ($main_data as $column => $value) {
            if (is_array($value)) {
                $main_data[$column] = json_encode($this->uppercaseRecursive($value), JSON_UNESCAPED_UNICODE);
            } elseif (is_string($value)) {
                $main_data[$column] = $this->formatStringValue($value, $this->shouldUppercaseField($column));
            }
        }
        
        return $main_data;
    }
    
    /**
     * Insert new main application
     */
    private function insertMainApplication($main_data) {
        $main_data['created_at'] = date('Y-m-d H:i:s');
        $main_data['application_reference'] = 'APP-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
        
        $columns = array_keys($main_data);
        $placeholders = ':' . implode(', :', $columns);
        
        $stmt = $this->pdo->prepare("INSERT INTO application_application_main (" . implode(',', $columns) . ") VALUES (" . $placeholders . ")");
        $stmt->execute($main_data);
        
        $application_id = $this->pdo->lastInsertId();
        return [$application_id, $main_data['application_reference']];
    }
    
    /**
     * Update existing main application
     */
    private function updateMainApplication($application_id, $main_data) {
        unset($main_data['created_at']); // Don't update creation time
        
        $update_fields = [];
        foreach ($main_data as $key => $val) {
            $update_fields[] = "`{$key}` = :{$key}";
        }
        
        $stmt = $this->pdo->prepare("UPDATE application_application_main SET " . implode(', ', $update_fields) . " WHERE id = :id");
        $main_data['id'] = $application_id;
        $stmt->execute($main_data);
        
        // Get application reference
        $stmt = $this->pdo->prepare("SELECT application_reference FROM application_application_main WHERE id = ?");
        $stmt->execute([$application_id]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Handle file uploads
     */
    private function handleFileUploads($filesData, $application_id, $application_reference) {
        $updates = [];
        
        // Debug: Log what files we received
        error_log("handleFileUploads called with application_id={$application_id}, reference={$application_reference}");
        error_log("Files received: " . json_encode(array_keys($filesData)));
        
        // Load FileUploader module
        if (!function_exists('uploadApplicationDocument')) {
            $impl = __DIR__ . '/../modules/FileUploaderImplementation.php';
            if (is_file($impl)) { 
                require_once $impl;
                error_log("FileUploaderImplementation loaded");
            } else {
                error_log("ERROR: FileUploaderImplementation.php not found at {$impl}");
            }
        }
        
        if (!function_exists('uploadApplicationDocument')) {
            error_log("ERROR: uploadApplicationDocument function not available after loading");
            return $updates;
        }
        
        $mainFields = ['gambar_passport', 'salinan_ic', 'salinan_surat_beranak', 'salinan_lesen_memandu'];
        foreach ($mainFields as $f) {
            if (isset($filesData[$f])) {
                $err = (int)($filesData[$f]['error'] ?? UPLOAD_ERR_NO_FILE);
                error_log("Processing {$f}: error={$err}, size=" . ($filesData[$f]['size'] ?? 0));
                
                if ($err === UPLOAD_ERR_OK && function_exists('uploadApplicationDocument')) {
                    $existingPath = null;
                    try {
                        $col = $f . '_path';
                        $stmt = $this->pdo->prepare("SELECT `{$col}` FROM application_application_main WHERE id = ? LIMIT 1");
                        $stmt->execute([$application_id]);
                        $existingPath = $stmt->fetchColumn();
                    } catch (Throwable $e) {}
                    $p = uploadApplicationDocument($f, $application_reference, $f);
                    if ($p) {
                        if (!empty($existingPath) && $existingPath !== $p) {
                            $this->safeDeleteUpload($existingPath);
                        }
                        // Update database immediately with the uploaded file path
                        $pathColumn = $f . '_path';
                        $updateSql = "UPDATE application_application_main SET `{$pathColumn}` = ? WHERE id = ?";
                        $this->pdo->prepare($updateSql)->execute([$p, $application_id]);
                        $updates[$f] = $p;
                        error_log("SUCCESS: Uploaded {$f} to {$p} and stored in {$pathColumn}");
                    } else {
                        error_log("ERROR: Upload failed for {$f} - uploadApplicationDocument returned null");
                    }
                } elseif ($err !== UPLOAD_ERR_NO_FILE) {
                    error_log("ERROR: Upload error code {$err} for {$f}");
                }
            }
        }
        if (isset($filesData['salinan_kad_oku'])) {
            $err = (int)($filesData['salinan_kad_oku']['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($err === UPLOAD_ERR_OK && function_exists('uploadApplicationDocument')) {
                $p = uploadApplicationDocument('salinan_kad_oku', $application_reference, 'salinan_kad_oku');
                if ($p) {
                    $updates['salinan_kad_oku'] = $p;
                    error_log('Uploaded salinan_kad_oku to ' . $p);
                } else {
                    error_log('Upload failed for salinan_kad_oku');
                }
            } elseif ($err !== UPLOAD_ERR_NO_FILE) {
                error_log('Upload error code ' . $err . ' for salinan_kad_oku');
            }
        }
        if (isset($filesData['spm_salinan_sijil'])) {
            $err = (int)($filesData['spm_salinan_sijil']['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($err === UPLOAD_ERR_OK && function_exists('uploadApplicationDocument')) {
                $existingSpmPaths = [];
                try {
                    $stmt = $this->pdo->prepare("SELECT salinan_sijil_filename FROM application_spm_results WHERE application_reference = ? AND salinan_sijil_filename IS NOT NULL AND salinan_sijil_filename != ''");
                    $stmt->execute([$application_reference]);
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $existingSpmPaths[] = $row['salinan_sijil_filename']; }
                } catch (Throwable $e) {}
                $p = uploadApplicationDocument('spm_salinan_sijil', $application_reference, 'spm_salinan_sijil');
                if ($p) {
                    foreach ($existingSpmPaths as $old) { $this->safeDeleteUpload($old); }
                    $updates['spm_salinan_sijil_path'] = $p;
                    error_log('Uploaded spm_salinan_sijil to ' . $p);
                } else {
                    error_log('Upload failed for spm_salinan_sijil');
                }
            } elseif ($err !== UPLOAD_ERR_NO_FILE) {
                error_log('Upload error code ' . $err . ' for spm_salinan_sijil');
            }
        }
        if (isset($filesData['persekolahan']) && is_array($filesData['persekolahan'])) {
            $names = $filesData['persekolahan']['name'] ?? [];
            $types = $filesData['persekolahan']['type'] ?? [];
            $tmps = $filesData['persekolahan']['tmp_name'] ?? [];
            $errs = $filesData['persekolahan']['error'] ?? [];
            $sizes = $filesData['persekolahan']['size'] ?? [];
            foreach ($names as $i => $obj) {
                $n = $obj['sijil'] ?? null;
                if ($n === null || $n === '') { continue; }
                $t = $types[$i]['sijil'] ?? null;
                $tmp = $tmps[$i]['sijil'] ?? null;
                $e = (int)($errs[$i]['sijil'] ?? UPLOAD_ERR_NO_FILE);
                $s = (int)($sizes[$i]['sijil'] ?? 0);
                if ($e === UPLOAD_ERR_OK && $tmp && function_exists('uploadApplicationDocument')) {
                    $tempField = 'persekolahan_' . $i . '_sijil';
                    $_FILES[$tempField] = ['name' => $n, 'type' => $t, 'tmp_name' => $tmp, 'error' => $e, 'size' => $s];
                    $p = uploadApplicationDocument($tempField, $application_reference, 'persekolahan_' . $i);
                    unset($_FILES[$tempField]);
                    if ($p) {
                        $updates['persekolahan'][$i]['sijil_path'] = $p;
                        error_log('Uploaded persekolahan[' . $i . '] sijil to ' . $p);
                    } else {
                        error_log('Upload failed for persekolahan[' . $i . '] sijil');
                    }
                } elseif ($e !== UPLOAD_ERR_NO_FILE) {
                    error_log('Upload error code ' . $e . ' for persekolahan[' . $i . '] sijil');
                }
            }
        }
        if (isset($filesData['badan_profesional']) && is_array($filesData['badan_profesional'])) {
            $names = $filesData['badan_profesional']['name'] ?? [];
            $types = $filesData['badan_profesional']['type'] ?? [];
            $tmps = $filesData['badan_profesional']['tmp_name'] ?? [];
            $errs = $filesData['badan_profesional']['error'] ?? [];
            $sizes = $filesData['badan_profesional']['size'] ?? [];
            foreach ($names as $i => $obj) {
                $n = $obj['salinan_sijil'] ?? null;
                if ($n === null || $n === '') { continue; }
                $t = $types[$i]['salinan_sijil'] ?? null;
                $tmp = $tmps[$i]['salinan_sijil'] ?? null;
                $e = (int)($errs[$i]['salinan_sijil'] ?? UPLOAD_ERR_NO_FILE);
                $s = (int)($sizes[$i]['salinan_sijil'] ?? 0);
                if ($e === UPLOAD_ERR_OK && $tmp && function_exists('uploadApplicationDocument')) {
                    $tempField = 'badan_profesional_' . $i . '_salinan_sijil';
                    $_FILES[$tempField] = ['name' => $n, 'type' => $t, 'tmp_name' => $tmp, 'error' => $e, 'size' => $s];
                    $p = uploadApplicationDocument($tempField, $application_reference, 'badan_profesional_' . $i);
                    unset($_FILES[$tempField]);
                    if ($p) {
                        $updates['badan_profesional'][$i]['salinan_sijil_path'] = $p;
                        error_log('Uploaded badan_profesional[' . $i . '] salinan_sijil to ' . $p);
                    } else {
                        error_log('Upload failed for badan_profesional[' . $i . '] salinan_sijil');
                    }
                } elseif ($e !== UPLOAD_ERR_NO_FILE) {
                    error_log('Upload error code ' . $e . ' for badan_profesional[' . $i . '] salinan_sijil');
                }
            }
        }
        if (isset($filesData['kegiatan_luar']) && is_array($filesData['kegiatan_luar'])) {
            $names = $filesData['kegiatan_luar']['name'] ?? [];
            $types = $filesData['kegiatan_luar']['type'] ?? [];
            $tmps = $filesData['kegiatan_luar']['tmp_name'] ?? [];
            $errs = $filesData['kegiatan_luar']['error'] ?? [];
            $sizes = $filesData['kegiatan_luar']['size'] ?? [];
            foreach ($names as $i => $obj) {
                $n = $obj['salinan_sijil'] ?? null;
                if ($n === null || $n === '') { continue; }
                $t = $types[$i]['salinan_sijil'] ?? null;
                $tmp = $tmps[$i]['salinan_sijil'] ?? null;
                $e = (int)($errs[$i]['salinan_sijil'] ?? UPLOAD_ERR_NO_FILE);
                $s = (int)($sizes[$i]['salinan_sijil'] ?? 0);
                if ($e === UPLOAD_ERR_OK && $tmp && function_exists('uploadApplicationDocument')) {
                    $tempField = 'kegiatan_luar_' . $i . '_salinan_sijil';
                    $_FILES[$tempField] = ['name' => $n, 'type' => $t, 'tmp_name' => $tmp, 'error' => $e, 'size' => $s];
                    $p = uploadApplicationDocument($tempField, $application_reference, 'kegiatan_luar_' . $i);
                    unset($_FILES[$tempField]);
                    if ($p) {
                        $updates['kegiatan_luar'][$i]['salinan_sijil_path'] = $p;
                        error_log('Uploaded kegiatan_luar[' . $i . '] salinan_sijil to ' . $p);
                    } else {
                        error_log('Upload failed for kegiatan_luar[' . $i . '] salinan_sijil');
                    }
                } elseif ($e !== UPLOAD_ERR_NO_FILE) {
                    error_log('Upload error code ' . $e . ' for kegiatan_luar[' . $i . '] salinan_sijil');
                }
            }
        }
        return $updates;
    }

    private function safeDeleteUpload($path) {
        try {
            if (!$path) { return; }
            if (preg_match('/^https?:\/\//i', (string)$path)) { return; }
            $root = realpath(dirname(__DIR__));
            $candidate = realpath($root . DIRECTORY_SEPARATOR . ltrim($path, '\\/'));
            if ($candidate && strpos($candidate, $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'applications') === 0) {
                if (is_file($candidate)) { @unlink($candidate); }
            }
        } catch (Throwable $e) {}
    }

    private function mergePostData($base, $updates) {
        foreach ($updates as $k => $v) {
            if (is_array($v)) {
                $base[$k] = isset($base[$k]) && is_array($base[$k]) ? $this->mergePostData($base[$k], $v) : $v;
            } else {
                $base[$k] = $v;
            }
        }
        return $base;
    }
    
    /**
     * Save data to separate tables
     */
    private function saveSeparateTableData($postData, $application_id, $application_reference, $is_edit) {
        // If editing, clean up existing data first
        if ($is_edit) {
            $this->cleanupExistingData($application_reference);
        }
        
        // 1. Language Skills
        $this->saveLanguageSkills($postData, $application_id, $application_reference);
        
        // 2. Computer Skills
        $this->saveComputerSkills($postData, $application_id, $application_reference);
        
        // 3. Education
        $this->saveEducation($postData, $application_id, $application_reference);
        
        // 4. SPM Results
        $this->saveSpmResults($postData, $application_id, $application_reference);
        
        // 5. SPM Additional Subjects
        $this->saveSpmAdditionalSubjects($postData, $application_id, $application_reference);
        
        // 6. Professional Bodies
        $this->saveProfessionalBodies($postData, $application_id, $application_reference);
        
        // 7. Extracurricular Activities
        $this->saveExtracurricular($postData, $application_id, $application_reference);
        
        // 8. Work Experience
        $this->saveWorkExperience($postData, $application_id, $application_reference);
        
        // 9. References
        $this->saveReferences($postData, $application_id, $application_reference);
        
        // 10. Family Members
        $this->saveFamilyMembers($postData, $application_id, $application_reference);
        
        // 11. Health Data
        $this->saveHealthData($postData, $application_id, $application_reference);
    }
    
    /**
     * Clean up existing data for edit mode
     */
    private function cleanupExistingData($application_reference) {
        $tables = [
            'application_language_skills',
            'application_computer_skills', 
            'application_education',
            'application_spm_results',
            'application_spm_additional_subjects',
            'application_professional_bodies',
            'application_extracurricular',
            'application_work_experience',
            'application_references',
            'application_family_members',
            'application_health'
        ];
        
        foreach ($tables as $table) {
            try {
                $this->pdo->prepare("DELETE FROM {$table} WHERE application_reference = ?")->execute([$application_reference]);
            } catch (Exception $e) {
                error_log("Could not clean up table {$table}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Save language skills
     */
    private function saveLanguageSkills($postData, $application_id, $application_reference) {
        if (!isset($postData['kemahiran_bahasa']) || !is_array($postData['kemahiran_bahasa'])) return;
        
        // First, ensure the table has the correct structure
        $this->ensureLanguageSkillsTableStructure();
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO application_language_skills (application_reference, application_id, bahasa, tahap_lisan, tahap_penulisan, gred_spm) VALUES (?, ?, ?, ?, ?, ?)"
        );
        
        foreach ($postData['kemahiran_bahasa'] as $lang) {
            $bahasa = $this->formatStringValue($lang['bahasa'] ?? null);
            if ($bahasa === null) {
                continue;
            }

            $pertuturan = $this->formatStringValue($lang['pertuturan'] ?? null);
            $penulisan = $this->formatStringValue($lang['penulisan'] ?? null);
            $gredSpm = $this->formatStringValue($lang['gred_spm'] ?? null);

            $stmt->execute([
                $application_reference,
                $application_id,
                $bahasa,
                $pertuturan,
                $penulisan,
                $gredSpm
            ]);
        }
    }
    
    /**
     * Ensure language skills table has correct structure
     */
    private function ensureLanguageSkillsTableStructure() {
        if ($this->languageSchemaPrepared) {
            return;
        }
        try {
            // Check if table exists
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'application_language_skills'");
            if (!$stmt->fetch()) {
                // Create table if it doesn't exist
                $this->pdo->exec("CREATE TABLE application_language_skills (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    application_reference VARCHAR(50) NULL,
                    application_id INT NULL,
                    bahasa VARCHAR(50) NOT NULL,
                    tahap_lisan VARCHAR(50) NULL,
                    tahap_penulisan VARCHAR(50) NULL,
                    gred_spm VARCHAR(10) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX (application_reference),
                    INDEX (application_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                error_log("Created application_language_skills table");
                return;
            }
            
            // Add missing columns if they don't exist
            $columns_to_add = [
                'application_reference' => 'VARCHAR(50) NULL',
                'gred_spm' => 'VARCHAR(10) NULL'
            ];
            
            foreach ($columns_to_add as $column => $definition) {
                $stmt = $this->pdo->query("SHOW COLUMNS FROM application_language_skills LIKE '{$column}'");
                if (!$stmt->fetch()) {
                    $this->pdo->exec("ALTER TABLE application_language_skills ADD COLUMN {$column} {$definition}");
                    error_log("Added {$column} column to application_language_skills table");
                }
            }
            
            // Change ENUM columns to VARCHAR if needed
            $enum_columns = ['tahap_lisan', 'tahap_penulisan'];
            foreach ($enum_columns as $column) {
                $stmt = $this->pdo->query("SHOW COLUMNS FROM application_language_skills LIKE '{$column}'");
                $col_info = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($col_info && strpos($col_info['Type'], 'enum') !== false) {
                    $this->pdo->exec("ALTER TABLE application_language_skills MODIFY COLUMN {$column} VARCHAR(50) NULL");
                    error_log("Changed {$column} from ENUM to VARCHAR in application_language_skills table");
                }
            }
            
        } catch (Exception $e) {
            error_log("Error ensuring language skills table structure: " . $e->getMessage());
            throw $e;
        } finally {
            $this->languageSchemaPrepared = true;
        }
    }
    
    /**
     * Save computer skills
     */
    private function saveComputerSkills($postData, $application_id, $application_reference) {
        if (!isset($postData['kemahiran_komputer']) || !is_array($postData['kemahiran_komputer'])) return;
        
        // First, ensure the table has the correct structure
        $this->ensureComputerSkillsTableStructure();
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO application_computer_skills (application_reference, application_id, nama_perisian, tahap_kemahiran) VALUES (?, ?, ?, ?)"
        );
        
        foreach ($postData['kemahiran_komputer'] as $comp) {
            $namaPerisian = $this->formatStringValue($comp['nama_perisian'] ?? null);
            if ($namaPerisian === null) {
                continue;
            }

            $tahapKemahiran = $this->formatStringValue($comp['tahap_kemahiran'] ?? null);

            $stmt->execute([
                $application_reference,
                $application_id,
                $namaPerisian,
                $tahapKemahiran
            ]);
        }
    }
    
    /**
     * Ensure computer skills table has correct structure
     */
    private function ensureComputerSkillsTableStructure() {
        if ($this->computerSchemaPrepared) {
            return;
        }
        try {
            // Check if table exists
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'application_computer_skills'");
            if (!$stmt->fetch()) {
                // Create table if it doesn't exist
                $this->pdo->exec("CREATE TABLE application_computer_skills (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    application_reference VARCHAR(50) NULL,
                    application_id INT NULL,
                    nama_perisian VARCHAR(255) NOT NULL,
                    tahap_kemahiran VARCHAR(50) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX (application_reference),
                    INDEX (application_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                error_log("Created application_computer_skills table");
                return;
            }
            
            // Add application_reference column if missing
            $stmt = $this->pdo->query("SHOW COLUMNS FROM application_computer_skills LIKE 'application_reference'");
            if (!$stmt->fetch()) {
                $this->pdo->exec("ALTER TABLE application_computer_skills ADD COLUMN application_reference VARCHAR(50) NULL AFTER id");
                error_log("Added application_reference column to application_computer_skills table");
            }
            
            // Change tahap_kemahiran from ENUM to VARCHAR if needed
            $stmt = $this->pdo->query("SHOW COLUMNS FROM application_computer_skills LIKE 'tahap_kemahiran'");
            $col_info = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($col_info && strpos($col_info['Type'], 'enum') !== false) {
                $this->pdo->exec("ALTER TABLE application_computer_skills MODIFY COLUMN tahap_kemahiran VARCHAR(50) NULL");
                error_log("Changed tahap_kemahiran from ENUM to VARCHAR in application_computer_skills table");
            }
            
        } catch (Exception $e) {
            error_log("Error ensuring computer skills table structure: " . $e->getMessage());
            throw $e;
        } finally {
            $this->computerSchemaPrepared = true;
        }
    }
    
    /**
     * Save education data
     */
    private function saveEducation($postData, $application_id, $application_reference) {
        if (!isset($postData['persekolahan']) || !is_array($postData['persekolahan'])) return;
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO application_education (application_reference, application_id, nama_institusi, dari_tahun, hingga_tahun, kelayakan, pangkat_gred_cgpa, sijil_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        foreach ($postData['persekolahan'] as $edu) {
            $institusi = $this->formatStringValue($edu['institusi'] ?? null);
            if ($institusi === null) {
                continue;
            }

            $dariTahun = $this->formatStringValue($edu['dari_tahun'] ?? null);
            $hinggaTahun = $this->formatStringValue($edu['hingga_tahun'] ?? null);
            $kelayakan = $this->formatStringValue($edu['kelayakan'] ?? null);
            $gred = $this->formatStringValue($edu['gred'] ?? null);
            $sijilPath = $this->formatStringValue($edu['sijil_path'] ?? null, false);

            $stmt->execute([
                $application_reference,
                $application_id,
                $institusi,
                $dariTahun,
                $hinggaTahun,
                $kelayakan,
                $gred,
                $sijilPath
            ]);
        }
    }
    
    /**
     * Save SPM results
     */
    private function saveSpmResults($postData, $application_id, $application_reference) {
        // Ensure table structure is compatible
        $this->ensureSpmResultsTableStructure();

        // Save main SPM data
        $tahunSpm = $this->formatStringValue($postData['spm_tahun'] ?? null);
        if ($tahunSpm !== null) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO application_spm_results (application_reference, application_id, tahun, gred_keseluruhan, angka_giliran, bahasa_malaysia, bahasa_inggeris, matematik, sejarah, subjek_lain, gred_subjek_lain, salinan_sijil_filename) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            
            // Handle subjek_lain array
            $subjek_lain = '';
            $gred_subjek_lain = '';
            if (isset($postData['spm_subjek_lain']) && is_array($postData['spm_subjek_lain'])) {
                $subjects = [];
                $grades = [];
                foreach ($postData['spm_subjek_lain'] as $subj) {
                    $subjectName = $this->formatStringValue($subj['subjek'] ?? null);
                    if ($subjectName === null) {
                        continue;
                    }
                    $subjects[] = $subjectName;
                    $grades[] = $this->formatStringValue($subj['gred'] ?? null);
                }
                $subjek_lain = implode(',', $subjects);
                $gred_subjek_lain = implode(',', $grades);
            }

            // Safeguard lengths before insert (DB column may be legacy VARCHAR)
            $subjek_lain = $subjek_lain !== '' ? substr($subjek_lain, 0, 2000) : null;
            $gred_subjek_lain = $gred_subjek_lain !== '' ? substr($gred_subjek_lain, 0, 2000) : null;
            
            $stmt->execute([
                $application_reference,
                $application_id,
                $tahunSpm,
                $this->formatStringValue($postData['spm_gred_keseluruhan'] ?? null),
                $this->formatStringValue($postData['spm_angka_giliran'] ?? null),
                $this->formatStringValue($postData['spm_bahasa_malaysia'] ?? null),
                $this->formatStringValue($postData['spm_bahasa_inggeris'] ?? null),
                $this->formatStringValue($postData['spm_matematik'] ?? null),
                $this->formatStringValue($postData['spm_sejarah'] ?? null),
                $subjek_lain,
                $gred_subjek_lain,
                $this->formatStringValue($postData['spm_salinan_sijil_path'] ?? null, false)
            ]);
        }
    }
    
    /**
     * Save SPM additional subjects
     */
    private function saveSpmAdditionalSubjects($postData, $application_id, $application_reference) {
        if (!isset($postData['spm_subjek_lain']) || !is_array($postData['spm_subjek_lain'])) return;
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO application_spm_additional_subjects (application_reference, application_id, tahun, angka_giliran, subjek, gred, salinan_sijil) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        
        $spm_tahun = $this->formatStringValue($postData['spm_tahun'] ?? null);
        $spm_angka_giliran = $this->formatStringValue($postData['spm_angka_giliran'] ?? null);
        
        foreach ($postData['spm_subjek_lain'] as $subj) {
            $subjectName = $this->formatStringValue($subj['subjek'] ?? null);
            if ($subjectName === null) {
                continue;
            }

            $stmt->execute([
                $application_reference,
                $application_id,
                $spm_tahun,
                $spm_angka_giliran,
                $subjectName,
                $this->formatStringValue($subj['gred'] ?? null),
                $this->formatStringValue($subj['salinan_sijil'] ?? null, false)
            ]);
        }
    }

    /**
     * Ensure schema prerequisites exist before starting transaction
     */
    private function ensureSchemaPreconditions(array $postData): void
    {
        if (isset($postData['kemahiran_bahasa']) && is_array($postData['kemahiran_bahasa'])) {
            $this->ensureLanguageSkillsTableStructure();
        }

        if (isset($postData['kemahiran_komputer']) && is_array($postData['kemahiran_komputer'])) {
            $this->ensureComputerSkillsTableStructure();
        }

        $hasSpm =
            !empty($postData['spm_tahun']) ||
            (isset($postData['spm_subjek_lain']) && is_array($postData['spm_subjek_lain']) && !empty($postData['spm_subjek_lain']));
        if ($hasSpm) {
            $this->ensureSpmResultsTableStructure();
        }
    }

    /**
     * Ensure SPM results table can store long subject/grade strings
     */
    private function ensureSpmResultsTableStructure(): void
    {
        if ($this->spmSchemaPrepared) {
            return;
        }
        try {
            // Ensure table exists with basic structure
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS application_spm_results (
                id INT AUTO_INCREMENT PRIMARY KEY,
                application_id INT NULL,
                application_reference VARCHAR(50) NULL,
                tahun VARCHAR(4) NOT NULL,
                gred_keseluruhan VARCHAR(50) NULL,
                angka_giliran VARCHAR(50) NOT NULL,
                bahasa_malaysia VARCHAR(5) NULL,
                bahasa_inggeris VARCHAR(5) NULL,
                matematik VARCHAR(5) NULL,
                sejarah VARCHAR(5) NULL,
                subjek_lain TEXT NULL,
                gred_subjek_lain TEXT NULL,
                salinan_sijil_filename VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (application_id),
                INDEX (application_reference)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Widen legacy columns when required
            foreach (['subjek_lain', 'gred_subjek_lain'] as $column) {
                $stmt = $this->pdo->query("SHOW COLUMNS FROM application_spm_results LIKE '{$column}'");
                $col = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($col && stripos($col['Type'], 'text') === false) {
                    $this->pdo->exec("ALTER TABLE application_spm_results MODIFY COLUMN {$column} TEXT NULL");
                    error_log("Adjusted {$column} column to TEXT in application_spm_results");
                }
            }

            // Ensure application_reference exists
            $stmt = $this->pdo->query("SHOW COLUMNS FROM application_spm_results LIKE 'application_reference'");
            if (!$stmt->fetch()) {
                $this->pdo->exec("ALTER TABLE application_spm_results ADD COLUMN application_reference VARCHAR(50) NULL AFTER application_id");
                $this->pdo->exec("ALTER TABLE application_spm_results ADD INDEX idx_spm_app_ref (application_reference)");
            }
        } catch (Exception $e) {
            error_log('ensureSpmResultsTableStructure error: ' . $e->getMessage());
        } finally {
            $this->spmSchemaPrepared = true;
        }
    }
    
    /**
     * Save professional bodies
     */
    private function saveProfessionalBodies($postData, $application_id, $application_reference) {
        if (!isset($postData['badan_profesional']) || !is_array($postData['badan_profesional'])) return;

        $columnsInfo = [];
        try {
            $columnsInfo = $this->pdo->query("SHOW COLUMNS FROM application_professional_bodies")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $columnsInfo = [];
        }

        $hasTarikh = false;
        $hasTahun = false;
        $isTahunDate = false;
        $salinanCol = 'salinan_sijil_filename';
        foreach ($columnsInfo as $col) {
            $field = $col['Field'] ?? '';
            $type = $col['Type'] ?? '';
            if ($field === 'tarikh_sijil') { $hasTarikh = true; }
            if ($field === 'tahun') { $hasTahun = true; if (stripos($type, 'date') !== false) { $isTahunDate = true; } }
            if ($field === 'salinan_sijil_path') { $salinanCol = 'salinan_sijil_path'; }
            if ($field === 'salinan_sijil_filename') { $salinanCol = 'salinan_sijil_filename'; }
            if ($field === 'salinan_sijil') { $salinanCol = 'salinan_sijil'; }
        }

        $columns = ['application_reference','application_id','nama_lembaga','no_ahli','sijil_diperoleh'];
        if ($hasTarikh) {
            $columns[] = 'tarikh_sijil';
        } elseif ($hasTahun) {
            $columns[] = 'tahun';
        }
        $columns[] = $salinanCol;
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        $stmt = $this->pdo->prepare(
            "INSERT INTO application_professional_bodies (" . implode(', ', $columns) . ") VALUES (" . $placeholders . ")"
        );

        foreach ($postData['badan_profesional'] as $prof) {
            $namaLembaga = $this->formatStringValue($prof['nama_lembaga'] ?? null);
            if ($namaLembaga === null) {
                continue;
            }

            $tarikh = $this->formatStringValue($prof['tarikh_sijil'] ?? null);
            $tahunVal = null;
            if ($tarikh !== null) {
                $tahunVal = $isTahunDate ? date('Y-m-d', strtotime($tarikh)) : date('Y', strtotime($tarikh));
            } else {
                $tahunRaw = $this->formatStringValue($prof['tahun'] ?? null);
                if ($tahunRaw !== null) {
                    $tahunVal = $isTahunDate ? (preg_match('/^\d{4}$/', $tahunRaw) ? ($tahunRaw . '-01-01') : $tahunRaw) : $tahunRaw;
                }
            }

            $values = [
                $application_reference,
                $application_id,
                $namaLembaga,
                $this->formatStringValue($prof['no_ahli'] ?? null),
                $this->formatStringValue($prof['sijil'] ?? null)
            ];

            if ($hasTarikh) {
                $values[] = $tarikh;
            } elseif ($hasTahun) {
                $values[] = $tahunVal;
            }

            $values[] = $this->formatStringValue($prof['salinan_sijil_path'] ?? null, false);

            $stmt->execute($values);
        }
    }
    
    /**
     * Save extracurricular activities
     */
    private function saveExtracurricular($postData, $application_id, $application_reference) {
        if (!isset($postData['kegiatan_luar']) || !is_array($postData['kegiatan_luar'])) return;
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO application_extracurricular (application_reference, application_id, sukan_persatuan_kelab, jawatan, peringkat, tahap, tarikh_sijil, salinan_sijil_filename) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        foreach ($postData['kegiatan_luar'] as $activity) {
            $sukan = $this->formatStringValue($activity['sukan_persatuan_kelab'] ?? null);
            if ($sukan === null) {
                continue;
            }

            $stmt->execute([
                $application_reference,
                $application_id,
                $sukan,
                $this->formatStringValue($activity['jawatan'] ?? null),
                $this->formatStringValue($activity['peringkat'] ?? null),
                $this->formatStringValue($activity['tahap'] ?? null),
                $this->formatStringValue($activity['tarikh_sijil'] ?? null),
                $this->formatStringValue($activity['salinan_sijil_path'] ?? null, false)
            ]);
        }
    }
    
    /**
     * Save work experience
     */
    private function saveWorkExperience($postData, $application_id, $application_reference) {
        if (!isset($postData['pengalaman_kerja']) || !is_array($postData['pengalaman_kerja'])) return;
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO application_work_experience (
                application_reference,
                application_id,
                nama_syarikat,
                jawatan,
                unit_bahagian,
                gred,
                taraf_jawatan,
                dari_bulan,
                dari_tahun,
                hingga_bulan,
                hingga_tahun,
                gaji,
                mula_berkhidmat,
                tamat_berkhidmat,
                bidang_tugas,
                alasan_berhenti
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        foreach ($postData['pengalaman_kerja'] as $work) {
            $namaSyarikat = $this->formatStringValue($work['syarikat'] ?? $work['nama_syarikat'] ?? null);
            if ($namaSyarikat === null) {
                continue;
            }

            $dariBulan = $this->normalizeInteger($work['dari_bulan'] ?? null);
            $dariTahun = $this->normalizeYear($work['dari_tahun'] ?? null);
            $hinggaBulan = $this->normalizeInteger($work['hingga_bulan'] ?? null);
            $hinggaTahun = $this->normalizeYear($work['hingga_tahun'] ?? null);
            $gajiNilai = $this->normalizeDecimal($work['gaji'] ?? null);

            $mulaBerkhidmatRaw = $work['mula_berkhidmat'] ?? null;
            $tamatBerkhidmatRaw = $work['tamat_berkhidmat'] ?? null;

            $mulaBerkhidmat = null;
            if ($mulaBerkhidmatRaw !== null && $mulaBerkhidmatRaw !== '') {
                $mb = is_string($mulaBerkhidmatRaw) ? trim($mulaBerkhidmatRaw) : $mulaBerkhidmatRaw;
                if (is_string($mb) && preg_match('/^\d{4}-\d{2}$/', $mb)) {
                    $mulaBerkhidmat = $mb . '-01';
                } else {
                    $mulaBerkhidmat = is_string($mb) && $mb !== '' ? $mb : null;
                }
            } elseif ($dariTahun !== null || $dariBulan !== null) {
                $bulanPad = $dariBulan !== null ? str_pad((string)$dariBulan, 2, '0', STR_PAD_LEFT) : '01';
                $tahunVal = $dariTahun !== null ? $dariTahun : date('Y');
                $mulaBerkhidmat = $tahunVal . '-' . $bulanPad . '-01';
            }

            $tamatBerkhidmat = null;
            if ($tamatBerkhidmatRaw !== null && $tamatBerkhidmatRaw !== '') {
                $tb = is_string($tamatBerkhidmatRaw) ? trim($tamatBerkhidmatRaw) : $tamatBerkhidmatRaw;
                if (is_string($tb) && preg_match('/^\d{4}-\d{2}$/', $tb)) {
                    $tamatBerkhidmat = $tb . '-01';
                } else {
                    $tamatBerkhidmat = is_string($tb) && $tb !== '' ? $tb : null;
                }
            } elseif ($hinggaTahun !== null || $hinggaBulan !== null) {
                $bulanPadH = $hinggaBulan !== null ? str_pad((string)$hinggaBulan, 2, '0', STR_PAD_LEFT) : '01';
                $tahunValH = $hinggaTahun !== null ? $hinggaTahun : date('Y');
                $tamatBerkhidmat = $tahunValH . '-' . $bulanPadH . '-01';
            }

            $stmt->execute([
                $application_reference,
                $application_id,
                $namaSyarikat,
                $this->formatStringValue($work['jawatan'] ?? null),
                $this->formatStringValue($work['unit_bahagian'] ?? null),
                $this->formatStringValue($work['gred'] ?? null),
                $this->formatStringValue($work['taraf_jawatan'] ?? null),
                $dariBulan,
                $dariTahun,
                $hinggaBulan,
                $hinggaTahun,
                $gajiNilai,
                $mulaBerkhidmat,
                $tamatBerkhidmat,
                $this->formatStringValue($work['bidang_tugas'] ?? null),
                $this->formatStringValue($work['alasan'] ?? $work['alasan_berhenti'] ?? null)
            ]);
        }
    }
    
    /**
     * Save references
     */
    private function saveReferences($postData, $application_id, $application_reference) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO application_references (application_reference, application_id, nama, no_telefon, tempoh_mengenali, jawatan, alamat) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        
        // Handle individual reference fields
        for ($i = 1; $i <= 3; $i++) {
            $nama = $this->formatStringValue($postData["rujukan_{$i}_nama"] ?? null);
            if ($nama === null) {
                continue;
            }

            $stmt->execute([
                $application_reference,
                $application_id,
                $nama,
                $this->formatStringValue($postData["rujukan_{$i}_telefon"] ?? null),
                $this->formatStringValue($postData["rujukan_{$i}_tempoh"] ?? null),
                $this->formatStringValue($postData["rujukan_{$i}_jawatan"] ?? null),
                $this->formatStringValue($postData["rujukan_{$i}_alamat"] ?? null)
            ]);
        }
        
        // Handle array format references
        if (isset($postData['rujukan']) && is_array($postData['rujukan'])) {
            foreach ($postData['rujukan'] as $ref) {
                $namaRef = $this->formatStringValue($ref['nama'] ?? null);
                if ($namaRef === null) {
                    continue;
                }

                $stmt->execute([
                    $application_reference,
                    $application_id,
                    $namaRef,
                    $this->formatStringValue($ref['telefon'] ?? $ref['no_telefon'] ?? null),
                    $this->formatStringValue($ref['tempoh'] ?? $ref['tempoh_mengenali'] ?? null),
                    $this->formatStringValue($ref['jawatan'] ?? null),
                    $this->formatStringValue($ref['alamat'] ?? null)
                ]);
            }
        }
    }
    
    /**
     * Save family members
     */
    private function saveFamilyMembers($postData, $application_id, $application_reference) {
        if (!isset($postData['ahli_keluarga']) || !is_array($postData['ahli_keluarga'])) return;
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO application_family_members (application_reference, application_id, hubungan, nama, pekerjaan, telefon, kewarganegaraan) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        
        foreach ($postData['ahli_keluarga'] as $family) {
            $nama = $this->formatStringValue($family['nama'] ?? null);
            if ($nama === null) {
                continue;
            }

            $stmt->execute([
                $application_reference,
                $application_id,
                $this->formatStringValue($family['hubungan'] ?? null),
                $nama,
                $this->formatStringValue($family['pekerjaan'] ?? null),
                $this->formatStringValue($family['telefon'] ?? null),
                $this->formatStringValue($family['kewarganegaraan'] ?? null)
            ]);
        }
    }
    
    /**
     * Save health data
     */
    private function saveHealthData($postData, $application_id, $application_reference) {
        $health_fields = [
            'darah_tinggi', 'kencing_manis', 'penyakit_buah_pinggang', 'penyakit_jantung',
            'batuk_kering_tibi', 'kanser', 'aids', 'penagih_dadah', 'perokok', 'penyakit_lain',
            'penyakit_lain_nyatakan', 'pemegang_kad_oku', 'jenis_oku', 'memakai_cermin_mata',
            'jenis_rabun', 'berat_kg', 'tinggi_cm', 'salinan_kad_oku'
        ];
        
        $health_data = [
            'application_reference' => $application_reference,
            'application_id' => $application_id
        ];
        
        foreach ($health_fields as $field) {
            if (isset($postData[$field])) {
                $value = $postData[$field];
                if (is_array($value)) {
                    $encoded = $this->uppercaseRecursive($value);
                    $health_data[$field] = $encoded !== null ? json_encode($encoded, JSON_UNESCAPED_UNICODE) : null;
                } else {
                    $health_data[$field] = $this->formatStringValue($value, $this->shouldUppercaseField($field));
                }
            }
        }
        
        $columns = array_keys($health_data);
        $placeholders = ':' . implode(', :', $columns);
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO application_health (" . implode(',', $columns) . ") VALUES (" . $placeholders . ")"
        );
        $stmt->execute($health_data);
    }

    /**
     * Normalize integer inputs, returning NULL when empty/non-numeric
     */
    private function normalizeInteger($value): ?int
    {
        if ($value === null) {
            return null;
        }
        if (is_array($value)) {
            $value = reset($value);
        }
        $value = is_string($value) ? trim($value) : $value;
        if ($value === '' || $value === null) {
            return null;
        }
        return is_numeric($value) ? (int)$value : null;
    }

    /**
     * Normalize year values (stored as VARCHAR) to 4-digit strings
     */
    private function normalizeYear($value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_array($value)) {
            $value = reset($value);
        }
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        // Keep only digits, limit to 4 characters
        $year = preg_replace('/\D/', '', $value);
        return $year !== '' ? substr($year, 0, 4) : null;
    }

    /**
     * Normalize decimal/monetary values, returning NULL when empty/non-numeric
     */
    private function normalizeDecimal($value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_array($value)) {
            $value = reset($value);
        }
        $value = is_string($value) ? trim($value) : $value;
        if ($value === '' || $value === null) {
            return null;
        }
        // Replace commas, keep numeric + dot
        $normalized = str_replace(',', '', (string)$value);
        if (!is_numeric($normalized)) {
            return null;
        }
        return number_format((float)$normalized, 2, '.', '');
    }

    /**
     * Normalize and optionally uppercase a string value while preserving intentional formatting.
     */
    private function formatStringValue($value, bool $uppercase = true): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif (is_numeric($value)) {
            $value = (string)$value;
        } elseif (is_array($value)) {
            return null;
        }

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if ($uppercase) {
            $value = function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
        }

        return $value;
    }

    /**
     * Determine whether a field should be uppercased.
     */
    private function shouldUppercaseField(string $field): bool
    {
        $lower = strtolower($field);

        if (
            str_contains($lower, 'email') ||
            str_contains($lower, 'salinan') ||
            str_contains($lower, 'path') ||
            str_contains($lower, 'filename') ||
            str_contains($lower, 'url')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Helper to uppercase each element in an array of scalar values.
     */
    private function formatArrayValues(array $values, bool $uppercase = true): array
    {
        $formatted = [];

        foreach ($values as $value) {
            $formattedValue = $this->formatStringValue($value, $uppercase);
            if ($formattedValue !== null) {
                $formatted[] = $formattedValue;
            }
        }

        return $formatted;
    }

    /**
     * Recursively uppercase nested array/string values.
     */
    private function uppercaseRecursive($value)
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = $this->uppercaseRecursive($item);
            }
            return $result;
        }

        if (is_string($value)) {
            return $this->formatStringValue($value) ?? null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return $value;
    }
    
    /**
     * Check if application exists (for duplicate checking)
     */
    public function checkDuplicate($job_id, $nric) {
        $stmt = $this->pdo->prepare('SELECT id FROM application_application_main WHERE job_id = ? AND nombor_ic = ? LIMIT 1');
        $stmt->execute([$job_id, $nric]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Get application by reference
     */
    public function getApplicationByReference($reference) {
        $stmt = $this->pdo->prepare('SELECT * FROM application_application_main WHERE application_reference = ? LIMIT 1');
        $stmt->execute([$reference]);
        return $stmt->fetch();
    }
    
    /**
     * Get application by ID
     */
    public function getApplicationById($id) {
        $stmt = $this->pdo->prepare('SELECT * FROM application_application_main WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
