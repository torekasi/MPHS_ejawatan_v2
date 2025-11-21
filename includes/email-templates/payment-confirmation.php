<?php
/**
 * Payment Confirmation Email Template
 * 
 * Variables available:
 * - $payment_reference
 * - $transaction_id
 * - $applicant_name
 * - $applicant_email
 * - $applicant_phone
 * - $amount
 * - $payment_date
 * - $job_title
 * - $job_code
 * - $kod_gred
 * - $ad_close_date
 * - $year (for application link)
 */

$email_html = '
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Pengesahan Pembayaran - MPHS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f7fa;
            padding: 20px 10px;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: #ffffff;
            padding: 40px 30px;
            text-align: center;
        }
        
        .header img {
            max-width: 80px;
            margin-bottom: 15px;
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 8px 0;
            letter-spacing: -0.5px;
        }
        
        .header p {
            font-size: 14px;
            margin: 0;
            opacity: 0.95;
        }
        
        .success-badge {
            background: #10b981;
            color: white;
            padding: 12px 24px;
            text-align: center;
            font-size: 16px;
            font-weight: 600;
        }
        
        .success-badge svg {
            width: 20px;
            height: 20px;
            vertical-align: middle;
            margin-right: 8px;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 16px;
        }
        
        .message {
            font-size: 15px;
            color: #4b5563;
            line-height: 1.8;
            margin-bottom: 30px;
        }
        
        .info-box {
            background: #f3f4f6;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin: 25px 0;
            border-radius: 6px;
        }
        
        .info-box h3 {
            font-size: 14px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-row {
            display: table;
            width: 100%;
            margin: 10px 0;
            font-size: 14px;
        }
        
        .info-label {
            display: table-cell;
            color: #6b7280;
            font-weight: 500;
            padding: 6px 0;
            width: 45%;
        }
        
        .info-value {
            display: table-cell;
            color: #1f2937;
            font-weight: 600;
            padding: 6px 0;
        }
        
        .amount-highlight {
            font-size: 28px;
            color: #10b981;
            font-weight: 700;
        }
        
        .cta-button {
            display: inline-block;
            background: #3b82f6;
            color: #ffffff !important;
            text-decoration: none;
            padding: 16px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            margin: 25px 0;
            transition: background 0.3s ease;
        }
        
        .cta-button:hover {
            background: #2563eb;
        }
        
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        
        .important-notes {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 20px;
            margin: 25px 0;
            border-radius: 6px;
        }
        
        .important-notes h4 {
            font-size: 14px;
            font-weight: 700;
            color: #92400e;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
        }
        
        .important-notes h4 svg {
            width: 18px;
            height: 18px;
            margin-right: 8px;
        }
        
        .important-notes ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .important-notes li {
            color: #78350f;
            font-size: 13px;
            padding: 6px 0;
            padding-left: 20px;
            position: relative;
        }
        
        .important-notes li:before {
            content: "•";
            position: absolute;
            left: 8px;
            font-weight: bold;
        }
        
        .next-steps {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin: 25px 0;
            border-radius: 6px;
        }
        
        .next-steps h4 {
            font-size: 14px;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 12px;
        }
        
        .next-steps ol {
            padding-left: 20px;
            margin: 0;
        }
        
        .next-steps li {
            color: #1e40af;
            font-size: 13px;
            padding: 6px 0;
        }
        
        .footer {
            background: #f9fafb;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        
        .footer-logo {
            max-width: 60px;
            margin-bottom: 15px;
            opacity: 0.6;
        }
        
        .footer p {
            font-size: 13px;
            color: #6b7280;
            margin: 8px 0;
            line-height: 1.6;
        }
        
        .footer a {
            color: #3b82f6;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        .divider {
            height: 1px;
            background: #e5e7eb;
            margin: 25px 0;
        }
        
        @media only screen and (max-width: 600px) {
            .email-container {
                border-radius: 0;
            }
            
            .header {
                padding: 30px 20px;
            }
            
            .header h1 {
                font-size: 20px;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .info-label,
            .info-value {
                display: block;
                width: 100%;
            }
            
            .info-label {
                font-weight: 600;
                margin-bottom: 4px;
            }
            
            .info-value {
                margin-bottom: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>Majlis Perbandaran Hulu Selangor</h1>
            <p>Sistem eJawatan</p>
        </div>
        
        <!-- Success Badge -->
        <div class="success-badge">
            ✓ Pembayaran Berjaya
        </div>
        
        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Assalamualaikum ' . htmlspecialchars($applicant_name) . ',
            </div>
            
            <div class="message">
                Terima kasih atas pembayaran anda. Kami dengan sukacitanya mengesahkan bahawa pembayaran yuran permohonan jawatan anda telah <strong>berjaya diproses</strong>.
            </div>
            
            <!-- Payment Details -->
            <div class="info-box">
                <h3>📄 Maklumat Pembayaran</h3>
                
                <div class="info-row">
                    <div class="info-label">Rujukan Pembayaran:</div>
                    <div class="info-value">' . htmlspecialchars($payment_reference) . '</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">ID Transaksi:</div>
                    <div class="info-value">' . htmlspecialchars($transaction_id ?? 'N/A') . '</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Tarikh Pembayaran:</div>
                    <div class="info-value">' . date('d/m/Y, h:i A', strtotime($payment_date)) . '</div>
                </div>
                
                <div class="divider"></div>
                
                <div class="info-row">
                    <div class="info-label">Jumlah Dibayar:</div>
                    <div class="info-value">
                        <span class="amount-highlight">RM ' . number_format($amount, 2) . '</span>
                    </div>
                </div>
            </div>
            
            <!-- Job Details -->
            <div class="info-box">
                <h3>💼 Maklumat Jawatan</h3>
                
                <div class="info-row">
                    <div class="info-label">Jawatan:</div>
                    <div class="info-value">' . htmlspecialchars($job_title) . '</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Kod Jawatan:</div>
                    <div class="info-value">' . htmlspecialchars($job_code ?? 'N/A') . '</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Kod Gred:</div>
                    <div class="info-value">' . htmlspecialchars($kod_gred ?? 'N/A') . '</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Tarikh Tutup Permohonan:</div>
                    <div class="info-value">' . date('d/m/Y', strtotime($ad_close_date)) . '</div>
                </div>
            </div>
            
            <!-- Next Steps -->
            <div class="next-steps">
                <h4>📋 Langkah Seterusnya</h4>
                <ol>
                    <li>Klik butang di bawah untuk meneruskan permohonan anda</li>
                    <li>Lengkapkan borang permohonan dengan maklumat yang tepat</li>
                    <li>Muat naik dokumen sokongan yang diperlukan</li>
                    <li>Hantar permohonan sebelum tarikh tutup</li>
                </ol>
            </div>
            
            <!-- CTA Button -->
            <div class="button-container">
                <a href="' . htmlspecialchars($config['base_url']) . '/job-application-1.php?job_id=' . urlencode($job_id ?? '') . '&payment_ref=' . urlencode($payment_reference) . '&name=' . urlencode($applicant_name) . '&phone=' . urlencode($applicant_phone) . '&email=' . urlencode($applicant_email) . '" class="cta-button">
                    🚀 Teruskan ke Borang Permohonan
                </a>
            </div>
            
            <!-- Important Notes -->
            <div class="important-notes">
                <h4>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-1.964-1.333-2.732 0L4.082 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Nota Penting
                </h4>
                <ul>
                    <li>Simpan emel ini sebagai bukti pembayaran anda</li>
                    <li>Rujukan pembayaran akan diperlukan semasa mengisi borang permohonan</li>
                    <li>Pastikan semua maklumat yang diberikan adalah tepat dan betul</li>
                    <li>Lengkapkan permohonan sebelum tarikh tutup: <strong>' . date('d/m/Y', strtotime($ad_close_date)) . '</strong></li>
                    <li>Resit pembayaran rasmi akan dihantar ke emel ini selepas permohonan selesai</li>
                </ul>
            </div>
            
            <div class="message" style="margin-top: 30px;">
                Sekiranya anda mempunyai sebarang pertanyaan atau memerlukan bantuan, sila hubungi kami di:
                <br><br>
                📧 Email: <a href="mailto:' . htmlspecialchars($config['admin_email'] ?? 'admin@mphs.gov.my') . '" style="color: #3b82f6;">' . htmlspecialchars($config['admin_email'] ?? 'admin@mphs.gov.my') . '</a><br>
                📞 Telefon: ' . htmlspecialchars($config['contact_phone'] ?? '03-6064 1111') . '<br>
                🕒 Waktu Operasi: Isnin - Jumaat, 8:00 AM - 5:00 PM
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p style="font-weight: 600; color: #1f2937; margin-bottom: 12px;">
                Majlis Perbandaran Hulu Selangor
            </p>
            <p>
                Sistem Permohonan Jawatan Dalam Talian (eJawatan)<br>
                No. 1, Jalan Serendah 26600, Kalumpang, Selangor
            </p>
            <p style="margin-top: 15px;">
                <a href="' . htmlspecialchars($config['base_url']) . '">Portal eJawatan</a> | 
                <a href="mailto:' . htmlspecialchars($config['admin_email'] ?? 'admin@mphs.gov.my') . '">Hubungi Kami</a>
            </p>
            <p style="margin-top: 20px; font-size: 11px; color: #9ca3af;">
                Emel ini dijana secara automatik. Sila jangan membalas ke alamat ini.<br>
                &copy; ' . date('Y') . ' Majlis Perbandaran Hulu Selangor. Hak Cipta Terpelihara.
            </p>
        </div>
    </div>
</body>
</html>
';

return $email_html;
?>

