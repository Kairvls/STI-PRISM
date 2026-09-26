<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class EquipmentAuditPack
{
    public static function download(int $equipmentId): Response
    {
        $timeline = EquipmentTimeline::forEquipment($equipmentId);
        $equipment = $timeline['equipment'] ?? null;

        if (! $equipment) {
            abort(404, 'Equipment not found.');
        }

        $pdf = Pdf::loadView('maintenance-personnel.equipment.audit-pack-pdf', [
            'equipment' => $equipment,
            'events' => $timeline['events'] ?? [],
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        $safeName = preg_replace('/[^A-Za-z0-9\-_]+/', '-', (string) ($equipment['name'] ?? 'equipment'));
        $filename = 'equipment-audit-'.$equipmentId.'-'.trim($safeName, '-').'.pdf';

        return $pdf->download($filename);
    }
}
