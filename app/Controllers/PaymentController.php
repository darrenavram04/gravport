<?php

namespace App\Controllers;

use App\Libraries\MidtransService;
use App\Models\PendingRegistrationModel;

/**
 * PaymentController — Midtrans Snap payment flow
 *
 * Routes:
 *   GET  /payment/pay/(individual|team)/(:num)  → pay()      show Snap popup
 *   POST /payment/webhook                        → webhook()  Midtrans notification (CSRF-exempt)
 *   GET  /payment/finish                         → finish()   post-payment landing page
 */
class PaymentController extends BaseController
{
    private MidtransService         $midtrans;
    private PendingRegistrationModel $pending;

    public function __construct()
    {
        $this->midtrans = new MidtransService();
        $this->pending  = new PendingRegistrationModel();
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /payment/pay/(individual|team)/{pending_id}
    // Ambil pending record, buat Snap token, render payment page.
    // ─────────────────────────────────────────────────────────────────

    public function pay(string $type, int $pendingId)
    {
        if ($type === 'individual') {
            $record = $this->pending->findById($pendingId);
        } else {
            $record = $this->pending->findOrgById($pendingId);
        }

        if (empty($record) || $record['status'] !== 'pending_payment') {
            return redirect()->to(site_url('signup'))
                ->with('error', 'Sesi pendaftaran tidak ditemukan atau sudah kadaluarsa. Silakan daftar ulang.');
        }

        try {
            if ($type === 'individual') {
                $result = $this->midtrans->snapTokenForIndividual($pendingId, [
                    'full_name'     => $record['full_name'],
                    'email'         => $record['email'],
                    'tier_name'     => $record['tier_name'],
                    'billing_cycle' => $record['billing_cycle'],
                ]);
            } else {
                $result = $this->midtrans->snapTokenForTeam($pendingId, [
                    'contact_name'  => $record['contact_name'],
                    'org_email'     => $record['org_email'],
                    'billing_cycle' => $record['billing_cycle'],
                    'seat_count'    => $record['seat_count'],
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', '[PaymentController::pay] Snap token error: ' . $e->getMessage());
            return redirect()->to(site_url('signup'))
                ->with('error', 'Gagal memuat halaman pembayaran. Coba lagi atau hubungi support.');
        }

        // Simpan order_id ke pending record agar webhook bisa match
        $this->pending->setOrderId($pendingId, $result['order_id'], $type);

        return view('v_payment', [
            'snap_token'  => $result['snap_token'],
            'client_key'  => MidtransService::clientKey(),
            'snap_js_url' => MidtransService::snapJsUrl(),
            'order_id'    => $result['order_id'],
            'amount'      => $result['amount'],
            'pending_id'  => $pendingId,
            'type'        => $type,
            'record'      => $record,
            'finish_url'  => site_url('payment/finish'),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /payment/webhook
    // Terima notifikasi Midtrans, verifikasi signature, aktifkan akun.
    // Route ini CSRF-exempt (dikonfigurasi di Config/Security.php).
    // ─────────────────────────────────────────────────────────────────

    public function webhook()
    {
        try {
            $payload = $this->midtrans->verifyNotification();
        } catch (\Throwable $e) {
            log_message('warning', '[PaymentController::webhook] Signature check failed: ' . $e->getMessage());
            return $this->response->setStatusCode(403)->setBody('Forbidden');
        }

        $orderId = $payload['order_id'];
        $status  = $payload['transaction_status'];
        $fraud   = $payload['fraud_status'];

        $isSuccess = ($status === 'settlement')
            || ($status === 'capture' && $fraud === 'accept');

        if (!$isSuccess) {
            log_message('info', '[PaymentController::webhook] Non-success status: order=' . $orderId . ' status=' . $status);
            return $this->response->setStatusCode(200)->setBody('OK');
        }

        // Format order_id: GRAV-INDV-{id}-{timestamp} atau GRAV-TEAM-{id}-{timestamp}
        if (!preg_match('/^GRAV-(INDV|TEAM)-(\d+)-\d+$/', $orderId, $m)) {
            log_message('warning', '[PaymentController::webhook] Unrecognized order_id format: ' . $orderId);
            return $this->response->setStatusCode(200)->setBody('OK');
        }

        $type      = $m[1] === 'INDV' ? 'individual' : 'team';
        $pendingId = (int) $m[2];

        // Aktifkan akun — pakai RegistrationController yang sudah ada
        $reg = new RegistrationController();
        $ok  = $type === 'individual'
            ? $reg->activateIndividual($pendingId, 0)
            : $reg->activateTeam($pendingId, 0);

        if ($ok) {
            log_message('info', '[PaymentController::webhook] Activated ' . $type . ' #' . $pendingId . ' via order ' . $orderId);
        } else {
            log_message('warning', '[PaymentController::webhook] activateIndividual/Team returned false for #' . $pendingId . ' (mungkin sudah diaktifkan sebelumnya)');
        }

        return $this->response->setStatusCode(200)->setBody('OK');
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /payment/finish — Midtrans redirect setelah pembayaran
    // ─────────────────────────────────────────────────────────────────

    public function finish()
    {
        // Midtrans mengirim parameter result via query string
        $transactionStatus = $this->request->getGet('transaction_status') ?? '';
        $orderId           = $this->request->getGet('order_id') ?? '';

        return view('v_payment_finish', [
            'transaction_status' => $transactionStatus,
            'order_id'           => $orderId,
            'login_url'          => site_url('login'),
        ]);
    }
}
