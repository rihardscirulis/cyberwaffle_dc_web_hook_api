<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FourthwallApiService
{
    protected string $baseUrl = 'https://api.fourthwall.com/v1';
    protected ?string $username;
    protected ?string $password;

    public function __construct()
    {
        $this->username = config('services.fourthwall.api_username');
        $this->password = config('services.fourthwall.api_password');
    }

    /**
     * Get complete product details from Fourthwall API
     */
    public function getProduct(string $productId): ?array
    {
        if (!$this->username || !$this->password) {
            return null;
        }

        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                ])->get("{$this->baseUrl}/products/{$productId}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Construct shop URL based on shop domain and product slug
     */
    public function buildShopUrl(string $shopDomain, string $productSlug): string
    {
        return "https://{$shopDomain}/products/{$productSlug}";
    }
}