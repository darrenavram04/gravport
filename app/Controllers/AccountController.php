<?php

namespace App\Controllers;

use App\Libraries\MarketplaceService;
use App\Models\ApiKeyModel;
use App\Models\DownloadTransactionModel;
use App\Models\InvoiceModel;
use App\Models\OrganizationModel;
use App\Models\SubscriptionModel;

class AccountController extends BaseController
{
    private MarketplaceService      $marketplace;
    private DownloadTransactionModel $transactions;
    private ApiKeyModel             $apiKeys;
    private InvoiceModel            $invoices;
    private OrganizationModel       $orgs;
    private SubscriptionModel       $subscriptions;

    public function __construct()
    {
        $this->marketplace   = new MarketplaceService();
        $this->transactions  = new DownloadTransactionModel();
        $this->apiKeys       = new ApiKeyModel();
        $this->invoices      = new InvoiceModel();
        $this->orgs          = new OrganizationModel();
        $this->subscriptions = new SubscriptionModel();
    }

    // ────────────────────────────────────────────────────────────────
    // GET /account — Main account dashboard
    // ────────────────────────────────────────────────────────────────
    public function index()
    {
        $userId = (int)(session()->get('user_id') ?? 0);
        $quota  = $this->marketplace->checkQuota($userId);
        $recent = $this->transactions->recentForUser($userId, 50); // increased from 10

        // Load additional data for new sections
        $tier    = $quota['tier'] ?? 'none';
        $isPro   = in_array($tier, ['pro', 'team', 'enterprise', 'government']);

        $apiKeys = $isPro ? $this->apiKeys->allForUser($userId) : [];
        $invoices = $this->invoices->allForUser($userId);
        $org      = $this->orgs->orgForUser($userId);

        // Active subscription details
        $subscription = $this->subscriptions->activeFor($userId);

        // Flash: newly generated API key (shown once)
        $newApiKey = session()->getFlashdata('new_api_key');

        return view('v_account', [
            'quota'        => $quota,
            'recent'       => $recent,
            'apiKeys'      => $apiKeys,
            'invoices'     => $invoices,
            'org'          => $org,
            'subscription' => $subscription,
            'isPro'        => $isPro,
            'newApiKey'    => $newApiKey,
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // POST /account/api-keys/generate — Generate new API key
    // ────────────────────────────────────────────────────────────────
    public function generateApiKey()
    {
        $userId = (int)(session()->get('user_id') ?? 0);
        $quota  = $this->marketplace->checkQuota($userId);
        $tier   = $quota['tier'] ?? 'none';

        // Only Pro+ can have API keys
        if (!in_array($tier, ['pro', 'team', 'enterprise', 'government'])) {
            return redirect()->to(site_url('account'))
                ->with('error', 'API key hanya tersedia untuk pengguna tier Pro atau lebih tinggi.');
        }

        // Limit: max 5 active keys per user
        if ($this->apiKeys->countForUser($userId) >= 5) {
            return redirect()->to(site_url('account'))
                ->with('error', 'Maksimum 5 API key aktif per akun. Cabut key lama sebelum membuat yang baru.');
        }

        $name   = trim($this->request->getPost('key_name') ?? 'My API Key');
        $name   = $name ?: 'My API Key';
        $scopes = $this->request->getPost('scopes') ?? ['read'];
        if (!is_array($scopes)) {
            $scopes = [$scopes];
        }
        $scopes = array_intersect($scopes, ['read', 'download']);
        if (empty($scopes)) {
            $scopes = ['read'];
        }

        $result = $this->apiKeys->generate($userId, $name, $scopes);

        // Store plain key in flash — shown only ONCE to user
        session()->setFlashdata('new_api_key', $result['plain_key']);
        session()->setFlashdata('new_api_key_name', $name);

        return redirect()->to(site_url('account') . '#api-keys')
            ->with('success', 'API key baru berhasil dibuat. Salin dan simpan sekarang — tidak akan ditampilkan lagi.');
    }

    // ────────────────────────────────────────────────────────────────
    // POST /account/api-keys/{id}/revoke — Revoke an API key
    // ────────────────────────────────────────────────────────────────
    public function revokeApiKey(int $keyId)
    {
        $userId = (int)(session()->get('user_id') ?? 0);
        $result = $this->apiKeys->revoke($keyId, $userId);

        if ($result) {
            return redirect()->to(site_url('account') . '#api-keys')
                ->with('success', 'API key berhasil dicabut.');
        }

        return redirect()->to(site_url('account') . '#api-keys')
            ->with('error', 'API key tidak ditemukan atau sudah dicabut.');
    }

    // ────────────────────────────────────────────────────────────────
    // GET /account/upgrade — Self-service tier upgrade info
    // ────────────────────────────────────────────────────────────────
    public function upgradePrompt()
    {
        $userId = (int)(session()->get('user_id') ?? 0);
        $quota  = $this->marketplace->checkQuota($userId);
        $tiers  = $this->subscriptions->allTiers();

        return view('v_upgrade', [
            'quota'      => $quota,
            'tiers'      => $tiers,
            'activePage' => 'account',
        ]);
    }
}
