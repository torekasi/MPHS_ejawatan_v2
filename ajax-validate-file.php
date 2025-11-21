<?php
// AJAX File Validation Endpoint
// Validates file size and type server-side

// Prevent direct access and ensure it's an AJAX request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Basic security check - ensure it's an AJAX request
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    exit('Unauthorized');
}

// Validate CSRF token
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== 'ajax_validation') {
    http_response_code(403);
    exit('Invalid token');
}

// Check if file was uploaded
if (empty($_FILES['file'])) {
    http_response_code(400);
    exit(json_encode(['valid' => false, 'message' => 'Tiada fail dimuat naik']));
}

$file = $_FILES['file'];
$field_name = $_POST['field_name'] ?? 'unknown';

// Get file information
$file_name = $file['name'];
$file_size = $file['size'];
$file_type = $file['tmp_name'] ? mime_content_type($file['tmp_name']) : '';
$file_size_mb = round($file_size / 1024 / 1024, 2);

// Define validation rules for each field
$validation_rules = [
    'gambar_passport' => [
        'max_size_mb' => 2,
        'allowed_types' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'],
        'field_label' => 'Gambar Passport'
    ],
    'salinan_ic' => [
        'max_size_mb' => 2,
        'allowed_types' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'],
        'field_label' => 'Salinan Kad Pengenalan'
    ],
    'salinan_surat_beranak' => [
        'max_size_mb' => 2,
        'allowed_types' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'],
        'field_label' => 'Salinan Surat Beranak'
    ],
    'salinan_lesen_memandu' => [
        'max_size_mb' => 2,
        'allowed_types' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'],
        'field_label' => 'Salinan Lesen Memandu'
    ],
    'badan_profesional' => [
        'max_size_mb' => 3,
        'allowed_types' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf', 'image/bmp'],
        'field_label' => 'Sijil Badan Profesional'
    ],
    'kegiatan_luar' => [
        'max_size_mb' => 2,
        'allowed_types' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'],
        'field_label' => 'Sijil Kegiatan Luar'
    ],
    'spm_results' => [
        'max_size_mb' => 2,
        'allowed_types' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'],
        'field_label' => 'Sijil SPM/SPV'
    ],
    'persekolahan' => [
        'max_size_mb' => 2,
        'allowed_types' => ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'image/gif'],
        'field_label' => 'Sijil Pendidikan'
    ]
];

// Get validation rules for this field
$rules = $validation_rules[$field_name] ?? [
    'max_size_mb' => 2,
    'allowed_types' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'],
    'field_label' => ucfirst(str_replace('_', ' ', $field_name))
];

// Validate file size
if ($file_size_mb > $rules['max_size_mb']) {
    $response = [
        'valid' => false,
        'message' => "{$rules['field_label']}: Saiz fail melebihi {$rules['max_size_mb']}MB (saiz semasa {$file_size_mb}MB)",
        'field_label' => $rules['field_label'],
        'file_size_mb' => $file_size_mb,
        'file_name' => $file_name,
        'field_name' => $field_name
    ];
    echo json_encode($response);
    exit;
}

// Validate file type
if (!in_array($file_type, $rules['allowed_types'])) {
    $response = [
        'valid' => false,
        'message' => "{$rules['field_label']}: Jenis fail tidak dibenarkan ({$file_type})",
        'field_label' => $rules['field_label'],
        'file_size_mb' => $file_size_mb,
        'file_name' => $file_name,
        'field_name' => $field_name
    ];
    echo json_encode($response);
    exit;
}

// File is valid
$response = [
    'valid' => true,
    'message' => "{$rules['field_label']} sah",
    'field_label' => $rules['field_label'],
    'file_size_mb' => $file_size_mb,
    'file_name' => $file_name,
    'field_name' => $field_name,
    'show_toast' => true
];

// Clean up the temporary file
if (file_exists($file['tmp_name'])) {
    @unlink($file['tmp_name']);
}

echo json_encode($response);
?>
