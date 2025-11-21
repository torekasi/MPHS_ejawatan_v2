<?php
/**
 * @FileID: app_section_part1_001
 * @Module: ApplicationSectionPart1
 * @Author: Nefi
 * @LastModified: 2025-11-09T12:10:00Z
 * @SecurityTag: validated
 */
if (!defined('APP_SECURE')) { http_response_code(403); exit; }
?>
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
    <div class="section-title">Maklumat peribadi</div>
    <div class="p-6 space-y-6">
        <div class="border border-gray-200 rounded-md p-4 space-y-6">
        <div class="grid grid-cols-1 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Penuh <span class="required">*</span></label>
                <input type="text" id="nama_penuh" name="nama_penuh" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" required value="<?php echo htmlspecialchars($application['nama_penuh'] ?? ''); ?>">
            </div>
        </div>

        <div class="pt-4 space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nombor IC <span class="required">*</span></label>
                <input type="text" id="nombor_ic" name="nombor_ic" class="w-full px-3 py-2 border border-gray-300 rounded-md" pattern="^\d{6}-\d{2}-\d{4}$" minlength="14" maxlength="14" placeholder="800101-14-1234" required value="<?php echo htmlspecialchars($application['nombor_ic'] ?? ''); ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nombor Surat Beranak <span class="required">*</span></label>
                <input type="text" id="nombor_surat_beranak" name="nombor_surat_beranak" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" placeholder="NOMBOR SURAT BERANAK" required value="<?php echo htmlspecialchars($application['nombor_surat_beranak'] ?? ''); ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Agama <span class="required">*</span></label>
                <select id="agama" name="agama" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    <option value="">Sila Pilih</option>
                    <?php foreach (["Islam","Buddha","Hindu","Kristian","Lain-lain"] as $opt): ?>
                        <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo (strtoupper($application['agama'] ?? '')===strtoupper($opt)) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Jantina <span class="required">*</span></label>
                <select id="jantina" name="jantina" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    <option value="">Sila Pilih</option>
                    <option value="Lelaki" <?php $jantina = $application['jantina'] ?? ''; echo ($jantina==='LELAKI' || $jantina==='Lelaki') ? 'selected' : ''; ?>>Lelaki</option>
                    <option value="Perempuan" <?php $jantina = $application['jantina'] ?? ''; echo ($jantina==='PEREMPUAN' || $jantina==='Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md" required value="<?php echo htmlspecialchars($application['email'] ?? ''); ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Taraf Perkahwinan <span class="required">*</span></label>
                <select id="taraf_perkahwinan" name="taraf_perkahwinan" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    <option value="">Sila Pilih</option>
                    <?php foreach (["Bujang","Berkahwin","Duda","Janda","Balu"] as $opt): ?>
                        <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo (strtoupper($application['taraf_perkahwinan'] ?? '')===strtoupper($opt)) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
        </div>
        <!-- Row: Nombor Telefon + Tarikh Lahir + Umur -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nombor Telefon <span class="required">*</span></label>
                <input type="tel" name="nombor_telefon" class="w-full px-3 py-2 border border-gray-300 rounded-md" pattern="[0-9]{7,15}" minlength="7" maxlength="15" placeholder="0123456789" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required value="<?php echo htmlspecialchars($application['nombor_telefon'] ?? ''); ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tarikh Lahir <span class="required">*</span></label>
                <input type="date" id="tarikh_lahir" name="tarikh_lahir" class="w-full px-3 py-2 border border-gray-300 rounded-md" required value="<?php echo htmlspecialchars($application['tarikh_lahir'] ?? ''); ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Umur <span class="required">*</span></label>
                <input type="text" id="umur" name="umur" class="w-full px-3 py-2 border border-gray-300 rounded-md" inputmode="numeric" pattern="^[0-9]{1,2}$" maxlength="2" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required value="<?php echo htmlspecialchars($application['umur'] ?? ''); ?>">
            </div>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Negeri Kelahiran <span class="required">*</span></label>
                <select id="negeri_kelahiran" name="negeri_kelahiran" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    <option value="">Sila Pilih</option>
                    <?php foreach (($malaysian_states ?? []) as $state): ?>
                        <option value="<?php echo htmlspecialchars($state); ?>" <?php echo (strtoupper($application['negeri_kelahiran'] ?? '')===strtoupper($state)) ? 'selected' : ''; ?>><?php echo htmlspecialchars($state); ?></option>
                    <?php endforeach; ?>
                    <option value="Bukan Malaysia" <?php echo (strtoupper($application['negeri_kelahiran'] ?? '')==='BUKAN MALAYSIA') ? 'selected' : ''; ?>>Bukan Malaysia</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Bangsa <span class="required">*</span></label>
                <select id="bangsa" name="bangsa" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    <option value="">Sila Pilih</option>
                    <?php foreach (["Melayu","Cina","India","Kadazan","Lain-lain"] as $opt): ?>
                        <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo (strtoupper($application['bangsa'] ?? '')===strtoupper($opt)) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Warganegara <span class="required">*</span></label>
                <select id="warganegara" name="warganegara" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    <option value="">Sila Pilih</option>
                    <?php foreach (["Warganegara Malaysia","Penduduk Tetap","Bukan Warganegara","Pelancong"] as $opt): ?>
                        <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo (strtoupper($application['warganegara'] ?? '')===strtoupper($opt)) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text sm font-medium text-gray-700 mb-2">Tempoh Di Selangor <span class="required">*</span></label>
                <input type="text" id="tempoh_bermastautin_selangor" name="tempoh_bermastautin_selangor" class="w-full px-3 py-2 border border-gray-300 rounded-md" inputmode="numeric" pattern="^[0-9]{1,2}$" maxlength="2" placeholder="0-99" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required value="<?php echo htmlspecialchars($application['tempoh_bermastautin_selangor'] ?? ''); ?>">
            </div>
        </div>
        
        <!-- Maklumat Pasangan - dipaparkan bila Taraf Perkahwinan = Berkahwin -->
        <div id="maklumat_pasangan_field" class="border border-gray-200 rounded-md p-4 mt-4" style="display: none;">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Maklumat Pasangan</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                <div class="col-span-1 sm:col-span-2 md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Pasangan <span class="required">*</span></label>
                    <input type="text" id="nama_pasangan" name="nama_pasangan" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" value="<?php echo htmlspecialchars($application['nama_pasangan'] ?? ''); ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombor Telefon Pasangan <span class="required">*</span></label>
                    <input type="tel" id="telefon_pasangan" name="telefon_pasangan" class="w-full px-3 py-2 border border-gray-300 rounded-md" pattern="[0-9]{7,15}" minlength="7" maxlength="15" placeholder="0123456789" oninput="this.value = this.value.replace(/[^0-9]/g, '')" value="<?php echo htmlspecialchars($application['telefon_pasangan'] ?? ''); ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status Pekerjaan Pasangan <span class="required">*</span></label>
                    <select id="status_pasangan" name="status_pasangan" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        <option value="">Sila Pilih</option>
                        <?php 
                            // Selaraskan pilihan dengan borang asal (Bahagian 1)
                            $spouseStatusOptions = [
                                "MPHS","Kerajaan","Berkanun","Swasta","Bekerja sendiri","Tidak Bekerja"
                            ];
                            foreach ($spouseStatusOptions as $opt): ?>
                            <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo (strtoupper($application['status_pasangan'] ?? '')===strtoupper($opt)) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bilangan Anak</label>
                    <input type="text" id="bilangan_anak" name="bilangan_anak" class="w-full px-3 py-2 border border-gray-300 rounded-md" inputmode="numeric" pattern="^[0-9]{1,2}$" maxlength="2" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 2)" value="<?php echo htmlspecialchars($application['bilangan_anak'] ?? ''); ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pekerjaan Pasangan</label>
                    <input type="text" id="pekerjaan_pasangan" name="pekerjaan_pasangan" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" value="<?php echo htmlspecialchars($application['pekerjaan_pasangan'] ?? ''); ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Majikan Pasangan</label>
                    <input type="text" id="nama_majikan_pasangan" name="nama_majikan_pasangan" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" value="<?php echo htmlspecialchars($application['nama_majikan_pasangan'] ?? ''); ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Telefon Pejabat Pasangan</label>
                    <input type="tel" id="telefon_pejabat_pasangan" name="telefon_pejabat_pasangan" class="w-full px-3 py-2 border border-gray-300 rounded-md" pattern="[0-9]{7,15}" minlength="7" maxlength="15" placeholder="0376543210" oninput="this.value = this.value.replace(/[^0-9]/g, '')" value="<?php echo htmlspecialchars($application['telefon_pejabat_pasangan'] ?? ''); ?>">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Alamat Majikan Pasangan</label>
                    <input type="text" id="alamat_majikan_pasangan" name="alamat_majikan_pasangan" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" value="<?php echo htmlspecialchars($application['alamat_majikan_pasangan'] ?? ''); ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Poskod Majikan Pasangan</label>
                    <input type="text" id="poskod_majikan_pasangan" name="poskod_majikan_pasangan" class="w-full px-3 py-2 border border-gray-300 rounded-md" pattern="[0-9]{5}" minlength="5" maxlength="5" oninput="this.value = this.value.replace(/[^0-9]/g, '')" value="<?php echo htmlspecialchars($application['poskod_majikan_pasangan'] ?? ''); ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bandar Majikan Pasangan</label>
                    <input type="text" id="bandar_majikan_pasangan" name="bandar_majikan_pasangan" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" value="<?php echo htmlspecialchars($application['bandar_majikan_pasangan'] ?? ''); ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Negeri Majikan Pasangan</label>
                    <select id="negeri_majikan_pasangan" name="negeri_majikan_pasangan" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">PILIH NEGERI</option>
                        <?php foreach (($malaysian_states ?? []) as $state): ?>
                            <option value="<?php echo htmlspecialchars($state); ?>" <?php echo (strtoupper($application['negeri_majikan_pasangan'] ?? '') === strtoupper($state)) ? 'selected' : ''; ?>><?php echo htmlspecialchars($state); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        </div>
    </div>
