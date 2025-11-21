<?php
/**
 * @FileID: app_section_part2_001
 * @Module: ApplicationSectionPart2
 * @Author: Nefi (updated by AI Assistant)
 * @LastModified: 2025-11-09T12:00:00Z
 * @SecurityTag: validated
 */
if (!defined('APP_SECURE')) { http_response_code(403); exit; }
?>
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="section-title">Maklumat Kesihatan</div>
    <div class="p-6">
        <?php 
        $health_questions = [
            'darah_tinggi' => 'Menghidapi darah tinggi?',
            'kencing_manis' => 'Menghidapi masalah kencing manis?',
            'penyakit_buah_pinggang' => 'Mempunyai penyakit buah pinggang?',
            'penyakit_jantung' => 'Menghidapi penyakit jantung?',
            'batuk_kering_tibi' => 'Menghidapi batuk kering / tibi?',
            'kanser' => 'Mempunyai penyakit kanser?',
            'aids' => 'Mempunyai penyakit AIDS?',
            'penagih_dadah' => 'Seorang penagihan dadah?',
            'perokok' => 'Adakah anda seorang perokok?'
        ];
        ?>
        <div class="space-y-4">
        <?php foreach ($health_questions as $field => $label):
            $val = $application[$field] ?? '';
        ?>
            <div class="pl-2 md:pl-4">
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo $label; ?> <span class="required">*</span></label>
                <div class="mt-2 flex items-center gap-6">
                    <label class="inline-flex items-center">
                        <input type="radio" name="<?php echo $field; ?>" value="Ya" class="form-radio h-4 w-4 text-blue-600" <?php echo ($val==='YA' || $val==='Ya') ? 'checked' : ''; ?> required>
                        <span class="ml-2">Ya</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="<?php echo $field; ?>" value="Tidak" class="form-radio h-4 w-4 text-blue-600" <?php echo ($val==='TIDAK' || $val==='Tidak') ? 'checked' : ''; ?> required>
                        <span class="ml-2">Tidak</span>
                    </label>
                </div>
            </div>
        <?php endforeach; ?>
        </div>

        <div>
            <div class="flex items-start space-x-0 mt-6 mb-4">
                <div class="flex-1 pl-2 md:pl-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Adakah anda sedang menghidapi penyakit-penyakit lain? <span class="required">*</span></label>
                    <div class="mt-2 flex items-center gap-6">
                        <label class="inline-flex items-center">
                            <input type="radio" name="penyakit_lain" value="Ya" class="form-radio h-4 w-4 text-blue-600" <?php echo (($application['penyakit_lain'] ?? '')==='YA' || ($application['penyakit_lain'] ?? '')==='Ya') ? 'checked' : ''; ?> required>
                            <span class="ml-2">Ya</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="penyakit_lain" value="Tidak" class="form-radio h-4 w-4 text-blue-600" <?php echo (($application['penyakit_lain'] ?? '')==='TIDAK' || ($application['penyakit_lain'] ?? '')==='Tidak') ? 'checked' : ''; ?> required>
                            <span class="ml-2">Tidak</span>
                        </label>
                    </div>
                </div>
                <div class="w-1/2" id="penyakit_lain_field" style="display: <?php echo (($application['penyakit_lain'] ?? '')==='YA' || ($application['penyakit_lain'] ?? '')==='Ya') ? 'block' : 'none'; ?>;">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Lain-lain (Nyatakan jika ada penyakit lain)</label>
                    <textarea name="penyakit_lain_nyatakan" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase-input" placeholder="MASUKKAN PENYAKIT LAIN"><?php echo htmlspecialchars($application['penyakit_lain_nyatakan'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <div>
            <div class="flex items-start space-x-0 mb-4">
                <div class="flex-1 pl-2 md:pl-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Adakah anda pemegang kad OKU? <span class="required">*</span></label>
                    <div class="mt-2 flex items-center gap-6">
                        <label class="inline-flex items-center">
                            <input type="radio" name="pemegang_kad_oku" value="Ya" class="form-radio h-4 w-4 text-blue-600" <?php echo (($application['pemegang_kad_oku'] ?? '')==='YA' || ($application['pemegang_kad_oku'] ?? '')==='Ya') ? 'checked' : ''; ?> required>
                            <span class="ml-2">Ya</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="pemegang_kad_oku" value="Tidak" class="form-radio h-4 w-4 text-blue-600" <?php echo (($application['pemegang_kad_oku'] ?? '')==='TIDAK' || ($application['pemegang_kad_oku'] ?? '')==='Tidak') ? 'checked' : ''; ?> required>
                            <span class="ml-2">Tidak</span>
                        </label>
                    </div>
                </div>
                <?php $jenis_oku = !empty($application['jenis_oku']) ? json_decode($application['jenis_oku'], true) : []; ?>
                <div class="w-1/2" id="oku_field" style="display: <?php echo (($application['pemegang_kad_oku'] ?? '')==='YA' || ($application['pemegang_kad_oku'] ?? '')==='Ya') ? 'block' : 'none'; ?>;">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jenis OKU (Pilihan)</label>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <?php foreach (['OKU Penglihatan','OKU Pendengaran','OKU Pertuturan','OKU Fizikal','OKU Pembelajaran','OKU Mental','OKU Pelbagai','Lain-lain'] as $opt): ?>
                            <label class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-full bg-white hover:bg-green-50">
                                <input type="checkbox" name="jenis_oku[]" value="<?php echo htmlspecialchars($opt); ?>" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" <?php echo in_array($opt, $jenis_oku ?? []) ? 'checked' : ''; ?>>
                                <span class="ml-2 text-sm text-gray-700"><?php echo htmlspecialchars($opt); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-xs text-red-600 mt-1 hidden" id="jenis_oku_error">Sila pilih sekurang-kurangnya satu jenis OKU.</p>
                    <p class="text-xs text-gray-500 mt-1">Anda boleh pilih satu atau lebih jenis OKU jika berkaitan</p>

                    <!-- Salinan Kad OKU dipindahkan ke dalam kontena OKU dan akan turut disembunyikan apabila 'Tidak' dipilih -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Salinan Kad OKU</label>
                        <input type="file" id="salinan_kad_oku" name="salinan_kad_oku" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" accept=".jpg,.jpeg,.png,.gif,.pdf" data-max-size="2" data-allowed-types="image/jpeg,image/jpg,image/png,image/gif,application/pdf" data-field-label="Salinan Kad OKU" onchange="validateFileSize(this, 2)">
                        <p class="text-xs text-gray-500 mt-1"><strong>Format:</strong> JPG, JPEG, PNG, GIF, PDF | <strong>Maksimum:</strong> 2MB</p>
                        <div class="file-status mt-1" id="salinan_kad_oku_status"></div>
