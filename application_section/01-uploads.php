<?php
/**
 * @FileID: app_section_uploads_001
 * @Module: ApplicationSectionUploads
 * @Author: Nefi
 * @LastModified: 2025-11-16T12:15:00Z
 * @SecurityTag: validated
 */
if (!defined('APP_SECURE')) { http_response_code(403); exit; }
?>
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
    <div class="section-title">Muat Naik Dokumen</div>
    <div class="p-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Gambar Passport <span class="required">*</span></label>
                <?php $passportFile = $application['gambar_passport_path'] ?? $application['gambar_passport'] ?? null; ?>
                <input type="file" name="gambar_passport" class="w-full px-3 py-2 border border-gray-300 rounded-md" accept=".jpg,.jpeg,.png,.gif" data-max-size="5" data-allowed-types="image/jpeg,image/jpg,image/png,image/gif" data-field-label="Gambar Passport" onchange="validateFileSize(this, 5)" <?php echo empty($passportFile) ? 'required' : ''; ?>>
                <div id="preview_gambar_passport" class="mt-2"></div>
                <?php if (!empty($passportFile)): ?>
                    <p class="text-xs text-green-700 mt-1">Fail dimuat naik: <?php echo htmlspecialchars(basename($passportFile)); ?></p>
                    <input type="hidden" name="gambar_passport_path" value="<?php echo htmlspecialchars($passportFile); ?>">
                    <?php 
                        $pf = (string)$passportFile;
                        $purl = preg_match('/^https?:\/\//i',$pf) ? $pf : ('/' . ltrim($pf,'/'));
                        $pext = strtolower(pathinfo($pf, PATHINFO_EXTENSION));
                    ?>
                    <div class="mt-2 border border-gray-200 rounded-md bg-white flex items-center justify-center" style="height:200px; overflow:hidden;">
                        <?php if (in_array($pext,['jpg','jpeg','png','gif','webp'])): ?>
                            <img src="<?php echo htmlspecialchars($purl); ?>" alt="Pratonton Gambar Passport" style="max-height:200px; max-width:100%; object-fit:contain; display:block;">
                        <?php elseif ($pext==='pdf'): ?>
                            <iframe src="<?php echo htmlspecialchars($purl); ?>" title="Pratonton PDF" style="height:200px; width:100%;"></iframe>
                        <?php else: ?>
                            <a class="text-blue-600 underline" href="<?php echo htmlspecialchars($purl); ?>" target="_blank" rel="noopener">Lihat fail</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Salinan IC <span class="required">*</span></label>
                <?php $icFile = $application['salinan_ic_path'] ?? $application['salinan_ic'] ?? null; ?>
                <input type="file" name="salinan_ic" class="w-full px-3 py-2 border border-gray-300 rounded-md" accept=".jpg,.jpeg,.png,.gif,.pdf" data-max-size="5" data-allowed-types="image/jpeg,image/jpg,image/png,image/gif,application/pdf" data-field-label="Salinan IC" onchange="validateFileSize(this, 5)" <?php echo empty($icFile) ? 'required' : ''; ?>>
                <div id="preview_salinan_ic" class="mt-2"></div>
                <?php if (!empty($icFile)): ?>
                    <p class="text-xs text-green-700 mt-1">Fail dimuat naik: <?php echo htmlspecialchars(basename($icFile)); ?></p>
                    <input type="hidden" name="salinan_ic_path" value="<?php echo htmlspecialchars($icFile); ?>">
                    <?php 
                        $if = (string)$icFile;
                        $iurl = preg_match('/^https?:\/\//i',$if) ? $if : ('/' . ltrim($if,'/'));
                        $iext = strtolower(pathinfo($if, PATHINFO_EXTENSION));
                    ?>
                    <div class="mt-2 border border-gray-200 rounded-md bg-white flex items-center justify-center" style="height:200px; overflow:hidden;">
                        <?php if (in_array($iext,['jpg','jpeg','png','gif','webp'])): ?>
                            <img src="<?php echo htmlspecialchars($iurl); ?>" alt="Pratonton Salinan IC" style="max-height:200px; max-width:100%; object-fit:contain; display:block;">
                        <?php elseif ($iext==='pdf'): ?>
                            <iframe src="<?php echo htmlspecialchars($iurl); ?>" title="Pratonton PDF" style="height:200px; width:100%;"></iframe>
                        <?php else: ?>
                            <a class="text-blue-600 underline" href="<?php echo htmlspecialchars($iurl); ?>" target="_blank" rel="noopener">Lihat fail</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Salinan Surat Beranak <span class="required">*</span></label>
                <?php $beranakFile = $application['salinan_surat_beranak_path'] ?? $application['salinan_surat_beranak'] ?? null; ?>
                <input type="file" name="salinan_surat_beranak" class="w-full px-3 py-2 border border-gray-300 rounded-md" accept=".jpg,.jpeg,.png,.gif,.pdf" data-max-size="5" data-allowed-types="image/jpeg,image/jpg,image/png,image/gif,application/pdf" data-field_label="Salinan Surat Beranak" onchange="validateFileSize(this, 5)" <?php echo empty($beranakFile) ? 'required' : ''; ?>>
                <div id="preview_salinan_surat_beranak" class="mt-2"></div>
                <?php if (!empty($beranakFile)): ?>
                    <p class="text-xs text-green-700 mt-1">Fail dimuat naik: <?php echo htmlspecialchars(basename($beranakFile)); ?></p>
                    <input type="hidden" name="salinan_surat_beranak_path" value="<?php echo htmlspecialchars($beranakFile); ?>">
                    <?php 
                        $bf = (string)$beranakFile;
                        $burl = preg_match('/^https?:\/\//i',$bf) ? $bf : ('/' . ltrim($bf,'/'));
                        $bext = strtolower(pathinfo($bf, PATHINFO_EXTENSION));
                    ?>
                    <div class="mt-2 border border-gray-200 rounded-md bg-white flex items-center justify-center" style="height:200px; overflow:hidden;">
                        <?php if (in_array($bext,['jpg','jpeg','png','gif','webp'])): ?>
                            <img src="<?php echo htmlspecialchars($burl); ?>" alt="Pratonton Salinan Surat Beranak" style="max-height:200px; max-width:100%; object-fit:contain; display:block;">
                        <?php elseif ($bext==='pdf'): ?>
                            <iframe src="<?php echo htmlspecialchars($burl); ?>" title="Pratonton PDF" style="height:200px; width:100%;"></iframe>
                        <?php else: ?>
                            <a class="text-blue-600 underline" href="<?php echo htmlspecialchars($burl); ?>" target="_blank" rel="noopener">Lihat fail</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Nota: Salinan Lesen Memandu dipindahkan ke seksyen Maklumat Lesen Memandu dalam 02-personal-info.php -->

        <script>
        (function() {
          var objectURLs = {};
          function setPreview(name, file) {
            var container = document.getElementById('preview_' + name);
            if (!container) return;
            // Cleanup previous
            container.innerHTML = '';
            if (objectURLs[name]) { URL.revokeObjectURL(objectURLs[name]); objectURLs[name] = null; }
            if (!file) return;
            // Wrapper to ensure consistent height
            var wrap = document.createElement('div');
            wrap.style.height = '200px';
            wrap.style.width = '100%';
            wrap.style.overflow = 'hidden';
            wrap.className = 'border border-gray-200 rounded-md bg-white flex items-center justify-center';
            container.appendChild(wrap);

            var isImage = file.type && file.type.indexOf('image/') === 0;
            if (isImage) {
              var reader = new FileReader();
              reader.onload = function(e) {
                var img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'Pratonton imej';
                img.style.maxHeight = '200px';
                img.style.maxWidth = '100%';
                img.style.objectFit = 'contain';
                img.style.display = 'block';
                wrap.appendChild(img);
              };
              reader.onerror = function() {
                var msg = document.createElement('p');
                msg.className = 'text-xs text-red-600';
                msg.textContent = 'Gagal memuat pratonton imej.';
                wrap.appendChild(msg);
              };
              reader.readAsDataURL(file);
            } else if (file.type === 'application/pdf') {
              var url = URL.createObjectURL(file);
              objectURLs[name] = url;
              var iframe = document.createElement('iframe');
              iframe.src = url;
              iframe.style.height = '200px';
              iframe.style.width = '100%';
              iframe.className = 'bg-white';
              iframe.setAttribute('title','Pratonton PDF');
              wrap.appendChild(iframe);
            } else {
              var msg2 = document.createElement('p');
              msg2.className = 'text-xs text-gray-600';
              msg2.textContent = 'Tidak dapat pratonton format ini.';
              wrap.appendChild(msg2);
            }
          }
          var inputs = [
            'gambar_passport',
            'salinan_ic',
            'salinan_surat_beranak'
          ];
          inputs.forEach(function(name) {
            var input = document.querySelector('input[name="' + name + '"]');
            if (!input) return;
            input.addEventListener('change', function() {
              var file = this.files && this.files[0];
              setPreview(name, file);
            });
          });
        })();
        </script>
    </div>
</div>