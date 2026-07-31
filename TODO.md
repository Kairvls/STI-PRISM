# Admin Dashboard Refactoring - TODO

## Steps Completed

### Step 1: Controller - Split Activity Logs into Pending & Completed
- [x] Replaced `$activityLogs` with `$pendingActivityLogs` (limit 3, pending statuses) and `$completedActivityLogs` (limit 2, completed statuses)
- [x] Passed both new variables to the view via compact()

### Step 2: Blade - Compact Stat Cards (~50% smaller)
- [x] Reduced padding: 14px 16px → 8px 10px
- [x] Reduced stat-value font: 24px → 16px
- [x] Reduced icon containers: 34px → 24px
- [x] Reduced grid gaps: 14px → 6px
- [x] Reduced all supporting text sizes proportionally

### Step 3: Blade - Pending RIS Card Conditional & Top
- [x] Moved Pending RIS card to first position in stat grid
- [x] Wrapped in @if($pendingRis > 0) condition

### Step 4: Blade - Rework Activity List with Toggle
- [x] Split into pending (3 items) and completed (2 items) sections
- [x] Default: show only pending forms
- [x] Added "Show completed" toggle button to expand to all 5
- [x] Added JavaScript toggle functionality
- [x] Added CSS for section separator with horizontal line

### Step 5: Reorganize Dashboard to Minimize Empty Spaces
- [x] Reduced overall dashboard padding: 20px 24px → 12px 16px
- [x] Reduced header margin: 20px → 10px, title font: 20px → 16px
- [x] Reduced main grid gap: 20px → 12px
- [x] Reduced sidebar gap: 16px → 10px
- [x] Reduced hero alert padding: 20px 24px → 12px 16px, margin-bottom: 20px → 8px
- [x] Reduced chart/table card border-radius: 16px → 10px
- [x] Reduced chart/table header padding: 18px 22px → 10px 14px
- [x] Reduced table cell padding: 12-14px 22px → 8px 14px
- [x] Reduced sidebar card border-radius: 14-16px → 10px
- [x] Reduced sidebar card padding proportionally
- [x] Reduced calendar day font: 11px → 9px
- [x] Reduced activity item padding: 12px 20px → 8px 14px
- [x] Reduced act-icon: 28px → 24px
- [x] Reduced stats list gap: 10px → 4px
- [x] Reduced stat-item padding: 8px → 5px

