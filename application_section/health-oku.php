<?php
/**
 * @FileID: app_section_part2_001
 * @Module: ApplicationSectionPart2
 * @Author: Nefi
 * @LastModified: 2025-11-09T00:00:00Z
 * @SecurityTag: validated
 */
if (!defined('APP_SECURE')) { http_response_code(403); exit; }
?>
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
    <div class="section-title">4. MAKLUMAT KESEHATAN / FIZIKAL</div>
    <div class="p-6 space-y-6">
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
        foreach ($health_questions as $field => $label):
            $val = $application[$field] ?? '';
        ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo $label; ?> <span class="required">*</span></label>
                <div class="mt-2 space-x-4">
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

        <div>
            <div class="flex items-start space-x-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Adakah anda sedang menghidapi penyakit-penyakit lain? <span class="required">*</span></label>
                    <div class="mt-2 space-x-4">
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
            <div class="flex items-start space-x-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Adakah anda pemegang kad OKU? <span class="required">*</span></label>
                    <div class="mt-2 space-x-4">
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
                    <p class="text-xs text-gray-500 mt-1">Anda boleh pilih satu atau lebih jenis OKU jika berkaitan</p>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <div class="flex items-start space-x-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Adakah anda memakai cermin mata? <span class="required">*</span></label>
                    <div class="mt-2 space-x-4">
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
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Berat (KG) <span class="required">*</span></label>
                <input type="number" step="0.1" name="berat_kg" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($application['berat_kg'] ?? ''); ?>" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tinggi (cm) <span class="required">*</span></label>
                <input type="number" step="0.1" name="tinggi_cm" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($application['tinggi_cm'] ?? ''); ?>" required>
            </div>
        </div>

        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Salinan Kad OKU (Pilihan)</label>
            <input type="file" id="salinan_kad_oku" name="salinan_kad_oku" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" accept=".jpg,.jpeg,.png,.gif,.pdf" data-max-size="2" data-allowed-types="image/jpeg,image/jpg,image/png,image/gif,application/pdf" data-field-label="Salinan Kad OKU" onchange="validateFileSize(this, 2)">
            <p class="text-xs text-gray-500 mt-1"><strong>Format:</strong> JPG, JPEG, PNG, GIF, PDF | <strong>Maksimum:</strong> 2MB</p>
            <div class="file-status mt-1" id="salinan_kad_oku_status"></div>
            <?php if (!empty($application['salinan_kad_oku'])): ?>
                <p class="text-xs text-green-700 mt-1">Fail dimuat naik: <?php echo htmlspecialchars(basename($application['salinan_kad_oku'])); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>