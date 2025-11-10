# Fourthwall to Discord Webhook API

A Laravel-based webhook receiver that processes Fourthwall events and sends formatted notifications to Discord channels.

## Features

- 🎣 Receives webhook events from Fourthwall API
- 🔒 Webhook signature verification for security
- 📝 Customizable message formatting
- 💬 Discord integration via webhooks
- 📊 Support for multiple event types:
  - Orders (created, updated, fulfilled)
  - Subscriptions (created, updated, cancelled)
  - Donations
  - And more...

## Requirements

- PHP 8.2 or higher
- Composer
- Laravel 12.x

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd fourthwallapi
```

2. Install dependencies:
```bash
composer install
```

3. Copy the environment file and configure:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Configure your environment variables in `.env`:
```env
# Discord Webhook Configuration
DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/YOUR_WEBHOOK_URL

# Fourthwall Configuration (optional for signature verification)
FOURTHWALL_WEBHOOK_SECRET=your_webhook_secret_here
```

## Configuration

### Discord Webhook URL

1. Go to your Discord server
2. Navigate to Server Settings → Integrations → Webhooks
3. Create a new webhook or edit an existing one
4. Copy the webhook URL and add it to your `.env` file

### Fourthwall Webhook Setup

1. Log in to your Fourthwall dashboard
2. Navigate to Settings → Webhooks
3. Add a new webhook with the URL: `https://yourdomain.com/api/webhooks/fourthwall`
4. Copy the webhook secret and add it to your `.env` file
5. Select the events you want to receive

## Usage

### Starting the Application

For development:
```bash
php artisan serve
```

For production, configure your web server to point to the `public` directory.

### Webhook Endpoint

The webhook endpoint is available at:
```
POST /api/webhooks/fourthwall
```

### Supported Event Types

The application currently supports the following Fourthwall event types:

- `order.created` - New order received
- `order.updated` - Order status updated
- `order.fulfilled` - Order fulfilled and shipped
- `subscription.created` - New subscription
- `subscription.updated` - Subscription updated
- `subscription.cancelled` - Subscription cancelled
- `donation.created` - New donation received

### Customizing Message Formatting

To customize how messages are formatted for Discord, edit the `MessageFormatterService` class:

```php
app/Services/MessageFormatterService.php
```

Each event type has its own formatting method. You can modify:
- Message content and structure
- Embed colors
- Field layouts
- Titles and descriptions

Example of customizing the order created event:

```php
protected function formatOrderCreated(array $data): array
{
    // Your custom formatting logic here
    return [
        'embeds' => [
            [
                'title' => 'Custom Title',
                'description' => 'Custom description',
                'color' => 5763719,
                // ... more customization
            ]
        ]
    ];
}
```

### Discord Embed Colors

Common Discord embed colors used in the application:
- 🟢 Green (5763719) - Success, new orders, fulfilled orders
- 🔵 Blue (3447003) - Updates, informational
- 🟡 Gold (15844367) - Subscriptions, premium events
- 🔴 Red (15158332) - Cancellations, errors
- 🟣 Pink (15277667) - Donations
- ⚫ Gray (9807270) - Unknown/generic events

## Testing

You can test the webhook endpoint using curl:

```bash
curl -X POST https://yourdomain.com/api/webhooks/fourthwall \
  -H "Content-Type: application/json" \
  -d '{
    "type": "order.created",
    "data": {
      "id": "order_123",
      "customer": {"name": "John Doe"},
      "total": 49.99,
      "currency": "USD",
      "items": [
        {"name": "Product 1", "quantity": 1, "price": 49.99}
      ]
    }
  }'
```

## Logging

All webhook events are logged for debugging. Check the logs at:
```
storage/logs/laravel.log
```

## Security

- The application verifies webhook signatures using HMAC SHA-256
- If `FOURTHWALL_WEBHOOK_SECRET` is not set, signature verification is skipped (dev mode)
- Always use HTTPS in production
- Keep your webhook secrets secure and never commit them to version control

## Troubleshooting

### Webhook not receiving events

1. Ensure your application is publicly accessible
2. Check if the webhook URL is correctly configured in Fourthwall
3. Verify your firewall allows incoming requests
4. Check the Laravel logs for errors

### Messages not appearing in Discord

1. Verify the Discord webhook URL is correct
2. Check if the webhook has been deleted in Discord
3. Review the logs for any error messages
4. Test the Discord webhook URL manually with curl

### Signature verification failing

1. Ensure the webhook secret matches exactly
2. Check if the secret has been changed in Fourthwall
3. Verify there are no extra spaces or characters in the `.env` file

## Project Structure

```
app/
├── Http/
│   └── Controllers/
│       └── FourthwallWebhookController.php  # Main webhook handler
└── Services/
    ├── DiscordService.php                    # Discord API integration
    └── MessageFormatterService.php           # Message formatting logic

routes/
└── api.php                                   # API routes

config/
└── services.php                              # Third-party service configuration
```

## Contributing

Feel free to submit issues and enhancement requests!

## License

This project is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
