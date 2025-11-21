<?php
/**
 * Skills Section Renderer for Preview
 */

function renderSkillsSection($language_rows, $computer_rows, $bodies_rows, $extra_rows) {
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
    <!-- Kemahiran -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-6">
        <div class="bg-purple-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Kemahiran</h2>
        </div>
        <div class="p-6">
            <div class="mb-6">
                <h3 class="font-medium mb-2">Kemahiran Bahasa</h3>
                <?php echo renderTable($language_rows, 'Tiada maklumat kemahiran bahasa', ['bahasa', 'pertuturan', 'penulisan', 'gred_spm']); ?>
            </div>
            <div class="mb-6">
                <h3 class="font-medium mb-2">Kemahiran Komputer</h3>
                <?php echo renderTable($computer_rows, 'Tiada maklumat kemahiran komputer', ['nama_perisian', 'tahap_kemahiran']); ?>
            </div>
            <div class="mb-6">
                <h3 class="font-medium mb-2">Badan Profesional</h3>
                <?php echo renderTable($bodies_rows, 'Tiada maklumat badan profesional', ['nama_lembaga', 'sijil_diperoleh', 'no_ahli', 'tahun'], true); ?>
            </div>
            <div class="mb-2">
                <h3 class="font-medium mb-2">Kegiatan Luar</h3>
                <?php echo renderTable($extra_rows, 'Tiada maklumat kegiatan luar', ['sukan_persatuan_kelab', 'jawatan', 'peringkat', 'tahun'], true); ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>
