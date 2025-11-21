<?php
/**
 * DataFetcher for Preview Application
 * Fetches all application-related data from various tables
 */

class PreviewDataFetcher {
    private $pdo;
    private $app;
    private $reference;
    
    public function __construct($pdo, $app) {
        $this->pdo = $pdo;
        $this->app = $app;
        $this->reference = $app['application_reference'];
    }
    
    /**
     * Safe fetch all with error handling
     */
    private function safeFetchAll($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            if (function_exists('log_error')) {
                log_error('Query failed in preview data fetcher', [
                    'sql' => $sql, 
                    'params' => $params, 
                    'error' => $e->getMessage()
                ]);
            }
            return [];
        }
    }
    
    /**
     * Fetch health data from application_health table
     * Explicitly selects all fields: id, application_reference, job_id, darah_tinggi, 
     * kencing_manis, penyakit_buah_pinggang, penyakit_jantung, batuk_kering_tibi, 
     * kanser, aids, penagih_dadah, perokok, penyakit_lain, penyakit_lain_nyatakan, 
     * pemegang_kad_oku, jenis_oku, salinan_kad_oku, memakai_cermin_mata, jenis_rabun, 
     * berat_kg, tinggi_cm, created_at, updated_at
     */
    public function getHealthData() {
        try {
            $stmt = $this->pdo->prepare("SELECT 
                id, 
                application_reference, 
                job_id, 
                darah_tinggi, 
                kencing_manis, 
                penyakit_buah_pinggang, 
                penyakit_jantung, 
                batuk_kering_tibi, 
                kanser, 
                aids, 
                penagih_dadah, 
                perokok, 
                penyakit_lain, 
                penyakit_lain_nyatakan, 
                pemegang_kad_oku, 
                jenis_oku, 
                salinan_kad_oku, 
                memakai_cermin_mata, 
                jenis_rabun, 
                berat_kg, 
                tinggi_cm, 
                created_at, 
                updated_at
            FROM application_health 
            WHERE application_reference = ? 
            ORDER BY id DESC
            LIMIT 1");
            $stmt->execute([$this->reference]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            // Log error but return null to prevent page crash
            if (function_exists('log_error')) {
                log_error('Failed to fetch health data', [
                    'application_reference' => $this->reference,
                    'error' => $e->getMessage()
                ]);
            }
            return null;
        }
    }
    
    /**
     * Fetch language skills
     */
    public function getLanguageSkills() {
        $rows = $this->safeFetchAll(
            "SELECT * FROM application_language_skills WHERE application_reference = ? ORDER BY id",
            [$this->reference]
        );
        foreach ($rows as &$row) {
            if (!isset($row['tahap_lisan'])) {
                $row['tahap_lisan'] = $row['pertuturan'] ?? '';
            }
            if (!isset($row['tahap_penulisan'])) {
                $row['tahap_penulisan'] = $row['penulisan'] ?? '';
            }
            if (!isset($row['application_id'])) {
                $row['application_id'] = '';
            }
            if (!isset($row['created_at'])) {
                $row['created_at'] = '';
            }
        }
        unset($row);
        return $rows;
    }
    
    /**
     * Fetch computer skills
     */
    public function getComputerSkills() {
        return $this->safeFetchAll(
            "SELECT nama_perisian, tahap_kemahiran FROM application_computer_skills WHERE application_reference = ? ORDER BY id", 
            [$this->reference]
        );
    }
    
    /**
     * Fetch professional bodies
     */
    public function getProfessionalBodies() {
        $fileCol = $this->resolveColumn('application_professional_bodies', ['salinan_sijil_filename','salinan_sijil']);
        $createdCol = $this->resolveColumn('application_professional_bodies', ['created_at']);
        $dateCol = $this->resolveColumn('application_professional_bodies', ['tarikh_sijil']);

        $selectCols = [
            'id',
            'application_reference',
            'application_id',
            'nama_lembaga',
            'sijil_diperoleh',
            'no_ahli',
            'tahun'
        ];
        if ($fileCol) { $selectCols[] = "$fileCol AS salinan_sijil_filename"; }
        if ($createdCol) { $selectCols[] = $createdCol; }
        if ($dateCol) { $selectCols[] = "$dateCol AS tarikh_sijil"; }

        $sql = "SELECT " . implode(', ', $selectCols) . " FROM application_professional_bodies WHERE application_reference = ? ORDER BY id";
        $rows = $this->safeFetchAll($sql, [$this->reference]);
        if (!$rows) {
            $appId = $this->app['id'] ?? null;
            if ($appId) {
                $sql2 = "SELECT " . implode(', ', $selectCols) . " FROM application_professional_bodies WHERE application_id = ? ORDER BY id";
                $rows = $this->safeFetchAll($sql2, [$appId]);
            }
        }
        foreach ($rows as &$row) {
            if (!isset($row['sijil_diperoleh']) && isset($row['sijil'])) {
                $row['sijil_diperoleh'] = $row['sijil'];
            }
            if (!isset($row['tahun']) && isset($row['tarikh_sijil'])) {
                $row['tahun'] = $row['tarikh_sijil'];
            }
            if (!isset($row['salinan_sijil']) && isset($row['salinan_sijil_filename'])) {
                $row['salinan_sijil'] = $row['salinan_sijil_filename'];
            }
            if (!isset($row['created_at'])) { $row['created_at'] = ''; }
            if (!isset($row['application_id'])) { $row['application_id'] = $this->app['id'] ?? ''; }
        }
        unset($row);
        return $rows;
    }
    
    /**
     * Resolve column name from table
     */
    private function resolveColumn($table, $candidates) {
        static $cache = [];
        if (!isset($cache[$table])) {
            try {
                $stmt = $this->pdo->query("SHOW COLUMNS FROM `{$table}`");
                $cache[$table] = $stmt ? array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field') : [];
            } catch (Throwable $e) {
                $cache[$table] = [];
            }
        }
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $cache[$table], true)) { 
                return $candidate; 
            }
        }
        return null; // Return null if no match found - don't assume columns exist
    }
    
    /**
     * Fetch education records
     */
    public function getEducation() {
        $rows = $this->safeFetchAll(
            "SELECT id, application_reference, nama_institusi, dari_tahun, hingga_tahun, kelayakan, pangkat_gred_cgpa, sijil_path, sijil_tambahan, created_at, application_id FROM application_education WHERE application_reference = ? ORDER BY id", 
            [$this->reference]
        );
        
        // Map column names to expected names for consistency
        foreach ($rows as &$row) {
            // Map institution column
            $row['institusi'] = $row['nama_institusi'] ?? '';
            // Map grade column
            $row['gred'] = $row['pangkat_gred_cgpa'] ?? '';
            // Map sijil columns
            $row['sijil_filename'] = $row['sijil_path'] ?? '';
            $row['sijil_tambahan_filename'] = $row['sijil_tambahan'] ?? '';
        }
        unset($row);
        
        return $rows;
    }
    
    /**
     * Fetch SPM results
     */
    public function getSpmResults() {
        return $this->safeFetchAll(
            "SELECT id, application_reference, tahun, gred_keseluruhan, angka_giliran, bahasa_malaysia, bahasa_inggeris, matematik, sejarah, subjek_lain, gred_subjek_lain, salinan_sijil_filename FROM application_spm_results WHERE application_reference = ? ORDER BY id", 
            [$this->reference]
        );
    }
    
    /**
     * Fetch work experience
     */
    public function getWorkExperience() {
        // Use SELECT * to avoid column name issues, then map in PHP
        // Try to order by date columns if they exist
        $rows = $this->safeFetchAll(
            "SELECT * FROM application_work_experience WHERE application_reference = ? ORDER BY id DESC", 
            [$this->reference]
        );
        
        // Try to sort by date if columns exist
        usort($rows, function($a, $b) {
            $a_year = $a['dari_tahun'] ?? 0;
            $b_year = $b['dari_tahun'] ?? 0;
            if ($a_year != $b_year) {
                return $b_year - $a_year; // DESC
            }
            $a_month = $a['dari_bulan'] ?? 0;
            $b_month = $b['dari_bulan'] ?? 0;
            return $b_month - $a_month; // DESC
        });
        
        // Map column names to expected names for consistency
        foreach ($rows as &$row) {
            // Map company column
            if (isset($row['nama_syarikat']) && !isset($row['syarikat'])) {
                $row['syarikat'] = $row['nama_syarikat'];
            }
            // Map reason column
            if (isset($row['alasan_berhenti']) && !isset($row['alasan'])) {
                $row['alasan'] = $row['alasan_berhenti'];
            }
        }
        unset($row);
        
        return $rows;
    }
    
    /**
     * Fetch extracurricular activities
     */
    public function getExtracurricular() {
        $fileCol = $this->resolveColumn('application_extracurricular', ['salinan_sijil_filename','salinan_sijil']);
        $dateCol = $this->resolveColumn('application_extracurricular', ['tarikh_sijil']);
        $createdCol = $this->resolveColumn('application_extracurricular', ['created_at']);

        $selectCols = [
            'id',
            'application_reference',
            'sukan_persatuan_kelab',
            'jawatan',
            'peringkat',
            'tahap'
        ];
        if ($dateCol) { $selectCols[] = $dateCol . ' AS tarikh_sijil'; } else { $selectCols[] = 'tahun'; }
        if ($fileCol) { $selectCols[] = "$fileCol AS salinan_sijil_filename"; }
        if ($createdCol) { $selectCols[] = $createdCol; }
        $selectCols[] = 'application_id';

        $sql = "SELECT " . implode(', ', $selectCols) . " FROM application_extracurricular WHERE application_reference = ? ORDER BY id";
        $rows = $this->safeFetchAll($sql, [$this->reference]);
        if (!$rows) {
            $appId = $this->app['id'] ?? null;
            if ($appId) {
                $sql2 = "SELECT " . implode(', ', $selectCols) . " FROM application_extracurricular WHERE application_id = ? ORDER BY id";
                $rows = $this->safeFetchAll($sql2, [$appId]);
            }
        }
        foreach ($rows as &$row) {
            if (!isset($row['salinan_sijil']) && isset($row['salinan_sijil_filename'])) {
                $row['salinan_sijil'] = $row['salinan_sijil_filename'];
            }
            if (!isset($row['created_at'])) { $row['created_at'] = ''; }
            if (!isset($row['application_id'])) { $row['application_id'] = $this->app['id'] ?? ''; }
        }
        unset($row);
        return $rows;
    }
    
    /**
     * Fetch references
     */
    public function getReferences() {
        return $this->safeFetchAll(
            "SELECT id, application_reference, nama, no_telefon as telefon, tempoh_mengenali as tempoh, jawatan, alamat FROM application_references WHERE application_reference = ? ORDER BY id", 
            [$this->reference]
        );
    }
    
    /**
     * Fetch family members
     */
    public function getFamilyMembers() {
        try {
            $check = $this->pdo->query("SHOW TABLES LIKE 'application_family_members'");
            if ($check && $check->rowCount() > 0) {
                return $this->safeFetchAll(
                    "SELECT hubungan, nama, pekerjaan, telefon, kewarganegaraan FROM application_family_members WHERE application_reference = ? ORDER BY id", 
                    [$this->reference]
                );
            }
        } catch (Exception $e) {
            // Table doesn't exist
        }
        return [];
    }
    
    /**
     * Fetch SPM additional subjects
     */
    public function getSpmAdditionalSubjects() {
        return $this->safeFetchAll(
            "SELECT id, application_reference, tahun, angka_giliran, subjek, gred, salinan_sijil, created_at FROM application_spm_additional_subjects WHERE application_reference = ? ORDER BY id", 
            [$this->reference]
        );
    }
    
    /**
     * Get all data at once
     */
    public function getAllData() {
        return [
            'health' => $this->getHealthData(),
            'language_skills' => $this->getLanguageSkills(),
            'computer_skills' => $this->getComputerSkills(),
            'professional_bodies' => $this->getProfessionalBodies(),
            'education' => $this->getEducation(),
            'spm_results' => $this->getSpmResults(),
            'spm_additional_subjects' => $this->getSpmAdditionalSubjects(),
            'work_experience' => $this->getWorkExperience(),
            'extracurricular' => $this->getExtracurricular(),
            'references' => $this->getReferences(),
            'family_members' => $this->getFamilyMembers()
        ];
    }
    
    /**
     * Log data counts for debugging
     */
    public function logDataCounts() {
        if (function_exists('error_log')) {
            $data = $this->getAllData();
            error_log("[Preview] Application Reference: {$this->reference}");
            error_log("[Preview] Health data: " . ($data['health'] ? 'Found' : 'Not found'));
            error_log("[Preview] Language rows: " . count($data['language_skills'] ?? []));
            error_log("[Preview] Computer rows: " . count($data['computer_skills'] ?? []));
            error_log("[Preview] Bodies rows: " . count($data['professional_bodies'] ?? []));
            error_log("[Preview] Education rows: " . count($data['education'] ?? []));
            error_log("[Preview] SPM rows: " . count($data['spm_results'] ?? []));
            error_log("[Preview] SPM Additional rows: " . count($data['spm_additional_subjects'] ?? []));
            error_log("[Preview] Work rows: " . count($data['work_experience'] ?? []));
            error_log("[Preview] Extra rows: " . count($data['extracurricular'] ?? []));
            error_log("[Preview] Reference rows: " . count($data['references'] ?? []));
            error_log("[Preview] Family rows: " . count($data['family_members'] ?? []));
        }
    }
}
?>
