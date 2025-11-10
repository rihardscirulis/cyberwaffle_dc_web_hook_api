<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MessageFormatterService
{
    protected FourthwallApiService $apiService;

    public function __construct(FourthwallApiService $apiService)
    {
        $this->apiService = $apiService;
    }
    /**
     * Format a Fourthwall event into a Discord message
     *
     * @param string $eventType
     * @param array $eventData
     * @return array
     */
    public function format(string $eventType, array $eventData): array
    {
        // Route to specific formatter based on event type
        return match ($eventType) {
            'PRODUCT_CREATED' => $this->formatProductCreated($eventData),
            'PROMOTION_CREATED' => $this->formatPromotionCreated($eventData),
            default => $this->formatGenericEvent($eventType, $eventData),
        };
    }

    /**
     * Format product created event
     */
    protected function formatProductCreated(array $data): array
    {
        $product = $data['data'] ?? $data;
        $productName = $product['name'] ?? 'Unknown Product';
        $productPrice = $product['price'] ?? null;
        $currency = $product['currency'] ?? 'USD';
        // Try multiple possible URL sources with logging (prioritizing product_url)
        $possibleUrls = [
            'product_url' => $product['product_url'] ?? null,
            'url' => $product['url'] ?? null,
            'permalink' => $product['permalink'] ?? null,
            'link' => $product['link'] ?? null,
            'shop_url' => $product['shop_url'] ?? null,
            'public_url' => $product['public_url'] ?? null,
            'web_url' => $product['web_url'] ?? null
        ];

        $availableUrls = array_filter($possibleUrls);

        $productUrl = null;
        foreach ($possibleUrls as $source => $url) {
            if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
                $productUrl = $url;
                break;
            }
        }

        // If no URL found in webhook, try to fetch from API or construct one
        if (!$productUrl) {
            $productId = $product['id'] ?? null;

            if ($productId) {
                // Try to get product details from API
                $apiProduct = $this->apiService->getProduct($productId);
                if ($apiProduct && isset($apiProduct['url'])) {
                    $productUrl = $apiProduct['url'];
                } else {
                    // Fallback: construct URL using shop domain
                    $shopDomain = config('services.fourthwall.shop_domain');
                    $productSlug = $product['slug'] ?? $product['handle'] ?? $productId;

                    if ($shopDomain && $productSlug) {
                        $productUrl = $this->apiService->buildShopUrl($shopDomain, $productSlug);
                    }
                }
            }
        }
        // Try multiple possible image sources with logging
        $possibleImageUrls = [
            'image.url' => $product['image']['url'] ?? null,
            'image_url' => $product['image_url'] ?? null,
            'thumbnail' => $product['thumbnail'] ?? null,
            'featured_image' => $product['featured_image'] ?? null,
            'images.0.url' => $product['images'][0]['url'] ?? null,
            'image.large' => $product['image']['large'] ?? null,
            'image.medium' => $product['image']['medium'] ?? null,
            'image.small' => $product['image']['small'] ?? null,
            'photos.0.url' => $product['photos'][0]['url'] ?? null,
            'cover_image' => $product['cover_image'] ?? null
        ];

        $availableImages = array_filter($possibleImageUrls);

        $workingImageUrl = null;
        foreach ($possibleImageUrls as $source => $url) {
            if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
                $workingImageUrl = $url;
                break; // Use the first valid URL found
            }
        }

        $description = "**New Product Added!**\n";
        if ($productPrice !== null) {
            $description .= "**Price:** {$currency} {$productPrice}";
        }

        $embed = [
            'title' => "🛍️ {$productName}",
            'description' => $description,
            'color' => 5763719, // Green color
            'timestamp' => now()->toIso8601String(),
            'footer' => [
                'text' => 'Fourthwall Product'
            ]
        ];

        // Add product URL if available
        if ($productUrl) {
            $embed['url'] = $productUrl;
        }

        // Add working image if found
        if ($workingImageUrl) {
            $embed['image'] = ['url' => $workingImageUrl];
        }

        return [
            'content' => '<@&' . config('services.discord.merch_role_id') . '> New merch just dropped!',
            'embeds' => [$embed]
        ];
    }

    /**
     * Format promotion created event
     */
    protected function formatPromotionCreated(array $data): array
    {
        $promotion = $data['data'] ?? $data;
        $promoCode = $promotion['code'] ?? 'Unknown Code';
        $discount = $promotion['discount'] ?? [];
        $requirements = $promotion['requirements'] ?? [];
        $limits = $promotion['limits'] ?? [];

        $description = "**New Promotion Available!**\n";
        $description .= "**Code:** `{$promoCode}`\n";

        // Add discount information
        if (isset($discount['type'])) {
            if ($discount['type'] === 'PERCENTAGE') {
                $percentage = $discount['percentage'] ?? 0;
                $description .= "**Discount:** {$percentage}% off\n";
            } elseif ($discount['type'] === 'FIXED_AMOUNT') {
                $amount = $discount['amount'] ?? 0;
                $currency = $discount['currency'] ?? 'USD';
                $description .= "**Discount:** {$currency} {$amount} off\n";
            }
        }

        // Add minimum order requirement
        if (isset($requirements['minimumOrderValue'])) {
            $minValue = $requirements['minimumOrderValue']['value'] ?? 0;
            $currency = $requirements['minimumOrderValue']['currency'] ?? 'USD';
            $description .= "**Minimum Order:** {$currency} {$minValue}";
        }

        $fields = [];

        // Add usage limits
        if (isset($limits['maximumUsesNumber'])) {
            $maxUses = $limits['maximumUsesNumber'];
            $currentUses = $promotion['usageCount'] ?? 0;
            $fields[] = [
                'name' => 'Usage Limit',
                'value' => "{$currentUses}/{$maxUses} uses",
                'inline' => true
            ];
        }

        if (isset($limits['oneUsePerCustomer']) && $limits['oneUsePerCustomer']) {
            $fields[] = [
                'name' => 'Restriction',
                'value' => 'One use per customer',
                'inline' => true
            ];
        }

        $embed = [
            'title' => '🎉 New Promotion!',
            'description' => $description,
            'color' => 15844367, // Gold color
            'timestamp' => now()->toIso8601String(),
            'footer' => [
                'text' => 'Fourthwall Promotion'
            ]
        ];

        if (!empty($fields)) {
            $embed['fields'] = $fields;
        }

        return [
            'content' => '<@&' . config('services.discord.merch_role_id') . '> New merch just dropped!',
            'embeds' => [$embed]
        ];
    }

    /**
     * Format order created event
     */
    protected function formatOrderCreated(array $data): array
    {
        $order = $data['data'] ?? $data;
        $orderId = $order['id'] ?? 'N/A';
        $customerName = $order['customer']['name'] ?? 'Anonymous';
        $total = $order['total'] ?? 0;
        $currency = $order['currency'] ?? 'USD';
        $items = $order['items'] ?? [];

        $itemsList = $this->formatItems($items);

        return [
            'embeds' => [
                [
                    'title' => '🛒 New Order Received!',
                    'description' => "**Customer:** {$customerName}\n**Order ID:** {$orderId}\n**Total:** {$currency} {$total}",
                    'color' => 5763719, // Green color
                    'fields' => [
                        [
                            'name' => 'Items',
                            'value' => $itemsList ?: 'No items',
                            'inline' => false
                        ]
                    ],
                    'timestamp' => now()->toIso8601String(),
                    'footer' => [
                        'text' => 'Fourthwall Order'
                    ]
                ]
            ]
        ];
    }

    /**
     * Format order updated event
     */
    protected function formatOrderUpdated(array $data): array
    {
        $order = $data['data'] ?? $data;
        $orderId = $order['id'] ?? 'N/A';
        $status = $order['status'] ?? 'unknown';

        return [
            'embeds' => [
                [
                    'title' => '📦 Order Updated',
                    'description' => "**Order ID:** {$orderId}\n**Status:** {$status}",
                    'color' => 3447003, // Blue color
                    'timestamp' => now()->toIso8601String(),
                    'footer' => [
                        'text' => 'Fourthwall Order'
                    ]
                ]
            ]
        ];
    }

    /**
     * Format order fulfilled event
     */
    protected function formatOrderFulfilled(array $data): array
    {
        $order = $data['data'] ?? $data;
        $orderId = $order['id'] ?? 'N/A';
        $customerName = $order['customer']['name'] ?? 'Anonymous';
        $trackingNumber = $order['tracking_number'] ?? 'N/A';

        return [
            'embeds' => [
                [
                    'title' => '✅ Order Fulfilled',
                    'description' => "**Customer:** {$customerName}\n**Order ID:** {$orderId}\n**Tracking:** {$trackingNumber}",
                    'color' => 3066993, // Green color
                    'timestamp' => now()->toIso8601String(),
                    'footer' => [
                        'text' => 'Fourthwall Order'
                    ]
                ]
            ]
        ];
    }

    /**
     * Format subscription created event
     */
    protected function formatSubscriptionCreated(array $data): array
    {
        $subscription = $data['data'] ?? $data;
        $subscriberId = $subscription['id'] ?? 'N/A';
        $subscriberName = $subscription['customer']['name'] ?? 'Anonymous';
        $tier = $subscription['tier'] ?? 'Unknown';
        $amount = $subscription['amount'] ?? 0;
        $currency = $subscription['currency'] ?? 'USD';

        return [
            'embeds' => [
                [
                    'title' => '⭐ New Subscription!',
                    'description' => "**Subscriber:** {$subscriberName}\n**Tier:** {$tier}\n**Amount:** {$currency} {$amount}",
                    'color' => 15844367, // Gold color
                    'timestamp' => now()->toIso8601String(),
                    'footer' => [
                        'text' => 'Fourthwall Subscription'
                    ]
                ]
            ]
        ];
    }

    /**
     * Format subscription updated event
     */
    protected function formatSubscriptionUpdated(array $data): array
    {
        $subscription = $data['data'] ?? $data;
        $subscriberName = $subscription['customer']['name'] ?? 'Anonymous';
        $tier = $subscription['tier'] ?? 'Unknown';

        return [
            'embeds' => [
                [
                    'title' => '🔄 Subscription Updated',
                    'description' => "**Subscriber:** {$subscriberName}\n**New Tier:** {$tier}",
                    'color' => 3447003, // Blue color
                    'timestamp' => now()->toIso8601String(),
                    'footer' => [
                        'text' => 'Fourthwall Subscription'
                    ]
                ]
            ]
        ];
    }

    /**
     * Format subscription cancelled event
     */
    protected function formatSubscriptionCancelled(array $data): array
    {
        $subscription = $data['data'] ?? $data;
        $subscriberName = $subscription['customer']['name'] ?? 'Anonymous';

        return [
            'embeds' => [
                [
                    'title' => '❌ Subscription Cancelled',
                    'description' => "**Subscriber:** {$subscriberName}",
                    'color' => 15158332, // Red color
                    'timestamp' => now()->toIso8601String(),
                    'footer' => [
                        'text' => 'Fourthwall Subscription'
                    ]
                ]
            ]
        ];
    }

    /**
     * Format donation created event
     */
    protected function formatDonationCreated(array $data): array
    {
        $donation = $data['data'] ?? $data;
        $donorName = $donation['donor']['name'] ?? 'Anonymous';
        $amount = $donation['amount'] ?? 0;
        $currency = $donation['currency'] ?? 'USD';
        $message = $donation['message'] ?? '';

        return [
            'embeds' => [
                [
                    'title' => '💝 New Donation!',
                    'description' => "**Donor:** {$donorName}\n**Amount:** {$currency} {$amount}",
                    'color' => 15277667, // Pink color
                    'fields' => $message ? [
                        [
                            'name' => 'Message',
                            'value' => $message,
                            'inline' => false
                        ]
                    ] : [],
                    'timestamp' => now()->toIso8601String(),
                    'footer' => [
                        'text' => 'Fourthwall Donation'
                    ]
                ]
            ]
        ];
    }

    /**
     * Format generic event for unknown types
     */
    protected function formatGenericEvent(string $eventType, array $data): array
    {
        return [
            'embeds' => [
                [
                    'title' => '📢 Fourthwall Event',
                    'description' => "**Event Type:** {$eventType}",
                    'color' => 9807270, // Gray color
                    'fields' => [
                        [
                            'name' => 'Event Data',
                            'value' => '```json' . "\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n" . '```',
                            'inline' => false
                        ]
                    ],
                    'timestamp' => now()->toIso8601String(),
                    'footer' => [
                        'text' => 'Fourthwall Event'
                    ]
                ]
            ]
        ];
    }

    /**
     * Format items list
     */
    protected function formatItems(array $items): string
    {
        if (empty($items)) {
            return 'No items';
        }

        $formatted = [];
        foreach ($items as $item) {
            $name = $item['name'] ?? 'Unknown item';
            $quantity = $item['quantity'] ?? 1;
            $price = $item['price'] ?? 0;
            $formatted[] = "• {$name} (x{$quantity}) - \${$price}";
        }

        return implode("\n", $formatted);
    }
}
