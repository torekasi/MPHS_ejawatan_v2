<?php
require_once 'includes/ErrorHandler.php';
require_once 'includes/MailSender.php';

// Start session for verification code storage
session_start();

// Get database connection from config
$result = require 'config.php';
$config = $result['config'] ?? $result;

// Initialize response array
$response = [
    'success' => false,
    'message' => ''
];

// Validate NRIC
$raw_nric = $_POST['nric'] ?? '';
// Remove any non-numeric characters
$clean_nric = preg_replace('/\D/', '', $raw_nric);

if (empty($clean_nric) || strlen($clean_nric) !== 12) {
    $response['message'] = 'No. Kad Pengenalan tidak sah. Sila masukkan 12 digit nombor yang sah.\nFormat: XXXXXX-XX-XXXX';
    echo json_encode($response);
    exit;
}

// Validate format (should have hyphens in correct positions)
if (!preg_match('/^\d{6}-\d{2}-\d{4}$/', $raw_nric)) {
    $response['message'] = 'Format No. Kad Pengenalan tidak betul. Sila gunakan format: XXXXXX-XX-XXXX';
    echo json_encode($response);
    exit;
}

// Use clean NRIC for further processing
$nric = $clean_nric;

// Basic validation for Malaysian IC format
$year = substr($nric, 0, 2);
$month = substr($nric, 2, 2);
$day = substr($nric, 4, 2);
$state = substr($nric, 6, 2);

// Validate year
$current_year = date('y');
$birth_year = intval($year);
if ($birth_year > $current_year && $birth_year < 100) {
    // If birth year is greater than current year, it must be from previous century
    $birth_year += 1900;
} else {
    $birth_year += ($birth_year <= 30 ? 2000 : 1900);
}

// Calculate age
$age = date('Y') - $birth_year;
if (date('md') < $month . $day) {
    $age--;
}

// Validate age (must be at least 18 years old)
if ($age < 18) {
    $response['message'] = 'Umur mestilah 18 tahun ke atas untuk memohon.';
    echo json_encode($response);
    exit;
}

// Validate month (01-12)
if ($month < 1 || $month > 12) {
    $response['message'] = 'Format No. Kad Pengenalan tidak sah (bulan tidak sah).';
    echo json_encode($response);
    exit;
}

// Get days in month (accounting for leap years)
$days_in_month = cal_days_in_month(CAL_GREGORIAN, intval($month), $birth_year);

// Validate day based on actual month length
if ($day < 1 || $day > $days_in_month) {
    $response['message'] = "Format No. Kad Pengenalan tidak sah (hari tidak sah untuk bulan $month).";
    echo json_encode($response);
    exit;
}

// Validate birth date is not in future
$birth_date = DateTime::createFromFormat('Y-m-d', $birth_year . '-' . $month . '-' . $day);
$today = new DateTime();
if ($birth_date > $today) {
    $response['message'] = 'Format No. Kad Pengenalan tidak sah (tarikh lahir masa hadapan).';
    echo json_encode($response);
    exit;
}

// Validate state code with specific state names
$state_codes = [
    '01' => 'Johor',
    '02' => 'Kedah',
    '03' => 'Kelantan',
    '04' => 'Melaka',
    '05' => 'Negeri Sembilan',
    '06' => 'Pahang',
    '07' => 'Perak',
    '08' => 'Perlis',
    '09' => 'Pulau Pinang',
    '10' => 'Selangor',
    '11' => 'Terengganu',
    '12' => 'Sabah',
    '13' => 'Sarawak',
    '14' => 'Wilayah Persekutuan Kuala Lumpur',
    '15' => 'Wilayah Persekutuan Labuan',
    '16' => 'Wilayah Persekutuan Putrajaya',
    '21' => 'Negeri Tidak Diketahui',
    '22' => 'Negeri Tidak Diketahui',
    '23' => 'Negeri Tidak Diketahui',
    '24' => 'Negeri Tidak Diketahui',
    '25' => 'Negeri Tidak Diketahui',
    '26' => 'Negeri Tidak Diketahui',
    '27' => 'Negeri Tidak Diketahui',
    '28' => 'Negeri Tidak Diketahui',
    '29' => 'Negeri Tidak Diketahui',
    '30' => 'Negeri Tidak Diketahui',
    '31' => 'Negeri Tidak Diketahui',
    '32' => 'Negeri Tidak Diketahui',
    '33' => 'Negeri Tidak Diketahui',
    '34' => 'Negeri Tidak Diketahui',
    '35' => 'Negeri Tidak Diketahui',
    '36' => 'Negeri Tidak Diketahui',
    '37' => 'Negeri Tidak Diketahui',
    '38' => 'Negeri Tidak Diketahui',
    '39' => 'Negeri Tidak Diketahui',
    '40' => 'Negeri Tidak Diketahui',
    '41' => 'Negeri Tidak Diketahui',
    '42' => 'Negeri Tidak Diketahui',
    '43' => 'Negeri Tidak Diketahui',
    '44' => 'Negeri Tidak Diketahui',
    '45' => 'Negeri Tidak Diketahui',
    '46' => 'Negeri Tidak Diketahui',
    '47' => 'Negeri Tidak Diketahui',
    '48' => 'Negeri Tidak Diketahui',
    '49' => 'Negeri Tidak Diketahui',
    '50' => 'Negeri Tidak Diketahui',
    '51' => 'Pendaftaran di Luar Negara',
    '52' => 'Pendaftaran di Luar Negara',
    '53' => 'Pendaftaran di Luar Negara',
    '54' => 'Pendaftaran di Luar Negara',
    '55' => 'Pendaftaran di Luar Negara',
    '56' => 'Pendaftaran di Luar Negara',
    '57' => 'Pendaftaran di Luar Negara',
    '58' => 'Pendaftaran di Luar Negara',
    '59' => 'Pendaftaran di Luar Negara',
    '60' => 'Pendaftaran di Luar Negara',
    '61' => 'Pendaftaran di Luar Negara',
    '62' => 'Pendaftaran di Luar Negara',
    '63' => 'Pendaftaran di Luar Negara',
    '64' => 'Pendaftaran di Luar Negara',
    '65' => 'Pendaftaran di Luar Negara',
    '66' => 'Pendaftaran di Luar Negara',
    '67' => 'Pendaftaran di Luar Negara',
    '68' => 'Pendaftaran di Luar Negara',
    '69' => 'Pendaftaran di Luar Negara',
    '70' => 'Pemastautin Tetap',
    '71' => 'Pemastautin Tetap',
    '72' => 'Pemastautin Tetap',
    '73' => 'Pemastautin Tetap',
    '74' => 'Pemastautin Tetap',
    '75' => 'Pemastautin Tetap',
    '76' => 'Pemastautin Tetap',
    '77' => 'Pemastautin Tetap',
    '78' => 'Pemastautin Tetap',
    '79' => 'Pemastautin Tetap',
    '80' => 'Pemastautin Tetap',
    '81' => 'Pemastautin Tetap',
    '82' => 'Pemastautin Tetap',
    '83' => 'Pemastautin Tetap',
    '84' => 'Pemastautin Tetap',
    '85' => 'Pemastautin Tetap',
    '86' => 'Pemastautin Tetap',
    '87' => 'Pemastautin Tetap',
    '88' => 'Pemastautin Tetap',
    '89' => 'Pemastautin Tetap',
    '90' => 'Pemastautin Tetap',
    '91' => 'Pemastautin Tetap',
    '92' => 'Pemastautin Tetap',
    '93' => 'Pemastautin Tetap',
    '94' => 'Pemastautin Tetap',
    '95' => 'Pemastautin Tetap',
    '96' => 'Pemastautin Tetap',
    '97' => 'Pemastautin Tetap',
    '98' => 'Pemastautin Tetap',
    '99' => 'Pemastautin Tetap'
];

