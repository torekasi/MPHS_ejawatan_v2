<?php

/**
 * @FileID: app_section_part4_001
 * @Module: ApplicationSectionPart4
 * @Author: Nefi
 * @LastModified: 2025-11-09T00:00:00Z
 * @SecurityTag: validated
 */
if (!defined('APP_SECURE')) { http_response_code(403); exit; }
?>
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
    <div class="section-title">Pengisytiharan</div>
    <div class="p-6 space-y-6">
        <?php
        $declarations = [
            ['key' => 'pekerja_perkhidmatan_awam', 'label' => 'Adakah anda seorang pekerja perkhidmatan awam?'],
            ['key' => 'pertalian_kakitangan', 'label' => 'Adakah anda mempunyai pertalian keluarga dengan mana-mana kakitangan MPHS?'],
            ['key' => 'pernah_bekerja_mphs', 'label' => 'Adakah anda pernah bekerja dengan MPHS?'],
            ['key' => 'tindakan_tatatertib', 'label' => 'Adakah anda pernah dikenakan tindakan tatatertib?'],
            ['key' => 'kesalahan_undangundang', 'label' => 'Adakah anda pernah disabitkan dengan kesalahan undang-undang?'],
            ['key' => 'muflis', 'label' => 'Adakah anda pernah diisytiharkan muflis?'],
        ];
        foreach ($declarations as $d):
            $val = strtoupper($application[$d['key']] ?? '');
            $nyatakan_key = $d['key'] . '_nyatakan';
            $nyatakan_val = $application[$nyatakan_key] ?? '';
        ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo htmlspecialchars($d['label']); ?> <span class="required">*</span></label>
            <div class="mt-2 space-x-4">
                <label class="inline-flex items-center">
                    <input type="radio" name="<?php echo $d['key']; ?>" value="Ya" class="form-radio h-4 w-4 text-blue-600" <?php echo ($val==='YA' || $val==='Ya') ? 'checked' : ''; ?> required>
                    <span class="ml-2">Ya</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="<?php echo $d['key']; ?>" value="Tidak" class="form-radio h-4 w-4 text-blue-600" <?php echo ($val==='TIDAK' || $val==='Tidak') ? 'checked' : ''; ?> required>
                    <span class="ml-2">Tidak</span>
                </label>
            </div>
            <div class="mt-2" id="<?php echo $d['key']; ?>_nyatakan_field" style="display: <?php echo ($val==='YA' || $val==='Ya') ? 'block' : 'none'; ?>;">
                <?php if ($d['key'] !== 'pertalian_kakitangan'): ?>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nyatakan (jika Ya)</label>
                <textarea name="<?php echo $nyatakan_key; ?>" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" placeholder="NYATAKAN MAKLUMAT"><?php echo htmlspecialchars($nyatakan_val); ?></textarea>
                <?php endif; ?>
                <?php if ($d['key'] === 'pertalian_kakitangan'): ?>
                <div class="mt-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Kakitangan Berkaitan (jika ada)</label>
                    <input type="text" name="nama_kakitangan_pertalian" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" value="<?php echo htmlspecialchars($application['nama_kakitangan_pertalian'] ?? ''); ?>">
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
    <div class="section-title">Rujukan</div>
    <div class="p-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="font-medium text-gray-800 mb-3">Rujukan 1</h4>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama <span class="required">*</span></label>
                <?php $ref1_nama = $_POST['rujukan_1_nama'] ?? $application['rujukan_1_nama'] ?? ($prefill_references[0]['nama'] ?? ''); ?>
                <input type="text" name="rujukan_1_nama" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" required value="<?php echo htmlspecialchars($ref1_nama); ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">No. Telefon <span class="required">*</span></label>
                        <?php $ref1_telefon = $_POST['rujukan_1_telefon'] ?? $application['rujukan_1_telefon'] ?? ($prefill_references[0]['no_telefon'] ?? ''); ?>
                        <input type="tel" name="rujukan_1_telefon" class="w-full px-3 py-2 border border-gray-300 rounded-md" pattern="[0-9]{7,15}" minlength="7" maxlength="15" required value="<?php echo htmlspecialchars($ref1_telefon); ?>" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tempoh Mengenali (tahun) <span class="required">*</span></label>
                        <?php $ref1_tempoh = $_POST['rujukan_1_tempoh'] ?? $application['rujukan_1_tempoh'] ?? ($prefill_references[0]['tempoh_mengenali'] ?? ''); ?>
                        <input type="text" name="rujukan_1_tempoh" class="w-full px-3 py-2 border border-gray-300 rounded-md" inputmode="numeric" pattern="[0-9]{1,2}" maxlength="2" required value="<?php echo htmlspecialchars($ref1_tempoh); ?>" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 2)">
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="font-medium text-gray-800 mb-3">Rujukan 2</h4>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama <span class="required">*</span></label>
                <?php $ref2_nama = $_POST['rujukan_2_nama'] ?? $application['rujukan_2_nama'] ?? ($prefill_references[1]['nama'] ?? ''); ?>
                <input type="text" name="rujukan_2_nama" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" required value="<?php echo htmlspecialchars($ref2_nama); ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">No. Telefon <span class="required">*</span></label>
                        <?php $ref2_telefon = $_POST['rujukan_2_telefon'] ?? $application['rujukan_2_telefon'] ?? ($prefill_references[1]['no_telefon'] ?? ''); ?>
                        <input type="tel" name="rujukan_2_telefon" class="w-full px-3 py-2 border border-gray-300 rounded-md" pattern="[0-9]{7,15}" minlength="7" maxlength="15" required value="<?php echo htmlspecialchars($ref2_telefon); ?>" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tempoh Mengenali (tahun) <span class="required">*</span></label>
                        <?php $ref2_tempoh = $_POST['rujukan_2_tempoh'] ?? $application['rujukan_2_tempoh'] ?? ($prefill_references[1]['tempoh_mengenali'] ?? ''); ?>
                        <input type="text" name="rujukan_2_tempoh" class="w-full px-3 py-2 border border-gray-300 rounded-md" inputmode="numeric" pattern="[0-9]{1,2}" maxlength="2" required value="<?php echo htmlspecialchars($ref2_tempoh); ?>" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 2)">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>