# RIS Preview Fix Plan

## Step 1: ✅ Added `printRis()` method to `PurchaserController.php`
- Fetches RIS + items and returns the print preview view

## Step 2: ✅ Created `resources/views/purchaser/ris/print.blade.php`
- Standalone HTML preview matching president's viewer design
- Same form layout, signatures, 10-row item grid, APPROVED watermark
- Includes print @media rules for landscape printing

## Step 3: ✅ Updated admin's preview modal in `index.blade.php`
- Replaced old modal header + scrollable area with president-style centered iframe
- Added `scaleRisPreviewToFit()` on open and window resize
- Added Print button in the modal header
- Updated backdrop to `bg-black/60 backdrop-blur-sm`
- Added proper 11in x 8.5in fixed iframe dimensions with CSS scaling

## Step 4: ✅ Tooltips verified
- `_table.blade.php`: All buttons/data cells have `title` attributes (already present)
- `_content.blade.php`: Filter buttons, search input, stat cards all have `title` attributes (already present)

