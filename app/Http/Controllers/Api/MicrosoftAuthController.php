<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MicrosoftAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'access_token' => ['required'],
        ]);

        $accessToken = $request->access_token;

        // =====================================================
        // VERIFY TOKEN WITH MICROSOFT GRAPH
        // =====================================================

        $graphResponse = Http::withToken($accessToken)
            ->get('https://graph.microsoft.com/v1.0/me');

        if (!$graphResponse->successful()) {

            return response()->json([
                'message' => 'Invalid Microsoft token.',
            ], 401);

        }

        $graphUser = $graphResponse->json();

        // =====================================================
        // GET EMAIL
        // =====================================================

        $email =
            $graphUser['mail']
            ?? $graphUser['userPrincipalName']
            ?? null;

        if (!$email) {

            return response()->json([
                'message' => 'Unable to retrieve Microsoft email.',
            ], 400);

        }

        // =====================================================
        // FIND USER
        // =====================================================

        $user = User::where(
            'user_email_address',
            $email
        )->first();

        if (!$user) {

            return response()->json([
                'message' => 'Account not found.',
            ], 404);

        }

        // =====================================================
        // ROLE CHECK (primary OR additional Maintenance)
        // =====================================================

        if (! \App\Support\RoleAccess::hasRole(
            \App\Support\RoleAccess::MAINTENANCE,
            $user
        )) {

            return response()->json([
                'message' => 'Only Maintenance Personnel can use this app.',
            ], 403);

        }

        $roleIds = \App\Support\RoleAccess::roleIds($user);
        $mobilePortals = \App\Support\RoleAccess::mobilePortals($user);
        $primaryRoleId = (int) $user->user_role_id;
        $activeRoleId = \App\Support\RoleAccess::MAINTENANCE;

        // =====================================================
        // CREATE SANCTUM TOKEN
        // =====================================================

        $token = $user
            ->createToken('mobile')
            ->plainTextToken;

        // =====================================================
        // RESPONSE
        // =====================================================

        return response()->json([

            'user' => [

                'id' => $user->user_id,

                'name' => $user->user_full_name,

                'email' => $user->user_email_address,

                // Primary role (web default dashboard). Kept for backward compat.
                'role' => $primaryRoleId,

                // Active mobile portal role (primary if mobile-eligible, else first).
                'active_role' => (int) $activeRoleId,

                // All assigned role IDs (primary + additional).
                'roles' => $roleIds,

            ],

            'mobile_portals' => array_map(static function (array $portal) {
                return [
                    'role_id' => (int) $portal['role_id'],
                    'key' => $portal['key'],
                    'label' => $portal['label'],
                ];
            }, $mobilePortals),

            'token' => $token,

        ]);

    }

    public function logout(Request $request)
    {
        $request
            ->user()
            ?->currentAccessToken()
            ?->delete();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }
}