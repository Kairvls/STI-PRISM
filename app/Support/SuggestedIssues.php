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
            'Ventilation Equipment',
            'Air Conditioning Equipment',
            'Display Equipment',
            'Computer Equipment',
            'Network Equipment',
            'Lighting Equipment',
            'Printing Equipment',
        ];
    }

    public static function components(): array
    {
        return [
            'Chair',
            'Whiteboard',
            'Table',
            'Curtains',
            'Electric Fan',
            'Ceiling Fan',
            'Wall Fan',
            'Split Type',
            'Window Type',
            'Floor Standing',
            'Flat Screen TV',
            'Monitor',
            'Mouse',
            'Keyboard',
            'System Unit',
            'UPS / AVR',
            'Ethernet Cable',
            'Fluorescent',
            'LED Bulb',
            'CFL Bulb',
            'Printer',
        ];
    }

    public static function detectComponent(?string $equipmentName): ?string
    {
        $name = strtolower(trim((string) $equipmentName));

        if ($name === '') {
            return null;
        }

        $map = [
            'Mouse' => ['mouse'],
            'Keyboard' => ['keyboard'],
            'Monitor' => ['monitor'],
            'UPS / AVR' => ['ups', 'avr'],
            'System Unit' => ['system unit', 'desktop', 'cpu', 'system'],
            'Chair' => ['office chair', 'monoblock', 'stool', 'arm desk', 'stall chair', 'chair'],
            'Whiteboard' => ['white board', 'whiteboard'],
            'Table' => ['office table', 'classroom table', 'classrom table', 'laboratory table', 'long table', 'table'],
            'Curtains' => ['curtain'],
            'Ceiling Fan' => ['ceiling fan'],
            'Wall Fan' => ['wall fan'],
            'Electric Fan' => ['electric fan', 'fan'],
            'Floor Standing' => ['floor standing'],
            'Window Type' => ['window air', 'window type'],
            'Split Type' => ['split type', 'split-type'],
            'Flat Screen TV' => ['flat screen', 'television', 'tv'],
            'Ethernet Cable' => ['ethernet', 'internet cable', 'lan cable', 'network cable'],
            'CFL Bulb' => ['compact fluorescent', 'cfl'],
            'Fluorescent' => ['fluorescent', 'flourescent'],
            'LED Bulb' => ['led light', 'led bulb'],
            'Printer' => ['printer'],
        ];

        foreach ($map as $component => $needles) {
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
            ],
            'Ventilation Equipment' => [
                'Electric Fan' => ['Not spinning', 'No power', 'Noisy', 'Oscillation not working'],
                'Ceiling Fan' => ['Not spinning', 'No power', 'Noisy', 'Wobbly blades'],
                'Wall Fan' => ['Not spinning', 'No power', 'Noisy', 'Loose mount'],
            ],
            'Air Conditioning Equipment' => [
                'Split Type' => ['Not cooling', 'Water leakage', 'Not turning on', 'Strange noise', 'Remote not working'],
                'Window Type' => ['Not cooling', 'Water leakage', 'Not turning on', 'Strange noise', 'Remote not working'],
                'Floor Standing' => ['Not cooling', 'Water leakage', 'Not turning on', 'Strange noise', 'Remote not working'],
            ],
            'Display Equipment' => [
                'Flat Screen TV' => ['No power', 'No display', 'No sound', 'HDMI not detected', 'Remote not working'],
            ],
            'Computer Equipment' => [
                'Monitor' => ['Broken monitor', 'No display', 'Flickering screen', 'Blurry display'],
                'Keyboard' => ['Keyboard not working', 'Missing keys', 'Sticky keys'],
                'Mouse' => ['Mouse defective', 'Mouse not detected', 'Scroll wheel broken'],
                'System Unit' => ['No power', 'Slow performance', 'Cannot login', 'Network connection lost', 'Not booting', 'Overheating'],
                'UPS / AVR' => ['No backup power', 'Not turning on', 'Beeping continuously'],
            ],
            'Network Equipment' => [
                'Ethernet Cable' => ['Damaged cable', 'Loose connection', 'No internet'],
            ],
            'Lighting Equipment' => [
                'Fluorescent' => ['Burnt out', 'Flickering', 'Not turning on'],
                'LED Bulb' => ['Burnt out', 'Flickering', 'Not turning on'],
                'CFL Bulb' => ['Burnt out', 'Flickering', 'Not turning on'],
            ],
            'Printing Equipment' => [
                'Printer' => ['Paper jam', 'Ink or toner issue', 'Printer offline', 'Not printing', 'Poor print quality'],
            ],
        ];
    }
}
