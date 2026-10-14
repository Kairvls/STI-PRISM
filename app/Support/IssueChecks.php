<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Quick checks shown after a reporter picks a suggested issue, before the item is added.
 *
 * Troubleshoot checks ask the reporter to try something; "That fixed it" ends the report
 * and is logged as a self-fix. Questions (Yes / No) don't fix anything, but their answers
 * can set the item's priority (for example, "Is there another computer you can use?").
 */
class IssueChecks
{
    public const TABLE = 'issue_checks_table';
    public const FIXES_TABLE = 'issue_self_fixes_table';

    public const KIND_TRY = 'troubleshoot';
    public const KIND_ASK = 'question';

    public const ANSWER_FIXED = 'fixed';
    public const ANSWER_NOT_FIXED = 'not_fixed';
    public const ANSWER_YES = 'yes';
    public const ANSWER_NO = 'no';

    public const MAX_PER_ISSUE = 8;

    public static function ready(): bool
    {
        static $ready = null;

        return $ready ??= Schema::hasTable(self::TABLE);
    }

    public static function fixesReady(): bool
    {
        static $ready = null;

        return $ready ??= Schema::hasTable(self::FIXES_TABLE);
    }

    public static function itemColumnReady(): bool
    {
        static $ready = null;

        return $ready ??= Schema::hasTable('report_items_table')
            && Schema::hasColumn('report_items_table', 'report_item_checks');
    }

    public static function kinds(): array
    {
        return [self::KIND_TRY, self::KIND_ASK];
    }

    /** The suggested-issue template a reporter picked for this equipment, matched by name. */
    public static function templateFor(object $equipment, ?string $issue): ?object
    {
        $issue = mb_strtolower(trim((string) $issue));

        if ($issue === '' || ! Schema::hasTable('issue_templates_table')) {
            return null;
        }

        return SuggestedIssues::templatesQuery($equipment)
            ->whereRaw('LOWER(TRIM(issue_template_name)) = ?', [$issue])
            ->first();
    }

    public static function forTemplate(int $templateId): Collection
    {
        if (! self::ready()) {
            return collect();
        }

        return DB::table(self::TABLE)
            ->where('issue_template_id', $templateId)
            ->orderBy('issue_check_order')
            ->orderBy('issue_check_id')
            ->get();
    }

