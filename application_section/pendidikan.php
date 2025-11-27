<?php
/**
 * @FileID: app_section_part3_001
 * @Module: ApplicationSectionPart3
 * @Author: Nefi
 * @LastModified: 2025-11-09T00:00:00Z
 * @SecurityTag: validated
 */
if (!defined('APP_SECURE')) { http_response_code(403); exit; }

// Define year variables for dropdowns
$current_year = date('Y');
$last_year = $current_year - 1;
?>
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="section-title">Kemahiran Bahasa</div>
    <div class="p-6">
        <div id="language-skills-container">
            <?php 
            $idx = 0; 
            if (!empty($prefill_languages)):
                foreach ($prefill_languages as $lang): ?>
            <div class="language-skill-entry bg-gray-50 p-4 rounded-lg mb-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bahasa <span class="required">*</span></label>
                        <input type="text" name="kemahiran_bahasa[<?php echo $idx; ?>][bahasa]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase-input" placeholder="MASUKKAN BAHASA" required value="<?php echo htmlspecialchars($lang['bahasa']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pertuturan</label>
                        <?php $tutur = strtoupper($lang['pertuturan']); ?>
                        <select name="kemahiran_bahasa[<?php echo $idx; ?>][pertuturan]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">PILIH TAHAP</option>
                            <option value="Baik" <?php echo $tutur==='BAIK' ? 'selected' : ''; ?>>Baik</option>
                            <option value="Sederhana" <?php echo $tutur==='SEDERHANA' ? 'selected' : ''; ?>>Sederhana</option>
                            <option value="Lemah" <?php echo $tutur==='LEMAH' ? 'selected' : ''; ?>>Lemah</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Penulisan</label>
                        <?php $tulis = strtoupper($lang['penulisan']); ?>
                        <select name="kemahiran_bahasa[<?php echo $idx; ?>][penulisan]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">PILIH TAHAP</option>
                            <option value="Baik" <?php echo $tulis==='BAIK' ? 'selected' : ''; ?>>Baik</option>
                            <option value="Sederhana" <?php echo $tulis==='SEDERHANA' ? 'selected' : ''; ?>>Sederhana</option>
                            <option value="Lemah" <?php echo $tulis==='LEMAH' ? 'selected' : ''; ?>>Lemah</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="button" class="remove-language-btn text-red-500 text-sm" style="<?php echo $idx>0 ? '' : 'display: none;'; ?>">Buang</button>
                </div>
            </div>
            <?php $idx++; endforeach; else: ?>
            <div class="language-skill-entry bg-gray-50 p-4 rounded-lg mb-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bahasa <span class="required">*</span></label>
                        <input type="text" name="kemahiran_bahasa[0][bahasa]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase-input" placeholder="MASUKKAN BAHASA" required>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pertuturan</label>
                        <select name="kemahiran_bahasa[0][pertuturan]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">PILIH TAHAP</option>
                            <option value="Baik">Baik</option>
                            <option value="Sederhana">Sederhana</option>
                            <option value="Lemah">Lemah</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Penulisan</label>
                        <select name="kemahiran_bahasa[0][penulisan]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">PILIH TAHAP</option>
                            <option value="Baik">Baik</option>
                            <option value="Sederhana">Sederhana</option>
                            <option value="Lemah">Lemah</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="button" class="remove-language-btn text-red-500 text-sm" style="display: none;">Buang</button>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="flex justify-end">
            <button type="button" id="addLanguageSkill" class="add-row-btn">Tambah Bahasa</button>
        </div>
    </div>
    <div class="section-title">Kemahiran Komputer</div>
    <div class="p-6">
        <div id="computer-skills-container">
            <?php 
            $cidx = 0; 
            if (!empty($prefill_computers)):
                foreach ($prefill_computers as $comp): ?>
            <div class="computer-skill-entry bg-gray-50 p-4 rounded-lg mb-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Perisian <span class="required">*</span></label>
                        <input type="text" name="kemahiran_komputer[<?php echo $cidx; ?>][nama_perisian]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase-input" placeholder="MASUKKAN NAMA PERISIAN" required value="<?php echo htmlspecialchars($comp['nama_perisian']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tahap Kemahiran</label>
                        <?php $tk = strtoupper($comp['tahap_kemahiran']); ?>
                        <select name="kemahiran_komputer[<?php echo $cidx; ?>][tahap_kemahiran]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">PILIH TAHAP</option>
                            <option value="Mahir" <?php echo $tk==='MAHIR' ? 'selected' : ''; ?>>Mahir</option>
                            <option value="Sederhana" <?php echo $tk==='SEDERHANA' ? 'selected' : ''; ?>>Sederhana</option>
                            <option value="Asas" <?php echo $tk==='ASAS' ? 'selected' : ''; ?>>Asas</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="button" class="remove-computer-btn text-red-500 text-sm" style="<?php echo $cidx>0 ? '' : 'display: none;'; ?>">Buang</button>
                </div>
            </div>
            <?php $cidx++; endforeach; else: ?>
            <div class="computer-skill-entry bg-gray-50 p-4 rounded-lg mb-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Perisian <span class="required">*</span></label>
                        <input type="text" name="kemahiran_komputer[0][nama_perisian]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase-input" placeholder="MASUKKAN NAMA PERISIAN" required>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tahap Kemahiran</label>
                        <select name="kemahiran_komputer[0][tahap_kemahiran]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">PILIH TAHAP</option>
                            <option value="Mahir">Mahir</option>
                            <option value="Sederhana">Sederhana</option>
                            <option value="Asas">Asas</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="button" class="remove-computer-btn text-red-500 text-sm" style="display: none;">Buang</button>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="flex justify-end">
            <button type="button" id="addComputerSkill" class="add-row-btn">Tambah Kemahiran</button>
        </div>
    </div>
    <div class="section-title">Badan Profesional</div>
    <div class="p-6">
        <div id="professional-body-container">
            <?php 
            $prof_idx = 0; 
            if (!empty($prefill_professional_bodies)):
                foreach ($prefill_professional_bodies as $prof): ?>
            <div class="professional-body-entry bg-gray-50 p-4 rounded-lg mb-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lembaga</label>
                        <input type="text" name="badan_profesional[<?php echo $prof_idx; ?>][nama_lembaga]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" value="<?php echo htmlspecialchars($prof['nama_lembaga']); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">No. Ahli</label>
                        <input type="text" name="badan_profesional[<?php echo $prof_idx; ?>][no_ahli]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" value="<?php echo htmlspecialchars($prof['no_ahli']); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">No. Sijil</label>
                        <input type="text" name="badan_profesional[<?php echo $prof_idx; ?>][sijil]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" value="<?php echo htmlspecialchars($prof['sijil']); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tarikh Sijil</label>
                        <input type="date" name="badan_profesional[<?php echo $prof_idx; ?>][tarikh_sijil]" class="w-full px-3 py-2 border border-gray-300 rounded-md" value="<?php echo htmlspecialchars($prof['tarikh_sijil']); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Salinan Sijil (PDF/Gambar)</label>
                        <?php $profExistingCert = $prof['salinan_sijil'] ?? ''; ?>
                        <input type="file" id="badan_profesional_<?php echo $prof_idx; ?>_salinan_sijil" name="badan_profesional[<?php echo $prof_idx; ?>][salinan_sijil]" class="w-full px-3 py-2 border border-gray-300 rounded-md" accept=".jpg,.jpeg,.png,.gif,.pdf" data-max-size="5" data-allowed-types="image/jpeg,image/jpg,image/png,image/gif,application/pdf" data-field-label="Sijil Badan Profesional">
                        <?php if (!empty($profExistingCert)): ?>
                            <p class="text-xs text-green-700 mt-1">Fail dimuat naik: <?php echo htmlspecialchars(basename($profExistingCert)); ?></p>
                            <input type="hidden" name="badan_profesional[<?php echo $prof_idx; ?>][salinan_sijil_path]" value="<?php echo htmlspecialchars($profExistingCert); ?>">
                        <?php endif; ?>
                        <div id="badan_profesional_<?php echo $prof_idx; ?>_salinan_sijil_status" class="file-status text-xs text-gray-600 mt-1"></div>
                        
                    </div>
                </div>
                <div class="flex justify-end"><button type="button" class="remove-professional-btn text-red-500 text-sm" style="<?php echo $prof_idx > 0 ? '' : 'display: none;'; ?>">Buang</button></div>
            </div>
            <?php $prof_idx++; endforeach; else: ?>
            <div class="professional-body-entry bg-gray-50 p-4 rounded-lg mb-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lembaga</label>
                        <input type="text" name="badan_profesional[0][nama_lembaga]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">No. Ahli</label>
                        <input type="text" name="badan_profesional[0][no_ahli]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">No. Sijil</label>
                        <input type="text" name="badan_profesional[0][sijil]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tarikh Sijil</label>
                        <input type="date" name="badan_profesional[0][tarikh_sijil]" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Salinan Sijil (PDF/Gambar)</label>
                        <input type="file" id="badan_profesional_0_salinan_sijil" name="badan_profesional[0][salinan_sijil]" class="w-full px-3 py-2 border border-gray-300 rounded-md" accept=".jpg,.jpeg,.png,.gif,.pdf" data-max-size="5" data-allowed-types="image/jpeg,image/jpg,image/png,image/gif,application/pdf" data-field-label="Sijil Badan Profesional">
                        <div id="badan_profesional_0_salinan_sijil_status" class="file-status text-xs text-gray-600 mt-1"></div>
                    </div>
                </div>
                <div class="flex justify-end"><button type="button" class="remove-professional-btn text-red-500 text-sm" style="display: none;">Buang</button></div>
            </div>
            <?php endif; ?>
        </div>
        <div class="flex justify-end"><button type="button" id="addProfessionalBody" class="add-row-btn">Tambah Badan Profesional</button></div>
    </div>
    <div class="section-title">Kegiatan Luar</div>
    <div class="p-6">
        <div id="extracurricular-container">
            <?php 
            $extra_idx = 0; 
            if (!empty($prefill_extracurriculars)):
                foreach ($prefill_extracurriculars as $extra): ?>
            <div class="extracurricular-entry bg-gray-50 p-4 rounded-lg mb-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sukan/Persatuan/Kelab</label>
                        <input type="text" name="kegiatan_luar[<?php echo $extra_idx; ?>][sukan_persatuan_kelab]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" value="<?php echo htmlspecialchars($extra['sukan_persatuan_kelab']); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Jawatan</label>
                        <input type="text" name="kegiatan_luar[<?php echo $extra_idx; ?>][jawatan]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" value="<?php echo htmlspecialchars($extra['jawatan']); ?>">
                    </div>
                    <div>
                        <label class="block text sm font-medium text-gray-700 mb-2">Peringkat</label>
                        <select name="kegiatan_luar[<?php echo $extra_idx; ?>][peringkat]" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">Pilih</option>
                            <option value="Sekolah" <?php echo strtoupper($extra['peringkat'] ?? '')==='SEKOLAH' ? 'selected' : ''; ?>>Sekolah</option>
                            <option value="Daerah" <?php echo strtoupper($extra['peringkat'] ?? '')==='DAERAH' ? 'selected' : ''; ?>>Daerah</option>
                            <option value="Negeri" <?php echo strtoupper($extra['peringkat'] ?? '')==='NEGERI' ? 'selected' : ''; ?>>Negeri</option>
                            <option value="Kebangsaan" <?php echo strtoupper($extra['peringkat'] ?? '')==='KEBANGSAAN' ? 'selected' : ''; ?>>Kebangsaan</option>
                            <option value="Antarabangsa" <?php echo strtoupper($extra['peringkat'] ?? '')==='ANTARABANGSA' ? 'selected' : ''; ?>>Antarabangsa</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tarikh Sijil</label>
                        <input type="date" name="kegiatan_luar[<?php echo $extra_idx; ?>][tarikh_sijil]" class="w-full px-3 py-2 border border-gray-300 rounded-md" value="<?php echo htmlspecialchars($extra['tarikh_sijil'] ?? ''); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Salinan Sijil (PDF/Gambar)</label>
                        <?php if (!empty($extra['salinan_sijil'])): ?>
                            <div class="mb-2 text-sm text-green-600">
                                Fail sedia ada: <?php echo htmlspecialchars(basename($extra['salinan_sijil'])); ?>
                            </div>
                        <?php endif; ?>
                        <?php $extraExistingCert = $extra['salinan_sijil'] ?? ''; ?>
                        <input type="file" id="kegiatan_luar_<?php echo $extra_idx; ?>_salinan_sijil" name="kegiatan_luar[<?php echo $extra_idx; ?>][salinan_sijil]" class="w-full px-3 py-2 border border-gray-300 rounded-md" accept=".jpg,.jpeg,.png,.gif,.pdf" data-max-size="5" data-allowed-types="image/jpeg,image/jpg,image/png,image/gif,application/pdf" data-field-label="Sijil Kegiatan Luar">
                        <?php if (!empty($extraExistingCert)): ?>
                            <p class="text-xs text-green-700 mt-1">Fail dimuat naik: <?php echo htmlspecialchars(basename($extraExistingCert)); ?></p>
                            <input type="hidden" name="kegiatan_luar[<?php echo $extra_idx; ?>][salinan_sijil_path]" value="<?php echo htmlspecialchars($extraExistingCert); ?>">
                        <?php endif; ?>
                        <div id="kegiatan_luar_<?php echo $extra_idx; ?>_salinan_sijil_status" class="file-status text-xs text-gray-600 mt-1"></div>
                    </div>
                </div>
                <div class="flex justify-end"><button type="button" class="remove-extracurricular-btn text-red-500 text-sm"<?php echo $extra_idx === 0 ? ' style="display: none;"' : ''; ?>>Buang</button></div>
            </div>
            <?php $extra_idx++; endforeach; ?>
            <?php else: ?>
            <div class="extracurricular-entry bg-gray-50 p-4 rounded-lg mb-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sukan/Persatuan/Kelab</label>
                        <input type="text" name="kegiatan_luar[0][sukan_persatuan_kelab]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Jawatan</label>
                        <input type="text" name="kegiatan_luar[0][jawatan]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Peringkat</label>
                        <select name="kegiatan_luar[0][peringkat]" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">Pilih</option>
                            <option value="Sekolah">Sekolah</option>
                            <option value="Daerah">Daerah</option>
                            <option value="Negeri">Negeri</option>
                            <option value="Kebangsaan">Kebangsaan</option>
                            <option value="Antarabangsa">Antarabangsa</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tarikh Sijil</label>
                        <input type="date" name="kegiatan_luar[0][tarikh_sijil]" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Salinan Sijil (PDF/Gambar)</label>
                        <input type="file" id="kegiatan_luar_0_salinan_sijil" name="kegiatan_luar[0][salinan_sijil]" class="w-full px-3 py-2 border border-gray-300 rounded-md" accept=".jpg,.jpeg,.png,.gif,.pdf" data-max-size="5" data-allowed-types="image/jpeg,image/jpg,image/png,image/gif,application/pdf" data-field-label="Sijil Kegiatan Luar">
                        <div id="kegiatan_luar_0_salinan_sijil_status" class="file-status text-xs text-gray-600 mt-1"></div>
                    </div>
                </div>
                <div class="flex justify-end"><button type="button" class="remove-extracurricular-btn text-red-500 text-sm" style="display: none;">Buang</button></div>
            </div>
            <?php endif; ?>
        </div>
        <div class="flex justify-end"><button type="button" id="addExtracurricular" class="add-row-btn">Tambah Kegiatan Luar</button></div>
    </div>
    <div class="section-title">Kelulusan SPM/SPV</div>
    <div class="p-6">
        <?php 
        $spm_data = !empty($prefill_spm_results) ? $prefill_spm_results[0] : [];
        ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tahun <span class="required">*</span></label>
                <select name="spm_tahun" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    <option value="">Pilih Tahun</option>
                    <?php 
                    $selected_spm_year = $spm_data['tahun'] ?? $application['spm_tahun'] ?? '';
                    for ($year = $last_year; $year >= 1970; $year--): 
                    ?>
                        <option value="<?php echo $year; ?>" <?php echo ($selected_spm_year == $year) ? 'selected' : ''; ?>><?php echo $year; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Gred Keseluruhan <span class="required">*</span></label>
                <input type="text" name="spm_gred_keseluruhan" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" required value="<?php echo htmlspecialchars($spm_data['gred_keseluruhan'] ?? $application['spm_gred_keseluruhan'] ?? ''); ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Angka Giliran <span class="required">*</span></label>
                <input type="text" name="spm_angka_giliran" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" required value="<?php echo htmlspecialchars($spm_data['angka_giliran'] ?? $application['spm_angka_giliran'] ?? ''); ?>">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Bahasa Malaysia <span class="required">*</span></label>
                <input type="text" name="spm_bahasa_malaysia" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" required value="<?php echo htmlspecialchars($spm_data['bahasa_malaysia'] ?? $application['spm_bahasa_malaysia'] ?? ''); ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Bahasa Inggeris <span class="required">*</span></label>
                <input type="text" name="spm_bahasa_inggeris" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" required value="<?php echo htmlspecialchars($spm_data['bahasa_inggeris'] ?? $application['spm_bahasa_inggeris'] ?? ''); ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Matematik <span class="required">*</span></label>
                <input type="text" name="spm_matematik" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" required value="<?php echo htmlspecialchars($spm_data['matematik'] ?? $application['spm_matematik'] ?? ''); ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sejarah</label>
                <input type="text" name="spm_sejarah" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" value="<?php echo htmlspecialchars($spm_data['sejarah'] ?? $application['spm_sejarah'] ?? ''); ?>">
            </div>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg mb-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Subjek Lain</span>
                <button type="button" id="addSubjectLain" class="add-row-btn">Tambah Subjek</button>
            </div>
            <div id="spm-subjeklain-container">
                <?php 
                $subjek_idx = 0;
                $existing_subjects = [];
                if (!empty($prefill_spm_additional)) {
                    foreach ($prefill_spm_additional as $add_subj) {
                        if (!empty(trim((string)$add_subj['subjek']))) {
                            $existing_subjects[] = [
                                'subjek' => trim((string)$add_subj['subjek']), 
                                'gred' => trim((string)($add_subj['gred'] ?? ''))
                            ];
                        }
                    }
                }
                
                if (!empty($existing_subjects)):
                    foreach ($existing_subjects as $subj_data): ?>
                <div class="spm-subjeklain-entry grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Subjek</label>
                        <input type="text" name="spm_subjek_lain[<?php echo $subjek_idx; ?>][subjek]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" placeholder="CTH: SAINS" value="<?php echo htmlspecialchars($subj_data['subjek']); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gred</label>
                        <input type="text" name="spm_subjek_lain[<?php echo $subjek_idx; ?>][gred]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" placeholder="CTH: A" value="<?php echo htmlspecialchars($subj_data['gred']); ?>">
                    </div>
                    <div class="col-span-1 md:col-span-2 flex justify-end">
                        <button type="button" class="remove-subjeklain-btn text-red-500 text-sm" style="<?php echo $subjek_idx > 0 ? '' : 'display: none;'; ?>">Buang</button>
                    </div>
                </div>
                <?php $subjek_idx++; endforeach; else: ?>
                <div class="spm-subjeklain-entry grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Subjek</label>
                        <input type="text" name="spm_subjek_lain[0][subjek]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" placeholder="CTH: SAINS">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gred</label>
                        <input type="text" name="spm_subjek_lain[0][gred]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" placeholder="CTH: A">
                    </div>
                    <div class="col-span-1 md:col-span-2 flex justify-end">
                        <button type="button" class="remove-subjeklain-btn text-red-500 text-sm" style="display: none;">Buang</button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mb-2">
            <?php $spmExisting = ($spm_data['salinan_sijil'] ?? null) ?: ($application['spm_salinan_sijil_path'] ?? ($application['spm_salinan_sijil'] ?? null)); ?>
            <label class="block text-sm font-medium text-gray-700 mb-2">Salinan Sijil SPM (PDF/Gambar) <span class="required">*</span></label>
            <input type="file" id="spm_salinan_sijil" name="spm_salinan_sijil" class="w-full px-3 py-2 border border-gray-300 rounded-md" accept=".jpg,.jpeg,.png,.gif,.pdf" data-max-size="5" data-allowed-types="image/jpeg,image/jpg,image/png,image/gif,application/pdf" data-field-label="Sijil SPM" <?php echo empty($spmExisting) ? 'required' : ''; ?>>
            <?php if (!empty($spmExisting)): ?>
                <p class="text-xs text-green-700 mt-1">Fail dimuat naik: <?php echo htmlspecialchars(basename($spmExisting)); ?></p>
                <input type="hidden" name="spm_salinan_sijil_path" value="<?php echo htmlspecialchars($spmExisting); ?>">
                <input type="hidden" name="spm_salinan_sijil" value="<?php echo htmlspecialchars($spmExisting); ?>">
            <?php endif; ?>
            <div id="spm_salinan_sijil_status" class="file-status text-xs text-gray-600 mt-1"></div>
        </div>
    </div>
    <script>
    // SPM Subject Lain add/remove
    (function(){
        const container = document.getElementById('spm-subjeklain-container');
        const addBtn = document.getElementById('addSubjectLain');
        function updateRemoveButtons(){
            const entries = container.querySelectorAll('.spm-subjeklain-entry');
            entries.forEach((entry, idx) => {
                const btn = entry.querySelector('.remove-subjeklain-btn');
                btn.style.display = entries.length > 1 ? 'inline' : 'none';
                btn.onclick = function(){ if(entries.length>1){ entry.remove(); updateRemoveButtons(); } };
            });
        }
        function addSubject(){
            const first = container.querySelector('.spm-subjeklain-entry');
            const clone = first.cloneNode(true);
            const count = container.querySelectorAll('.spm-subjeklain-entry').length;
            clone.querySelectorAll('input').forEach(inp => {
                inp.value = '';
                inp.name = inp.name.replace('[0]', '['+count+']');
            });
            container.appendChild(clone);
            updateRemoveButtons();
        }
        if(addBtn){ addBtn.addEventListener('click', addSubject); }
        updateRemoveButtons();
    })();
    </script>
    <div class="section-title">Maklumat Persekolahan & IPT</div>
    <div class="p-6">
        <div id="education-container">
            <?php 
            $edu_idx = 0;
            if (!empty($prefill_education)):
                foreach ($prefill_education as $edu): ?>
            <div class="education-entry bg-gray-50 p-4 rounded-lg mb-4">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Institusi</label>
                    <input type="text" name="persekolahan[<?php echo $edu_idx; ?>][institusi]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" value="<?php echo htmlspecialchars($edu['institusi']); ?>">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kelayakan</label>
                        <select name="persekolahan[<?php echo $edu_idx; ?>][kelayakan]" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">Pilih</option>
                            <option value="STPM" <?php echo (strtoupper($edu['kelayakan'] ?? '') === 'STPM') ? 'selected' : ''; ?>>STPM</option>
                            <option value="Sijil" <?php echo (strtoupper($edu['kelayakan'] ?? '') === 'SIJIL') ? 'selected' : ''; ?>>Sijil</option>
                            <option value="Diploma" <?php echo (strtoupper($edu['kelayakan'] ?? '') === 'DIPLOMA') ? 'selected' : ''; ?>>Diploma</option>
                            <option value="Ijazah" <?php echo (strtoupper($edu['kelayakan'] ?? '') === 'IJAZAH') ? 'selected' : ''; ?>>Ijazah</option>
                            <option value="Sarjana" <?php echo (strtoupper($edu['kelayakan'] ?? '') === 'SARJANA') ? 'selected' : ''; ?>>Sarjana</option>
                            <option value="Master" <?php echo (strtoupper($edu['kelayakan'] ?? '') === 'MASTER') ? 'selected' : ''; ?>>Master</option>
                            <option value="Lain-lain" <?php echo (strtoupper($edu['kelayakan'] ?? '') === 'LAIN-LAIN') ? 'selected' : ''; ?>>Lain-lain</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Dari Tahun</label>
                        <select name="persekolahan[<?php echo $edu_idx; ?>][dari_tahun]" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">Pilih Tahun</option>
                            <?php 
                            $selected_dari = $edu['dari_tahun'] ?? '';
                            for ($year = $last_year; $year >= 1970; $year--): 
                            ?>
                                <option value="<?php echo $year; ?>" <?php echo ($selected_dari == $year) ? 'selected' : ''; ?>><?php echo $year; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hingga Tahun</label>
                        <select name="persekolahan[<?php echo $edu_idx; ?>][hingga_tahun]" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">Pilih Tahun</option>
                            <?php 
                            $selected_hingga = $edu['hingga_tahun'] ?? '';
                            for ($year = $last_year; $year >= 1970; $year--): 
                            ?>
                                <option value="<?php echo $year; ?>" <?php echo ($selected_hingga == $year) ? 'selected' : ''; ?>><?php echo $year; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pangkat/Gred/CGPA</label>
                        <input type="text" name="persekolahan[<?php echo $edu_idx; ?>][gred]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" value="<?php echo htmlspecialchars($edu['gred']); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Salinan Sijil (PDF/Gambar)</label>
                        <?php $eduExistingCert = $edu['sijil'] ?? ''; ?>
                        <input type="file" id="persekolahan_<?php echo $edu_idx; ?>_sijil" name="persekolahan[<?php echo $edu_idx; ?>][sijil]" class="w-full px-3 py-2 border border-gray-300 rounded-md" accept=".jpg,.jpeg,.png,.gif,.pdf" data-max-size="5" data-allowed-types="image/jpeg,image/jpg,image/png,image/gif,application/pdf" data-field-label="Sijil Pendidikan">
                        <?php if (!empty($eduExistingCert)): ?>
                            <p class="text-xs text-green-700 mt-1">Fail dimuat naik: <?php echo htmlspecialchars(basename($eduExistingCert)); ?></p>
                            <input type="hidden" name="persekolahan[<?php echo $edu_idx; ?>][sijil_path]" value="<?php echo htmlspecialchars($eduExistingCert); ?>">
                        <?php endif; ?>
                        <div id="persekolahan_<?php echo $edu_idx; ?>_sijil_status" class="file-status text-xs text-gray-600 mt-1"></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sijil Tambahan (PDF/Gambar)</label>
                        <?php $eduExistingCertTambahan = $edu['sijil_tambahan'] ?? ''; ?>
                        <input type="file" id="persekolahan_<?php echo $edu_idx; ?>_sijil_tambahan" name="persekolahan[<?php echo $edu_idx; ?>][sijil_tambahan]" class="w-full px-3 py-2 border border-gray-300 rounded-md" accept=".jpg,.jpeg,.png,.gif,.pdf" data-max-size="5" data-allowed-types="image/jpeg,image/jpg,image/png,image/gif,application/pdf" data-field-label="Sijil Tambahan">
                        <?php if (!empty($eduExistingCertTambahan)): ?>
                            <p class="text-xs text-green-700 mt-1">Fail dimuat naik: <?php echo htmlspecialchars(basename($eduExistingCertTambahan)); ?></p>
                            <input type="hidden" name="persekolahan[<?php echo $edu_idx; ?>][sijil_tambahan_path]" value="<?php echo htmlspecialchars($eduExistingCertTambahan); ?>">
                        <?php endif; ?>
                        <div id="persekolahan_<?php echo $edu_idx; ?>_sijil_tambahan_status" class="file-status text-xs text-gray-600 mt-1"></div>
                    </div>
                </div>
                <div class="flex justify-end"><button type="button" class="remove-education-btn text-red-500 text-sm" style="<?php echo $edu_idx > 0 ? '' : 'display: none;'; ?>">Buang</button></div>
            </div>
            <?php $edu_idx++; endforeach; else: ?>
            <div class="education-entry bg-gray-50 p-4 rounded-lg mb-4">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Institusi</label>
                    <input type="text" name="persekolahan[0][institusi]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kelayakan</label>
                        <select name="persekolahan[0][kelayakan]" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">Pilih</option>
                            <option value="SPM">SPM</option>
                            <option value="STPM">STPM</option>
                            <option value="Sijil">Sijil</option>
                            <option value="Diploma">Diploma</option>
                            <option value="Ijazah">Ijazah</option>
                            <option value="Sarjana">Sarjana</option>
                            <option value="Master">Master</option>
                            <option value="Lain-lain">Lain-lain</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Dari Tahun</label>
                        <select name="persekolahan[0][dari_tahun]" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">Pilih Tahun</option>
                            <?php 
                            for ($year = $last_year; $year >= 1970; $year--): 
                            ?>
                                <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hingga Tahun</label>
                        <select name="persekolahan[0][hingga_tahun]" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">Pilih Tahun</option>
                            <?php 
                            for ($year = $last_year; $year >= 1970; $year--): 
                            ?>
                                <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pangkat/Gred/CGPA</label>
                        <input type="text" name="persekolahan[0][gred]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Salinan Sijil (PDF/Gambar)</label>
                        <input type="file" id="persekolahan_0_sijil" name="persekolahan[0][sijil]" class="w-full px-3 py-2 border border-gray-300 rounded-md" accept=".jpg,.jpeg,.png,.gif,.pdf" data-max-size="5" data-allowed-types="image/jpeg,image/jpg,image/png,image/gif,application/pdf" data-field-label="Sijil Pendidikan">
                        <div id="persekolahan_0_sijil_status" class="file-status text-xs text-gray-600 mt-1"></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sijil Tambahan (PDF/Gambar)</label>
                        <input type="file" id="persekolahan_0_sijil_tambahan" name="persekolahan[0][sijil_tambahan]" class="w-full px-3 py-2 border border-gray-300 rounded-md" accept=".jpg,.jpeg,.png,.gif,.pdf" data-max-size="5" data-allowed-types="image/jpeg,image/jpg,image/png,image/gif,application/pdf" data-field-label="Sijil Tambahan">
                        <div id="persekolahan_0_sijil_tambahan_status" class="file-status text-xs text-gray-600 mt-1"></div>
                    </div>
                </div>
                <div class="flex justify-end"><button type="button" class="remove-education-btn text-red-500 text-sm" style="display: none;">Buang</button></div>
            </div>
            <?php endif; ?>
        </div>
        <div class="flex justify-end"><button type="button" id="addEducation" class="add-row-btn">Tambah Pendidikan</button></div>
    </div>