</div>
</div>



<?php $alamat_sama = $application['alamat_surat_sama'] ?? ''; ?>
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
    <div class="p-6 space-y-6">
        <div class="border border-gray-200 rounded-md p-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Alamat Tetap <span class="required">*</span></label>
            <input type="text" id="alamat_tetap" name="alamat_tetap" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" required value="<?php echo htmlspecialchars($application['alamat_tetap'] ?? ''); ?>">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Poskod Tetap <span class="required">*</span></label>
                    <input type="text" id="poskod_tetap" name="poskod_tetap" class="w-full px-3 py-2 border border-gray-300 rounded-md" pattern="[0-9]{5}" minlength="5" maxlength="5" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required value="<?php echo htmlspecialchars($application['poskod_tetap'] ?? ''); ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bandar Tetap <span class="required">*</span></label>
                    <input type="text" id="bandar_tetap" name="bandar_tetap" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" required value="<?php echo htmlspecialchars($application['bandar_tetap'] ?? ''); ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Negeri Tetap <span class="required">*</span></label>
                    <select id="negeri_tetap" name="negeri_tetap" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        <option value="">PILIH NEGERI</option>
                        <?php foreach (($malaysian_states ?? []) as $state): ?>
                            <option value="<?php echo htmlspecialchars($state); ?>" <?php echo (strtoupper($application['negeri_tetap'] ?? '') === strtoupper($state)) ? 'selected' : ''; ?>><?php echo htmlspecialchars($state); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="py-2">
            <label class="inline-flex items-center">
                <input type="checkbox" name="alamat_surat_sama" id="alamat_surat_sama" value="1" class="h-4 w-4 text-blue-600" <?php echo ($alamat_sama==='1' || strtoupper($alamat_sama)==='YA') ? 'checked' : ''; ?>>
                <span class="ml-2">Sama seperti alamat tetap</span>
            </label>
        </div>

        <div id="correspondence-fields" class="border border-gray-200 rounded-md p-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Alamat Surat-Menyurat <span class="required">*</span></label>
            <input type="text" id="alamat_surat" name="alamat_surat" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" required value="<?php echo htmlspecialchars($application['alamat_surat'] ?? ''); ?>">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Poskod Alamat Surat-Menyurat <span class="required">*</span></label>
                    <input type="text" id="poskod_surat" name="poskod_surat" class="w-full px-3 py-2 border border-gray-300 rounded-md" pattern="[0-9]{5}" minlength="5" maxlength="5" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required value="<?php echo htmlspecialchars($application['poskod_surat'] ?? ''); ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bandar Alamat Surat-Menyurat <span class="required">*</span></label>
                    <input type="text" id="bandar_surat" name="bandar_surat" class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase-input" required value="<?php echo htmlspecialchars($application['bandar_surat'] ?? ''); ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Negeri Alamat Surat-Menyurat <span class="required">*</span></label>
                    <select id="negeri_surat" name="negeri_surat" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        <option value="">PILIH NEGERI</option>
                        <?php foreach (($malaysian_states ?? []) as $state): ?>
                            <option value="<?php echo htmlspecialchars($state); ?>" <?php echo (strtoupper($application['negeri_surat'] ?? '')===strtoupper($state)) ? 'selected' : ''; ?>><?php echo htmlspecialchars($state); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
