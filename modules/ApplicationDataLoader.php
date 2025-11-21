<?php
/**
 * @FileID: application_data_loader
 * @Module: Application Data Loader
 * @Author: AI Assistant
 * @LastModified: 2025-11-14
 * @SecurityTag: validated
 */

class ApplicationDataLoader {
    private $pdo;
    private $application_reference;
    private $application_id;
    
    public function __construct($pdo, $application_reference, $application_id = null) {
        $this->pdo = $pdo;
        $this->application_reference = $application_reference;
        $this->application_id = $application_id;
    }
    
    /**
     * Safe query execution with error handling
     */
    private function safeQuery($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Query failed in ApplicationDataLoader: " . $e->getMessage() . " SQL: " . $sql);
            return [];
        }
    }
    
    /**
     * Load all application data from separate tables
     */
    public function loadAllData() {
        $data = [];
        
        // 1. Main application data (already loaded)
        $data['main'] = $this->loadMainApplication();
        
        // 2. Computer Skills
        $data['computer_skills'] = $this->safeQuery(
            "SELECT * FROM application_computer_skills WHERE application_reference = ? ORDER BY id",
            [$this->application_reference]
        );
        
        // 3. Education
        $data['education'] = $this->safeQuery(
            "SELECT * FROM application_education WHERE application_reference = ? ORDER BY id",
            [$this->application_reference]
        );
        
        // 4. Extracurricular
        $data['extracurricular'] = $this->safeQuery(
            "SELECT * FROM application_extracurricular WHERE application_reference = ? ORDER BY id",
            [$this->application_reference]
        );
        
        // 5. Health
        $data['health'] = $this->safeQuery(
            "SELECT * FROM application_health WHERE application_reference = ? LIMIT 1",
            [$this->application_reference]
        );
        $data['health'] = !empty($data['health']) ? $data['health'][0] : null;
        
        // 6. Language Skills
        $data['language_skills'] = $this->safeQuery(
            "SELECT * FROM application_language_skills WHERE application_reference = ? ORDER BY id",
            [$this->application_reference]
        );
        
        // 7. Professional Bodies
        $data['professional_bodies'] = $this->safeQuery(
            "SELECT * FROM application_professional_bodies WHERE application_reference = ? ORDER BY id",
            [$this->application_reference]
        );
        
        // 8. References
        $data['references'] = $this->safeQuery(
            "SELECT * FROM application_references WHERE application_reference = ? ORDER BY id",
            [$this->application_reference]
        );
        
        // 9. SPM Additional Subjects
        $data['spm_additional_subjects'] = $this->safeQuery(
            "SELECT * FROM application_spm_additional_subjects WHERE application_reference = ? ORDER BY id",
            [$this->application_reference]
        );
        
        // 10. SPM Results
        $data['spm_results'] = $this->safeQuery(
            "SELECT * FROM application_spm_results WHERE application_reference = ? ORDER BY id",
            [$this->application_reference]
        );
        
        // 11. Work Experience
        $data['work_experience'] = $this->safeQuery(
            "SELECT * FROM application_work_experience WHERE application_reference = ? ORDER BY id",
            [$this->application_reference]
        );
        
        // 12. Family Members
        $data['family_members'] = $this->safeQuery(
            "SELECT * FROM application_family_members WHERE application_reference = ? ORDER BY id",
            [$this->application_reference]
        );
        
        return $data;
    }
    
    /**
     * Load main application data
     */
    private function loadMainApplication() {
        if ($this->application_id) {
            $main = $this->safeQuery(
                "SELECT * FROM application_application_main WHERE id = ? LIMIT 1",
                [$this->application_id]
            );
            if (!empty($main)) return $main[0];
            
            // Fallback to job_applications table
            $main = $this->safeQuery(
                "SELECT * FROM job_applications WHERE id = ? LIMIT 1",
                [$this->application_id]
            );
            if (!empty($main)) return $main[0];
        }
        
        if ($this->application_reference) {
            $main = $this->safeQuery(
                "SELECT * FROM application_application_main WHERE application_reference = ? LIMIT 1",
                [$this->application_reference]
            );
            if (!empty($main)) return $main[0];
            
            // Fallback to job_applications table
            $main = $this->safeQuery(
                "SELECT * FROM job_applications WHERE application_reference = ? LIMIT 1",
                [$this->application_reference]
            );
            if (!empty($main)) return $main[0];
        }
        
        return null;
    }
    
    /**
     * Format data for form prefilling
     */
    public function formatForPrefill() {
        $allData = $this->loadAllData();
        
        $formatted = [];
        
        // Format language skills
        $formatted['languages'] = [];
        foreach ($allData['language_skills'] as $lang) {
            $formatted['languages'][] = [
                'bahasa' => $lang['bahasa'] ?? '',
                'pertuturan' => $lang['tahap_lisan'] ?? $lang['pertuturan'] ?? '',
                'penulisan' => $lang['tahap_penulisan'] ?? $lang['penulisan'] ?? '',
                'gred_spm' => $lang['gred_spm'] ?? ''
            ];
        }
        
        // Format computer skills
        $formatted['computers'] = [];
        foreach ($allData['computer_skills'] as $comp) {
            $formatted['computers'][] = [
                'nama_perisian' => $comp['nama_perisian'] ?? $comp['nama_kemahiran'] ?? '',
                'tahap_kemahiran' => $comp['tahap_kemahiran'] ?? $comp['tahap'] ?? ''
            ];
        }
        
        // Format education
        $formatted['education'] = [];
        foreach ($allData['education'] as $edu) {
            $formatted['education'][] = [
                'institusi' => $edu['nama_institusi'] ?? $edu['institusi'] ?? '',
                'dari_tahun' => $edu['dari_tahun'] ?? '',
                'hingga_tahun' => $edu['hingga_tahun'] ?? '',
                'kelayakan' => $edu['kelayakan'] ?? '',
                'gred' => $edu['pangkat_gred_cgpa'] ?? $edu['gred'] ?? '',
                'sijil' => $edu['sijil_path'] ?? $edu['sijil_filename'] ?? ''
            ];
        }
        
        // Format SPM results
        $formatted['spm_results'] = [];
        foreach ($allData['spm_results'] as $spm) {
            $formatted['spm_results'][] = [
                'tahun' => $spm['tahun'] ?? '',
                'gred_keseluruhan' => $spm['gred_keseluruhan'] ?? '',
                'angka_giliran' => $spm['angka_giliran'] ?? '',
                'bahasa_malaysia' => $spm['bahasa_malaysia'] ?? '',
                'bahasa_inggeris' => $spm['bahasa_inggeris'] ?? '',
                'matematik' => $spm['matematik'] ?? '',
                'sejarah' => $spm['sejarah'] ?? '',
                'subjek_lain' => $spm['subjek_lain'] ?? '',
                'gred_subjek_lain' => $spm['gred_subjek_lain'] ?? '',
                'salinan_sijil' => $spm['salinan_sijil_filename'] ?? $spm['salinan_sijil_path'] ?? ''
            ];
        }
        
        // Format SPM additional subjects
        $formatted['spm_additional'] = [];
        foreach ($allData['spm_additional_subjects'] as $add) {
            $formatted['spm_additional'][] = [
                'tahun' => $add['tahun'] ?? '',
                'angka_giliran' => $add['angka_giliran'] ?? '',
                'subjek' => $add['subjek'] ?? '',
                'gred' => $add['gred'] ?? '',
                'salinan_sijil' => $add['salinan_sijil'] ?? ''
            ];
        }
        
        // Format work experience
        $formatted['work_experience'] = [];
        foreach ($allData['work_experience'] as $work) {
            $formatted['work_experience'][] = [
                'nama_syarikat' => $work['nama_syarikat'] ?? $work['syarikat'] ?? '',
                'jawatan' => $work['jawatan'] ?? '',
                'mula_berkhidmat' => $work['mula_berkhidmat'] ? date('Y-m', strtotime($work['mula_berkhidmat'])) : '',
                'tamat_berkhidmat' => $work['tamat_berkhidmat'] ? date('Y-m', strtotime($work['tamat_berkhidmat'])) : '',
                'dari_bulan' => $work['dari_bulan'] ?? '',
                'dari_tahun' => $work['dari_tahun'] ?? '',
                'hingga_bulan' => $work['hingga_bulan'] ?? '',
                'hingga_tahun' => $work['hingga_tahun'] ?? '',
                'unit_bahagian' => $work['unit_bahagian'] ?? '',
                'gred' => $work['gred'] ?? '',
                'gaji' => $work['gaji'] ?? '',
                'taraf_jawatan' => $work['taraf_jawatan'] ?? '',
                'bidang_tugas' => $work['bidang_tugas'] ?? '',
                'alasan_berhenti' => $work['alasan_berhenti'] ?? $work['alasan'] ?? ''
            ];
        }
        
        // Format professional bodies
        $formatted['professional_bodies'] = [];
        foreach ($allData['professional_bodies'] as $prof) {
            $tarikh = $prof['tarikh_sijil'] ?? null;
            if (empty($tarikh)) {
                $tahun = $prof['tahun'] ?? '';
                if ($tahun !== '') {
                    $tarikh = preg_match('/^\d{4}$/', (string)$tahun) ? ($tahun . '-01-01') : $tahun;
                }
            }
            $formatted['professional_bodies'][] = [
                'nama_lembaga' => $prof['nama_lembaga'] ?? '',
                'no_ahli' => $prof['no_ahli'] ?? '',
                'sijil' => $prof['sijil_diperoleh'] ?? $prof['sijil'] ?? '',
                'tarikh_sijil' => $tarikh ?? '',
                'salinan_sijil' => $prof['salinan_sijil_filename'] ?? $prof['salinan_sijil_path'] ?? $prof['salinan_sijil'] ?? ''
            ];
        }
        
        // Format extracurricular
        $formatted['extracurriculars'] = [];
        foreach ($allData['extracurricular'] as $extra) {
            $formatted['extracurriculars'][] = [
                'sukan_persatuan_kelab' => $extra['sukan_persatuan_kelab'] ?? '',
                'jawatan' => $extra['jawatan'] ?? '',
                'peringkat' => $extra['peringkat'] ?? $extra['tahap'] ?? '',
                'tarikh_sijil' => $extra['tarikh_sijil'] ?? (!empty($extra['tahun']) ? ($extra['tahun'] . '-01-01') : ''),
                'salinan_sijil' => $extra['salinan_sijil_filename'] ?? $extra['salinan_sijil_path'] ?? $extra['salinan_sijil'] ?? ''
            ];
        }
        
        // Format references
        $formatted['references'] = [];
        foreach ($allData['references'] as $ref) {
            $formatted['references'][] = [
                'nama' => $ref['nama'] ?? '',
                'no_telefon' => $ref['no_telefon'] ?? $ref['telefon'] ?? '',
                'tempoh_mengenali' => str_replace(' tahun', '', $ref['tempoh_mengenali'] ?? $ref['tempoh'] ?? ''),
                'jawatan' => $ref['jawatan'] ?? '',
                'alamat' => $ref['alamat'] ?? ''
            ];
        }
        
        // Format family members
        $formatted['family_members'] = [];
        foreach ($allData['family_members'] as $family) {
            $formatted['family_members'][] = [
                'hubungan' => $family['hubungan'] ?? '',
                'nama' => $family['nama'] ?? '',
                'pekerjaan' => $family['pekerjaan'] ?? '',
                'telefon' => $family['telefon'] ?? '',
                'kewarganegaraan' => $family['kewarganegaraan'] ?? ''
            ];
        }
        
        return $formatted;
    }
    
    /**
     * Log data counts for debugging
     */
    public function logDataCounts() {
        $data = $this->loadAllData();
        error_log("=== ApplicationDataLoader Debug ===");
        error_log("Application Reference: " . $this->application_reference);
        error_log("Application ID: " . ($this->application_id ?? 'null'));
        error_log("Computer Skills: " . count($data['computer_skills']));
        error_log("Education: " . count($data['education']));
        error_log("Extracurricular: " . count($data['extracurricular']));
        error_log("Health: " . ($data['health'] ? 'Found' : 'Not found'));
        error_log("Language Skills: " . count($data['language_skills']));
        error_log("Professional Bodies: " . count($data['professional_bodies']));
        error_log("References: " . count($data['references']));
        error_log("SPM Additional Subjects: " . count($data['spm_additional_subjects']));
        error_log("SPM Results: " . count($data['spm_results']));
        error_log("Work Experience: " . count($data['work_experience']));
        error_log("Family Members: " . count($data['family_members']));
        error_log("=== End ApplicationDataLoader Debug ===");
    }
}
