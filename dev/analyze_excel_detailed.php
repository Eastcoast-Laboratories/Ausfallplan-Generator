<?php
/**
 * Detailed Excel Analysis
 */
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;

$file = __DIR__ . '/example.xls';
if (!file_exists($file)) {
    die("File not found: $file\n");
}

echo "📊 DETAILED EXCEL ANALYSIS: example.xls\n";
echo str_repeat('=', 80) . "\n\n";

$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getActiveSheet();

$highestRow = $sheet->getHighestRow();
$highestColumn = $sheet->getHighestColumn();
$highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

echo "📐 DIMENSIONS:\n";
echo "  Rows: $highestRow\n";
echo "  Columns: $highestColumn ($highestColumnIndex)\n\n";

// Analyze first 20 rows in detail
echo "📋 CELL-BY-CELL ANALYSIS (First 20 rows, 15 columns):\n";
echo str_repeat('-', 80) . "\n";

for ($row = 1; $row <= min(20, $highestRow); $row++) {
    echo "\nROW $row:\n";
    
    for ($col = 1; $col <= min(15, $highestColumnIndex); $col++) {
        $cellCoord = Coordinate::stringFromColumnIndex($col) . $row;
        $cell = $sheet->getCell($cellCoord);
        $value = $cell->getValue();
        
        // Skip empty cells
        if ($value === null || $value === '') {
            continue;
        }
        
        $colLetter = Coordinate::stringFromColumnIndex($col);
        
        // Get cell info
        $dataType = $cell->getDataType();
        $isFormula = ($dataType === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA);
        $calculatedValue = $isFormula ? $cell->getCalculatedValue() : $value;
        
        // Get borders
        $style = $cell->getStyle();
        $borders = $style->getBorders();
        $borderInfo = [];
        
        if ($borders->getTop()->getBorderStyle() !== Border::BORDER_NONE) {
            $borderInfo[] = 'T';
        }
        if ($borders->getBottom()->getBorderStyle() !== Border::BORDER_NONE) {
            $borderInfo[] = 'B';
        }
        if ($borders->getLeft()->getBorderStyle() !== Border::BORDER_NONE) {
            $borderInfo[] = 'L';
        }
        if ($borders->getRight()->getBorderStyle() !== Border::BORDER_NONE) {
            $borderInfo[] = 'R';
        }
        
        $borderStr = empty($borderInfo) ? 'NO_BORDER' : implode('+', $borderInfo);
        
        // Get background color
        $fill = $style->getFill();
        $bgColor = $fill->getStartColor()->getRGB();
        $hasBg = ($bgColor !== 'FFFFFF' && $bgColor !== null);
        
        echo "  $cellCoord ($colLetter$row): ";
        
        if ($isFormula) {
            echo "FORMULA: $value = $calculatedValue";
        } else {
            echo "VALUE: \"$value\"";
        }
        
        echo " | Borders: [$borderStr]";
        
        if ($hasBg) {
            echo " | BG: #$bgColor";
        }
        
        echo "\n";
    }
}

// Analyze column widths
echo "\n\n📏 COLUMN WIDTHS:\n";
echo str_repeat('-', 80) . "\n";
for ($col = 1; $col <= min(15, $highestColumnIndex); $col++) {
    $colLetter = Coordinate::stringFromColumnIndex($col);
    $width = $sheet->getColumnDimension($colLetter)->getWidth();
    echo "  Column $colLetter: width = $width\n";
}

// Find all formulas
echo "\n\n📐 ALL FORMULAS IN SHEET:\n";
echo str_repeat('-', 80) . "\n";
$formulaCount = 0;
for ($row = 1; $row <= $highestRow; $row++) {
    for ($col = 1; $col <= $highestColumnIndex; $col++) {
        $cellCoord = Coordinate::stringFromColumnIndex($col) . $row;
        $cell = $sheet->getCell($cellCoord);
        
        if ($cell->getDataType() === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA) {
            $formula = $cell->getValue();
            $result = $cell->getCalculatedValue();
            echo "  $cellCoord: $formula = $result\n";
            $formulaCount++;
        }
    }
}
echo "\nTotal formulas: $formulaCount\n";

// Analyze border patterns
echo "\n\n🔲 BORDER PATTERNS:\n";
echo str_repeat('-', 80) . "\n";

// Check which cells have borders
$borderedCells = [];
for ($row = 1; $row <= min(20, $highestRow); $row++) {
    for ($col = 1; $col <= min(15, $highestColumnIndex); $col++) {
        $cellCoord = Coordinate::stringFromColumnIndex($col) . $row;
        $cell = $sheet->getCell($cellCoord);
        $value = $cell->getValue();
        
        $borders = $cell->getStyle()->getBorders();
        $hasBorder = (
            $borders->getTop()->getBorderStyle() !== Border::BORDER_NONE ||
            $borders->getBottom()->getBorderStyle() !== Border::BORDER_NONE ||
            $borders->getLeft()->getBorderStyle() !== Border::BORDER_NONE ||
            $borders->getRight()->getBorderStyle() !== Border::BORDER_NONE
        );
        
        if ($hasBorder) {
            $borderedCells[] = [
                'coord' => $cellCoord,
                'value' => substr($value, 0, 20),
                'isEmpty' => ($value === null || $value === '')
            ];
        }
    }
}

echo "Cells with borders: " . count($borderedCells) . "\n";
echo "First 20 bordered cells:\n";
foreach (array_slice($borderedCells, 0, 20) as $info) {
    $empty = $info['isEmpty'] ? ' (EMPTY)' : '';
    echo "  {$info['coord']}: \"{$info['value']}\"$empty\n";
}

echo "\n✅ Analysis complete!\n";
