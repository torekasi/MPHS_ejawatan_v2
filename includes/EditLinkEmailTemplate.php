<?php
/**
 * Email Template for Edit Link
 * Generates secure edit links and email content for existing applications
 */

class EditLinkEmailTemplate {
    private $config;
    private $base_url;
    
    public function __construct($config) {
        $this->config = $config;
        $this->base_url = $config['base_url'] ?? 'http://localhost:8000';
    }
    
    /**
     * Generate secure edit token
     * @param array $application - Application data
     * @return string - Secure token
     */
    public function generateEditToken($application) {
        $data = [
            'id' => $application['id'],
            'email' => $application['email'],
            'nric' => preg_replace('/[^0-9]/', '', $application['nombor_ic']),
            'expires' => time() + (24 * 60 * 60) // 24 hours from now
        ];
        
        // Create a secure token using hash
        $token_data = base64_encode(json_encode($data));
        $signature = hash_hmac('sha256', $token_data, $this->config['app_secret'] ?? 'default_secret_key');
        
        return $token_data . '.' . $signature;
    }
    
    /**
     * Verify edit token
     * @param string $token - Token to verify
     * @return array|false - Application data if valid, false if invalid
     */
    public function verifyEditToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return false;
        }
        
        [$token_data, $signature] = $parts;
        
        // Verify signature
        $expected_signature = hash_hmac('sha256', $token_data, $this->config['app_secret'] ?? 'default_secret_key');
        if (!hash_equals($expected_signature, $signature)) {
            return false;
        }
        
        // Decode data
        $data = json_decode(base64_decode($token_data), true);
        if (!$data) {
            return false;
        }
        
        // Check expiration
        if (time() > $data['expires']) {
            return false;
        }
        
        return $data;
    }
    
    /**
     * Generate edit link URL
     * @param array $application - Application data
     * @return string - Edit link URL
     */
    public function generateEditLink($application) {
        $token = $this->generateEditToken($application);
        return $this->base_url . '/edit-application.php?token=' . urlencode($token);
    }
    
    /**
     * Generate email subject
     * @param array $application - Application data
     * @param array $job - Job data
     * @return string - Email subject
     */
    public function getEmailSubject($application, $job = null) {
        $job_title = $job ? $job['job_title'] : 'Jawatan';
        return "eJawatan MPHS - Pautan Edit Permohonan: " . strtoupper($job_title);
    }
    
    /**
     * Generate email body (HTML)
     * @param array $application - Application data
     * @param array $job - Job data
     * @param string $edit_link - Edit link URL
     * @return string - Email HTML content
     */
    public function getEmailBody($application, $job, $edit_link) {
        $applicant_name = strtoupper($application['nama_penuh'] ?? 'Pemohon');
        $job_title = strtoupper($job['job_title'] ?? 'JAWATAN');
        $app_ref = $application['application_reference'] ?? 'N/A';
        $expiry_time = date('d/m/Y H:i', time() + (24 * 60 * 60));
        $logo = rtrim($this->base_url, '/') . '/' . ltrim((string)($this->config['logo_url'] ?? ''), '/');
        
        return "
        <!DOCTYPE html>
        <html lang='ms'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Pautan Edit Permohonan - eJawatan MPHS</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #e0f2fe; color: #1e3a8a; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
                .btn { display: inline-block; background: #10b981; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 20px 0; }
                .btn:hover { background: #059669; }
                .info-box { background: #e0f2fe; border-left: 4px solid #0891b2; padding: 15px; margin: 20px 0; }
                .warning-box { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; font-size: 0.9em; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='{$logo}' alt='Logo' style='height:48px;margin-bottom:0'>
                    <h1>eJawatan MPHS</h1>
                    <p>Majlis Perbandaran Hulu Selangor</p>
                </div>
                
                <div class='content'>
                    <h2>Pautan Edit Permohonan Jawatan</h2>
                    
                    <p>Assalamualaikum dan salam sejahtera <strong>{$applicant_name}</strong>,</p>
                    
                    <p>Kami telah menerima permintaan anda untuk mengedit permohonan jawatan yang telah dibuat sebelum ini.</p>
                    
                    <div class='info-box'>
                        <h3>Maklumat Permohonan:</h3>
                        <ul>
                            <li><strong>Jawatan:</strong> {$job_title}</li>
                            <li><strong>Rujukan Permohonan:</strong> {$app_ref}</li>
                            <li><strong>Email Pemohon:</strong> {$application['email']}</li>
                        </ul>
                    </div>
                    
                    <p>Untuk mengedit permohonan anda, sila klik pautan di bawah:</p>
                    
                    <div style='text-align: center;'>
                        <a href='{$edit_link}' class='btn'>EDIT PERMOHONAN SAYA</a>
                    </div>
                    
                    <div class='warning-box'>
                        <h4>⚠️ PENTING:</h4>
                        <ul>
                            <li>Pautan ini sah sehingga <strong>{$expiry_time}</strong> sahaja</li>
                            <li>Pautan ini hanya boleh digunakan sekali</li>
                            <li>Jangan kongsi pautan ini dengan orang lain</li>
                            <li>Jika anda tidak meminta pautan ini, sila abaikan email ini</li>
                        </ul>
                    </div>
                    
                    <p>Jika pautan di atas tidak berfungsi, sila salin dan tampal URL berikut ke dalam pelayar web anda:</p>
                    <p style='word-break: break-all; background: #f1f5f9; padding: 10px; border-radius: 4px; font-family: monospace;'>{$edit_link}</p>
                    
                    <p>Sebarang pertanyaan, sila hubungi pihak kami di:</p>
                    <ul>
                        <li><strong>Telefon:</strong> 03-6064 2611</li>
                        <li><strong>Email:</strong> hr@mphs.gov.my</li>
                        <li><strong>Waktu Operasi:</strong> Isnin - Jumaat, 8:00 AM - 5:00 PM</li>
                    </ul>
                </div>
                
                <div class='footer'>
                    <p>Email ini dijana secara automatik oleh sistem eJawatan MPHS.</p>
                    <p>Sila jangan membalas email ini.</p>
                    <p>&copy; " . date('Y') . " Majlis Perbandaran Hulu Selangor. Hak Cipta Terpelihara.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Generate email body (Plain text)
     * @param array $application - Application data
     * @param array $job - Job data
     * @param string $edit_link - Edit link URL
     * @return string - Email plain text content
     */
    public function getEmailBodyText($application, $job, $edit_link) {
        $applicant_name = strtoupper($application['nama_penuh'] ?? 'Pemohon');
        $job_title = strtoupper($job['job_title'] ?? 'JAWATAN');
        $app_ref = $application['application_reference'] ?? 'N/A';
        $expiry_time = date('d/m/Y H:i', time() + (24 * 60 * 60));
        
        return "
EJAWATAN MPHS - PAUTAN EDIT PERMOHONAN
=====================================

Assalamualaikum dan salam sejahtera {$applicant_name},

Kami telah menerima permintaan anda untuk mengedit permohonan jawatan yang telah dibuat sebelum ini.

MAKLUMAT PERMOHONAN:
- Jawatan: {$job_title}
- Rujukan Permohonan: {$app_ref}
- Email Pemohon: {$application['email']}

Untuk mengedit permohonan anda, sila klik pautan di bawah:
{$edit_link}

PENTING:
- Pautan ini sah sehingga {$expiry_time} sahaja
- Pautan ini hanya boleh digunakan sekali
- Jangan kongsi pautan ini dengan orang lain
- Jika anda tidak meminta pautan ini, sila abaikan email ini

Sebarang pertanyaan, sila hubungi pihak kami di:
- Telefon: 03-6064 2611
- Email: hr@mphs.gov.my
- Waktu Operasi: Isnin - Jumaat, 8:00 AM - 5:00 PM

Email ini dijana secara automatik oleh sistem eJawatan MPHS.
Sila jangan membalas email ini.

© " . date('Y') . " Majlis Perbandaran Hulu Selangor. Hak Cipta Terpelihara.
        ";
    }
    
    /**
     * Generate security notification email body (HTML) for incorrect email attempts
     * @param array $application - Application data
     * @param array $job - Job data
     * @param string $submitted_email - The incorrect email that was submitted
     * @return string - Email HTML content
     */
    public function getSecurityNotificationBody($application, $job, $submitted_email) {
        $applicant_name = strtoupper($application['nama_penuh'] ?? 'Pemohon');
        $job_title = strtoupper($job['job_title'] ?? 'JAWATAN');
        $app_ref = $application['application_reference'] ?? 'N/A';
        $current_time = date('d/m/Y H:i:s');
        $masked_email = $this->maskEmail($submitted_email);
        $logo = rtrim($this->base_url, '/') . '/' . ltrim((string)($this->config['logo_url'] ?? ''), '/');
        
        return "
        <!DOCTYPE html>
        <html lang='ms'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Amaran Keselamatan - eJawatan MPHS</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
                .alert-box { background: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin: 20px 0; }
                .info-box { background: #e0f2fe; border-left: 4px solid #0891b2; padding: 15px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; font-size: 0.9em; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div style='background:#e0f2fe;display:inline-block;padding:8px;border-radius:6px'><img src='{$logo}' alt='Logo' style='height:48px;margin-bottom:0'></div>
                    <h1>🔒 AMARAN KESELAMATAN</h1>
                    <p>eJawatan MPHS - Majlis Perbandaran Hulu Selangor</p>
                </div>
                
                <div class='content'>
                    <h2>Percubaan Edit Permohonan Tidak Sah</h2>
                    
                    <p>Assalamualaikum dan salam sejahtera <strong>{$applicant_name}</strong>,</p>
                    
                    <div class='alert-box'>
                        <h3>⚠️ AMARAN KESELAMATAN</h3>
                        <p>Seseorang telah cuba meminta pautan edit untuk permohonan jawatan anda menggunakan email yang <strong>TIDAK SAH</strong>.</p>
                        <ul>
                            <li><strong>Masa Percubaan:</strong> {$current_time}</li>
                            <li><strong>Email yang Digunakan:</strong> {$masked_email}</li>
                        </ul>
                    </div>
                    
                    <div class='info-box'>
                        <h3>Maklumat Permohonan Yang Dicuba:</h3>
                        <ul>
                            <li><strong>Jawatan:</strong> {$job_title}</li>
                            <li><strong>Rujukan Permohonan:</strong> {$app_ref}</li>
                            <li><strong>Email Sah Pemohon:</strong> {$application['email']}</li>
                        </ul>
                    </div>
                    
                    <h3>Apa Yang Perlu Anda Lakukan:</h3>
                    <ol>
                        <li><strong>Jika ANDA yang membuat permintaan ini:</strong>
                            <ul>
                                <li>Pastikan anda menggunakan email yang betul ({$application['email']})</li>
                                <li>Cuba lagi dengan email yang sah</li>
                            </ul>
                        </li>
                        <li><strong>Jika BUKAN anda yang membuat permintaan ini:</strong>
                            <ul>
                                <li>Seseorang mungkin cuba mengakses permohonan anda tanpa kebenaran</li>
                                <li>Sila hubungi pihak kami SEGERA di 03-6064 2611</li>
                                <li>Pertimbangkan untuk menukar kata laluan email anda</li>
                            </ul>
                        </li>
                    </ol>
                    
                    <div class='alert-box'>
                        <h4>🛡️ LANGKAH KESELAMATAN YANG TELAH DIAMBIL:</h4>
                        <ul>
                            <li>Tiada pautan edit dihantar ke email yang tidak sah</li>
                            <li>Permohonan anda kekal selamat dan tidak terjejas</li>
                            <li>Percubaan ini telah direkodkan untuk siasatan</li>
                        </ul>
                    </div>
                    
                    <p><strong>Jika anda memerlukan bantuan untuk mengedit permohonan anda,</strong> sila hubungi pihak kami:</p>
                    <ul>
                        <li><strong>Telefon:</strong> 03-6064 2611</li>
                        <li><strong>Email:</strong> hr@mphs.gov.my</li>
                        <li><strong>Waktu Operasi:</strong> Isnin - Jumaat, 8:00 AM - 5:00 PM</li>
                    </ul>
                </div>
                
                <div class='footer'>
                    <p>Email ini dijana secara automatik oleh sistem keselamatan eJawatan MPHS.</p>
                    <p>Sila jangan membalas email ini.</p>
                    <p>&copy; " . date('Y') . " Majlis Perbandaran Hulu Selangor. Hak Cipta Terpelihara.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Generate security notification email body (Plain text) for incorrect email attempts
     * @param array $application - Application data
     * @param array $job - Job data
     * @param string $submitted_email - The incorrect email that was submitted
     * @return string - Email plain text content
     */
    public function getSecurityNotificationBodyText($application, $job, $submitted_email) {
        $applicant_name = strtoupper($application['nama_penuh'] ?? 'Pemohon');
        $job_title = strtoupper($job['job_title'] ?? 'JAWATAN');
        $app_ref = $application['application_reference'] ?? 'N/A';
        $current_time = date('d/m/Y H:i:s');
        $masked_email = $this->maskEmail($submitted_email);
        
        return "
🔒 AMARAN KESELAMATAN - EJAWATAN MPHS
====================================

Assalamualaikum dan salam sejahtera {$applicant_name},

⚠️ AMARAN KESELAMATAN
Seseorang telah cuba meminta pautan edit untuk permohonan jawatan anda menggunakan email yang TIDAK SAH.

BUTIRAN PERCUBAAN:
- Masa Percubaan: {$current_time}
- Email yang Digunakan: {$masked_email}

MAKLUMAT PERMOHONAN YANG DICUBA:
- Jawatan: {$job_title}
- Rujukan Permohonan: {$app_ref}
- Email Sah Pemohon: {$application['email']}

APA YANG PERLU ANDA LAKUKAN:

1. JIKA ANDA yang membuat permintaan ini:
   - Pastikan anda menggunakan email yang betul ({$application['email']})
   - Cuba lagi dengan email yang sah

2. JIKA BUKAN anda yang membuat permintaan ini:
   - Seseorang mungkin cuba mengakses permohonan anda tanpa kebenaran
   - Sila hubungi pihak kami SEGERA di 03-6064 2611
   - Pertimbangkan untuk menukar kata laluan email anda

🛡️ LANGKAH KESELAMATAN YANG TELAH DIAMBIL:
- Tiada pautan edit dihantar ke email yang tidak sah
- Permohonan anda kekal selamat dan tidak terjejas
- Percubaan ini telah direkodkan untuk siasatan

Jika anda memerlukan bantuan untuk mengedit permohonan anda, sila hubungi pihak kami:
- Telefon: 03-6064 2611
- Email: hr@mphs.gov.my
- Waktu Operasi: Isnin - Jumaat, 8:00 AM - 5:00 PM

Email ini dijana secara automatik oleh sistem keselamatan eJawatan MPHS.
Sila jangan membalas email ini.

© " . date('Y') . " Majlis Perbandaran Hulu Selangor. Hak Cipta Terpelihara.
        ";
    }
    
    /**
     * Mask email address for security purposes
     * @param string $email - Email to mask
     * @return string - Masked email
     */
    private function maskEmail($email) {
        if (strpos($email, '@') === false) {
            return str_repeat('*', strlen($email));
        }
        
        [$local, $domain] = explode('@', $email, 2);
        
        // Mask local part (keep first and last character if length > 2)
        if (strlen($local) <= 2) {
            $masked_local = str_repeat('*', strlen($local));
        } else {
            $masked_local = $local[0] . str_repeat('*', strlen($local) - 2) . substr($local, -1);
        }
        
        // Mask domain (keep first character and TLD)
        $domain_parts = explode('.', $domain);
        if (count($domain_parts) >= 2) {
            $domain_name = $domain_parts[0];
            $tld = implode('.', array_slice($domain_parts, 1));
            
            if (strlen($domain_name) <= 2) {
                $masked_domain = str_repeat('*', strlen($domain_name)) . '.' . $tld;
            } else {
                $masked_domain = $domain_name[0] . str_repeat('*', strlen($domain_name) - 1) . '.' . $tld;
            }
        } else {
            $masked_domain = str_repeat('*', strlen($domain));
        }
        
        return $masked_local . '@' . $masked_domain;
    }
}
?>