<?php $okuFile = $application['salinan_kad_oku'] ?? ($application['salinan_kad_oku_path'] ?? null); ?>
<?php if (!empty($okuFile)): ?>
<p class="text-xs text-green-700 mt-1">Fail dimuat naik: <?php echo htmlspecialchars(basename($okuFile)); ?></p>
<?php 
    $of = (string)$okuFile;
    $ourl = preg_match('/^https?:\/\//i',$of) ? $of : ('/' . ltrim($of,'/'));
    $oext = strtolower(pathinfo($of, PATHINFO_EXTENSION));
?>
<div class="mt-2 border border-gray-200 rounded-md bg-white flex items-center justify-center" style="height:200px; overflow:hidden;">
    <?php if (in_array($oext,['jpg','jpeg','png','gif','webp'])): ?>
        <img src="<?php echo htmlspecialchars($ourl); ?>" alt="Pratonton Kad OKU" style="max-height:200px; max-width:100%; object-fit:contain; display:block;">
    <?php elseif ($oext==='pdf'): ?>
        <iframe src="<?php echo htmlspecialchars($ourl); ?>" title="Pratonton PDF" style="height:200px; width:100%;"></iframe>
    <?php else: ?>
        <a class="text-blue-600 underline" href="<?php echo htmlspecialchars($ourl); ?>" target="_blank" rel="noopener">Lihat fail</a>
    <?php endif; ?>