    /**
     * @param  array<int, int>  $templateIds
     * @return array<int, array<int, array<string, mixed>>>  checks payload keyed by template id
     */
    public static function payloadsForTemplates(array $templateIds): array
    {
        if (! self::ready() || $templateIds === []) {
            return [];
        }

        return DB::table(self::TABLE)
            ->whereIn('issue_template_id', $templateIds)
            ->orderBy('issue_check_order')
            ->orderBy('issue_check_id')
            ->get()
            ->groupBy('issue_template_id')
            ->map(fn ($rows) => $rows->map(fn ($row) => self::payload($row))->values()->all())
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public static function payloadsFor(object $equipment, ?string $issue): array
    {
        $template = self::ready() ? self::templateFor($equipment, $issue) : null;

        if (! $template) {
            return [];
        }

        return self::forTemplate((int) $template->issue_template_id)
            ->map(fn ($row) => self::payload($row))
            ->values()
            ->all();
    }

    public static function payload(object $row): array
    {
        return [
            'id' => (int) $row->issue_check_id,
            'kind' => $row->issue_check_kind === self::KIND_ASK ? self::KIND_ASK : self::KIND_TRY,
            'question' => (string) $row->issue_check_question,
            'hint' => $row->issue_check_hint ?: null,
            // A "Yes" that makes it Critical means stop: don't touch it, report it now.
            'safety' => $row->issue_check_kind === self::KIND_ASK
                && $row->issue_check_severity_if_yes === ReportSeverity::CRITICAL,
        ];
    }

    public static function countsByTemplate(): array
    {
        if (! self::ready()) {
            return [];
        }

        return DB::table(self::TABLE)
            ->selectRaw('issue_template_id, COUNT(*) AS aggregate')
            ->groupBy('issue_template_id')
            ->pluck('aggregate', 'issue_template_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /**
     * Keep only answers to real checks, with the question text taken from the database.
     *
     * @param  mixed  $raw  JSON string or array of {id, answer}
     * @return array<int, array{id: int, kind: string, question: string, answer: string, level: ?string}>
     */
    public static function normalizeAnswers(mixed $raw): array
    {
        if (! self::ready()) {
            return [];
        }

        if (is_string($raw)) {
            $raw = trim($raw) === '' ? [] : json_decode($raw, true);
        }

        if (! is_array($raw)) {
            return [];
        }

        $answers = [];
        foreach (array_slice($raw, 0, self::MAX_PER_ISSUE * 2) as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $id = (int) ($entry['id'] ?? $entry['check_id'] ?? 0);
            $answer = strtolower(trim((string) ($entry['answer'] ?? '')));
            if ($id > 0 && $answer !== '' && ! isset($answers[$id])) {
                $answers[$id] = $answer;
            }
        }

        if ($answers === []) {
            return [];
        }

        $rows = DB::table(self::TABLE)
            ->whereIn('issue_check_id', array_keys($answers))
            ->get()
            ->keyBy('issue_check_id');

        $normalized = [];
        foreach ($answers as $id => $answer) {
            $row = $rows->get($id);
            if (! $row) {
                continue;
            }

            $isQuestion = $row->issue_check_kind === self::KIND_ASK;
            $allowed = $isQuestion
                ? [self::ANSWER_YES, self::ANSWER_NO]
                : [self::ANSWER_NOT_FIXED, self::ANSWER_FIXED];

            if (! in_array($answer, $allowed, true)) {
                continue;
            }

            $level = null;
            if ($isQuestion) {
                $level = $answer === self::ANSWER_YES
                    ? $row->issue_check_severity_if_yes
                    : $row->issue_check_severity_if_no;
                $level = ReportSeverity::isLevel($level) ? $level : null;
            }

            $normalized[] = [
                'id' => (int) $id,
                'kind' => $isQuestion ? self::KIND_ASK : self::KIND_TRY,
                'question' => (string) $row->issue_check_question,
                'answer' => $answer,
                'level' => $level,
            ];
        }

        return $normalized;
    }

    public static function answerLabel(string $answer): string
    {
        return match ($answer) {
            self::ANSWER_YES => 'Yes',
            self::ANSWER_NO => 'No',
            self::ANSWER_FIXED => 'That fixed it',
            default => 'Tried it, still not working',
        };
    }

    /** First answer that marks a safety hazard (a question whose answer makes it Critical). */
    public static function safetyAnswer(array $answers): ?array
    {
        foreach ($answers as $answer) {
            if (($answer['level'] ?? null) === ReportSeverity::CRITICAL) {
                return $answer;
            }
        }

        return null;
    }

    /**
     * The level the reporter's answers point to (highest wins), or null when no answer sets one.
     *
     * @return array{level: string, reason: string}|null
     */
    public static function levelFromAnswers(array $answers): ?array
    {
        $best = null;

        foreach ($answers as $answer) {
            $level = $answer['level'] ?? null;
            if (! ReportSeverity::isLevel($level)) {
                continue;
            }
            if ($best === null || ReportSeverity::rank($level) > ReportSeverity::rank($best['level'])) {
                $best = $answer;
            }
        }

        if ($best === null) {
            return null;
        }

        return [
            'level' => $best['level'],
            'reason' => 'Reporter answered "'.self::answerLabel($best['answer']).'" to "'
                .rtrim($best['question'], '?').'?", so it is '.$best['level'].'.',
        ];
    }

    public static function triedCount(array $answers): int
    {
        return count(array_filter(
            $answers,
            fn ($answer) => ($answer['kind'] ?? null) === self::KIND_TRY
        ));
    }

    /** @return array<int, array<string, mixed>> */
    public static function decodeStored(?string $json): array
    {
        if ($json === null || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
    }

    public static function encodeForStorage(array $answers): ?string
    {
        if ($answers === []) {
            return null;
        }

        return json_encode(array_map(fn ($answer) => [
            'id' => $answer['id'],
            'kind' => $answer['kind'],
            'question' => $answer['question'],
            'answer' => $answer['answer'],
            'level' => $answer['level'] ?? null,
        ], $answers), JSON_UNESCAPED_UNICODE);
    }

    public static function logSelfFix(array $data): void
    {
        if (! self::fixesReady()) {
            return;
        }

        DB::table(self::FIXES_TABLE)->insert([
            'issue_self_fix_reporter_id' => $data['reporter_id'] ?? null,
            'issue_self_fix_employee_id' => $data['employee_id'] ?? null,
            'issue_self_fix_equipment_id' => $data['equipment_id'] ?? null,
            'issue_self_fix_room_id' => $data['room_id'] ?? null,
            'issue_self_fix_template_id' => $data['template_id'] ?? null,
            'issue_self_fix_issue' => isset($data['issue']) ? mb_substr((string) $data['issue'], 0, 255) : null,
            'issue_self_fix_check_id' => $data['check_id'] ?? null,
            'issue_self_fix_check_question' => isset($data['check_question']) ? mb_substr((string) $data['check_question'], 0, 255) : null,
            'issue_self_fix_source' => $data['source'] ?? 'web',
            'issue_self_fix_created_at' => now(),
        ]);
    }

    /**
     * Logs "That fixed it" for a check on this equipment. Returns false when the check
     * doesn't belong to the equipment's issue list.
     */
    public static function recordSelfFix(object $equipment, int $checkId, ?object $reporter, string $source): bool
    {
        if (! self::ready()) {
            return false;
        }

        $check = DB::table(self::TABLE)
            ->join('issue_templates_table', 'issue_templates_table.issue_template_id', '=', self::TABLE.'.issue_template_id')
            ->where(self::TABLE.'.issue_check_id', $checkId)
            ->where(self::TABLE.'.issue_check_kind', self::KIND_TRY)
            ->first([
                self::TABLE.'.issue_check_id',
                self::TABLE.'.issue_check_question',
                'issue_templates_table.issue_template_id',
                'issue_templates_table.issue_template_name',
                'issue_templates_table.issue_template_category_id',
            ]);

        if (! $check || (int) $check->issue_template_category_id !== (int) $equipment->equipment_category_id) {
            return false;
        }

        self::logSelfFix([
            'reporter_id' => $reporter->reporter_id ?? null,
            'employee_id' => $reporter->reporter_employee_id ?? null,
            'equipment_id' => (int) $equipment->equipment_id,
            'room_id' => $equipment->equipment_room_id ?? null,
            'template_id' => (int) $check->issue_template_id,
            'issue' => $check->issue_template_name,
            'check_id' => (int) $check->issue_check_id,
            'check_question' => $check->issue_check_question,
            'source' => $source,
        ]);

        return true;
    }

    public static function selfFixCount(?\DateTimeInterface $since = null, ?int $templateId = null): int
    {
        if (! self::fixesReady()) {
            return 0;
        }

        return DB::table(self::FIXES_TABLE)
            ->when($since, fn ($query) => $query->where('issue_self_fix_created_at', '>=', $since))
            ->when($templateId, fn ($query) => $query->where('issue_self_fix_template_id', $templateId))
            ->count();
    }

    /** @return array<int, int> fixes keyed by check id */
    public static function selfFixCountsByCheck(int $templateId): array
    {
        if (! self::fixesReady()) {
            return [];
        }

        return DB::table(self::FIXES_TABLE)
            ->where('issue_self_fix_template_id', $templateId)
            ->whereNotNull('issue_self_fix_check_id')
            ->selectRaw('issue_self_fix_check_id, COUNT(*) AS aggregate')
            ->groupBy('issue_self_fix_check_id')
            ->pluck('aggregate', 'issue_self_fix_check_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /** Adds the default checks to every suggested issue that has none yet. Returns checks added. */
    public static function seedDefaults(): int
    {
        if (! self::ready() || ! Schema::hasColumn('issue_templates_table', 'issue_template_component')) {
            return 0;
        }

        $withChecks = DB::table(self::TABLE)->distinct()->pluck('issue_template_id')->map(fn ($id) => (int) $id)->all();
        $rules = self::defaults();
        $now = now();
        $rows = [];

        $templates = DB::table('issue_templates_table')
            ->get(['issue_template_id', 'issue_template_component', 'issue_template_name']);

        foreach ($templates as $template) {
            if (in_array((int) $template->issue_template_id, $withChecks, true)) {
                continue;
            }

            $checks = self::defaultChecksFor($rules, (string) $template->issue_template_component, (string) $template->issue_template_name);

            foreach ($checks as $order => $check) {
                $rows[] = [
                    'issue_template_id' => (int) $template->issue_template_id,
                    'issue_check_order' => $order + 1,
                    'issue_check_kind' => $check[0] === 'ask' ? self::KIND_ASK : self::KIND_TRY,
                    'issue_check_question' => $check[1],
                    'issue_check_hint' => $check[2] ?? null,
                    'issue_check_severity_if_yes' => $check[3] ?? null,
                    'issue_check_severity_if_no' => $check[4] ?? null,
                    'issue_check_created_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table(self::TABLE)->insert($chunk);
        }

        return count($rows);
    }

    /** Rules list components; '*' matches issues that show for the whole category. */
    private static function defaultChecksFor(array $rules, string $component, string $issue): array
    {
        $component = mb_strtolower(trim($component)) ?: '*';
        $issue = mb_strtolower(trim($issue));

        foreach ($rules as $rule) {
            $components = array_map('mb_strtolower', $rule['components']);
            $issues = array_map('mb_strtolower', $rule['issues']);

            if (in_array($component, $components, true) && in_array($issue, $issues, true)) {
                return $rule['checks'];
            }
        }

        return [];
    }

    /**
     * Default checks per component + issue name.
     * Each check: ['try', question, hint] or ['ask', question, hint, level if Yes, level if No].
     */
    public static function defaults(): array
    {
        $critical = ReportSeverity::CRITICAL;
        $high = ReportSeverity::HIGH;
        $medium = ReportSeverity::MEDIUM;

        $sparks = ['ask', 'Do you see sparks or smoke, or smell something burning?', "If yes, don't touch it and keep people away from it.", $critical, null];
        $otherComputer = ['ask', 'Is there another computer you can use for now?', null, $medium, $high];
        $classNow = ['ask', 'Is a class or activity using this room right now?', null, $high, $medium];
        $othersOffline = ['ask', 'Are other computers in this room also offline?', null, $high, $medium];
        $otherLightsOut = ['ask', 'Are other lights in this room also out?', null, $high, $medium];
        $mayFall = ['ask', 'Does it look loose, like it could fall?', "If yes, switch it off and keep people away from under it.", $critical, null];
        $mayHurt = ['ask', 'Could someone fall or get hurt using it?', 'If yes, set it aside so nobody uses it.', $high, null];
        $plugged = ['try', 'Is the power cable plugged in firmly?', 'Push the plug in at the unit and at the wall outlet or extension.'];
        $outlet = ['try', 'Does the wall outlet work?', 'Plug in something else, like a phone charger, to test it.'];
        $restart = ['try', 'Did you restart the computer?', null];
        $remote = [
            ['try', 'Did you replace or reinsert the remote\'s batteries?', null],
            ['try', 'Are you pointing the remote straight at the unit from close by?', null],
        ];
        $hdmi = ['try', 'Is the HDMI cable plugged in firmly at both ends?', 'Unplug it and plug it back in at the screen and at the laptop or computer.'];
        $source = ['try', 'Is the input or source set to the right HDMI port?', 'Press Source or Input on the remote or on the unit.'];
        $laptop = ['try', 'On the laptop, did you press Windows + P and choose Duplicate?', null];

        return [
            // Computer equipment
            ['components' => ['Monitor'], 'issues' => ['No display'], 'checks' => [
                ['try', 'Is the system unit turned on?', 'Press its power button. The power light should turn on.'],
                ['try', 'Is the monitor turned on and its power cable plugged in?', "Press the monitor's power button. A small light should glow."],
                ['try', 'Is the video cable (HDMI or VGA) plugged in firmly at both ends?', 'Unplug it and plug it back in at the monitor and at the system unit.'],
                $otherComputer,
            ]],
            ['components' => ['Monitor'], 'issues' => ['Flickering screen'], 'checks' => [
                ['try', 'Is the video cable plugged in firmly at both ends?', 'Unplug it and plug it back in.'],
                ['ask', "Can you still read what's on the screen?", null, $medium, $high],
            ]],
            ['components' => ['Monitor'], 'issues' => ['Broken monitor'], 'checks' => [$otherComputer]],
            ['components' => ['Keyboard'], 'issues' => ['Keyboard not working'], 'checks' => [
                ['try', 'Is the keyboard plugged in firmly?', 'Unplug it and plug it into another USB port at the back of the system unit.'],
                $restart,
            ]],
            ['components' => ['Mouse'], 'issues' => ['Mouse not detected'], 'checks' => [
                ['try', 'Is the mouse plugged in firmly?', 'Unplug it and plug it into another USB port.'],
                $restart,
            ]],
            ['components' => ['Mouse'], 'issues' => ['Mouse defective'], 'checks' => [
                ['try', 'Does it work on a flat, clean surface?', 'Wipe the sensor underneath and try it on a mouse pad or a sheet of paper.'],
                ['try', 'Does it work in another USB port?', null],
            ]],
            ['components' => ['System Unit'], 'issues' => ['No power'], 'checks' => [
                $sparks,
                ['try', 'Is the power cable plugged in firmly at the back of the system unit?', null],
                ['try', 'Is the AVR or UPS turned on?', 'Its switch should be on and its light lit.'],
                $outlet,
                $otherComputer,
            ]],
            ['components' => ['System Unit'], 'issues' => ['Not booting', 'System Unit Not Booting'], 'checks' => [
                ['try', 'Did you hold the power button for 5 seconds, then turn it on again?', null],
                ['try', 'Did you remove any USB flash drives?', 'A plugged-in flash drive can stop the computer from starting.'],
                $otherComputer,
            ]],
            ['components' => ['System Unit'], 'issues' => ['Network connection lost'], 'checks' => [
                ['try', 'Is the network (LAN) cable clicked in at the back of the system unit?', 'Its small light should blink when it is connected.'],
                $restart,
                $othersOffline,
            ]],
            ['components' => ['System Unit'], 'issues' => ['Cannot login'], 'checks' => [
                ['try', 'Is Caps Lock off, and are you using the right username and password?', null],
                $otherComputer,
            ]],
            ['components' => ['System Unit'], 'issues' => ['Slow performance'], 'checks' => [
                ['try', 'Did you close the programs you are not using?', null],
                $restart,
            ]],
            ['components' => ['System Unit'], 'issues' => ['Overheating'], 'checks' => [
                $sparks,
                ['try', 'Are the air vents clear?', 'Move anything blocking the back and sides of the system unit.'],
            ]],
            ['components' => ['Headset'], 'issues' => ['No sound', 'Headset No Sound'], 'checks' => [
                ['try', 'Is the headset plugged into the headphone (green) port?', null],
                ['try', 'Is the volume turned up and not muted?', "Check the computer's volume and the control on the headset cable."],
            ]],
            ['components' => ['Speaker'], 'issues' => ['No sound'], 'checks' => [
                ['try', 'Is the speaker turned on and plugged in?', null],
                ['try', 'Is the volume turned up and not muted?', "Check the speaker's knob and the computer's volume."],
            ]],
            ['components' => ['Webcam'], 'issues' => ['Webcam Not Detected'], 'checks' => [
                ['try', 'Is the webcam plugged in firmly?', 'Unplug it and plug it into another USB port.'],
                $restart,
            ]],
            ['components' => ['Webcam'], 'issues' => ['No Camera Image'], 'checks' => [
                ['try', "Is the webcam's privacy cover or shutter open?", null],
                ['try', 'Is another app already using the camera?', 'Close other apps that use the camera, then try again.'],
            ]],
            ['components' => ['Headset'], 'issues' => ['Microphone not working'], 'checks' => [
                ['try', 'Is the microphone plugged into the mic (pink) port?', null],
                ['try', 'Is the mute switch on the headset cable turned off?', null],
            ]],
            ['components' => ['UPS / AVR', 'AVR / UPS'], 'issues' => ['Not turning on', 'AVR Not Turning On'], 'checks' => [
                ['try', 'Is it plugged into a working wall outlet?', 'Plug in something else, like a phone charger, to test the outlet.'],
                ['try', 'Is its power switch on?', null],
            ]],
            ['components' => ['UPS / AVR', 'AVR / UPS'], 'issues' => ['Beeping continuously'], 'checks' => [
                ['try', 'Is it plugged in firmly at the wall outlet?', 'Continuous beeping usually means it lost wall power and is running on battery.'],
            ]],
            ['components' => ['Ethernet Cable'], 'issues' => ['No internet'], 'checks' => [
                ['try', 'Is the cable clicked in at both ends?', 'Push it in until it clicks. The port light should blink.'],
                $othersOffline,
            ]],
            ['components' => ['Ethernet Cable'], 'issues' => ['Loose connection'], 'checks' => [
                ['try', 'Did you push the cable in until it clicks?', null],
            ]],

            // Displays and AV
            ['components' => ['Flat Screen TV', 'Projector', 'AVP'], 'issues' => ['No power'], 'checks' => [
                $sparks,
                $plugged,
                ['try', 'Is the wall outlet or extension cord switched on?', null],
                $classNow,
            ]],
            ['components' => ['Flat Screen TV', 'AVP'], 'issues' => ['No display'], 'checks' => [
                ['try', 'Is it turned on?', 'The power light should be on.'],
                $source,
                $hdmi,
                $classNow,
            ]],
            ['components' => ['Projector'], 'issues' => ['No display'], 'checks' => [
                ['try', 'Is the projector turned on and the lens cap off?', null],
                ['try', 'Is the source set to the right input (HDMI or VGA)?', 'Press Source on the remote or on the projector.'],
                $hdmi,
                $laptop,
                $classNow,
            ]],
            ['components' => ['Flat Screen TV', 'AVP'], 'issues' => ['No sound'], 'checks' => [
                ['try', 'Is the volume turned up and not muted?', null],
                ['try', 'Is the audio or HDMI cable plugged in firmly?', null],
            ]],
            ['components' => ['Flat Screen TV'], 'issues' => ['HDMI not detected'], 'checks' => [$hdmi, $source, $laptop, $classNow]],
            ['components' => ['AVP'], 'issues' => ['Input not detected'], 'checks' => [$hdmi, $source, $laptop, $classNow]],
            ['components' => ['Projector'], 'issues' => ['Overheating'], 'checks' => [
                $sparks,
                ['try', "Are the projector's air vents clear?", 'Nothing should block the vents. Turn it off and let it cool for 10 minutes.'],
            ]],
            ['components' => ['Projector'], 'issues' => ['Lamp issue'], 'checks' => [$classNow]],
            ['components' => ['Flat Screen TV', 'Projector', 'Split Type', 'Window Type', 'Floor Standing'], 'issues' => ['Remote not working'], 'checks' => $remote],

            // Fans
            ['components' => ['Electric Fan', 'Ceiling Fan', 'Wall Fan'], 'issues' => ['Not spinning'], 'checks' => [
                ['try', 'Is it plugged in and switched on?', 'Check the plug, the wall switch, and the speed knob.'],
                $outlet,
            ]],
            ['components' => ['Electric Fan', 'Ceiling Fan', 'Wall Fan'], 'issues' => ['No power'], 'checks' => [
                $sparks,
                ['try', 'Is it plugged in and switched on?', 'Check the plug, the wall switch, and the speed knob.'],
                $outlet,
            ]],
            ['components' => ['Ceiling Fan'], 'issues' => ['Wobbly blades'], 'checks' => [$mayFall]],
            ['components' => ['Wall Fan'], 'issues' => ['Loose mount'], 'checks' => [$mayFall]],

            // Air conditioners
            ['components' => ['Split Type', 'Window Type', 'Floor Standing'], 'issues' => ['Not cooling'], 'checks' => [
                ['try', 'Is it set to Cool mode (not Fan or Dry)?', null],
                ['try', 'Is the temperature set to 24°C or lower?', null],
                ['try', 'Are the doors and windows closed?', null],
                $classNow,
            ]],
            ['components' => ['Split Type', 'Window Type', 'Floor Standing'], 'issues' => ['Not turning on'], 'checks' => [
                ['try', 'Is its outlet or circuit breaker switched on?', null],
                ['try', "Did you replace the remote's batteries?", null],
                $classNow,
            ]],
            ['components' => ['Split Type', 'Window Type', 'Floor Standing'], 'issues' => ['Water leakage'], 'checks' => [
                ['ask', 'Is water dripping near an outlet, wiring, or other equipment?', "If yes, keep people away and don't touch the outlet.", $critical, null],
            ]],

            // Lights
            ['components' => ['Fluorescent', 'LED Bulb', 'CFL Bulb'], 'issues' => ['Not turning on'], 'checks' => [
                ['try', 'Is the wall switch turned on?', null],
                $otherLightsOut,
            ]],
            ['components' => ['Fluorescent', 'LED Bulb', 'CFL Bulb'], 'issues' => ['Burnt out', 'Flickering'], 'checks' => [$otherLightsOut]],

            // Printer
            ['components' => ['Printer'], 'issues' => ['Paper jam'], 'checks' => [
                ['try', 'Did you open the cover and gently pull out the stuck paper?', 'Turn the printer off first. Pull the paper slowly in the direction it moves.'],
            ]],
            ['components' => ['Printer'], 'issues' => ['Printer offline'], 'checks' => [
                ['try', 'Is the printer turned on?', null],
                ['try', 'Is its USB or network cable plugged in firmly?', null],
                ['try', 'Did you restart the printer and the computer?', null],
            ]],
            ['components' => ['Printer'], 'issues' => ['Not printing'], 'checks' => [
                ['try', 'Is there paper in the tray?', null],
                ['try', 'Is the right printer selected when you print?', null],
                ['try', 'Did you restart the printer?', null],
            ]],

            // Issues that show for every item in a category
            ['components' => ['*'], 'issues' => ['No Power'], 'checks' => [$sparks, $plugged, $outlet]],
            ['components' => ['*'], 'issues' => ['Aircon Not Cooling'], 'checks' => [
                ['try', 'Is it set to Cool mode (not Fan or Dry)?', null],
                ['try', 'Is the temperature set to 24°C or lower?', null],
                ['try', 'Are the doors and windows closed?', null],
                $classNow,
            ]],
            ['components' => ['*'], 'issues' => ['Aircon Not Turning On'], 'checks' => [
                ['try', 'Is its outlet or circuit breaker switched on?', null],
                ['try', "Did you replace the remote's batteries?", null],
                $classNow,
            ]],
            ['components' => ['*'], 'issues' => ['Water Leakage'], 'checks' => [
                ['ask', 'Is water dripping near an outlet, wiring, or other equipment?', "If yes, keep people away and don't touch the outlet.", $critical, null],
            ]],
            ['components' => ['*'], 'issues' => ['Remote Control Not Working'], 'checks' => $remote],
            ['components' => ['*'], 'issues' => ['HDMI Not Detected'], 'checks' => [$hdmi, $source, $laptop, $classNow]],
            ['components' => ['*'], 'issues' => ['Projector Overheating'], 'checks' => [
                $sparks,
                ['try', "Are the projector's air vents clear?", 'Nothing should block the vents. Turn it off and let it cool for 10 minutes.'],
            ]],
            ['components' => ['*'], 'issues' => ['Cannot Login'], 'checks' => [
                ['try', 'Is Caps Lock off, and are you using the right username and password?', null],
                $otherComputer,
            ]],
            ['components' => ['*'], 'issues' => ['Network Connection Lost'], 'checks' => [
                ['try', 'Is the network (LAN) cable clicked in at the back of the system unit?', 'Its small light should blink when it is connected.'],
                $restart,
                $othersOffline,
            ]],
            ['components' => ['*'], 'issues' => ['Slow Performance'], 'checks' => [
                ['try', 'Did you close the programs you are not using?', null],
                $restart,
            ]],
            ['components' => ['*'], 'issues' => ['Paper Jam'], 'checks' => [
                ['try', 'Did you open the cover and gently pull out the stuck paper?', 'Turn the printer off first. Pull the paper slowly in the direction it moves.'],
            ]],
            ['components' => ['*'], 'issues' => ['Printer Offline'], 'checks' => [
                ['try', 'Is the printer turned on?', null],
                ['try', 'Is its USB or network cable plugged in firmly?', null],
                ['try', 'Did you restart the printer and the computer?', null],
            ]],
            ['components' => ['*'], 'issues' => ['Not Printing'], 'checks' => [
                ['try', 'Is there paper in the tray?', null],
                ['try', 'Is the right printer selected when you print?', null],
                ['try', 'Did you restart the printer?', null],
            ]],

            // Furniture and fixtures
            ['components' => ['Door Knob'], 'issues' => ['Stuck', 'Broken latch'], 'checks' => [
                ['ask', 'Is someone locked inside or unable to leave the room?', null, $critical, null],
            ]],
            ['components' => ['Chair', 'Table'], 'issues' => ['Unstable / wobbly', 'Broken frame', 'Broken leg'], 'checks' => [$mayHurt]],
        ];
    }
}
