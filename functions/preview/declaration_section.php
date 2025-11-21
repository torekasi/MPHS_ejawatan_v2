<?php
/**
 * Declaration Section Renderer for Preview
 */

function renderDeclarationSection($app, $ref_rows) {
    $h = function($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); };
    
    function renderTable($data, $emptyMessage = 'Tiada maklumat', $columns = [], $escape = true) {
        $h = function($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); };
        
        if (!is_array($data) || empty($data)) {
            return '<p class="text-gray-500">' . $h($emptyMessage) . '</p>';
        }
        
        if (empty($columns)) {
            $first = $data[0];
            $columns = array_keys($first);
        }
        
        $out = '<div class="overflow-x-auto"><table class="min-w-full border border-gray-200 text-sm table-fixed"><thead><tr>';
        foreach ($columns as $c) {
            $out .= '<th class="px-3 py-2 text-left bg-gray-50 border-b border-gray-200">' . $h(ucwords(str_replace('_',' ', $c))) . '</th>';
        }
        $out .= '</tr></thead><tbody class="divide-y divide-gray-100">';
        foreach ($data as $row) {
            $out .= '<tr>'; 
            foreach ($columns as $c) {
                $cell = $row[$c] ?? '';
                if ($escape) {
                    $cell = $h($cell);
                }
                $out .= '<td class="px-3 py-2 align-top">' . $cell . '</td>';
            }
            $out .= '</tr>';
        }
        $out .= '</tbody></table></div>';
        return $out;
    }
    
    ob_start();
    ?>
    <!-- Pengisytiharan Diri -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-6">
        <div class="bg-red-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Pengisytiharan Diri</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <?php 
                $decls = [
                    ['label' => 'Pekerja Perkhidmatan Awam', 'key' => 'pekerja_perkhidmatan_awam', 'nyatakan' => 'pekerja_perkhidmatan_awam_nyatakan'],
                    ['label' => 'Pertalian Kakitangan', 'key' => 'pertalian_kakitangan', 'nyatakan' => 'pertalian_kakitangan_nyatakan'],
                    ['label' => 'Pernah Bekerja di MPHS', 'key' => 'pernah_bekerja_mphs', 'nyatakan' => 'pernah_bekerja_mphs_nyatakan'],
                    ['label' => 'Tindakan Tatatertib', 'key' => 'tindakan_tatatertib', 'nyatakan' => 'tindakan_tatatertib_nyatakan'],
                    ['label' => 'Kesalahan Undang-undang', 'key' => 'kesalahan_undangundang', 'nyatakan' => 'kesalahan_undangundang_nyatakan'],
                    ['label' => 'Muflis', 'key' => 'muflis', 'nyatakan' => 'muflis_nyatakan'],
                ];
                foreach ($decls as $d): 
                    $v = $app[$d['key']] ?? '';
                    $cls = ($v === 'YA') ? 'text-red-600 font-semibold' : 'text-gray-900';
                ?>
                <div>
                    <span class="text-gray-500"><?php echo $h($d['label']); ?></span>
                    <p class="<?php echo $cls; ?>"><?php echo $h($v); ?></p>
                    <?php if ($v === 'YA' && !empty($app[$d['nyatakan']] ?? '')): ?>
                        <p class="text-gray-600 mt-1"><?php echo $h($app[$d['nyatakan']] ?? ''); ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Rujukan -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-6">
        <div class="bg-yellow-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Rujukan</h2>
        </div>
        <div class="p-6">
            <?php echo renderTable($ref_rows, 'Tiada maklumat rujukan', ['nama','telefon','tempoh']); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>
