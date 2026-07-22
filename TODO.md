# TODO - RIS Approval Fixes

## Completed Steps
- [x] Analyzed codebase structure and identified issues
- [x] Created implementation plan
- [x] Edit blade: Renamed "Approve" button to "Approved for President" with updated title/confirm messages
- [x] Edit blade: Removed "View Form" action link
- [x] Edit AdminController: Fixed approveRis() - no longer fills President's signature field
- [x] Edit AdminController: Fixed rejectRis() - resets submission fields so Purchaser can resubmit

## Summary of Changes

### `resources/views/admin/procurement-review/index.blade.php`
1. **Button rename**: Changed "Approve" → "Approved for President" with updated tooltip and confirm dialog
2. **Removed**: The entire "View Form" `<a>` link from the actions column

### `app/Http/Controllers/AdminController.php`
1. **`approveRis()`**: Removed the line setting `ris_approved_by_signature` - only sets `ris_approved_by_date` so President sees it in their queue and can affix their signature
2. **`rejectRis()`**: Instead of just setting status to 'Rejected', it now resets `ris_requested_by_signature`, `ris_requested_by_date`, `ris_submitted_by`, and `ris_submitted_at` back to null, and keeps status as 'Pending' - allowing the Purchaser to edit and resubmit

