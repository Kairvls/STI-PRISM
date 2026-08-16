<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SuggestedIssues
{
    public static function surveyCategories(): array
    {
        return [
            'Furniture and Fixtures',
            'Audio Visual Equipment',
            'Computer Equipment',
        ];
    }

    public static function components(): array
    {
        return [
            'Chair',
            'Whiteboard',
            'Table',
            'Curtains',
            'Door Knob',
            'Electric Fan',
            'Ceiling Fan',
            'Wall Fan',
            'Split Type',
            'Window Type',
            'Floor Standing',
            'Flat Screen TV',
            'Projector',
            'Printer',
            'Fluorescent',
            'LED Bulb',
            'CFL Bulb',
            'Monitor',
            'Mouse',
            'Keyboard',
            'System Unit',
            'Headset',
            'AVP',
            'UPS / AVR',
            'Ethernet Cable',
        ];
    }

    public static function componentNeedles(): array
    {
        return [
            'Headset' => ['headset', 'headphone'],
            'AVP' => ['avp'],
            'Mouse' => ['mouse'],
            'Keyboard' => ['keyboard'],
            'Monitor' => ['monitor'],
            'UPS / AVR' => ['ups', 'avr'],
            'System Unit' => ['system unit', 'desktop', 'cpu', 'system'],
            'Chair' => ['office chair', 'monoblock', 'stool', 'arm desk', 'stall chair', 'chair'],
            'Whiteboard' => ['white board', 'whiteboard'],
            'Table' => ['office table', 'classroom table', 'classrom table', 'laboratory table', 'long table', 'table'],
            'Curtains' => ['curtain'],
            'Door Knob' => ['door knob', 'doorknob'],
            'Ceiling Fan' => ['ceiling fan'],
            'Wall Fan' => ['wall fan'],
            'Electric Fan' => ['electric fan', 'fan'],
            'Floor Standing' => ['floor standing'],
            'Window Type' => ['window air', 'window type'],
            'Split Type' => ['split type', 'split-type', 'aircon', 'air conditioner'],
            'Projector' => ['projector'],
            'Flat Screen TV' => ['flat screen', 'television', 'tv'],
            'Ethernet Cable' => ['ethernet', 'internet cable', 'lan cable', 'network cable'],
            'CFL Bulb' => ['compact fluorescent', 'cfl'],
            'Fluorescent' => ['fluorescent', 'flourescent'],
            'LED Bulb' => ['led light', 'led bulb', 'bulb'],
            'Printer' => ['printer'],
        ];
    }

    public static function detectComponent(?string $equipmentName): ?string
    {
        $name = strtolower(trim((string) $equipmentName));

        if ($name === '') {
            return null;
        }

        foreach (self::componentNeedles() as $component => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($name, $needle)) {
                    return $component;
                }
            }
        }

        if (str_contains($name, 'computer') && !str_contains($name, 'computer laboratory')) {
            return 'System Unit';
        }

        return null;
    }

    public static function detectCategory(?string $equipmentName): ?string
    {
        $component = self::detectComponent($equipmentName);

        if (!$component) {
            return null;
        }

        foreach (self::defaultIssues() as $category => $components) {
            if (array_key_exists($component, $components)) {
                return $category;
            }
        }

        return null;
    }

    public static function categoryDetectPayload($categories): array
    {
        $ids = [];

        foreach ($categories as $category) {
            $ids[$category->equipment_category_name] = (int) $category->equipment_category_id;
        }

        $componentToCategory = [];

        foreach (self::defaultIssues() as $category => $components) {
            foreach (array_keys($components) as $component) {
                $componentToCategory[$component] = $category;
            }
        }

        $rules = [];

        foreach (self::componentNeedles() as $component => $needles) {
            if (!isset($componentToCategory[$component])) {
                continue;
            }

            $rules[] = [
                'needles' => $needles,
                'category' => $componentToCategory[$component],
            ];
        }

        $rules[] = [
            'needles' => ['computer'],
            'except' => ['computer laboratory'],
            'category' => 'Computer Equipment',
        ];

        return [
            'ids' => $ids,
            'rules' => $rules,
        ];
    }

    public static function namesForEquipment(object $equipment)
    {
        $query = DB::table('issue_templates_table')
            ->where('issue_template_category_id', $equipment->equipment_category_id)
            ->orderBy('issue_template_name');

        if (!Schema::hasColumn('issue_templates_table', 'issue_template_component')) {
            return $query->pluck('issue_template_name');
        }

        $component = self::detectComponent($equipment->equipment_name ?? '');

        if ($component) {
            $query->where('issue_template_component', $component);
        } else {
            $query->where(function ($inner) {
                $inner
                    ->whereNull('issue_template_component')
                    ->orWhere('issue_template_component', '');
            });
        }

        return $query->pluck('issue_template_name');
    }

    public static function defaultIssues(): array
    {
        return [
            'Furniture and Fixtures' => [
                'Chair' => ['Unstable / wobbly', 'Broken frame', 'Damaged seat or backrest', 'Missing parts'],
                'Whiteboard' => ['Stained / hard to erase', 'Unstable stand', 'Broken tray', 'Surface damaged'],
                'Table' => ['Unstable / wobbly', 'Damaged surface', 'Broken leg', 'Missing parts'],
                'Curtains' => ['Torn', 'Will not open or close', 'Missing hooks or rail'],
                'Door Knob' => ['Loose', 'Stuck', 'Broken latch', 'Missing parts'],
            ],
            'Audio Visual Equipment' => [
                'Electric Fan' => ['Not spinning', 'No power', 'Noisy', 'Oscillation not working'],
                'Ceiling Fan' => ['Not spinning', 'No power', 'Noisy', 'Wobbly blades'],
                'Wall Fan' => ['Not spinning', 'No power', 'Noisy', 'Loose mount'],
                'Split Type' => ['Not cooling', 'Water leakage', 'Not turning on', 'Strange noise', 'Remote not working'],
                'Window Type' => ['Not cooling', 'Water leakage', 'Not turning on', 'Strange noise', 'Remote not working'],
                'Floor Standing' => ['Not cooling', 'Water leakage', 'Not turning on', 'Strange noise', 'Remote not working'],
                'Flat Screen TV' => ['No power', 'No display', 'No sound', 'HDMI not detected', 'Remote not working'],
                'Projector' => ['No power', 'No display', 'Lamp issue', 'Overheating', 'Remote not working'],
                'Fluorescent' => ['Burnt out', 'Flickering', 'Not turning on'],
                'LED Bulb' => ['Burnt out', 'Flickering', 'Not turning on'],
                'CFL Bulb' => ['Burnt out', 'Flickering', 'Not turning on'],
                'Printer' => ['Paper jam', 'Ink or toner issue', 'Printer offline', 'Not printing', 'Poor print quality'],
            ],
            'Computer Equipment' => [
                'Monitor' => ['Broken monitor', 'No display', 'Flickering screen', 'Blurry display'],
                'Keyboard' => ['Keyboard not working', 'Missing keys', 'Sticky keys'],
                'Mouse' => ['Mouse defective', 'Mouse not detected', 'Scroll wheel broken'],
                'System Unit' => ['No power', 'Slow performance', 'Cannot login', 'Network connection lost', 'Not booting', 'Overheating'],
                'Headset' => ['No sound', 'Microphone not working', 'Broken headband'],
                'AVP' => ['No power', 'No display', 'No sound', 'Input not detected'],
                'UPS / AVR' => ['No backup power', 'Not turning on', 'Beeping continuously'],
                'Ethernet Cable' => ['Damaged cable', 'Loose connection', 'No internet'],
            ],
        ];
    }
}
