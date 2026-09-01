<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PurchaserUrgentReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobilePurchaserController extends Controller
{
    public function summary(PurchaserUrgentReportService $reports): JsonResponse
    {
        return response()->json($reports->summary());
    }

    public function listReports(Request $request, PurchaserUrgentReportService $reports): JsonResponse
    {
        $payload = $reports->listReports([
            'search' => $request->query('search'),
            'status' => $request->query('status'),
            'archive' => $request->boolean('archive'),
            'limit' => $request->query('limit', 50),
        ]);

        return response()->json($payload);
    }

    public function showReport(int $id, PurchaserUrgentReportService $reports): JsonResponse
    {
        $payload = $reports->getReport($id);

        if (! ($payload['success'] ?? false)) {
            return response()->json($payload, 404);
        }

        return response()->json($payload);
    }

    public function acceptReport(int $id, PurchaserUrgentReportService $reports): JsonResponse
    {
        $payload = $reports->acceptReport($id, (int) request()->user()->user_id);
        $status = ($payload['success'] ?? false) ? 200 : 422;

        return response()->json($payload, $status);
    }

    public function resolveReport(Request $request, int $id, PurchaserUrgentReportService $reports): JsonResponse
    {
        $validated = $request->validate([
            'resolution_notes' => 'nullable|string|max:5000',
            'resolution_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'report_item_ids' => 'nullable|array',
            'report_item_ids.*' => 'integer',
        ]);

        $imagePath = null;

        if ($request->hasFile('resolution_image')) {
            $imagePath = $request
                ->file('resolution_image')
                ->store('report-resolutions', 'public');
        }

        $payload = $reports->resolveReport(
            $id,
            (int) $request->user()->user_id,
            $validated['resolution_notes'] ?? null,
            $imagePath,
            $validated['report_item_ids'] ?? []
        );

        $status = ($payload['success'] ?? false) ? 200 : 422;

        return response()->json($payload, $status);
    }

    public function replaceReport(Request $request, int $id, PurchaserUrgentReportService $reports): JsonResponse
    {
        $validated = $request->validate([
            'replacement_notes' => 'required|string|max:5000',
            'replacement_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'report_item_ids' => 'nullable|array',
            'report_item_ids.*' => 'integer',
        ]);

        $imagePath = null;

        if ($request->hasFile('replacement_image')) {
            $imagePath = $request
                ->file('replacement_image')
                ->store('report-replacements', 'public');
        }

        $payload = $reports->replaceReport(
            $id,
            (int) $request->user()->user_id,
            $validated['replacement_notes'],
            $imagePath,
            $validated['report_item_ids'] ?? []
        );

        $status = ($payload['success'] ?? false) ? 200 : 422;

        return response()->json($payload, $status);
    }

    public function rejectReport(Request $request, int $id, PurchaserUrgentReportService $reports): JsonResponse
    {
        $validated = $request->validate([
            'rejection_notes' => 'required|string|max:5000',
        ]);

        $payload = $reports->rejectReport($id, $validated['rejection_notes']);
        $status = ($payload['success'] ?? false) ? 200 : 422;

        return response()->json($payload, $status);
    }

    public function archiveReport(int $id, PurchaserUrgentReportService $reports): JsonResponse
    {
        $payload = $reports->archiveReport($id, (int) request()->user()->user_id);
        $status = ($payload['success'] ?? false) ? 200 : 422;

        return response()->json($payload, $status);
    }

    public function restoreReport(int $id, PurchaserUrgentReportService $reports): JsonResponse
    {
        $payload = $reports->restoreReport($id, (int) request()->user()->user_id);
        $status = ($payload['success'] ?? false) ? 200 : 422;

        return response()->json($payload, $status);
    }
}
