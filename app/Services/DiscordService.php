<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordService
{
    protected $webhookUrl;

    public function __construct()
    {
        $this->webhookUrl = config('services.discord.webhook_url');
    }

    /**
     * Send a message to Discord
     *
     * @param array $message
     * @return bool
     */
    public function sendMessage(array $message): bool
    {
        try {
            if (empty($this->webhookUrl)) {
                Log::warning('Discord webhook URL not configured');
                return false;
            }

            $response = Http::post($this->webhookUrl, $message);

            if ($response->successful()) {
                Log::info('Message sent to Discord successfully');
                return true;
            } else {
                Log::error('Failed to send message to Discord', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Error sending message to Discord', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send a simple text message to Discord
     *
     * @param string $content
     * @return bool
     */
    public function sendSimpleMessage(string $content): bool
    {
        return $this->sendMessage([
            'content' => $content
        ]);
    }

    /**
     * Send an embed message to Discord
     *
     * @param array $embed
     * @return bool
     */
    public function sendEmbed(array $embed): bool
    {
        return $this->sendMessage([
            'embeds' => [$embed]
        ]);
    }
}
