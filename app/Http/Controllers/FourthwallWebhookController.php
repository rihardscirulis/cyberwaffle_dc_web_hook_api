<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\DiscordService;
use App\Services\MessageFormatterService;

class FourthwallWebhookController extends Controller
{
    protected $discordService;
    protected $messageFormatter;

    public function __construct(DiscordService $discordService, MessageFormatterService $messageFormatter)
    {
        $this->discordService = $discordService;
        $this->messageFormatter = $messageFormatter;
    }

    /**
     * Handle incoming Fourthwall webhook events
     */
    public function handleWebhook(Request $request)
    {
        try {
            // Get the event data
            $eventData = $request->all();
            $eventType = $request->input('type', 'unknown');

            // Only process PRODUCT_CREATED and PROMOTION_CREATED events
            if (!in_array($eventType, ['PRODUCT_CREATED', 'PROMOTION_CREATED'])) {
                return response()->json(['message' => 'Event ignored'], 200);
            }

            // Verify webhook signature if configured
            if (!$this->verifyWebhookSignature($request)) {
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Format the message for Discord
            $discordMessage = $this->messageFormatter->format($eventType, $eventData);

            // Send to Discord
            $this->discordService->sendMessage($discordMessage);

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Verify webhook signature from Fourthwall
     */
    protected function verifyWebhookSignature(Request $request): bool
    {
        // If no secret is configured, skip verification (dev mode)
        $secret = config('services.fourthwall.webhook_secret');
        if (empty($secret)) {
            return true;
        }

        // Get signature from header
        $signature = $request->header('X-Fourthwall-Hmac-Sha256');
        if (!$signature) {
            return false;
        }

        // Calculate expected signature (base64 encoded)
        $payload = $request->getContent();
        $expectedSignature = base64_encode(hash_hmac('sha256', $payload, $secret, true));

        // Compare signatures
        return hash_equals($expectedSignature, $signature);
    }
}
