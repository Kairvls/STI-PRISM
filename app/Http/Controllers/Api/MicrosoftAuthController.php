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
        // ROLE CHECK
        // =====================================================

        if ($user->user_role_id != 2) {

            return response()->json([
                'message' => 'Only Maintenance Personnel can use this app.',
            ], 403);

        }

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

                'role' => $user->user_role_id,

            ],

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