<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Automatic report priority (ITIL-style impact × urgency), decided by the system
 * from the equipment, the suggested issue, the room, repeat reports, and safety signals.
 *
 * report_urgency_level (Urgent / Non-Urgent) is still saved and derived from the level,
 * so every existing Urgent-based rule keeps working.
 */
class ReportSeverity
{
    public const CRITICAL = 'Critical';
    public const HIGH = 'High';
    public const MEDIUM = 'Medium';
    public const LOW = 'Low';

    private const RANKS = [
        self::LOW => 1,
        self::MEDIUM => 2,
        self::HIGH => 3,
        self::CRITICAL => 4,
    ];

    /** Phrases matched (case-insensitive) inside the suggested issue name. Low is checked first. */
    private const LOW_ISSUES = [
        'remote', 'stained', 'hard to erase', 'torn', 'missing hooks', 'missing keys', 'sticky',
        'scroll wheel', 'poor print quality', 'oscillation', 'broken headband', 'blurry display',
        'broken tray', 'surface damaged', 'damaged surface',
    ];

    private const HIGH_ISSUES = [
        'no display', 'no power', 'not booting', 'not turning on', 'not cooling', 'water leak',
        'hdmi not detected', 'input not detected', 'no internet', 'network connection lost',
        'no backup power', 'cannot login', 'broken latch', 'stuck', 'wobbly blades', 'loose mount',
        'overheating', 'broken monitor', 'lamp issue',
    ];

    /** Equipment the whole class looks at; a problem in a teaching room stops the lesson. */
    private const ROOM_DISPLAY_COMPONENTS = ['Projector', 'Flat Screen TV', 'AVP'];

    private const TEACHING_ROOM_TYPES = ['lecture room', 'computer laboratory'];

    private const SAFETY_PATTERN = '/\b(smoke|smoking|sparks?|sparking|burning|burnt smell|fire|flames?|electric shock|electrocut\w*|shock(ed)?|exposed wires?|live wires?|short circuit|flood(ing|ed)?|water (near|on|in|inside) (the |an |a )?(electric\w*|outlets?|sockets?|wires?|wiring|plugs?|extension)|wet (outlets?|sockets?|wires?|wiring|plugs?)|broken glass|shattered|injur\w*|bleeding|gas leak|usok|sunog|apoy|nakuryente)\b/i';

    /** @var array<string, array<string, mixed>>|null */
    private static ?array $templateSeverities = null;

    public static function levels(): array
    {
        return [self::CRITICAL, self::HIGH, self::MEDIUM, self::LOW];
    }

    public static function isLevel(?string $level): bool
    {
        return isset(self::RANKS[(string) $level]);
    }

    public static function rank(?string $level): int
    {
        return self::RANKS[(string) $level] ?? 0;
    }

    public static function max(?string $a, ?string $b): string
    {
        return self::rank($a) >= self::rank($b) ? ($a ?: self::MEDIUM) : ($b ?: self::MEDIUM);
    }

    public static function urgencyFor(string $level): string
    {
        return self::rank($level) >= self::RANKS[self::HIGH] ? 'Urgent' : 'Non-Urgent';
    }

    public static function fromUrgency(?string $urgency): string
    {
        return $urgency === 'Urgent' ? self::HIGH : self::MEDIUM;
    }

    /** Level shown for a report row (falls back to the legacy Urgent/Non-Urgent value). */
    public static function forReport(object $report): string
    {
        $level = $report->report_severity ?? null;
        $urgency = $report->report_urgency_level ?? null;

        if (!self::isLevel($level)) {
            return self::fromUrgency($urgency);
        }

        // List pages fold duplicate tickets into one row and mark it Urgent if any of them is.
        return $urgency === 'Urgent' ? self::max($level, self::HIGH) : $level;
    }

    public static function meta(?string $level): array
    {
        return match ($level) {
            self::CRITICAL => [
                'label' => 'Critical',
                'meaning' => 'Safety hazard or room unusable',
                'target' => 'Respond within 1 hour',
                'pill' => 'bg-red-600 text-white',
                'soft' => 'bg-red-50 text-red-700 ring-1 ring-red-200',
                'color' => '#dc2626',
            ],
            self::HIGH => [
                'label' => 'High',
                'meaning' => 'Class or work disrupted, no workaround',
                'target' => 'Respond the same day',
                'pill' => 'bg-orange-100 text-orange-700',
                'soft' => 'bg-orange-50 text-orange-700 ring-1 ring-orange-200',
                'color' => '#ea580c',
            ],
            self::LOW => [
                'label' => 'Low',
                'meaning' => 'Cosmetic or minor',
                'target' => 'Within 5 school days',
                'pill' => 'bg-slate-100 text-slate-600',
                'soft' => 'bg-slate-50 text-slate-600 ring-1 ring-slate-200',
                'color' => '#64748b',
            ],
            default => [
                'label' => 'Medium',
                'meaning' => 'Still usable or has a workaround',
                'target' => 'Within 2 school days',
                'pill' => 'bg-amber-100 text-amber-800',
                'soft' => 'bg-amber-50 text-amber-800 ring-1 ring-amber-200',
                'color' => '#d97706',
            ],
        };
    }