<script>
// Generic cloner for entries with index-based names [0]
(document.addEventListener('DOMContentLoaded', function(){
    function cloneEntry(containerId, entryClass){
        const container = document.getElementById(containerId);
        if(!container) return;
        const first = container.querySelector('.'+entryClass);
        if(!first) return;
        const clone = first.cloneNode(true);
        const count = container.querySelectorAll('.'+entryClass).length;
        clone.querySelectorAll('input, select, textarea').forEach(inp => {
            // reset values
            if(inp.type === 'file'){ 
                inp.value=''; 
                inp.removeAttribute('required'); // Remove required from cloned file inputs
            }
            else if(inp.type === 'hidden' && inp.name && (inp.name.includes('_path') || inp.name.includes('sijil_path'))) {
                // Remove hidden path inputs from cloned entry
                inp.remove();
                return;
            }
            else { 
                inp.value=''; 
            }
            if(inp.name){ inp.name = inp.name.replace('[0]', '['+count+']'); }
            if(inp.type === 'file'){
                const m = inp.name.match(/^([a-zA-Z_]+)\[(\d+)\]\[([a-zA-Z_]+)\]$/);
                if(m){ inp.id = `${m[1]}_${m[2]}_${m[3]}`; }
            }
        });
        
        // Remove "existing file" messages from cloned entry
        clone.querySelectorAll('p.text-xs.text-green-700').forEach(p => {
            if(p.textContent.includes('Fail dimuat naik:')) {
                p.remove();
            }
        });
        
        // update status div ids next to file inputs
        clone.querySelectorAll('.file-status').forEach(div => {
            const prev = div.previousElementSibling;
            if(prev && prev.tagName === 'INPUT' && prev.type === 'file'){
                div.id = prev.id + '_status';
                div.textContent = '';
            }
        });
        // show remove button
        const removeBtn = clone.querySelector('[class*="remove-"]');
        if(removeBtn){ removeBtn.style.display = 'inline'; }
        container.appendChild(clone);
        updateRemoveVisibility(containerId, entryClass);
    }
    function updateRemoveVisibility(containerId, entryClass){
        const container = document.getElementById(containerId);
        const entries = container?.querySelectorAll('.'+entryClass) || [];
        const show = entries.length > 1;
        entries.forEach(entry => {
            const btn = entry.querySelector('[class*="remove-"]');
            if(btn){ btn.style.display = show ? 'inline' : 'none'; }
        });
    }
    function register(containerId, entryClass, addBtnId){
        const addBtn = document.getElementById(addBtnId);
        if(addBtn){ addBtn.addEventListener('click', () => cloneEntry(containerId, entryClass)); }
        const container = document.getElementById(containerId);
        if(container){
            updateRemoveVisibility(containerId, entryClass);
            container.addEventListener('click', (e)=>{
                const target = e.target;
                if(target && target.className && target.className.includes('remove-')){
                    const card = target.closest('.'+entryClass);
                    const all = container.querySelectorAll('.'+entryClass);
                    if(all.length>1 && card){ card.remove(); updateRemoveVisibility(containerId, entryClass); }
                }
            });
        }
    }
    register('education-container','education-entry','addEducation');
    register('professional-body-container','professional-body-entry','addProfessionalBody');
    register('extracurricular-container','extracurricular-entry','addExtracurricular');
    register('work-experience-container','work-experience-entry','addWorkExperience');
    
    // Education field validation - make fields mandatory if Nama Institusi has value
    function setupEducationValidation() {
        const educationContainer = document.getElementById('education-container');
        if (!educationContainer) return;
        
        function validateEducationEntry(entry) {
            const institusiInput = entry.querySelector('input[name*="[institusi]"]');
            if (!institusiInput) return;
            
            const kelayakanSelect = entry.querySelector('select[name*="[kelayakan]"]');
            const dariTahunSelect = entry.querySelector('select[name*="[dari_tahun]"]');
            const hinggaTahunSelect = entry.querySelector('select[name*="[hingga_tahun]"]');
            
            const hasInstitusi = institusiInput.value.trim() !== '';
            
            // Set required attribute based on institusi value
            if (kelayakanSelect) {
                kelayakanSelect.required = hasInstitusi;
                if (hasInstitusi) {
                    kelayakanSelect.classList.add('border-red-300');
                    // Add required indicator to label
                    const label = kelayakanSelect.closest('div').querySelector('label');
                    if (label && !label.querySelector('.required')) {
                        const span = document.createElement('span');
                        span.className = 'required';
                        span.textContent = '*';
                        label.appendChild(document.createTextNode(' '));
                        label.appendChild(span);
                    }
                } else {
                    kelayakanSelect.classList.remove('border-red-300');
                    const label = kelayakanSelect.closest('div').querySelector('label');
                    if (label) {
                        const requiredSpan = label.querySelector('.required');
                        if (requiredSpan) requiredSpan.remove();
                    }
                }
            }
            
            if (dariTahunSelect) {
                dariTahunSelect.required = hasInstitusi;
                if (hasInstitusi) {
                    dariTahunSelect.classList.add('border-red-300');
                    const label = dariTahunSelect.closest('div').querySelector('label');
                    if (label && !label.querySelector('.required')) {
                        const span = document.createElement('span');
                        span.className = 'required';
                        span.textContent = '*';
                        label.appendChild(document.createTextNode(' '));
                        label.appendChild(span);
                    }
                } else {
                    dariTahunSelect.classList.remove('border-red-300');
                    const label = dariTahunSelect.closest('div').querySelector('label');
                    if (label) {
                        const requiredSpan = label.querySelector('.required');
                        if (requiredSpan) requiredSpan.remove();
                    }
                }
            }
            
            if (hinggaTahunSelect) {
                hinggaTahunSelect.required = hasInstitusi;
                if (hasInstitusi) {
                    hinggaTahunSelect.classList.add('border-red-300');
                    const label = hinggaTahunSelect.closest('div').querySelector('label');
                    if (label && !label.querySelector('.required')) {
                        const span = document.createElement('span');
                        span.className = 'required';
                        span.textContent = '*';
                        label.appendChild(document.createTextNode(' '));
                        label.appendChild(span);
                    }
                } else {
                    hinggaTahunSelect.classList.remove('border-red-300');
                    const label = hinggaTahunSelect.closest('div').querySelector('label');
                    if (label) {
                        const requiredSpan = label.querySelector('.required');
                        if (requiredSpan) requiredSpan.remove();
                    }
                }
            }
            
            // Remove red border when field is filled
            if (kelayakanSelect && kelayakanSelect.value !== '') {
                kelayakanSelect.classList.remove('border-red-300');
            }
            if (dariTahunSelect && dariTahunSelect.value !== '') {
                dariTahunSelect.classList.remove('border-red-300');
            }
            if (hinggaTahunSelect && hinggaTahunSelect.value !== '') {
                hinggaTahunSelect.classList.remove('border-red-300');
            }
        }
        
        // Setup validation for all existing entries
        function setupAllEntries() {
            const entries = educationContainer.querySelectorAll('.education-entry');
            entries.forEach(entry => {
                const institusiInput = entry.querySelector('input[name*="[institusi]"]');
                if (institusiInput) {
                    // Validate on input
                    institusiInput.addEventListener('input', () => validateEducationEntry(entry));
                    institusiInput.addEventListener('blur', () => validateEducationEntry(entry));
                    
                    // Also validate when other fields change
                    const kelayakanSelect = entry.querySelector('select[name*="[kelayakan]"]');
                    const dariTahunSelect = entry.querySelector('select[name*="[dari_tahun]"]');
                    const hinggaTahunSelect = entry.querySelector('select[name*="[hingga_tahun]"]');
                    
                    if (kelayakanSelect) {
                        kelayakanSelect.addEventListener('change', () => validateEducationEntry(entry));
                    }
                    if (dariTahunSelect) {
                        dariTahunSelect.addEventListener('change', () => validateEducationEntry(entry));
                    }
                    if (hinggaTahunSelect) {
                        hinggaTahunSelect.addEventListener('change', () => validateEducationEntry(entry));
                    }
                    
                    // Initial validation
                    validateEducationEntry(entry);
                }
            });
        }
        
        // Initial setup
        setupAllEntries();
        
        // Re-setup when new entries are added
        const observer = new MutationObserver(() => {
            setupAllEntries();
        });
        observer.observe(educationContainer, { childList: true });
    }
    
    // Run validation setup
    setupEducationValidation();
}));
</script>
    <div class="section-title">Pengalaman Kerja</div>
    <div class="p-6">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Ada Pengalaman Kerja</label>
            <div class="flex items-center gap-6">
                <label class="inline-flex items-center">
                    <input type="radio" name="ada_pengalaman_kerja" value="Ya" class="mr-2" checked>
                    <span>Ya</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="ada_pengalaman_kerja" value="Tidak" class="mr-2">
                    <span>Tidak</span>
                </label>
            </div>
        </div>
        <div id="work-experience-container">
            <?php 
            $work_idx = 0; 
            if (!empty($prefill_work_experience)):
                foreach ($prefill_work_experience as $work): ?>
            <div class="work-experience-entry bg-gray-50 p-4 rounded-lg mb-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Syarikat <span class="required">*</span></label>
                        <input type="text" name="pengalaman_kerja[<?php echo $work_idx; ?>][nama_syarikat]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" placeholder="MASUKKAN NAMA SYARIKAT" required value="<?php echo htmlspecialchars($work['nama_syarikat']); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Jawatan <span class="required">*</span></label>
                        <input type="text" name="pengalaman_kerja[<?php echo $work_idx; ?>][jawatan]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" placeholder="MASUKKAN JAWATAN" required value="<?php echo htmlspecialchars($work['jawatan']); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mula Berkhidmat</label>
                        <input type="month" name="pengalaman_kerja[<?php echo $work_idx; ?>][mula_berkhidmat]" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="YYYY-MM" value="<?php echo htmlspecialchars($work['mula_berkhidmat']); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tamat Berkhidmat</label>
                        <input type="month" name="pengalaman_kerja[<?php echo $work_idx; ?>][tamat_berkhidmat]" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="YYYY-MM" value="<?php echo htmlspecialchars($work['tamat_berkhidmat']); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Unit/Bahagian</label>
                        <input type="text" name="pengalaman_kerja[<?php echo $work_idx; ?>][unit_bahagian]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" placeholder="CTH: UNIT SUMBER MANUSIA" value="<?php echo htmlspecialchars($work['unit_bahagian']); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gred</label>
                        <input type="text" name="pengalaman_kerja[<?php echo $work_idx; ?>][gred]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" placeholder="CTH: N41" value="<?php echo htmlspecialchars($work['gred']); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gaji (RM)</label>
                        <input type="number" step="0.01" min="0" name="pengalaman_kerja[<?php echo $work_idx; ?>][gaji]" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="CTH: 3500.00" value="<?php echo htmlspecialchars($work['gaji']); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Taraf Jawatan</label>
                        <select name="pengalaman_kerja[<?php echo $work_idx; ?>][taraf_jawatan]" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">Pilih</option>
                            <option value="Tetap" <?php echo $work['taraf_jawatan'] === 'Tetap' ? 'selected' : ''; ?>>Tetap</option>
                            <option value="Kontrak" <?php echo $work['taraf_jawatan'] === 'Kontrak' ? 'selected' : ''; ?>>Kontrak</option>
                            <option value="Sementara" <?php echo $work['taraf_jawatan'] === 'Sementara' ? 'selected' : ''; ?>>Sementara</option>
                            <option value="Sambilan" <?php echo $work['taraf_jawatan'] === 'Sambilan' ? 'selected' : ''; ?>>Sambilan</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bidang Tugas</label>
                        <textarea name="pengalaman_kerja[<?php echo $work_idx; ?>][bidang_tugas]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" rows="3" placeholder="RINGKASAN TUGAS / PENCAPAIAN"><?php echo htmlspecialchars($work['bidang_tugas']); ?></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Alasan Berhenti</label>
                        <textarea name="pengalaman_kerja[<?php echo $work_idx; ?>][alasan_berhenti]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" rows="2" placeholder="CTH: KONTRAK TAMAT / BERPINDAH / LAIN-LAIN"><?php echo htmlspecialchars($work['alasan_berhenti']); ?></textarea>
                    </div>
                </div>
                <div class="flex justify-end mt-2">
                    <button type="button" class="remove-work-experience-btn text-red-500 text-sm" style="<?php echo $work_idx > 0 ? '' : 'display: none;'; ?>">Buang</button>
                </div>
            </div>
            <?php $work_idx++; endforeach; else: ?>
            <div class="work-experience-entry bg-gray-50 p-4 rounded-lg mb-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Syarikat <span class="required">*</span></label>
                        <input type="text" name="pengalaman_kerja[0][nama_syarikat]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" placeholder="MASUKKAN NAMA SYARIKAT" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Jawatan <span class="required">*</span></label>
                        <input type="text" name="pengalaman_kerja[0][jawatan]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" placeholder="MASUKKAN JAWATAN" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mula Berkhidmat</label>
                        <input type="month" name="pengalaman_kerja[0][mula_berkhidmat]" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="YYYY-MM">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tamat Berkhidmat</label>
                        <input type="month" name="pengalaman_kerja[0][tamat_berkhidmat]" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="YYYY-MM">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Unit/Bahagian</label>
                        <input type="text" name="pengalaman_kerja[0][unit_bahagian]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" placeholder="CTH: UNIT SUMBER MANUSIA">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gred</label>
                        <input type="text" name="pengalaman_kerja[0][gred]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" placeholder="CTH: N41">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gaji (RM)</label>
                        <input type="number" step="0.01" min="0" name="pengalaman_kerja[0][gaji]" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="CTH: 3500.00">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Taraf Jawatan</label>
                        <select name="pengalaman_kerja[0][taraf_jawatan]" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">Pilih</option>
                            <option value="Tetap">Tetap</option>
                            <option value="Kontrak">Kontrak</option>
                            <option value="Sementara">Sementara</option>
                            <option value="Sambilan">Sambilan</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bidang Tugas</label>
                        <textarea name="pengalaman_kerja[0][bidang_tugas]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" rows="3" placeholder="RINGKASAN TUGAS / PENCAPAIAN"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Alasan Berhenti</label>
                        <textarea name="pengalaman_kerja[0][alasan_berhenti]" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" rows="2" placeholder="CTH: KONTRAK TAMAT / BERPINDAH / LAIN-LAIN"></textarea>
                    </div>
                </div>
                <div class="flex justify-end mt-2">
                    <button type="button" class="remove-work-experience-btn text-red-500 text-sm" style="display: none;">Buang</button>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="flex justify-end">
            <button type="button" id="addWorkExperience" class="add-row-btn">Tambah Pengalaman</button>
        </div>
    </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        function setWorkExperienceVisibility(show){
            var container = document.getElementById('work-experience-container');
            var addBtn = document.getElementById('addWorkExperience');
            if(!container || !addBtn) return;
            if(show){
                container.classList.remove('hidden');
                addBtn.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
                addBtn.classList.add('hidden');
            }
            var inputs = container.querySelectorAll('input, select, textarea, button.remove-work-experience-btn');
            inputs.forEach(function(el){ el.disabled = !show; });
        }
        function initWorkExperienceToggle(){
            var radios = document.querySelectorAll('input[name="ada_pengalaman_kerja"]');
            radios.forEach(function(radio){
                radio.addEventListener('change', function(){
                    setWorkExperienceVisibility(this.value === 'Ya');
                });
            });
            var checked = document.querySelector('input[name="ada_pengalaman_kerja"]:checked');
            setWorkExperienceVisibility((checked && checked.value === 'Ya'));
        }
        initWorkExperienceToggle();
    });
    </script>