</div>
<?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="flex items-start space-x-0 mb-4">
                <div class="flex-1 pl-2 md:pl-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Adakah anda memakai cermin mata? <span class="required">*</span></label>
                    <div class="mt-2 flex items-center gap-6">
                        <label class="inline-flex items-center">
                            <input type="radio" name="memakai_cermin_mata" value="Ya" class="form-radio h-4 w-4 text-blue-600" <?php echo (($application['memakai_cermin_mata'] ?? '')==='YA' || ($application['memakai_cermin_mata'] ?? '')==='Ya') ? 'checked' : ''; ?> required>
                            <span class="ml-2">Ya</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="memakai_cermin_mata" value="Tidak" class="form-radio h-4 w-4 text-blue-600" <?php echo (($application['memakai_cermin_mata'] ?? '')==='TIDAK' || ($application['memakai_cermin_mata'] ?? '')==='Tidak') ? 'checked' : ''; ?> required>
                            <span class="ml-2">Tidak</span>
                        </label>
                    </div>
                </div>
                <div class="w-1/2" id="cermin_mata_field" style="display: <?php echo (($application['memakai_cermin_mata'] ?? '')==='YA' || ($application['memakai_cermin_mata'] ?? '')==='Ya') ? 'block' : 'none'; ?>;">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rabun apa? <span class="required">*</span></label>
                    <select name="jenis_rabun" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Sila Pilih</option>
                        <?php foreach (["Rabun Jauh","Rabun Dekat","Rabun Silau","Astigmatisme","Presbiopia","Lain-lain"] as $opt): ?>
                            <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo (strtoupper($application['jenis_rabun'] ?? '')===strtoupper($opt)) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-red-600 mt-1 hidden" id="jenis_rabun_error">Sila pilih jenis rabun.</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Berat (KG) <span class="required">*</span></label>
                <input type="number" step="0.1" name="berat_kg" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($application['berat_kg'] ?? ''); ?>" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tinggi (cm) <span class="required">*</span></label>
                <input type="number" step="0.1" name="tinggi_cm" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($application['tinggi_cm'] ?? ''); ?>" required>
            </div>
        </div>

    </div>
    <!-- Loading overlay shown on submit -->
    <div id="healthOkuLoading" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden" aria-hidden="true">
        <div class="bg-white rounded-md shadow-md p-4 flex items-center space-x-3">
            <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span class="text-sm">Menyimpan maklumat kesihatan & fizikal...</span>
        </div>
    </div>
</div>

