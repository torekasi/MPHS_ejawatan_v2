<?php
/**
 * Education Section Renderer for Preview
 */

function renderEducationSection($education_rows, $spm_rows) {
    $h = function($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); };
    
    function parseSubjectsHtml($subjek_lain, $gred_subjek_lain) {
        $h = function($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); };
        
        $subjects = [];
        $grades = [];
        
        if (is_string($subjek_lain)) {
            $tmp = json_decode($subjek_lain, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) {
                $subjects = $tmp;
            } else {
                $subjects = array_filter(array_map('trim', explode(',', $subjek_lain)));
            }
        } elseif (is_array($subjek_lain)) {
            $subjects = $subjek_lain;
        }

        if (is_string($gred_subjek_lain)) {
            $tmp = json_decode($gred_subjek_lain, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) {
                $grades = $tmp;
            } else {
                $grades = array_filter(array_map('trim', explode(',', $gred_subjek_lain)));
            }
        } elseif (is_array($gred_subjek_lain)) {
            $grades = $gred_subjek_lain;
        }

        if (empty($subjects)) return '<span class="text-gray-500">Tiada subjek lain</span>';
        $out = '<ul class="list-disc pl-5">';
        foreach ($subjects as $i => $s) {
            $g = $grades[$i] ?? '';
            $out .= '<li>' . $h($s) . ($g !== '' ? ' - <strong>' . $h($g) . '</strong>' : '') . '</li>';
        }
        $out .= '</ul>';
        return $out;
    }
    
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
    <!-- Pendidikan -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-6">
        <div class="bg-green-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Pendidikan</h2>
        </div>
        <div class="p-6">
            <?php 
            // Handle different column name variations
            $edu_columns = [];
            if (!empty($education_rows)) {
                $first_row = $education_rows[0];
                // Map actual column names to expected names
                $edu_columns = ['institusi' => 'institusi'];
                if (isset($first_row['nama_institusi'])) {
                    foreach ($education_rows as &$row) {
                        $row['institusi'] = $row['nama_institusi'] ?? $row['institusi'] ?? '';
                    }
                    unset($row);
                }
                if (isset($first_row['pangkat_gred_cgpa'])) {
                    foreach ($education_rows as &$row) {
                        $row['gred'] = $row['pangkat_gred_cgpa'] ?? $row['gred'] ?? '';
                    }
                    unset($row);
                }
            }
            
            $edu_display_columns = ['institusi','dari_tahun','hingga_tahun','kelayakan','gred'];
            echo renderTable($education_rows, 'Tiada maklumat pendidikan', $edu_display_columns, true);
            ?>
            <div class="mt-6">
                <h3 class="font-medium mb-2">Kelulusan SPM/SPV</h3>
                <?php 
                // Build display rows with parsed Subjek Lain + Gred list
                $spm_rows_display = [];
                foreach ($spm_rows as $row) {
                    $row['subjek_lain_diparse'] = parseSubjectsHtml($row['subjek_lain'] ?? '', $row['gred_subjek_lain'] ?? '');
                    $spm_rows_display[] = $row;
                }
                $spm_columns = ['tahun','gred_keseluruhan','angka_giliran','bahasa_malaysia','bahasa_inggeris','matematik','sejarah','subjek_lain_diparse'];
                echo renderTable($spm_rows_display, 'Tiada maklumat SPM', $spm_columns, false);
                ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>
