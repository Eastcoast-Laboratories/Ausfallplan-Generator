#!/usr/bin/env python3
"""
Analyze example.xls structure in detail
"""
import xlrd
import sys

# Open the workbook
wb = xlrd.open_workbook('dev/example.xls', formatting_info=True)
sheet = wb.sheet_by_index(0)

print("=" * 80)
print("DETAILED EXCEL ANALYSIS: example.xls")
print("=" * 80)
print()

print(f"Dimensions: {sheet.nrows} rows x {sheet.ncols} columns")
print()

# Analyze first 20 rows
print("ROW-BY-ROW ANALYSIS (first 20 rows):")
print("-" * 80)

for row_idx in range(min(20, sheet.nrows)):
    row_data = []
    
    for col_idx in range(min(15, sheet.ncols)):
        cell = sheet.cell(row_idx, col_idx)
        value = cell.value
        
        if value == '':
            continue
        
        # Get cell info
        col_letter = chr(65 + col_idx) if col_idx < 26 else f"A{chr(65 + col_idx - 26)}"
        coord = f"{col_letter}{row_idx + 1}"
        
        # Check if formula
        cell_type = cell.ctype
        type_name = ['EMPTY', 'TEXT', 'NUMBER', 'DATE', 'BOOLEAN', 'ERROR', 'BLANK'][cell_type]
        
        row_data.append(f"  {coord}: \"{value}\" (type: {type_name})")
    
    if row_data:
        print(f"\nROW {row_idx + 1}:")
        for data in row_data:
            print(data)

print()
print("=" * 80)
print("COLUMN STRUCTURE ANALYSIS:")
print("-" * 80)

# Analyze row 4 (header row)
print("\nRow 4 (Header):")
for col_idx in range(min(15, sheet.ncols)):
    cell = sheet.cell(3, col_idx)  # Row 4 = index 3
    value = cell.value
    col_letter = chr(65 + col_idx) if col_idx < 26 else f"A{chr(65 + col_idx - 26)}"
    
    if value:
        print(f"  Column {col_letter} (index {col_idx}): \"{value}\"")
    else:
        print(f"  Column {col_letter} (index {col_idx}): (empty)")

print()
print("✅ Analysis complete!")