<script>
// Client-side logic mirroring save-application-part2.php validation
document.addEventListener('DOMContentLoaded', function() {
    var okuYesRadio = document.querySelector('input[name="pemegang_kad_oku"][value="Ya"]');
    var okuNoRadio = document.querySelector('input[name="pemegang_kad_oku"][value="Tidak"]');
    var okuField = document.getElementById('oku_field');
    var jenisOkuError = document.getElementById('jenis_oku_error');
    var jenisOkuCheckboxes = Array.prototype.slice.call(document.querySelectorAll('input[name="jenis_oku[]"]'));

    var penyakitLainYes = document.querySelector('input[name="penyakit_lain"][value="Ya"]');
    var penyakitLainNo = document.querySelector('input[name="penyakit_lain"][value="Tidak"]');
    var penyakitLainField = document.getElementById('penyakit_lain_field');
    var penyakitLainTextarea = document.querySelector('textarea[name="penyakit_lain_nyatakan"]');

    var cerminYes = document.querySelector('input[name="memakai_cermin_mata"][value="Ya"]');
    var cerminNo = document.querySelector('input[name="memakai_cermin_mata"][value="Tidak"]');
    var cerminField = document.getElementById('cermin_mata_field');
    var jenisRabunSelect = document.querySelector('select[name="jenis_rabun"]');
    var jenisRabunError = document.getElementById('jenis_rabun_error');

    var salinanFile = document.getElementById('salinan_kad_oku');
    var salinanStatus = document.getElementById('salinan_kad_oku_status');

    // Safe fallback for validateFileSize if not globally defined
    if (typeof window.validateFileSize !== 'function') {
        window.validateFileSize = function(input, maxMB) {
            var statusEl = document.getElementById(input.id + '_status') || document.getElementById('salinan_kad_oku_status');
            if (!input.files || !input.files[0]) { if (statusEl) statusEl.textContent = ''; return; }
            var file = input.files[0];
            var sizeMB = file.size / (1024 * 1024);
            var allowedTypes = (input.getAttribute('data-allowed-types') || '').split(',').filter(Boolean);
            var messages = [];
            if (sizeMB > maxMB) messages.push('Saiz fail melebihi ' + maxMB + 'MB.');
            if (allowedTypes.length && allowedTypes.indexOf(file.type) === -1) messages.push('Jenis fail tidak dibenarkan.');
            if (statusEl) {
                statusEl.textContent = messages.join(' ');
                statusEl.className = 'file-status mt-1 ' + (messages.length ? 'text-red-600' : 'text-green-700');
                if (!messages.length) statusEl.textContent = 'Fail sah: ' + (file.name || '');
            }
            if (messages.length) {
                input.value = '';
            }
        };
    }

    function toggleVisibility() {
        // OKU field
        var okuYes = okuYesRadio && okuYesRadio.checked;
        okuField.style.display = okuYes ? 'block' : 'none';
        if (!okuYes) {
            jenisOkuError && (jenisOkuError.classList.add('hidden'));
            // Disable and clear OKU file input when not applicable
            if (salinanFile) {
                salinanFile.disabled = true;
                salinanFile.value = '';
                if (salinanStatus) { salinanStatus.textContent = ''; salinanStatus.className = 'file-status mt-1'; }
            }
        } else {
            if (salinanFile) salinanFile.disabled = false;
        }

        // Penyakit lain field
        var lainYes = penyakitLainYes && penyakitLainYes.checked;
        penyakitLainField.style.display = lainYes ? 'block' : 'none';
        if (penyakitLainTextarea) {
            if (lainYes) {
                penyakitLainTextarea.setAttribute('required', 'required');
            } else {
                penyakitLainTextarea.removeAttribute('required');
            }
        }

        // Cermin mata field
        var cYes = cerminYes && cerminYes.checked;
        cerminField.style.display = cYes ? 'block' : 'none';
        if (jenisRabunSelect) {
            if (cYes) {
                jenisRabunSelect.setAttribute('required', 'required');
            } else {
                jenisRabunSelect.removeAttribute('required');
                jenisRabunError && jenisRabunError.classList.add('hidden');
                jenisRabunSelect.value = '';
            }
        }
    }

    function validateOkuSelection() {
        var okuYes = okuYesRadio && okuYesRadio.checked;
        if (!okuYes) return true; // No requirement when not OKU holder
        var anyChecked = jenisOkuCheckboxes.some(function(cb) { return cb.checked; });
        if (!anyChecked) {
            jenisOkuError && jenisOkuError.classList.remove('hidden');
            return false;
        }
        hideJenisOkuError();
        return true;
    }

    function hideJenisOkuError() {
        if (jenisOkuError) jenisOkuError.classList.add('hidden');
    }

    function validateJenisRabun() {
        var cYes = cerminYes && cerminYes.checked;
        if (!cYes) return true;
        if (jenisRabunSelect && !jenisRabunSelect.value) {
            jenisRabunError && jenisRabunError.classList.remove('hidden');
            return false;
        }
        jenisRabunError && jenisRabunError.classList.add('hidden');
        return true;
    }

    // Attach listeners
    [okuYesRadio, okuNoRadio, penyakitLainYes, penyakitLainNo, cerminYes, cerminNo].forEach(function(el) {
        if (el) el.addEventListener('change', toggleVisibility);
    });
    jenisOkuCheckboxes.forEach(function(cb) {
        cb.addEventListener('change', function() { if (jenisOkuError) jenisOkuError.classList.add('hidden'); });
    });
    if (jenisRabunSelect) {
        jenisRabunSelect.addEventListener('change', function() { jenisRabunError && jenisRabunError.classList.add('hidden'); });
    }

    toggleVisibility();

    // Bind form submission for validation and loading overlay
    var container = document.currentScript.closest('.bg-white');
    var form = container ? container.closest('form') : document.querySelector('form');
    var overlay = document.getElementById('healthOkuLoading');
    if (form) {
        form.addEventListener('submit', function(e) {
            var ok = true;
            if (!validateOkuSelection()) ok = false;
            if (!validateJenisRabun()) ok = false;
            if (!ok) {
                e.preventDefault();
                e.stopPropagation();
                // Scroll to first error
                var target = jenisOkuError && !jenisOkuError.classList.contains('hidden') ? jenisOkuError : jenisRabunError && !jenisRabunError.classList.contains('hidden') ? jenisRabunError : null;
                if (target) target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
            // Show loading overlay
            if (overlay) overlay.classList.remove('hidden');
        });
    }
});
</script>