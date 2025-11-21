<?php
use RuntimeException;
/**
 * Centralized File Uploader Module
 * 
 * This module provides standardized file upload functionality across all application pages
 * with consistent directory structure: /uploads/applications/<year>/<application_reference>/<file>
 */

class FileUploader {
    private $allowed_types;
    private $max_file_size;
    private $relative_upload_base = 'uploads/applications';
    private $absolute_upload_root;
    
    /**
     * Constructor
     * 
     * @param int $max_file_size Maximum file size in bytes (default: 5MB)
     */
    public function __construct($max_file_size = 5242880) {
        $this->max_file_size = $max_file_size;
        $this->allowed_types = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'
        ];

        $this->absolute_upload_root = $this->resolveUploadRoot();
    }
    
    /**
     * Upload a file using standardized directory structure
     * 
     * @param string $file_field The name of the file input field
     * @param string $application_reference The application reference code
     * @param string $prefix File name prefix
     * @param string $custom_subfolder Optional custom subfolder within the application directory
     * @return array Result with success status and file path or error
     */
    public function uploadFile($file_field, $application_reference, $prefix = '', $custom_subfolder = '') {
        $result = [
            'success' => false,
            'file_path' => null,
            'error' => null
        ];
        
        // Check if file was uploaded
        if (!isset($_FILES[$file_field]) || $_FILES[$file_field]['error'] === UPLOAD_ERR_NO_FILE) {
            $result['error'] = 'No file uploaded';
            return $result;
        }
        
        // Check for upload errors
        if ($_FILES[$file_field]['error'] !== UPLOAD_ERR_OK) {
            $result['error'] = $this->getUploadErrorMessage($_FILES[$file_field]['error']);
            return $result;
        }
        
        // Validate file size
        if ($_FILES[$file_field]['size'] > $this->max_file_size) {
            $result['error'] = 'File size exceeds maximum allowed size of ' . $this->formatBytes($this->max_file_size);
            return $result;
        }
        
        // Validate file type
        $file_type = $_FILES[$file_field]['type'];
        $file_ext = strtolower(pathinfo($_FILES[$file_field]['name'], PATHINFO_EXTENSION));
        
        // Check file extension
        $valid_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        if (!in_array($file_ext, $valid_extensions)) {
            $result['error'] = "RALAT: Format fail '{$file_ext}' tidak dibenarkan. Sila muat naik fail dalam format JPG, JPEG, PNG, GIF, atau PDF sahaja.";
            return $result;
        }
        
        // Use finfo to check actual file content (more secure)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detected_mime = $finfo->file($_FILES[$file_field]['tmp_name']);
        $valid_mime_type = in_array($detected_mime, $this->allowed_types);
        
        // Also check the declared MIME type as a fallback
        $declared_mime_valid = in_array($file_type, $this->allowed_types);
        
        // If both checks fail, reject the file
        if (!$valid_mime_type && !$declared_mime_valid) {
            $result['error'] = "RALAT: Format fail tidak dibenarkan. Fail yang dimuat naik tidak dikenali sebagai format JPG, JPEG, PNG, GIF, atau PDF yang sah.";
            return $result;
        }
        
        // Create standardized directory structure: /uploads/applications/<year>/<application_reference>/
        $year = date('Y');
        $relative_dir = $this->relative_upload_base . '/' . $year . '/' . $application_reference . '/';
        
        // Add custom subfolder if provided
        if (!empty($custom_subfolder)) {
            $relative_dir .= trim($custom_subfolder, '/') . '/';
        }
        
        $full_upload_path = rtrim($this->absolute_upload_root, '/') . '/' . $year . '/' . $application_reference . '/';
        if (!empty($custom_subfolder)) {
            $full_upload_path .= trim($custom_subfolder, '/') . '/';
        }
        
        // Create directory if it doesn't exist
        if (!$this->createDirectory($full_upload_path)) {
            $result['error'] = 'Failed to create upload directory';
            return $result;
        }
        
        // Generate unique filename
        $timestamp = time();
        $unique_id = uniqid();
        
        // Build filename with prefix if provided
        $filename_parts = [];
        if (!empty($prefix)) {
            $filename_parts[] = $prefix;
        }
        $filename_parts[] = $timestamp;
        $filename_parts[] = $unique_id;
        
        $new_filename = implode('_', $filename_parts) . '.' . $file_ext;
        $filepath = $full_upload_path . $new_filename;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES[$file_field]['tmp_name'], $filepath)) {
            $result['success'] = true;
            // Return the web-accessible path (relative to document root)
            $result['file_path'] = $relative_dir . $new_filename;
        } else {
            $result['error'] = 'Failed to move uploaded file';
        }
        
        return $result;
    }
    
    /**
     * Upload multiple files with the same field name (e.g., multiple[] input)
     * 
     * @param string $file_field The name of the file input field
     * @param string $application_reference The application reference code
     * @param string $prefix File name prefix
     * @param string $custom_subfolder Optional custom subfolder within the application directory
     * @return array Results for each uploaded file
     */
    public function uploadMultipleFiles($file_field, $application_reference, $prefix = '', $custom_subfolder = '') {
        $results = [];
        
        // Check if files were uploaded
        if (!isset($_FILES[$file_field]) || !is_array($_FILES[$file_field]['name'])) {
            return [['success' => false, 'error' => 'No files uploaded or invalid format']];
        }
        
        $file_count = count($_FILES[$file_field]['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            // Create a temporary single file structure
            $temp_file = [
                'name' => $_FILES[$file_field]['name'][$i],
                'type' => $_FILES[$file_field]['type'][$i],
                'tmp_name' => $_FILES[$file_field]['tmp_name'][$i],
                'error' => $_FILES[$file_field]['error'][$i],
                'size' => $_FILES[$file_field]['size'][$i]
            ];
            
            // Skip empty uploads
            if ($temp_file['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            
            // Create a temporary file field
            $temp_field = $file_field . '_' . $i;
            $_FILES[$temp_field] = $temp_file;
            
            // Upload the file
            $index_prefix = $prefix . '_' . ($i + 1);
            $results[$i] = $this->uploadFile($temp_field, $application_reference, $index_prefix, $custom_subfolder);
            
            // Clean up temporary file field
            unset($_FILES[$temp_field]);
        }
        
        return $results;
    }
    
    /**
     * Delete an uploaded file
     * 
     * @param string $file_path Path to the file (relative to document root)
     * @return bool Success status
     */
    public function deleteFile($file_path) {
        // Convert relative path to full path
        $full_path = $_SERVER['DOCUMENT_ROOT'] ? 
            rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($file_path, '/') : 
            $file_path;
            
        if (file_exists($full_path)) {
            return unlink($full_path);
        }
        return false;
    }
    
    /**
     * Set allowed file types
     * 
     * @param array $types Array of MIME types
     */
    public function setAllowedTypes($types) {
        $this->allowed_types = $types;
    }
    
    /**
     * Set maximum file size
     * 
     * @param int $size Maximum file size in bytes
     */
    public function setMaxFileSize($size) {
        $this->max_file_size = $size;
    }
    
    /**
     * Create directory if it doesn't exist
     * 
     * @param string $path Directory path
     * @return bool Success status
     */
    private function createDirectory($path) {
        if (!is_dir($path)) {
            if (@mkdir($path, 0775, true)) {
                @chmod($path, 0775);
                return true;
            }
            $error = error_get_last();
            error_log('FileUploader mkdir failed for ' . $path . ': ' . ($error['message'] ?? 'unknown error'));
            return false;
        }
        return true;
    }

    /**
     * Resolve an absolute storage root for uploads.
     */
    private function resolveUploadRoot(): string
    {
        $relative = $this->relative_upload_base;
        $candidateRoots = [];

        $documentRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
        if ($documentRoot !== '' && is_dir($documentRoot)) {
            $candidateRoots[] = $documentRoot;
        }

        $projectRoot = realpath(__DIR__ . '/..');
        if ($projectRoot) {
            $publicDir = $projectRoot . '/public';
            if (is_dir($publicDir)) {
                $candidateRoots[] = $publicDir;
            }
            $candidateRoots[] = $projectRoot;
        }

        foreach ($candidateRoots as $root) {
            $candidatePath = rtrim($root, '/') . '/' . $relative;
            if ($this->createDirectory($candidatePath)) {
                return rtrim($candidatePath, '/');
            }
        }

        $fallback = sys_get_temp_dir() . '/' . $relative;
        if ($this->createDirectory($fallback)) {
            error_log('FileUploader fallback to temporary directory: ' . $fallback);
            return rtrim($fallback, '/');
        }

        throw new RuntimeException('Unable to prepare upload directory for applications.');
    }

    /**
     * Expose absolute upload root for helper utilities.
     */
    public function getAbsoluteUploadRoot(): string
    {
        return $this->absolute_upload_root;
    }
    
    /**
     * Get upload error message
     * 
     * @param int $error_code PHP upload error code
     * @return string Error message
     */
    private function getUploadErrorMessage($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'Fail melebihi had saiz yang dibenarkan oleh pelayan';
            case UPLOAD_ERR_FORM_SIZE:
                return 'Fail melebihi had saiz yang dibenarkan oleh borang';
            case UPLOAD_ERR_PARTIAL:
                return 'Fail hanya dimuat naik sebahagian sahaja';
            case UPLOAD_ERR_NO_FILE:
                return 'Tiada fail dimuat naik';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Folder sementara tidak dijumpai';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Gagal menulis fail ke cakera';
            case UPLOAD_ERR_EXTENSION:
                return 'Muat naik fail dihentikan oleh sambungan';
            default:
                return 'Ralat muat naik tidak diketahui';
        }
    }
    
    /**
     * Format bytes to human readable format
     * 
     * @param int $bytes Number of bytes
     * @return string Formatted string
     */
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
?>
