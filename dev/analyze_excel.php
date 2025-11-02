<?php
/**
 * Analyze example.xls structure
 * Shows borders, formulas, and formatting
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$excelFile = __DIR__ . '/example.xls';

if (!file_exists($excelFile)) {
    die("File not found: $excelFile\n");
}

echo "📊 Analyzing: example.xls\n";
echo str_repeat('=', 80) . "\n\n";

$spreadsheet = IOFactory::load($excelFile);
$sheet = $spreadsheet->getActiveSheet();

$highestRow = $sheet->getHighestRow();
$highestColumn = $sheet->getHighestColumn();
$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

echo "📐 Dimensions:\n";
echo "  Rows: $highestRow\n";
echo "  Columns: $highestColumn ($highestColumnIndex)\n\n";

// Analyze borders
echo "🔲 BORDER ANALYSIS:\n";
echo str_repeat('-', 80) . "\n";

$cellsWithBorders = [];
for ($row = 1; $row <= min(20, $highestRow); $row++) {
    for ($col = 1; $col <= min(15, $highestColumnIndex); $col++) {
        $cell = $sheet->getCellByColumnAndRow($col, $row);
        $value = $cell->getValue();
        
        if ($value !== null && $value !== '') {
            $style = $cell->getStyle();
            $borders = $style->getBorders();
            
            $hasBorder = false;
            $borderInfo = [];
            
            // Check all border types
            if ($borders->getTop()->getBorderStyle() !== \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE) {
                $borderInfo[] = 'T:' . $borders->getTop()->getBorderStyle();
                $hasBorder = true;
            }
            if ($borders->getBottom()->getBorderStyle() !== \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE) {
                $borderInfo[] = 'B:' . $borders->getBottom()->getBorderStyle();
                $hasBorder = true;
            }
            if ($borders->getLeft()->getBorderStyle() !== \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE) {
                $borderInfo[] = 'L:' . $borders->getLeft()->getBorderStyle();
                $hasBorder = true;
            }
            if ($borders->getRight()->getBorderStyle() !== \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE) {
                $borderInfo[] = 'R:' . $borders->getRight()->getBorderStyle();
                $hasBorder = true;
            }
            
            if ($hasBorder) {
                $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;
                $cellsWithBorders[] = [
                    'coord' => $cellCoord,
                    'value' => substr($value, 0, 20),
                    'borders' => implode(', ', $borderInfo)
                ];
            }
        }
    }
}

if (empty($cellsWithBorders)) {
    echo "  ❌ No borders found in first 20 rows\n";
} else {
    echo "  ✅ Found " . count($cellsWithBorders) . " cells with borders:\n";
    foreach (array_slice($cellsWithBorders, 0, 10) as $cellInfo) {
        echo "    {$cellInfo['coord']}: \"{$cellInfo['value']}\" [{$cellInfo['borders']}]\n";
    }
    if (count($cellsWithBorders) > 10) {
        echo "    ... and " . (count($cellsWithBorders) - 10) . " more\n";
    }
}

echo "\n";

// Analyze formulas
echo "📐 FORMULA ANALYSIS:\n";
echo str_repeat('-', 80) . "\n";

$cellsWithFormulas = [];
for ($row = 1; $row <= min(20, $highestRow); $row++) {
    for ($col = 1; $col <= min(15, $highestColumnIndex); $col++) {
        $cell = $sheet->getCellByColumnAndRow($col, $row);
        
        if ($cell->getDataType() === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA) {
            $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;
            $formula = $cell->getValue();
            $calculatedValue = $cell->getCalculatedValue();
            
            $cellsWithFormulas[] = [
                'coord' => $cellCoord,
                'formula' => $formula,
                'value' => $calculatedValue
            ];
        }
    }
}

if (empty($cellsWithFormulas)) {
    echo "  ❌ No formulas found in first 20 rows\n";
} else {
    echo "  ✅ Found " . count($cellsWithFormulas) . " cells with formulas:\n";
    foreach ($cellsWithFormulas as $cellInfo) {
        echo "    {$cellInfo['coord']}: {$cellInfo['formula']} = {$cellInfo['value']}\n";
    }
}

echo "\n";

// Show first few rows with values
echo "📋 FIRST 10 ROWS (with values):\n";
echo str_repeat('-', 80) . "\n";

for ($row = 1; $row <= min(10, $highestRow); $row++) {
    $rowData = [];
    for ($col = 1; $col <= min(12, $highestColumnIndex); $col++) {
        $cell = $sheet->getCellByColumnAndRow($col, $row);
        $value = $cell->getValue();
        
        if ($value !== null && $value !== '') {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $rowData[] = "$colLetter: \"$value\"";
        }
    }
    
    if (!empty($rowData)) {
        echo "  Row $row: " . implode(', ', $rowData) . "\n";
    }
}

echo "\n";
echo "✅ Analysis complete!\n";
