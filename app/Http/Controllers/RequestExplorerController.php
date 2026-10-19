<?php

namespace App\Http\Controllers;

use App\Support\RequestExplorer;
use App\Support\RoleAccess;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RequestExplorerController extends Controller
{
    public function show(Request $request, string $requestNo): View
    {
        $normalized = RequestExplorer::normalize($requestNo);
        $explorer = RequestExplorer::find($normalized);

        if ($request->hasSession()) {
            $portal = RoleAccess::currentPortalKey()
                ?? $request->session()->get(RoleAccess::PORTAL_SESSION_KEY);
            if (is_string($portal) && $portal !== '') {
                RoleAccess::enterPortal($portal);
            }
        }

        return view('requests.show', [
            'requestNo' => $normalized,
            'explorer' => $explorer,
            'layout' => RequestExplorer::layoutForPortal(),
            'stageLabels' => RequestExplorer::STAGE_LABELS,
            'stageOrder' => RequestExplorer::STAGE_ORDER,
        ]);
    }
}
