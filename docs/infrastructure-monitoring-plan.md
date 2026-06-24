# PRISM Infrastructure Monitoring — Implementation Blueprint

## Outcome

The module provides a responsive spatial directory for STI College Ormoc. A technician selects a floor, opens a room, and sees equipment condition, report volume, recurring problems, and upcoming preventive maintenance without leaving the map.

## Architecture

- `Building hasMany Floor`
- `Floor belongsTo Building` and `hasMany Room`
- `Room belongsTo Floor` and `hasMany Equipment`
- Reports reference rooms and optionally equipment.
- Maintenance schedules reference equipment, allowing schedules to be grouped back to a room.
- Room canvas geometry lives on `rooms_table` (`room_x`, `room_y`, `room_width`, `room_height`).
- Equipment placement lives on `equipment_table` (`equipment_placement_zone`, `equipment_position_x`, `equipment_position_y`).

The monitoring page is server-rendered with eager-loaded Eloquent relationships. Aggregated report and schedule queries prevent per-room query loops. Alpine.js owns transient UI state; Interact.js handles grid-snapped dragging; Laravel remains authoritative for validation and persistence.

## Delivered workflow

1. Floor tabs switch the active canvas without a page reload.
2. Dynamic room blocks use saved geometry, type colors, status borders, and a CSS-generated isometric depth.
3. A room selection opens a three-tab workspace for equipment, analytics, and maintenance.
4. Layout Editor enables bounded, 20px grid-snapped room movement.
5. Save Layout validates the active floor and only updates rooms belonging to it.
6. Campus Configuration creates a building, floors, rooms, and initial equipment in one database transaction.
7. Equipment provisioning captures a semantic placement zone and previews it on a miniature room grid.

## Validation and safety

- The wizard uses nested Laravel validation for every floor, room, and asset.
- Accepted floor levels, room types, room states, equipment conditions, and placement zones are allow-listed.
- Creation is atomic: any invalid or failed child record rolls back the whole cascade.
- Layout coordinates are floor-scoped and bounded to the canvas.
- Empty floors and empty equipment/report/schedule collections have explicit UI states.

## Recommended next increments

1. Add authorization policies for campus configuration versus read-only monitoring.
2. Add optimistic concurrency (a layout version) if multiple technicians can edit simultaneously.
3. Add undo/redo history and keyboard nudging for room geometry changes.
4. Add a dedicated equipment-placement endpoint for moving existing assets within a room.
5. Add feature tests for wizard rollback, floor-scoped layout updates, and monitoring aggregates.
6. Move CDN dependencies into the Vite bundle for offline campus-network reliability.

## Acceptance checks

- Every configured floor can be selected and only its rooms appear.
- Selecting a room displays quantities and statuses matching `equipment_table`.
- Today/weekly/monthly report counts match `reports_table` timestamps.
- Critical rooms visibly pulse; department colors remain distinguishable.
- Layout coordinates persist after reload.
- Invalid nested wizard input creates no partial building records.
- The canvas remains horizontally scrollable on small screens and the detail workspace stacks below it.
