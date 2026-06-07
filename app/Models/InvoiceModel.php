<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class InvoiceModel
{
    private BaseConnection $db;
    private float $vatPct = 11.00;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function generate(int $accId, ?int $subsId, string $tierName, float $subtotal,
                             string $billingCycle = 'monthly', int $createdBy = 0,
                             string $notes = ''): array
    {
        $vatAmount   = round($subtotal * $this->vatPct / 100, 2);
        $totalAmount = $subtotal + $vatAmount;
        $invoiceNum  = $this->nextInvoiceNumber();
        $dueDate     = date('Y-m-d', strtotime('+14 days'));

        $this->db->query(
            'INSERT INTO geoportal.invoices
             (invoice_number, acc_id, subs_id, billing_cycle, tier_name,
              subtotal, vat_pct, vat_amount, total_amount, due_date, created_by, notes)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $invoiceNum, $accId, $subsId, $billingCycle, $tierName,
                $subtotal, $this->vatPct, $vatAmount, $totalAmount,
                $dueDate, $createdBy ?: null, $notes ?: null,
            ]
        );

        $id = (int) $this->db->query('SELECT lastval() AS id')->getRow()->id;
        return $this->find($id);
    }

    public function find(int $invoiceId): array
    {
        return $this->db->query(
            'SELECT * FROM geoportal.invoices WHERE invoice_id = ?',
            [$invoiceId]
        )->getRowArray() ?? [];
    }

    public function allForUser(int $accId, int $limit = 20): array
    {
        if ($accId <= 0) return [];
        return $this->db->query(
            'SELECT * FROM geoportal.invoices WHERE acc_id = ? ORDER BY issued_at DESC LIMIT ?',
            [$accId, $limit]
        )->getResultArray();
    }

    public function all(int $limit = 100): array
    {
        return $this->db->query(
            'SELECT i.*, a.acc_email AS email, a.acc_name AS full_name
             FROM geoportal.invoices i
             LEFT JOIN geoportal.accounts a ON a.acc_id = i.acc_id
             ORDER BY i.issued_at DESC
             LIMIT ?',
            [$limit]
        )->getResultArray();
    }

    public function markPaid(int $invoiceId): bool
    {
        $this->db->query(
            "UPDATE geoportal.invoices SET status = 'paid', paid_at = now() WHERE invoice_id = ?",
            [$invoiceId]
        );
        return $this->db->affectedRows() > 0;
    }

    public function cancel(int $invoiceId): bool
    {
        $this->db->query(
            "UPDATE geoportal.invoices SET status = 'cancelled' WHERE invoice_id = ?",
            [$invoiceId]
        );
        return $this->db->affectedRows() > 0;
    }

    public function nextInvoiceNumber(): string
    {
        $year = date('Y');
        $row  = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM geoportal.invoices WHERE invoice_number LIKE ?",
            ["INV-{$year}-%"]
        )->getRow();

        $seq = ((int) ($row->cnt ?? 0)) + 1;
        return sprintf('INV-%s-%04d', $year, $seq);
    }

    public static function tierLabel(string $tierName): string
    {
        return match ($tierName) {
            'lite'        => 'Lite',
            'pro'         => 'Pro',
            'team'        => 'Enterprise',
            'enterprise'  => 'Enterprise',
            'government'  => 'Government',
            default       => ucfirst($tierName),
        };
    }
}
