<?php
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

// Convert xls to xlsx first
$xlsFile = __DIR__ . '/example.xls';
$spreadsheet = IOFactory::load($xlsFile);
$sheet = $spreadsheet->getActiveSheet();

echo "ULTRA-DETAILED EXCEL ANALYSIS\n";
echo str_repeat("=", 100) . "\n\n";

// 1. COLUMN WIDTHS
echo "COLUMN WIDTHS:\n";
echo str_repeat("-", 100) . "\n";
for ($col = 1; $col <= 20; $col++) {
    $colLetter = Coordinate::stringFromColumnIndex($col);
    $width = $sheet->getColumnDimension($colLetter)->getWidth();
    echo sprintf("Column %2s (index %2d): width = %.2f\n", $colLetter, $col, $width);
}

// 2. ROW HEIGHTS
echo "\n\nROW HEIGHTS:\n";
echo str_repeat("-", 100) . "\n";
for ($row = 1; $row <= 30; $row++) {
    $height = $sheet->getRowDimension($row)->getRowHeight();
    echo sprintf("Row %2d: height = %.2f\n", $row, $height);
}

// 3. MERGED CELLS
echo "\n\nMERGED CELLS:\n";
echo str_repeat("-", 100) . "\n";
$mergedCells = $sheet->getMergeCells();
if (empty($mergedCells)) {
    echo "No merged cells\n";
} else {
    foreach ($mergedCells as $range) {
        echo "Merged: $range\n";
    }
}

// 4. DETAILED CELL ANALYSIS
echo "\n\nDETAILED CELL ANALYSIS (First 20 rows):\n";
echo str_repeat("-", 100) . "\n";

for ($row = 1; $row <= 20; $row++) {
    $hasContent = false;
    
    for ($col = 1; $col <= 20; $col++) {
        $coord = Coordinate::stringFromColumnIndex($col) . $row;
        $cell = $sheet->getCell($coord);
        $value = $cell->getValue();
        
        if ($value !== null && $value !== "") {
            $hasContent = true;
            break;
        }
    }
    
    if (!$hasContent) continue;
    
    echo "\nROW $row:\n";
    
    for ($col = 1; $col <= 20; $col++) {
        $coord = Coordinate::stringFromColumnIndex($col) . $row;
        $cell = $sheet->getCell($coord);
        $value = $cell->getValue();
        
        // Get formula
        $isFormula = ($cell->getDataType() === DataType::TYPE_FORMULA);
        
        // Get borders
        $borders = $cell->getStyle()->getBorders();
        $borderParts = [];
        if ($borders->getTop()->getBorderStyle() !== Border::BORDER_NONE) $borderParts[] = "T";
        if ($borders->getBottom()->getBorderStyle() !== Border::BORDER_NONE) $borderParts[] = "B";
        if ($borders->getLeft()->getBorderStyle() !== Border::BORDER_NONE) $borderParts[] = "L";
        if ($borders->getRight()->getBorderStyle() !== Border::BORDER_NONE) $borderParts[] = "R";
        $borderStr = empty($borderParts) ? "" : "[" . implode("", $borderParts) . "]";
        
        if ($value !== null && $value !== "") {
            if ($isFormula) {
                $result = $cell->getCalculatedValue();
                echo sprintf("  %4s: FORMULA %-40s = %-10s %s\n", $coord, $value, $result, $borderStr);
            } else {
                $valueStr = is_string($value) ? "\"$value\"" : $value;
                echo sprintf("  %4s: %-50s %s\n", $coord, $valueStr, $borderStr);
            }
        } elseif (!empty($borderParts)) {
            echo sprintf("  %4s: (empty) %s\n", $coord, $borderStr);
        }
    }
}

echo "\n" . str_repeat("=", 100) . "\n";
echo "ANALYSIS COMPLETE\n";
