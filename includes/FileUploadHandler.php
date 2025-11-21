<?php
/**
 * Reusable File Upload Handler
 * 
 * This class provides a centralized file upload functionality
 * that can be used across the application for various file types.
 */

class FileUploadHandler {
    private $upload_base_dir;
    private $allowed_types;
    private $max_file_size;
    
    public function __construct($upload_base_dir = 'uploads/', $max_file_size = 2097152) { // 2MB default
        $base = $upload_base_dir ?? 'uploads/';
        if (!is_string($base) || $base === '') {
            $base = 'uploads/';
        }
        $this->upload_base_dir = rtrim($base, '/') . '/';
        $this->max_file_size = $max_file_size;
        $this->allowed_types = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'
        ];
    }
    
    /**
     * Upload a single file
     * 
     * @param array $file_info - $_FILES array element
     * @param string $subfolder - Subfolder within upload directory
     * @param string $prefix - Filename prefix
     * @param int $application_id - Application ID for unique naming
     * @param string $field_name - Field identifier
     * @return array - Result array with success status and file path or error message
     */
    public function uploadFile($file_info, $subfolder = '', $prefix = 'file', $application_id = null, $field_name = '') {
        $result = [
            'success' => false,
            'file_path' => null,
            'error' => null
        ];
        
        // Check if file was uploaded
        if (!isset($file_info['tmp_name']) || empty($file_info['tmp_name'])) {
            $result['error'] = 'No file uploaded';
            return $result;
        }
        
        // Check for upload errors
        if ($file_info['error'] !== UPLOAD_ERR_OK) {
            $result['error'] = $this->getUploadErrorMessage($file_info['error']);
            return $result;
        }
        
        // Validate file size
        if ($file_info['size'] > $this->max_file_size) {
            $result['error'] = 'File size exceeds maximum allowed size of ' . $this->formatBytes($this->max_file_size);
            return $result;
        }
        
        // Validate file type - more thorough check
        $file_type = $file_info['type'];
        $file_ext = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
        
        // Check file extension first
        $valid_extension = in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'pdf']);
        if (!$valid_extension) {
            $result['error'] = "RALAT: Format fail '{$file_ext}' tidak dibenarkan. Sila muat naik fail dalam format JPG, JPEG, PNG, GIF, atau PDF sahaja.";
            return $result;
        }
        
        // Use finfo to check actual file content (more secure)
        if (!file_exists($file_info['tmp_name'])) {
            $result['error'] = 'Temporary file not found for validation';
            return $result;
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detected_mime = $finfo->file($file_info['tmp_name']);
        $valid_mime_type = in_array($detected_mime, $this->allowed_types);
        
        // Also check the declared MIME type as a fallback
        $declared_mime_valid = in_array($file_type, $this->allowed_types);
        
        // If both checks fail, reject the file
        if (!$valid_mime_type && !$declared_mime_valid) {
            $result['error'] = "RALAT: Format fail tidak dibenarkan. Fail yang dimuat naik tidak dikenali sebagai format JPG, JPEG, PNG, GIF, atau PDF yang sah.";
            return $result;
        }
        
        // Generate unique filename
        $file_ext = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
        $timestamp = time();
        $unique_id = uniqid();
        
        $filename_parts = [$prefix];
        if ($application_id) $filename_parts[] = $application_id;
        if ($field_name) $filename_parts[] = $field_name;
        $filename_parts[] = $timestamp;
        $filename_parts[] = $unique_id;
        
        $new_filename = implode('_', $filename_parts) . '.' . $file_ext;
        
        // Create upload directory
        $upload_path = $this->upload_base_dir;
        if ($subfolder) {
            $upload_path .= rtrim($subfolder, '/') . '/';
        }
        
        if (!$this->createDirectory($upload_path)) {
            $result['error'] = 'Failed to create upload directory';
            return $result;
        }
        
        // Move uploaded file (support both HTTP uploads and temp files)
        $full_path = $upload_path . $new_filename;
        if (is_uploaded_file($file_info['tmp_name'])) {
            if (move_uploaded_file($file_info['tmp_name'], $full_path)) {
                $result['success'] = true;
                $result['file_path'] = $full_path;
            } else {
                $result['error'] = 'Failed to move uploaded file';
            }
        } else {
            if (@rename($file_info['tmp_name'], $full_path) || @copy($file_info['tmp_name'], $full_path)) {
                $result['success'] = true;
                $result['file_path'] = $full_path;
            } else {
                $result['error'] = 'Failed to store temporary file';
            }
        }
        
        return $result;
    }
    
    /**
     * Upload multiple files
     * 
     * @param array $files_info - Array of file information
     * @param string $subfolder - Subfolder within upload directory
     * @param string $prefix - Filename prefix
     * @param int $application_id - Application ID for unique naming
     * @param string $field_name - Field identifier
     * @return array - Array of upload results
     */
    public function uploadMultipleFiles($files_info, $subfolder = '', $prefix = 'file', $application_id = null, $field_name = '') {
        $results = [];
        
        foreach ($files_info as $index => $file_info) {
            $field_identifier = $field_name . '_' . $index;
            $results[$index] = $this->uploadFile($file_info, $subfolder, $prefix, $application_id, $field_identifier);
        }
        
        return $results;
    }
    
    /**
     * Delete uploaded file
     * 
     * @param string $file_path - Path to file to delete
     * @return bool - Success status
     */
    public function deleteFile($file_path) {
        if (file_exists($file_path)) {
            return unlink($file_path);
        }
        return false;
    }
    
    /**
     * Set allowed file types
     * 
     * @param array $types - Array of MIME types
     */
    public function setAllowedTypes($types) {
        $this->allowed_types = $types;
    }
    
    /**
     * Set maximum file size
     * 
     * @param int $size - Maximum file size in bytes
     */
    public function setMaxFileSize($size) {
        $this->max_file_size = $size;
    }
    
    /**
     * Get allowed file extensions
     * 
     * @return array - Array of allowed extensions
     */
    private function getAllowedExtensions() {
        // Return fixed list of allowed extensions to ensure consistency
        return ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    }
    
    /**
     * Create directory if it doesn't exist
     * 
     * @param string $path - Directory path
     * @return bool - Success status
     */
    private function createDirectory($path) {
        if (!is_dir($path)) {
            return mkdir($path, 0755, true);
        }
        return true;
    }
    
    /**
     * Get upload error message
     * 
     * @param int $error_code - PHP upload error code
     * @return string - Error message
     */
    private function getUploadErrorMessage($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive in HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }
    
    /**
     * Format bytes to human readable format
     * 
     * @param int $bytes - Number of bytes
     * @return string - Formatted string
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
