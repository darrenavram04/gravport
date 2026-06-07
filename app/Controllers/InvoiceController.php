<?php

namespace App\Controllers;

use App\Models\InvoiceModel;
use App\Models\SubscriptionModel;

/**
 * InvoiceController — Invoice Management
 *
 * Routes:
 *   GET  /account/invoice              → listForUser()  — user sees own invoices
 *   GET  /account/invoice/{id}         → show()         — HTML invoice (printable)
 *   POST /admin/invoice/generate       → generate()     — superadmin generates invoice
 *   POST /admin/invoice/{id}/pay       → markPaid()     — superadmin marks paid
 */
class InvoiceController extends BaseController
{
    private InvoiceModel     $invoices;
    private SubscriptionModel $subscriptions;

    public function __construct()
    {
        $this->invoices      = new InvoiceModel();
        $this->subscriptions = new SubscriptionModel();
    }

    // ────────────────────────────────────────────────────────────────
    // GET /account/invoice — list user's invoices
    // ────────────────────────────────────────────────────────────────
    public function listForUser()
    {
        $userId   = (int)(session()->get('user_id') ?? 0);
        $invoices = $this->invoices->allForUser($userId);

        return view('v_invoices', [
            'invoices'   => $invoices,
            'activePage' => 'account',
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // GET /account/invoice/{id} — HTML printable invoice
    // ────────────────────────────────────────────────────────────────
    public function show(int $invoiceId)
    {
        $userId  = (int)(session()->get('user_id') ?? 0);
        $invoice = $this->invoices->find($invoiceId);

        if (empty($invoice)) {
            return redirect()->to(site_url('account/invoice'))
                ->with('error', 'Invoice tidak ditemukan.');
        }

        // Security: user can only see their own invoices (superadmin can see all)
        $role = session()->get('role') ?? 'guest';
        if ($invoice['user_id'] !== $userId && $role !== 'superadmin') {
            return redirect()->to(site_url('account'))
                ->with('error', 'Akses ditolak.');
        }

        // Load user info
        $db   = \Config\Database::connect();
        $user = $db->query(
            'SELECT acc_name AS full_name, acc_email AS email FROM geoportal.accounts WHERE acc_id = ?',
            [(int)($invoice['acc_id'] ?? $invoice['user_id'] ?? 0)]
        )->getRowArray();

        return view('v_invoice_detail', [
            'invoice'    => $invoice,
            'user'       => $user ?? ['full_name' => 'N/A', 'email' => 'N/A'],
            'activePage' => 'account',
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // POST /admin/invoice/generate — Superadmin generates invoice for user
    // ────────────────────────────────────────────────────────────────
    public function generate()
    {
        $adminUserId = (int)(session()->get('user_id') ?? 0);
        $targetUserId = (int)($this->request->getPost('user_id') ?? 0);
        $subId       = (int)($this->request->getPost('subscription_id') ?? 0);
        $tierName    = $this->request->getPost('tier_name') ?? '';
        $subtotal    = (float)($this->request->getPost('subtotal') ?? 0);
        $cycle       = $this->request->getPost('billing_cycle') ?? 'monthly';
        $notes       = $this->request->getPost('notes') ?? '';

        if (!$targetUserId || !$subtotal || !$tierName) {
            return redirect()->back()->with('error', 'Data tidak lengkap. Pastikan user_id, tier_name, dan subtotal diisi.');
        }

        $invoice = $this->invoices->generate(
            $targetUserId,
            $subId ?: null,
            $tierName,
            $subtotal,
            $cycle,
            $adminUserId,
            $notes
        );

        return redirect()->to(site_url('account/invoice/' . $invoice['invoice_id']))
            ->with('success', "Invoice {$invoice['invoice_number']} berhasil dibuat.");
    }

    // ────────────────────────────────────────────────────────────────
    // POST /admin/invoice/{id}/pay — Mark invoice as paid
    // ────────────────────────────────────────────────────────────────
    public function markPaid(int $invoiceId)
    {
        $this->invoices->markPaid($invoiceId);
        return redirect()->back()->with('success', "Invoice #{$invoiceId} ditandai lunas.");
    }
}