</div>
</div>

<!-- Alamat Tetap container merged above into unified "Alamat" section -->
<script>
// Auto-copy Alamat Tetap ke Alamat Surat-Menyurat bila checkbox ditanda
(function() {
  const samaChk = document.querySelector('input[name="alamat_surat_sama"]');
  const fieldsTetap = {
    alamat: document.querySelector('input[name="alamat_tetap"]'),
    poskod: document.querySelector('input[name="poskod_tetap"]'),
    bandar: document.querySelector('input[name="bandar_tetap"]'),
    negeri: document.querySelector('select[name="negeri_tetap"]')
  };
  const fieldsSurat = {
    alamat: document.querySelector('input[name="alamat_surat"]'),
    poskod: document.querySelector('input[name="poskod_surat"]'),
    bandar: document.querySelector('input[name="bandar_surat"]'),
    negeri: document.querySelector('select[name="negeri_surat"]')
  };

  function syncSurat() {
    if (!fieldsTetap.alamat || !fieldsSurat.alamat) return;
    fieldsSurat.alamat.value = fieldsTetap.alamat.value;
    fieldsSurat.poskod.value = fieldsTetap.poskod.value;
    fieldsSurat.bandar.value = fieldsTetap.bandar.value;
    fieldsSurat.negeri.value = fieldsTetap.negeri.value;
  }

  function setSuratDisabled(disabled) {
    Object.values(fieldsSurat).forEach(function(el) { if (el) el.disabled = disabled; });
  }

  function handleCheckboxChange() {
    if (!samaChk) return;
    if (samaChk.checked) {
      syncSurat();
      setSuratDisabled(true);
    } else {
      setSuratDisabled(false);
    }
  }

  if (samaChk) {
    samaChk.addEventListener('change', handleCheckboxChange);
  }
  ['input','change'].forEach(function(evt) {
    Object.values(fieldsTetap).forEach(function(el) {
      if (el) el.addEventListener(evt, function() { if (samaChk && samaChk.checked) syncSurat(); });
    });
  });

  // Initialize on load
  handleCheckboxChange();
  
  // Ensure correspondence address is populated before form submission
  const form = document.getElementById('applicationFormFull');
  if (form) {
    form.addEventListener('submit', function(e) {
      if (samaChk && samaChk.checked) {
        // Re-enable fields temporarily to ensure they are submitted
        setSuratDisabled(false);
        syncSurat();
        // Let the form submit with the copied values
      }
    });
  }
  // Tunjuk/sembunyi Maklumat Pasangan bergantung pada Taraf Perkahwinan
  function handleMaritalStatusChange() {
    const statusSel = document.getElementById('taraf_perkahwinan');
    const pasanganField = document.getElementById('maklumat_pasangan_field');
    if (!statusSel || !pasanganField) return;
    const isMarried = statusSel.value === 'Berkahwin' || statusSel.value === 'BERKAHWIN';
    pasanganField.style.display = isMarried ? 'block' : 'none';
    // Toggle required untuk beberapa medan utama pasangan
    const requiredFields = ['nama_pasangan', 'telefon_pasangan'];
    requiredFields.forEach(function(id){
      const el = document.getElementById(id);
      if (el) el.required = isMarried;
    });
  }

  const maritalSel = document.getElementById('taraf_perkahwinan');
  if (maritalSel) {
    maritalSel.addEventListener('change', handleMaritalStatusChange);
    // Init pada load halaman
    handleMaritalStatusChange();
  }
})();
</script>

