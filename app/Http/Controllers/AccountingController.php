<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AccountingController extends Controller
{
    public function dashboard(): View
    {
        return view('accounting.dashboard');
    }

    public function requestCheck(): View
    {
        return view('accounting.request-check.index');
    }

    public function authorityToPurchase(): View
    {
        return view('accounting.authority-to-purchase.index');
    }

    public function financialRecords(): View
    {
        return view('accounting.financial-records.index');
    }

    public function liquidationReports(): View
    {
        return view('accounting.liquidation-reports.index');
    }

    public function notifications(): View
    {
        return view('accounting.notifications.index');
    }

    
}