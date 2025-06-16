<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;

class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        // $this->validate($request, [
        //     'endpoint' => 'required',
        //     'public_key' => 'required',
        //     'auth_token' => 'required',
        //     'device_token' => 'required'
        // ]);

        $subscription = PushSubscription::updateOrCreate(
            ['endpoint' => $request->endpoint],
            [
                'user_id' => auth()->id(),
                'public_key' => $request->public_key,
                'auth_token' => $request->auth_token,
                'device_token' => $request->device_token
            ]
        );

        Log::info('Suscripción guardada exitosamente', ['subscription' => $subscription]);

        return response()->json(['message' => 'Suscripción guardada exitosamente']);
    }

    public function destroy(Request $request)
    {
        $this->validate($request, ['endpoint' => 'required']);

        PushSubscription::where('endpoint', $request->endpoint)
            ->where('user_id', auth()->id())
            ->delete();

        return response()->json(['message' => 'Suscripción eliminada exitosamente']);
    }
}
