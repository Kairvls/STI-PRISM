# Admin Dashboard Restructuring Plan

## Steps

### Step 1: AdminController.php ✅
- [x] Add queries for maintenance schedule events (calendar)
- [x] Add queries for activity list (pending + completed from approval_logs & room_activity_logs)
- [x] Pass new variables to view

### Step 2: dashboard.blade.php ✅
- [x] Compact stat cards (reduced padding, font sizes, spacing)
- [x] Restructure sidebar:
  1. Calendar of Events (mini calendar + upcoming schedule items)
  2. RIS Status Overview (existing pie chart)
  3. Activity List (new - pending/completed activities from approval logs)
  4. Quick Summary (with Pending RIS as first priority)
- [x] Remove old Recent Activity section (replaced by Activity List)
- [x] Add calendar CSS/JS component
- [x] Add activity list CSS/JS component

