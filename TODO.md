# RIS Procurement Review AJAX Smooth Search/Filter/Pagination

## Steps
- [x] 1. Analyze current implementation and create plan
- [x] 2. Create `_content.blade.php` partial with stat cards + filter/search + table + pagination
- [x] 3. Update `index.blade.php` to use the new partial and add AJAX JS
- [x] 4. Update `AdminController.php` to handle AJAX requests and return only the partial
- [x] 5. Update `_table.blade.php` to add `data-page` attributes and `.ris-pagination-link` class
- [x] 6. Remove script block from `_content.blade.php` (scripts don't execute via innerHTML)

