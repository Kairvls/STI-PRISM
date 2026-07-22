# Signature History - Fixes

## Issues
1. Total RIS card includes active (Pending) forms, doesn't equal combined individual counts
2. Table defaults to "procurement_review" toggle instead of unified "all" view
3. Active/pending RIS forms are included in history

## Plan

- [x] 1. `AdminController@signatureHistory`:
       - Default filter: unified view (remove procurement_review/sign_ris toggle)
       - Base query: exclude `ris_status = 'Pending'` (active forms)
       - `$totalRis`: count only finished forms (= Direct Approved + Signed + Co-signed + Amended)
       - Remove toggle branching in table query

- [x] 2. `_signature-history-content.blade.php`:
       - Remove toggle buttons (Procurement Review / Sign RIS)
       - Show single unified filter state (default "All")
       - Add "Amended" card to the stats row
       - Updated card descriptions

- [x] 3. `_signature-history-table.blade.php`:
       - Simplify status badges (no toggle context needed)
       - Each row shows its actual status: Direct Approved, Signed, Co-signed, Amended
       - Fixed empty state text (removed $filter reference)

- [x] 4. `signature-history.blade.php`:
       - Updated page description
       - Removed toggle filter JS logic
       - Kept search + pagination + preview modal

