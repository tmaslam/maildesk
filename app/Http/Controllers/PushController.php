<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;

class PushController extends Controller
{
    public function subscribe(Request $request)
    {
        $data = $request->validate(['endpoint' => ['required', 'url', 'starts_with:https://', 'max:2000']]);
        // Only real browser push services — the server POSTs to this URL later.
        $host = strtolower((string) parse_url($data['endpoint'], PHP_URL_HOST));
        $allowed = ['fcm.googleapis.com', 'updates.push.services.mozilla.com'];
        $ok = in_array($host, $allowed, true)
            || str_ends_with($host, '.push.apple.com')
            || str_ends_with($host, '.notify.windows.com');
        abort_unless($ok, 422, 'Unsupported push service');

        PushSubscription::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $data['endpoint'])],
            [
                'user_id'    => $request->user()->id,
                'endpoint'   => $data['endpoint'],
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            ]
        );
        return response()->json(['ok' => true]);
    }

    public function unsubscribe(Request $request)
    {
        $endpoint = (string) $request->input('endpoint');
        PushSubscription::where('endpoint_hash', hash('sha256', $endpoint))
            ->where('user_id', $request->user()->id)
            ->delete();
        return response()->json(['ok' => true]);
    }
}
