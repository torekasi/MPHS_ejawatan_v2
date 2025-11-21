<?php
/**
 * Work Experience Section Renderer for Preview
 */

function renderWorkExperienceSection($work_rows, $app) {
    $h = function($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); };
    
    function formatWorkExperienceDate($dari_bulan, $dari_tahun, $hingga_bulan, $hingga_tahun) {
        $dari = '';
        if (!empty($dari_bulan) || !empty($dari_tahun)) {
            $dari = trim($dari_bulan . ' ' . $dari_tahun);
        }
        
        $hingga = '';
        if (!empty($hingga_bulan) || !empty($hingga_tahun)) {
            $hingga = trim($hingga_bulan . ' ' . $hingga_tahun);
        }
        
        return $dari . ($dari && $hingga ? ' - ' : '') . $hingga;
    }
    
    // Format gaji with RM prefix
    foreach ($work_rows as &$w) {
        if (isset($w['gaji']) && $w['gaji'] !== '') {
            $num = is_numeric($w['gaji']) ? (float)$w['gaji'] : $w['gaji'];
            if (is_numeric($num)) {
                $w['gaji'] = 'RM ' . number_format((float)$num, 2);
            } else {
                // If already text, ensure it has RM prefix once
                $w['gaji'] = (stripos($w['gaji'], 'RM') === 0) ? $w['gaji'] : ('RM ' . $w['gaji']);
            }
        }
    }
    unset($w);
    
    ob_start();
    ?>
    <!-- Pengalaman Bekerja -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-6">
        <div class="bg-orange-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Pengalaman Bekerja</h2>
        </div>
        <div class="p-6">
            <div class="mb-4">
                <span class="text-gray-500">Ada Pengalaman Kerja:</span>
                <span class="font-medium text-gray-900"><?php echo $h($app['ada_pengalaman_kerja'] ?? 'Tidak'); ?></span>
            </div>
            
            <?php if (!empty($work_rows)): ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Syarikat</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Jawatan</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Tempoh</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Gaji</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Alasan Berhenti</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($work_rows as $work): ?>
                            <tr>
                                <td class="px-4 py-2 text-sm"><?php echo $h($work['syarikat']); ?></td>
                                <td class="px-4 py-2 text-sm"><?php echo $h($work['jawatan']); ?></td>
                                <td class="px-4 py-2 text-sm"><?php echo $h(formatWorkExperienceDate($work['dari_bulan'], $work['dari_tahun'], $work['hingga_bulan'], $work['hingga_tahun'])); ?></td>
                                <td class="px-4 py-2 text-sm"><?php echo $h($work['gaji']); ?></td>
                                <td class="px-4 py-2 text-sm"><?php echo $h($work['alasan']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-gray-500 italic">Tiada maklumat pengalaman kerja</p>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>
