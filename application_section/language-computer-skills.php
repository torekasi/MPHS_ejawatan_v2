<?php
/**
 * @FileID: app_section_part3_001
 * @Module: ApplicationSectionPart3
 * @Author: Nefi
 * @LastModified: 2025-11-09T00:00:00Z
 * @SecurityTag: validated
 */
if (!defined('APP_SECURE')) { http_response_code(403); exit; }
?>
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
    <div class="section-title">5. KEMAHIRAN BAHASA</div>
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
</div>

<div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
    <div class="section-title">6. KEMAHIRAN KOMPUTER</div>
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
</div>