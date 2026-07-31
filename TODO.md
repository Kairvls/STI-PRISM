# Admin Dashboard Refactoring - TODO

## Steps Completed

### Step 1: Controller - Split Activity Logs into Pending & Completed
- [x] Replaced `$activityLogs` with `$pendingActivityLogs` (limit 3, pending statuses) and `$completedActivityLogs` (limit 2, completed statuses)
- [x] Passed both new variables to the view via compact()

### Step 2: Blade - Compact Stat Cards (~50% smaller)
- [x] Reduced padding: 14px 16px → 8px 10px
- [x] Reduced stat-value font: 24px → 16px
- [x] Reduced icon containers: 34px → 24px
- [x] Reduced grid gaps: 14px → 8px
- [x] Reduced stat-change font: 11px → 9px
- [x] Reduced stat-label font: 10px → 9px
- [x] Reduced stat-amount font: 11px → 9px
- [x] Reduced meta font: 10px → 9px, dots 6px → 5px

### Step 3: Blade - Pending RIS Card Conditional & Top
- [x] Moved Pending RIS card to first position in stat grid
- [x] Wrapped in @if($pendingRis > 0) condition

### Step 4: Blade - Rework Activity List with Toggle
- [x] Split into pending (3 items) and completed (2 items) sections
- [x] Default: show only pending forms
- [x] Added "Show completed" toggle button to expand to all 5
- [x] Added JavaScript toggle functionality
- [x] Added CSS for section separator with horizontal line

