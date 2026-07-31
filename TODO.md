# TODO: Remove "Total RIS" and "Total for Signing" Cards

## Steps

1. [x] **`_content.blade.php`** (Procurement Review) - Remove "Total RIS" `<div>` card
2. [x] **`_signature-history-content.blade.php`** (Signature History) - Remove "Total RIS" `<div>` card, change grid from `xl:grid-cols-5` to `xl:grid-cols-4`
3. [x] **`_sign-ris-content.blade.php`** (Sign RIS) - Remove "Total for Signing" `<div>` card, change grid from `xl:grid-cols-4` to `xl:grid-cols-3`
4. [x] **`AdminController.php`** - Remove `$totalRis` from `procurementReview()` and `risApprovals()` compact calls
5. [x] **`AdminController.php`** - Remove `$totalRis` variable/computation from `signatureHistory()` and its compact calls
6. [x] **`AdminController.php`** - Remove `$totalForSigning` and `$totalAmount` variables/computations from `signRis()` and its compact calls

