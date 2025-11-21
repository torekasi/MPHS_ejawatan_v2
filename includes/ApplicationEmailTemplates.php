<?php
/**
 * Application Email Templates
 * 
 * Contains functions to generate email templates for job applications
 */

/**
 * Generate application confirmation email template
 * 
 * @param array $application Application data
 * @return string HTML email content
 */
function generateApplicationConfirmationEmail($application) {
    // Format date nicely
    $formatted_date = date('d/m/Y H:i:s', strtotime($application['application_date'] ?? $application['created_at']));
    
    // Format job ID
    $job_id_formatted = 'N/A';
    if (isset($application['job_id'])) {
        $job_id = (int)$application['job_id'];
        $job_id_formatted = 'JOB-' . str_pad($job_id, 6, '0', STR_PAD_LEFT);
    }
    
    // Build HTML email
    $html = '
    <!DOCTYPE html>
    <html lang="ms">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pengesahan Penerimaan Permohonan Jawatan</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0;
                padding: 0;
            }
            .container { 
                max-width: 600px; 
                margin: 0 auto; 
                padding: 0;
                border: 1px solid #ddd;
            }
            .header { 
                background: #1e3a8a; 
                color: white; 
                padding: 20px; 
                text-align: center; 
            }
            .header h1 {
                margin: 0;
                padding: 0;
                font-size: 24px;
            }
            .header h2 {
                margin: 10px 0 0 0;
                padding: 0;
                font-size: 18px;
                font-weight: normal;
            }
            .content { 
                padding: 20px; 
                background: #f9f9f9; 
            }
            .application-details {
                background: white;
                border: 1px solid #ddd;
                padding: 15px;
                margin-bottom: 20px;
            }
            .application-details h3 {
                margin-top: 0;
                border-bottom: 1px solid #eee;
                padding-bottom: 10px;
                color: #1e3a8a;
            }
            .detail-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 8px;
                border-bottom: 1px dotted #eee;
                padding-bottom: 8px;
            }
            .detail-label {
                font-weight: bold;
                color: #555;
            }
            .detail-value {
                text-align: right;
            }
            .reference-number {
                font-size: 18px;
                color: #1e3a8a;
                font-weight: bold;
            }
            .next-steps {
                background: #e8f4ff;
                padding: 15px;
                border-left: 4px solid #1e3a8a;
                margin-bottom: 20px;
            }
            .next-steps h3 {
                margin-top: 0;
                color: #1e3a8a;
            }
            .next-steps ul {
                margin: 0;
                padding-left: 20px;
            }
            .footer { 
                padding: 20px; 
                text-align: center; 
                font-size: 12px; 
                color: #666; 
                background: #f1f1f1;
                border-top: 1px solid #ddd;
            }
            .important-note {
                background: #fff9e6;
                border: 1px solid #ffe0b2;
                padding: 10px 15px;
                margin-top: 20px;
                border-radius: 4px;
            }
            .important-note h4 {
                margin-top: 0;
                color: #e65100;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Majlis Perbandaran Hulu Selangor</h1>
                <h2>Pengesahan Penerimaan Permohonan Jawatan</h2>
            </div>
            
            <div class="content">
                <p>Salam Sejahtera,</p>
                <p>Terima kasih kerana memohon jawatan di Majlis Perbandaran Hulu Selangor. Permohonan anda telah diterima dan sedang dalam proses semakan.</p>
                
                <div class="application-details">
                    <h3>Butiran Permohonan</h3>
                    
                    <div class="detail-row">
                        <span class="detail-label">Nombor Rujukan:</span>
                        <span class="detail-value reference-number">'.htmlspecialchars($application['application_reference'] ?? 'N/A').'</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Jawatan Dipohon:</span>
                        <span class="detail-value">'.htmlspecialchars($application['job_title'] ?? 'N/A').'</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Kod Gred:</span>
                        <span class="detail-value">'.htmlspecialchars($application['kod_gred'] ?? 'N/A').'</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Job ID:</span>
                        <span class="detail-value">'.$job_id_formatted.'</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Tarikh Permohonan:</span>
                        <span class="detail-value">'.$formatted_date.'</span>
                    </div>
                </div>
                
                <div class="application-details">
                    <h3>Maklumat Pemohon</h3>
                    
                    <div class="detail-row">
                        <span class="detail-label">Nama Penuh:</span>
                        <span class="detail-value">'.htmlspecialchars($application['nama_penuh'] ?? 'N/A').'</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Nombor IC:</span>
                        <span class="detail-value">'.htmlspecialchars($application['nombor_ic'] ?? 'N/A').'</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Emel:</span>
                        <span class="detail-value">'.htmlspecialchars($application['email'] ?? 'N/A').'</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Nombor Telefon:</span>
                        <span class="detail-value">'.htmlspecialchars($application['nombor_telefon'] ?? 'N/A').'</span>
                    </div>
                </div>
                
                <div class="next-steps">
                    <h3>Langkah Seterusnya</h3>
                    <p>Permohonan anda akan melalui proses berikut:</p>
                    <ul>
                        <li>Semakan dokumen dan kelayakan</li>
                        <li>Panggilan temu duga (jika layak)</li>
                        <li>Keputusan permohonan</li>
                    </ul>
                    <p>Proses semakan mengambil masa 2-4 minggu bekerja.</p>
                </div>
                
                <div class="important-note">
                    <h4>Maklumat Penting</h4>
                    <ul>
                        <li>Simpan nombor rujukan permohonan anda untuk semakan status</li>
                        <li>Anda boleh menyemak status permohonan di laman web kami</li>
                        <li>Hubungi kami jika anda mempunyai sebarang pertanyaan</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer">
                <p>Emel ini dijana secara automatik. Sila jangan balas emel ini.</p>
                <p>&copy; '.date('Y').' Majlis Perbandaran Hulu Selangor. Hak Cipta Terpelihara.</p>
                <p>Jika anda mempunyai sebarang pertanyaan, sila hubungi kami di <a href="mailto:admin@mphs.gov.my">admin@mphs.gov.my</a></p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}
