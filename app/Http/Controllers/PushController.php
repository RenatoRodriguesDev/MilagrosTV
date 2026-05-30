<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushController extends Controller
{
    public function vapidKey()
    {
        return response()->json(['publicKey' => config('services.vapid.public_key')]);
    }

    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'endpoint'   => 'required|string',
            'publicKey'  => 'nullable|string',
            'authToken'  => 'nullable|string',
        ]);

        DB::table('push_subscriptions')->updateOrInsert(
            ['user_id' => Auth::id(), 'endpoint' => $data['endpoint']],
            ['public_key' => $data['publicKey'], 'auth_token' => $data['authToken'], 'updated_at' => now(), 'created_at' => now()]
        );

        return response()->json(['ok' => true]);
    }

    public function unsubscribe(Request $request)
    {
        DB::table('push_subscriptions')
            ->where('user_id', Auth::id())
            ->where('endpoint', $request->input('endpoint'))
            ->delete();

        return response()->json(['ok' => true]);
    }

    public static function sendToAll(string $title, string $body, ?string $url = null): void
    {
        $vapidPublic  = config('services.vapid.public_key');
        $vapidPrivate = config('services.vapid.private_key');
        if (!$vapidPublic || !$vapidPrivate) return;

        $subs = DB::table('push_subscriptions')->get();
        if ($subs->isEmpty()) return;

        $webPush = new WebPush([
            'VAPID' => [
                'subject'    => config('app.url'),
                'publicKey'  => $vapidPublic,
                'privateKey' => $vapidPrivate,
            ],
        ]);

        $payload = json_encode(['title' => $title, 'body' => $body, 'url' => $url ?? '/']);

        foreach ($subs as $sub) {
            try {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint'        => $sub->endpoint,
                        'keys'            => ['p256dh' => $sub->public_key, 'auth' => $sub->auth_token],
                    ]),
                    $payload
                );
            } catch (\Throwable) {}
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSubscriptionExpired()) {
                DB::table('push_subscriptions')->where('endpoint', $report->getEndpoint())->delete();
            }
        }
    }
}
