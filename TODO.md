# TODO: Procurement Review Table Changes

## 1. `AdminController.php` - Backend Changes
- [x] Add LEFT JOIN subquery for RIS item names (concatenated)
- [x] Add `directApprovedRis` count (status='Approved' AND ris_approved_by_date IS NULL)
- [x] Update `approvedRis` count to check ris_approved_by_date IS NOT NULL
- [x] Add `direct_approved` filter handling
- [x] Update `approved` filter to include ris_approved_by_date IS NOT NULL
- [x] Pass new variables to view

## 2. `_content.blade.php` - Stats & Filters
- [x] Rename "Approved" stat card → "Approved for President"
- [x] Add "Direct Approval" stat card
- [x] Rename "Approved" filter toggle → "Approved for President"
- [x] Add "Direct Approval" filter toggle

## 3. `_table.blade.php` - Table Columns & Status
- [x] Change "RIS No." header → "Reference No."
- [x] Change "Request" header → "Purpose", display ris_purpose_description
- [x] Replace single equipment with RIS items from subquery
- [x] Add "Direct Approved" status badge
- [x] Rename "Approved" status badge → "Approved for President"

## ✅ All changes implemented!

