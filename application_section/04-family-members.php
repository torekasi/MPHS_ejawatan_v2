<?php
/**
 * @FileID: application_section_family_members
 * @Module: ApplicationSectionFamilyMembers
 * @Author: Nefi
 * @LastModified: 2025-11-15
 * @SecurityTag: validated
 */
?>
<section class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
  <div class="section-title">Maklumat Ahli Keluarga</div>
  <div class="p-6 space-y-6">
    <?php
      $members = [];
      if (isset($prefill_family_members) && is_array($prefill_family_members) && count($prefill_family_members) > 0) {
        foreach ($prefill_family_members as $fm) {
          $h = strtolower(trim($fm['hubungan'] ?? ''));
          if ($h === 'ayah' || $h === 'ibu') {
            $members[] = $fm;
          }
        }
      }
      $hasAyah = false; $hasIbu = false;
      foreach ($members as $m) {
        $h = strtolower(trim($m['hubungan'] ?? ''));
        if ($h === 'ayah') $hasAyah = true;
        if ($h === 'ibu') $hasIbu = true;
      }
      if (!$hasAyah) { $members[] = ['hubungan' => 'Ayah', 'nama' => '', 'pekerjaan' => '', 'telefon' => '', 'kewarganegaraan' => '']; }
      if (!$hasIbu) { $members[] = ['hubungan' => 'Ibu', 'nama' => '', 'pekerjaan' => '', 'telefon' => '', 'kewarganegaraan' => '']; }
      usort($members, function($a,$b){
        $order = ['ayah'=>0,'ibu'=>1];
        return ($order[strtolower($a['hubungan'] ?? '')] ?? 99) <=> ($order[strtolower($b['hubungan'] ?? '')] ?? 99);
      });
    ?>
    <div id="family-members-container" class="space-y-6">
      <?php foreach ($members as $idx => $m): ?>
      <div class="space-y-3 family-member-entry">
        <div>
          <span class="text-sm text-gray-700 font-medium"><?php echo htmlspecialchars($m['hubungan'] ?? ''); ?></span>
          <input type="hidden" name="ahli_keluarga[<?php echo (int)$idx; ?>][hubungan]" value="<?php echo htmlspecialchars($m['hubungan'] ?? ''); ?>">
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nama <span class="required">*</span></label>
            <input type="text" name="ahli_keluarga[<?php echo (int)$idx; ?>][nama]" value="<?php echo htmlspecialchars($m['nama'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Pekerjaan <span class="required">*</span></label>
            <input type="text" name="ahli_keluarga[<?php echo (int)$idx; ?>][pekerjaan]" value="<?php echo htmlspecialchars($m['pekerjaan'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Telefon <span class="required">*</span></label>
            <input type="tel" name="ahli_keluarga[<?php echo (int)$idx; ?>][telefon]" value="<?php echo htmlspecialchars($m['telefon'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md" pattern="[0-9]{7,15}" minlength="7" maxlength="15" inputmode="numeric" placeholder="0123456789" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Kewarganegaraan <span class="required">*</span></label>
            <select name="ahli_keluarga[<?php echo (int)$idx; ?>][kewarganegaraan]" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
              <option value="">Sila Pilih</option>
              <?php 
                $opts = ["Warganegara Malaysia","Penduduk Tetap","Bukan Warganegara","Pelancong"]; 
                $val = (string)($m['kewarganegaraan'] ?? '');
                foreach ($opts as $opt) {
                  $sel = (strtoupper($val) === strtoupper($opt)) ? 'selected' : '';
                  echo '<option value="' . htmlspecialchars($opt) . '" ' . $sel . '>' . htmlspecialchars($opt) . '</option>';
                }
              ?>
            </select>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    
  </div>
</section>
<script>
// Hubungan dipaparkan sahaja (Ayah/Ibu); tiada dropdown atau penambahan baris.
</script>