<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ReceivingController extends Controller
{
    public function dashboard(): View
    {
        return view('receiving-officer.dashboard');
    }

    public function reports(): View
    {
        return view('receiving-officer.receiving-reports.index');
    }

    public function deliveredItems(): View
    {
        return view('receiving-officer.receiving-reports.delivered-items');
    }

    public function inventoryUpdate(): View
    {
        return view('receiving-officer.receiving-reports.inventory-update');
    }

    public function officialReceipts(): View
    {
        return view('receiving-officer.receiving-reports.official-receipts');
    }

    public function supplierRecords(): View
    {
        return view('receiving-officer.receiving-reports.supplier-records');
    }

    public function history(): View
    {
        return view('receiving-officer.receiving-reports.receiving-history');
    }

    public function receivingLogs(): View
    {
        return view('receiving-officer.receiving-reports.receiving-logs');
    }
}