    /** ORDER BY fragment: Critical first, Low last. */
    public static function orderSql(string $table = 'reports_table'): string
    {
        return "CASE {$table}.report_severity
            WHEN 'Critical' THEN 0 WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 WHEN 'Low' THEN 3 ELSE 2 END";
    }

    /** Days before an undated Medium/Low report shows up in maintenance reminders. */
    public static function reminderGraceDays(string $level): int
    {
        return $level === self::LOW ? 5 : 2;
    }

    public static function hasColumns(): bool
    {
        static $has = null;

        return $has ??= Schema::hasTable('reports_table')
            && Schema::hasColumn('reports_table', 'report_severity');
    }

    public static function hasTemplateColumn(): bool
    {
        static $has = null;

        return $has ??= Schema::hasTable('issue_templates_table')
            && Schema::hasColumn('issue_templates_table', 'issue_template_severity');
    }

    public static function mentionsSafetyHazard(?string $text): ?string
    {
        $text = trim((string) $text);

        if ($text !== '' && preg_match(self::SAFETY_PATTERN, $text, $match)) {
            return strtolower($match[0]);
        }

        return null;
    }

    /** Default level from the issue wording alone (used when maintenance has not pinned one). */
    public static function defaultForIssue(?string $issue): string
    {
        $issue = strtolower(trim((string) $issue));

        if ($issue === '') {
            return self::MEDIUM;
        }

        foreach (self::LOW_ISSUES as $phrase) {
            if (str_contains($issue, $phrase)) {
                return self::LOW;
            }
        }

        foreach (self::HIGH_ISSUES as $phrase) {
            if (str_contains($issue, $phrase)) {
                return self::HIGH;
            }
        }

        return self::MEDIUM;
    }

    public static function issueLevel(?int $categoryId, ?string $component, ?string $issue): string
    {
        $pinned = self::pinnedTemplateLevel($categoryId, $component, $issue);

        return $pinned ?? self::defaultForIssue($issue);
    }

