<?php

declare(strict_types=1);

namespace App\Payment\Service;

use App\Payment\Entity\CryptoInvoice;

class CryptoPaymentService
{
    private string $apiKey;
    private string $apiEndpoint;

    public function __construct(string $apiKey, string $apiEndpoint = 'https://api.commerce.coinbase.com')
    {
        $this->apiKey = $apiKey;
        $this->apiEndpoint = $apiEndpoint;
    }

    /**
     * Creates a cryptocurrency payment invoice.
     *
     * @param string $userId
     * @param string $amount   Amount in major units (e.g., USD)
     * @param string $currency Currency code (BTC, ETH, USDC)
     * @return CryptoInvoice
     */
    public function createInvoice(string $userId, string $amount, string $currency)
    {
        // Stub: In production, call Coinbase Commerce API:
        // POST /charges with {name, description, pricing_type: 'fixed_price', local_price: {amount, currency}}
        $chargeId = 'ch_' . bin2hex(random_bytes(8));
        $invoice = new CryptoInvoice($chargeId, $userId, $amount, $currency);
        $invoice->setStatus('pending');
        // Persist via repository...
        return $invoice;
    }

    /**
     * Verifies a payment via webhook signature.
     */
    public function verifyWebhook(string $payload, string $signature)
    {
        // Stub: compute HMAC with shared secret and compare.
        return true;
    }
}