if (!isset($state_codes[$state])) {
    $response['message'] = 'Format No. Kad Pengenalan tidak sah (kod negeri tidak sah).';
    echo json_encode($response);
    exit;
}

// Add detailed NRIC info to session for display
$_SESSION['nric_info'] = [
    'state' => $state_codes[$state],
    'state_code' => $state,
    'birth_date' => sprintf('%s-%s-%s', 
        (intval($year) > 30 ? '19' : '20') . $year,
        $month,
        $day
    ),
    'gender' => (intval(substr($nric, -1)) % 2 === 0) ? 'Perempuan' : 'Lelaki',
    'age' => $age,
    'status' => $state_int >= 70 ? 'Pemastautin Tetap' : 
                ($state_int >= 51 && $state_int <= 69 ? 'Pendaftaran di Luar Negara' : 
                'Warganegara Malaysia')
];

try {
    // Connect to database
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);

    // Check if NRIC exists in job_applications table
    $stmt = $pdo->prepare("SELECT id, email, nama_penuh FROM job_applications WHERE nombor_ic = ? ORDER BY application_date DESC LIMIT 1");
    $stmt->execute([$nric]);
    $application = $stmt->fetch();

    if (!$application) {
        $response['message'] = 'Tiada permohonan dijumpai untuk No. Kad Pengenalan ini.';
        echo json_encode($response);
        exit;
    }

    // Generate 6-digit verification code
    $verification_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Store verification code in session with timestamp
    $_SESSION['verification'] = [
        'nric' => $nric,
        'code' => $verification_code,
        'expires' => time() + (15 * 60), // 15 minutes expiry
        'attempts' => 0
    ];

    // Send verification code via email
    $mailSender = new MailSender($config);
    $email_content = "
        <p>Salam {$application['nama_penuh']},</p>
        <p>Berikut adalah kod pengesahan untuk menyemak status permohonan anda:</p>
        <h2 style='font-size: 24px; color: #2563eb; letter-spacing: 2px;'>{$verification_code}</h2>
        <p>Kod ini sah untuk 15 minit sahaja.</p>
        <p>Jika anda tidak meminta kod ini, sila abaikan email ini.</p>
        <br>
        <p>Terima kasih,</p>
        <p>Unit Sumber Manusia<br>Majlis Perbandaran Hulu Selangor</p>
    ";

    $mailSender->send(
        $application['email'],
        'Kod Pengesahan Status Permohonan - eJawatan MPHS',
        $email_content
    );

    // Log the verification code request
    error_log("Verification code sent to {$application['email']} for NRIC: {$nric}");

    $response['success'] = true;
    $response['message'] = 'Kod pengesahan telah dihantar ke email anda.';
    $response['nric_info'] = $_SESSION['nric_info'];

} catch (PDOException $e) {
    error_log('Database error in send-verification.php: ' . $e->getMessage());
    $response['message'] = 'Ralat sistem. Sila cuba lagi sebentar.';
} catch (Exception $e) {
    error_log('Error in send-verification.php: ' . $e->getMessage());
    $response['message'] = 'Ralat menghantar kod pengesahan. Sila cuba lagi.';
}

echo json_encode($response);