    /**
     * @param  array<int, array{equipment_id?: int|null, name?: string|null, issue?: string|null, room_id?: int|null}>  $items
     * @return array{level: string, urgency: string, reason: string, safety_hazard: bool}
     */
    public static function assess(array $items, ?string $description = null): array
    {
        $equipmentIds = collect($items)->pluck('equipment_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $equipment = $equipmentIds->isEmpty()
            ? collect()
            : DB::table('equipment_table')->whereIn('equipment_id', $equipmentIds->all())->get()->keyBy('equipment_id');

        $roomIds = collect($items)->pluck('room_id')
            ->merge($equipment->pluck('equipment_room_id'))
            ->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $roomTypes = $roomIds->isEmpty()
            ? collect()
            : DB::table('rooms_table')->whereIn('room_id', $roomIds->all())->pluck('room_type', 'room_id');

        $hazardWord = null;
        foreach ($items as $item) {
            $hazardWord ??= self::mentionsSafetyHazard($item['issue'] ?? null)
                ?? self::mentionsSafetyHazard($item['name'] ?? null);
        }
        $hazardWord ??= self::mentionsSafetyHazard($description);

        if ($hazardWord !== null) {
            return [
                'level' => self::CRITICAL,
                'urgency' => self::urgencyFor(self::CRITICAL),
                'reason' => 'The report mentions "'.$hazardWord.'", which is a safety hazard.',
                'safety_hazard' => true,
            ];
        }

        $best = null;

        foreach ($items as $item) {
            $row = !empty($item['equipment_id']) ? $equipment->get((int) $item['equipment_id']) : null;
            $name = trim((string) ($row->equipment_name ?? $item['name'] ?? 'Equipment'));
            $issue = trim((string) ($item['issue'] ?? ''));
            $roomId = (int) ($item['room_id'] ?? $row->equipment_room_id ?? 0);
            $roomType = (string) ($roomTypes[$roomId] ?? '');
            $component = SuggestedIssues::detectComponent($name);

            $level = self::issueLevel(
                isset($row->equipment_category_id) ? (int) $row->equipment_category_id : null,
                $component,
                $issue
            );
            $reasons = [
                $issue !== ''
                    ? $name.' · "'.$issue.'" is rated '.$level.'.'
                    : $name.' has no standard issue selected, so it starts at '.$level.'.',
            ];

            // Impact can raise an item by one step at most, and never to Critical.
            $boosted = false;
            $teachingRoom = in_array(strtolower($roomType), self::TEACHING_ROOM_TYPES, true);

            if ($level === self::MEDIUM && $teachingRoom && in_array($component, self::ROOM_DISPLAY_COMPONENTS, true)) {
                $level = self::HIGH;
                $boosted = true;
                $reasons[] = 'It is the room display in a '.strtolower($roomType).', so the whole class is affected.';
            }

            if (!$boosted && $row && $roomId > 0 && self::rank($level) < self::RANKS[self::HIGH]
                && ReportGrouping::findOpenReport((int) $row->equipment_id, $roomId)) {
                $level = self::raise($level);
                $boosted = true;
                $reasons[] = 'It was already reported and is still not fixed.';
            }

            if (str_contains(strtolower($roomType), 'storage') && $level !== self::LOW) {
                $level = self::lower($level);
                $reasons[] = 'It is in a storage room, so nobody is using it right now.';
            }

            if ($best === null || self::rank($level) > self::rank($best['level'])) {
                $best = ['level' => $level, 'reasons' => $reasons, 'boosted' => $boosted];
            }
        }

        $best ??= ['level' => self::MEDIUM, 'reasons' => ['No equipment details yet, so it starts at Medium.'], 'boosted' => false];

        $sameIssueCount = collect($items)
            ->map(fn ($item) => strtolower(trim((string) ($item['issue'] ?? ''))))
            ->filter()
            ->countBy()
            ->max() ?? 0;

        if ($sameIssueCount >= 3 && !$best['boosted'] && self::rank($best['level']) < self::RANKS[self::HIGH]) {
            $best['level'] = self::raise($best['level']);
            $best['reasons'][] = $sameIssueCount.' items have the same issue, which points to a room-wide problem.';
        }

        return [
            'level' => $best['level'],
            'urgency' => self::urgencyFor($best['level']),
            'reason' => mb_substr(implode(' ', $best['reasons']), 0, 500),
            'safety_hazard' => false,
        ];
    }

    /** Columns to store on reports_table for an assessment (empty when the migration has not run). */
    public static function columnsFor(array $assessment): array
    {
        if (!self::hasColumns()) {
            return [];
        }

        return [
            'report_severity' => $assessment['level'],
            'report_severity_reason' => $assessment['reason'],
            'report_severity_is_manual' => false,
            'report_safety_hazard' => (bool) ($assessment['safety_hazard'] ?? false),
        ];
    }

    private static function raise(string $level): string
    {
        return self::rank($level) < self::RANKS[self::HIGH]
            ? array_search(self::rank($level) + 1, self::RANKS, true)
            : $level;
    }

    private static function lower(string $level): string
    {
        return self::rank($level) > self::RANKS[self::LOW] && $level !== self::CRITICAL
            ? array_search(self::rank($level) - 1, self::RANKS, true)
            : $level;
    }

    private static function pinnedTemplateLevel(?int $categoryId, ?string $component, ?string $issue): ?string
    {
        $issue = strtolower(trim((string) $issue));

        if ($issue === '' || !self::hasTemplateColumn()) {
            return null;
        }

        if (self::$templateSeverities === null) {
            self::$templateSeverities = [];

            DB::table('issue_templates_table')
                ->whereNotNull('issue_template_severity')
                ->get(['issue_template_category_id', 'issue_template_component', 'issue_template_name', 'issue_template_severity'])
                ->each(function ($row) {
                    if (!self::isLevel($row->issue_template_severity)) {
                        return;
                    }
                    $key = strtolower(trim((string) $row->issue_template_name));
                    self::$templateSeverities[$key][] = $row;
                });
        }

        $candidates = self::$templateSeverities[$issue] ?? [];

        foreach ([true, false] as $strict) {
            foreach ($candidates as $row) {
                $sameCategory = $categoryId === null || (int) $row->issue_template_category_id === $categoryId;
                $sameComponent = (string) ($row->issue_template_component ?? '') === (string) ($component ?? '');

                if ($sameCategory && (!$strict || $sameComponent)) {
                    return $row->issue_template_severity;
                }
            }
        }

        return null;
    }
}
