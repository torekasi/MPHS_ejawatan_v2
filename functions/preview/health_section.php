<?php
/**
 * Health/Physical Section Renderer for Preview
 */

function renderHealthSection($app, $health) {
    $h = function($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); };
    
    $health_questions = [
        ['label' => 'Darah Tinggi', 'key' => 'darah_tinggi'],
        ['label' => 'Kencing Manis', 'key' => 'kencing_manis'],
        ['label' => 'Penyakit Buah Pinggang', 'key' => 'penyakit_buah_pinggang'],
        ['label' => 'Penyakit Jantung', 'key' => 'penyakit_jantung'],
        ['label' => 'Batuk Kering/Tibi', 'key' => 'batuk_kering_tibi'],
        ['label' => 'Kanser', 'key' => 'kanser'],
        ['label' => 'AIDS', 'key' => 'aids'],
        ['label' => 'Penagih Dadah', 'key' => 'penagih_dadah'],
        ['label' => 'Perokok', 'key' => 'perokok']
    ];
    
    ob_start();
    ?>
    <!-- Kesihatan/Fizikal -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-6">
        <div class="bg-indigo-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Kesihatan/Fizikal</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                <!-- Health Conditions -->
                <div class="space-y-3">
                    <h3 class="font-medium text-gray-900 mb-3">Keadaan Kesihatan</h3>
                    <?php foreach ($health_questions as $hq): ?>
                        <?php
                        $v = $health[$hq['key']] ?? $app[$hq['key']] ?? '';
                        $display_value = ($v === 'YA' || $v === 'TIDAK') ? $v : 'Tidak Dinyatakan';
                        ?>
                        <div class="flex justify-between">
                            <span class="text-gray-500"><?php echo $h($hq['label']); ?>:</span>
                            <span class="font-medium text-gray-900"><?php echo $h($display_value); ?></span>
                        </div>
                    <?php endforeach; ?>

                    <?php 
                    $penyakit_lain = $health['penyakit_lain'] ?? $app['penyakit_lain'] ?? '';
                    $penyakit_lain_nyatakan = $health['penyakit_lain_nyatakan'] ?? $app['penyakit_lain_nyatakan'] ?? '';
                    ?>
                    <?php if (!empty($penyakit_lain) && strtoupper($penyakit_lain) === 'YA'): ?>
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Penyakit Lain:</span>
                            <span class="font-medium text-gray-900">YA</span>
                        </div>
                        <?php if (!empty($penyakit_lain_nyatakan)): ?>
                        <div class="mt-2 text-sm">
                            <span class="text-gray-500">Nyatakan:</span>
                            <span class="text-gray-900"><?php echo $h($penyakit_lain_nyatakan); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Physical Information -->
                <div class="space-y-3">
                    <h3 class="font-medium text-gray-900 mb-3">Maklumat Fizikal</h3>

                    <!-- OKU Status -->
                    <div class="flex justify-between">
                        <span class="text-gray-500">Pemegang Kad OKU:</span>
                        <span class="font-medium text-gray-900">
                            <?php
                            $pemegang = $health['pemegang_kad_oku'] ?? $app['pemegang_kad_oku'] ?? '';
                            echo $pemegang ? $h(strtoupper($pemegang)) : 'TIDAK';
                            ?>
                        </span>
                    </div>

                    <?php
                    $jenisOkuRaw = $health['jenis_oku'] ?? $app['jenis_oku'] ?? '';
                    $salinanOku = $health['salinan_kad_oku'] ?? $app['salinan_kad_oku_path'] ?? $app['salinan_kad_oku'] ?? '';
                    ?>
                    <?php if (!empty($pemegang) && strtoupper($pemegang) === 'YA' && !empty($jenisOkuRaw)): ?>
                    <div class="mt-2 text-sm">
                        <span class="text-gray-500">Jenis OKU:</span>
                        <div class="mt-1">
                            <?php
                            $jenis_oku = is_string($jenisOkuRaw) ? json_decode($jenisOkuRaw, true) : $jenisOkuRaw;
                            if (is_array($jenis_oku)) {
                                foreach ($jenis_oku as $oku_type) {
                                    echo '<span class="inline-block bg-red-100 text-red-800 text-xs px-2 py-1 rounded mr-1 mb-1">' . $h(strtoupper($oku_type)) . '</span>';
                                }
                            }
                            ?>
                        </div>
                        <?php if (!empty($salinanOku)): ?>
                        <div class="mt-2">
                            <span class="text-gray-500">Salinan Kad OKU:</span>
                            <?php 
                            $buildAppFileUrl = function($filename, $app) {
                                if (!$filename) return '';
                                if (preg_match('/^https?:\/\//i', $filename)) return $filename;
                                if (strpos($filename, 'uploads/applications/') === 0) return '/' . $filename;
                                $year = date('Y');
                                $applicationReference = $app['application_reference'] ?? '';
                                $file = basename((string)$filename);
                                return '/uploads/applications/' . $year . '/' . rawurlencode((string)$applicationReference) . '/' . rawurlencode($file);
                            };
                            $oku_url = $buildAppFileUrl($salinanOku, $app); 
                            ?>
                            <a href="<?php echo $h($oku_url); ?>" target="_blank" class="text-blue-600 hover:underline ml-2">
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                Lihat Salinan Kad
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Eyesight -->
                    <div class="flex justify-between">
                        <span class="text-gray-500">Memakai Cermin Mata:</span>
                        <span class="font-medium text-gray-900">
                            <?php
                            $cermin = $health['memakai_cermin_mata'] ?? $app['memakai_cermin_mata'] ?? '';
                            echo $cermin ? $h(strtoupper($cermin)) : 'TIDAK';
                            ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($cermin) && strtoupper($cermin) === 'YA'): ?>
                    <div class="mt-2 text-sm">
                        <span class="text-gray-500">Jenis Rabun:</span>
                        <span class="text-gray-900"><?php echo $h($health['jenis_rabun'] ?? $app['jenis_rabun'] ?? ''); ?></span>
                    </div>
                    <?php endif; ?>

                    <!-- Physical Measurements -->
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <?php
                        $tinggi = $health['tinggi_cm'] ?? $app['tinggi_cm'] ?? null;
                        $berat = $health['berat_kg'] ?? $app['berat_kg'] ?? null;
                        $tinggi_display = ($tinggi !== null && $tinggi !== '') ? (is_numeric($tinggi) ? $tinggi . ' cm' : $h($tinggi)) : 'Tidak Dinyatakan';
                        $berat_display = ($berat !== null && $berat !== '') ? (is_numeric($berat) ? $berat . ' kg' : $h($berat)) : 'Tidak Dinyatakan';
                        ?>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Tinggi:</span>
                            <span class="font-medium text-gray-900"><?php echo $tinggi_display; ?></span>
                        </div>
                        <div class="flex justify-between mt-2">
                            <span class="text-gray-500">Berat:</span>
                            <span class="font-medium text-gray-900"><?php echo $berat_display; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>