<?php 
// Handle license data from multiple possible sources and formats
$lesen_memandu_checked = [];
if (!empty($application['lesen_memandu'])) {
    if (is_string($application['lesen_memandu'])) {
        // Try JSON first
        $json_decoded = json_decode($application['lesen_memandu'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json_decoded)) {
            $lesen_memandu_checked = $json_decoded;
        } else {
            // Try comma-separated
            $lesen_memandu_checked = array_filter(array_map('trim', explode(',', $application['lesen_memandu'])));
        }
    } elseif (is_array($application['lesen_memandu'])) {
        $lesen_memandu_checked = $application['lesen_memandu'];
    }
} elseif (!empty($application['lesen_memandu_set'])) {
    $lesen_memandu_checked = array_filter(array_map('trim', explode(',', $application['lesen_memandu_set'])));
}
?>
<?php $lesen_details = [
    'A' => 'Motosikal (kelas lama)',
    'B' => 'Motosikal >250cc',
    'B1' => 'Motosikal ≤500cc',
    'B2' => 'Motosikal ≤250cc',
    'C' => 'Traktor',
    'D' => 'Kereta',
    'E' => 'Lori/Treler',
    'E1' => 'Treler ringan',
    'E2' => 'Treler berat',
    'F' => 'Jentera pertanian',
    'G' => 'Kenderaan gandar khas',
    'H' => 'Kren',
    'I' => 'Forklift',
    'Tiada' => 'Tiada lesen'
]; ?>
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
    <div class="px-6 pt-6 pb-3 space-y-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Lesen Memandu (pilih yang berkenaan)</label>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach (array_keys($lesen_details) as $lc): ?>
                    <div class="inline-flex items-start space-x-2">
                        <input type="checkbox" name="lesen_memandu[]" value="<?php echo htmlspecialchars($lc); ?>" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" <?php echo in_array($lc, $lesen_memandu_checked) ? 'checked' : ''; ?>>
                        <div>
                            <span class="text-sm text-gray-700 font-medium"><?php echo htmlspecialchars($lc); ?></span>
                            <span class="ml-1 text-xs text-gray-500">— <?php echo htmlspecialchars($lesen_details[$lc]); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tarikh Tamat Lesen</label>
                <input type="date" name="tarikh_tamat_lesen" class="w-full px-3 py-2 border border-gray-300 rounded-md" value="<?php echo htmlspecialchars($application['tarikh_tamat_lesen'] ?? ''); ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Salinan Lesen Memandu</label>
                <input type="file" name="salinan_lesen_memandu" class="w-full px-3 py-2 border border-gray-300 rounded-md" accept=".jpg,.jpeg,.png,.gif,.pdf" data-max-size="5" data-allowed-types="image/jpeg,image/jpg,image/png,image/gif,application/pdf" data-field-label="Salinan Lesen Memandu" onchange="validateFileSize(this, 5)">
                <?php 
                $license_file = $application['salinan_lesen_memandu'] ?? $application['salinan_lesen_memandu_path'] ?? '';
                if (!empty($license_file)): ?>
                    <p class="text-xs text-green-700 mt-1">Fail sedia ada: <?php echo htmlspecialchars(basename($license_file)); ?></p>
                    <input type="hidden" name="existing_salinan_lesen_memandu" value="<?php echo htmlspecialchars($license_file); ?>">
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Seksyen muat naik dokumen pengenalan dipindahkan ke 01-uploads.php -->
<script>
// Kawal pilihan Lesen Memandu: apabila "Tiada" dipilih, matikan pilihan lain
(function(){
  var checkboxes = document.querySelectorAll('input[name="lesen_memandu[]"]');
  if (!checkboxes || checkboxes.length === 0) return;
  var noneCb = null;
  checkboxes.forEach(function(cb){ if (cb.value === 'Tiada') noneCb = cb; });

  function setDisabledStateForOthers(disabled) {
    checkboxes.forEach(function(cb){
      if (cb.value !== 'Tiada') {
        cb.disabled = disabled;
        var parent = cb.closest('.inline-flex');
        if (parent) {
          parent.classList.toggle('opacity-50', disabled);
          parent.classList.toggle('cursor-not-allowed', disabled);
        }
      }
    });
  }

  function updateLicenseOptions() {
    var anyNonNoneChecked = Array.prototype.some.call(checkboxes, function(cb){ return cb.value !== 'Tiada' && cb.checked; });
    var noneChecked = noneCb && noneCb.checked;

    if (noneChecked) {
      // Jika "Tiada" dipilih: nyahpilih dan matikan semua lain
      checkboxes.forEach(function(cb){
        if (cb.value !== 'Tiada') cb.checked = false;
      });
      setDisabledStateForOthers(true);
      if (noneCb) noneCb.disabled = false;
    } else {
      // Jika tidak memilih "Tiada": hidupkan semula yang lain
      setDisabledStateForOthers(false);
    }

    if (anyNonNoneChecked) {
      // Jika mana-mana selain "Tiada" dipilih: nyahpilih dan matikan "Tiada"
      if (noneCb) { noneCb.checked = false; noneCb.disabled = true; }
    } else {
      if (noneCb) noneCb.disabled = false;
    }
  }

  checkboxes.forEach(function(cb){ cb.addEventListener('change', updateLicenseOptions); });
  // Initialize on load (untuk pra-isi borang)
  updateLicenseOptions();
})();
</script>