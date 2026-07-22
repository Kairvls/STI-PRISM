# Implementation Plan - COMPLETED ✅

## Step 1: AdminController.php Updates ✅
- [x] Rename comments: "Approved for President" → "Forward to President" / "Forwarded to President"
- [x] Update `directApproveRis()` to accept `admin_name` and `admin_date` from request
- [x] Update counts/filters logic for differentiation:
  - "Forwarded to President": Approved + ris_approved_by_date NOT NULL + (signature IS NULL OR base64)
  - "Direct Approved": Approved + ris_approved_by_date NOT NULL + plain-text signature

## Step 2: _content.blade.php Updates ✅
- [x] Rename "Approved for President" → "Forwarded to President" in card
- [x] Rename "Direct Approval" → "Direct Approved" in card
- [x] Rename filter button texts
- [x] Add `title` tooltips to all interactive elements

## Step 3: _table.blade.php Updates ✅
- [x] Update status badge logic for Forwarded to President / Direct Approved
- [x] Rename action button "Approved for President" → "Forward to President"
- [x] Replace direct approval form with modal-triggering button
- [x] Add tooltips to all buttons and pagination

## Step 4: index.blade.php - Direct Approval Modal + PDF Export ✅
- [x] Add direct approval modal with:
  - Left: iframe RIS preview
  - Right: Admin Name input, Date input, Signature display (visual only, shows "Admin Signature (display only)")
  - Submit → POST to direct-approve route
- [x] Add PDF export button in the preview modal
- [x] Update ESC key to close both modals

## Step 5: print.blade.php - PDF Export Button ✅
- [x] Add toolbar with "Download PDF" and "Print" buttons (hidden during print)

