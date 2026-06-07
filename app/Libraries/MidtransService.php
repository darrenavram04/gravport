<?php

namespace App\Libraries;

class MidtransService
{
    private const PRICES = [
        'lite' => ['monthly' => 99000,   'annual' => 990000],
        'solo' => ['monthly' => 99000,   'annual' => 990000],
        'pro'  => ['monthly' => 349000,  'annual' => 3490000],
        'Enterprise' => ['monthly' => 999000,  'annual' => 9990000],
    ];

    private const TIER_LABELS = [
        'solo' => 'Solo',
        'pro'  => 'Pro',
        'Enterprise' => 'Enterprise',
    ];

    public function __construct()
    {
        \Midtrans\Config::$serverKey    = (string) env('midtrans.serverKey', '');
        \Midtrans\Config::$isProduction = (env('midtrans.isProduction', 'false') === 'true');
        \Midtrans\Config::$isSanitized  = true;
        \Midtrans\Config::$is3ds        = true;
    }

    /**
     * Create a Snap token for individual (Solo/Pro) registration.
     * Returns ['order_id' => ..., 'snap_token' => ..., 'amount' => ...]
     */
    public function snapTokenForIndividual(int $pendingId, array $data): array
    {
        $orderId   = 'GRAV-INDV-' . $pendingId . '-' . time();
        $amount    = self::PRICES[$data['tier_name']][$data['billing_cycle']] ?? 99000;
        $tierLabel = self::TIER_LABELS[$data['tier_name']] ?? $data['tier_name'];
        $cycle     = $data['billing_cycle'] === 'annual' ? 'Tahunan' : 'Bulanan';

        $params = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => $amount,
            ],
            'customer_details' => [
                'first_name' => (string) $data['full_name'],
                'email'      => (string) $data['email'],
            ],
            'item_details' => [[
                'id'       => $data['tier_name'] . '_' . $data['billing_cycle'],
                'price'    => $amount,
                'quantity' => 1,
                'name'     => 'GravPort ' . $tierLabel . ' ' . $cycle,
            ]],
            'expiry' => ['duration' => 24, 'unit' => 'hours'],
        ];

        log_message('debug', '[MidtransService] snapTokenForIndividual params: ' . json_encode($params));
        $snapToken = \Midtrans\Snap::getSnapToken($params);

        return ['order_id' => $orderId, 'snap_token' => (string) $snapToken, 'amount' => $amount];
    }

    /**
     * Create a Snap token for team/organization registration.
     */
    public function snapTokenForTeam(int $pendingId, array $data): array
    {
        $orderId  = 'GRAV-TEAM-' . $pendingId . '-' . time();
        $amount   = self::PRICES['Enterprise'][$data['billing_cycle']] ?? 999000;
        $cycle    = $data['billing_cycle'] === 'annual' ? 'Tahunan' : 'Bulanan';
        $seats    = (int) ($data['seat_count'] ?? 1);

        $params = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => $amount,
            ],
            'customer_details' => [
                'first_name' => (string) $data['contact_name'],
                'email'      => (string) $data['org_email'],
            ],
            'item_details' => [[
                'id'       => 'team_' . $data['billing_cycle'],
                'price'    => $amount,
                'quantity' => 1,
                'name'     => 'GravPort Enterprise ' . $cycle . ' (' . $seats . ' akun)',
            ]],
            'expiry' => ['duration' => 24, 'unit' => 'hours'],
        ];

        $snapToken = \Midtrans\Snap::getSnapToken($params);

        return ['order_id' => $orderId, 'snap_token' => (string) $snapToken, 'amount' => $amount];
    }

    /**
     * Verify the incoming Midtrans webhook notification.
     * Returns normalized payload on success; throws on invalid signature.
     */
    public function verifyNotification(): array
    {
        $notif = new \Midtrans\Notification();

        $expected = hash('sha512',
            $notif->order_id
            . $notif->status_code
            . $notif->gross_amount
            . \Midtrans\Config::$serverKey
        );

        if ($expected !== $notif->signature_key) {
            throw new \RuntimeException('Midtrans signature mismatch.');
        }

        return [
            'order_id'           => (string) $notif->order_id,
            'transaction_status' => (string) $notif->transaction_status,
            'fraud_status'       => (string) ($notif->fraud_status ?? 'accept'),
            'payment_type'       => (string) ($notif->payment_type ?? ''),
            'gross_amount'       => (string) ($notif->gross_amount ?? '0'),
        ];
    }

    public static function priceFor(string $tier, string $cycle): int
    {
        return self::PRICES[$tier][$cycle] ?? 0;
    }

    public static function clientKey(): string
    {
        return (string) env('midtrans.clientKey', '');
    }

    public static function isProduction(): bool
    {
        return env('midtrans.isProduction', 'false') === 'true';
    }

    public static function snapJsUrl(): string
    {
        return self::isProduction()
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }
}

