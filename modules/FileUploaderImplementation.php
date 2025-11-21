<?php
/**
 * FileUploader Implementation Module
 * 
 * This file provides functions to standardize file uploads across all application pages
 * using the centralized FileUploader module with consistent directory structure:
 * /uploads/applications/<year>/<application_reference>/<file>
 */

require_once __DIR__ . '/FileUploader.php';

/**
 * Upload a document for job application
 * 
 * @param string $file_field The name of the file input field
 * @param string $application_reference The application reference code
 * @param string $prefix File name prefix
 * @param string $custom_subfolder Optional custom subfolder within the application directory
 * @return string|null The file path if successful, null otherwise
 */
function uploadApplicationDocument($file_field, $application_reference, $prefix = '', $custom_subfolder = '') {
    $uploader = new FileUploader();
    $result = $uploader->uploadFile($file_field, $application_reference, $prefix, $custom_subfolder);
    
    if ($result['success']) {
        return $result['file_path'];
    }
    
    if ($result['error'] && $result['error'] !== 'No file uploaded') {
        error_log("File upload error for {$file_field}: " . $result['error']);
    }
    
    return null;
}

/**
 * Upload multiple documents for job application
 * 
 * @param string $file_field The name of the file input field (must be array)
 * @param string $application_reference The application reference code
 * @param string $prefix File name prefix
 * @param string $custom_subfolder Optional custom subfolder within the application directory
 * @return array Array of file paths for successful uploads
 */
function uploadMultipleApplicationDocuments($file_field, $application_reference, $prefix = '', $custom_subfolder = '') {
    $uploader = new FileUploader();
    $results = $uploader->uploadMultipleFiles($file_field, $application_reference, $prefix, $custom_subfolder);
    
    $file_paths = [];
    foreach ($results as $index => $result) {
        if ($result['success']) {
            $file_paths[$index] = $result['file_path'];
        } elseif ($result['error'] && $result['error'] !== 'No file uploaded') {
            error_log("File upload error for {$file_field}[{$index}]: " . $result['error']);
        }
    }
    
    return $file_paths;
}

/**
 * Validate if a file meets upload requirements without actually uploading it
 * 
 * @param array $file_info The file info array from $_FILES
 * @param int $max_size Maximum allowed file size in bytes
 * @param array $allowed_types Array of allowed MIME types
 * @return array Result with success status and error message if any
 */
function validateApplicationFile($file_info, $max_size = 5242880, $allowed_types = null) {
    $uploader = new FileUploader($max_size);
    
    if ($allowed_types) {
        $uploader->setAllowedTypes($allowed_types);
    }
    
    $result = [
        'success' => false,
        'error' => null
    ];
    
    // Check if file was uploaded
    if (!isset($file_info) || !isset($file_info['tmp_name']) || empty($file_info['tmp_name'])) {
        $result['error'] = 'No file uploaded';
        return $result;
    }
    
    // Check for upload errors
    if ($file_info['error'] !== UPLOAD_ERR_OK) {
        switch ($file_info['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $result['error'] = 'Fail melebihi had saiz yang dibenarkan oleh pelayan';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $result['error'] = 'Fail melebihi had saiz yang dibenarkan oleh borang';
                break;
            case UPLOAD_ERR_PARTIAL:
                $result['error'] = 'Fail hanya dimuat naik sebahagian sahaja';
                break;
            case UPLOAD_ERR_NO_FILE:
                $result['error'] = 'Tiada fail dimuat naik';
                break;
            default:
                $result['error'] = 'Ralat muat naik tidak diketahui';
        }
        return $result;
    }
    
    // Validate file size
    if ($file_info['size'] > $max_size) {
        $result['error'] = 'Fail melebihi had saiz yang dibenarkan';
        return $result;
    }
    
    // Validate file type
    $file_ext = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
    $valid_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    
    if (!in_array($file_ext, $valid_extensions)) {
        $result['error'] = "Format fail '{$file_ext}' tidak dibenarkan. Sila muat naik fail dalam format JPG, JPEG, PNG, GIF, atau PDF sahaja.";
        return $result;
    }
    
    // Use finfo to check actual file content (more secure)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detected_mime = $finfo->file($file_info['tmp_name']);
    
    $default_allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
    $types_to_check = $allowed_types ?? $default_allowed_types;
    
    $valid_mime_type = in_array($detected_mime, $types_to_check);
    $declared_mime_valid = in_array($file_info['type'], $types_to_check);
    
    if (!$valid_mime_type && !$declared_mime_valid) {
        $result['error'] = "Format fail tidak dibenarkan. Fail yang dimuat naik tidak dikenali sebagai format yang sah.";
        return $result;
    }
    
    // All checks passed
    $result['success'] = true;
    return $result;
}

/**
 * Get the standardized upload directory path for an application
 * 
 * @param string $application_reference The application reference code
 * @param string $custom_subfolder Optional custom subfolder within the application directory
 * @return string The directory path
 */
function getApplicationUploadDirectory($application_reference, $custom_subfolder = '') {
    $uploader = new FileUploader();
    $absoluteRoot = $uploader->getAbsoluteUploadRoot();
    $year = date('Y');

    $relativePath = 'uploads/applications/' . $year . '/' . $application_reference . '/';
    $absolutePath = rtrim($absoluteRoot, '/') . '/' . $year . '/' . $application_reference . '/';

    if (!empty($custom_subfolder)) {
        $relativePath .= trim($custom_subfolder, '/') . '/';
        $absolutePath .= trim($custom_subfolder, '/') . '/';
    }

    if (!is_dir($absolutePath)) {
        @mkdir($absolutePath, 0775, true);
        @chmod($absolutePath, 0775);
    }

    return $relativePath;
}
?>
