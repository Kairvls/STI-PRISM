# Amend Modal & Forward to President - Implementation Plan ✅

## Step 1: AdminController.php - Update rejectRis() to store amendment remarks ✅
- [x] Accept `$request->input('remarks')` and store in `ris_rejection_reason` column
- [x] Added validation requiring amendment remarks
- [x] Added approval log entry to track the amendment

## Step 2: AdminController.php - Update approveRis() to store admin name ✅
- [x] Store `Auth::user()->user_full_name` as `ris_approved_by_signature` (plain text)
- [x] Add approval log entry for admin approval record

## Step 3: _table.blade.php - Replace Amend form with modal-triggering button ✅
- [x] Replace `<form>` with a `<button>` that opens `openAmendModal()`
- [x] Keep existing styling and tooltip

## Step 4: index.blade.php - Add Amend Modal with textarea ✅
- [x] Add modal overlay with textarea for amendment remarks
- [x] POST to existing reject route with remarks field
- [x] Cancel and Confirm buttons
- [x] ESC key closes the modal
- [x] JavaScript functions for open/close

## Step 5: Purchaser ris/index.blade.php - Show rejection reason in View modal ✅
- [x] In the View RIS details modal, add display of `ris_rejection_reason` when status is "Rejected"
- [x] Rose-colored background to highlight amendment remarks

