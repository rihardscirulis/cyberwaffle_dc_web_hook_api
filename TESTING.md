# Local Testing Guide

## Quick Start

### 1. Start the Server
```bash
php artisan serve
```

Server runs at: `http://localhost:8000`

### 2. Test the Webhook
```bash
# Test different event types
./test-webhook.sh order.created
./test-webhook.sh order.fulfilled
./test-webhook.sh subscription.created
./test-webhook.sh donation.created
```

### 3. Configure Discord (Optional)
To see messages in Discord:

1. Get your Discord webhook URL:
   - Server Settings → Integrations → Webhooks → Create Webhook

2. Edit `.env`:
   ```bash
   DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/YOUR_ID/YOUR_TOKEN
   ```

3. Restart the server

### 4. Monitor Logs
```bash
# Watch logs in real-time
tail -f storage/logs/laravel.log

# View last 50 lines
tail -50 storage/logs/laravel.log
```

## Manual Testing with curl

### Test Order Created
```bash
curl -X POST http://localhost:8000/api/webhooks/fourthwall \
  -H "Content-Type: application/json" \
  -d '{
    "type": "order.created",
    "data": {
      "id": "order_123",
      "customer": {"name": "John Doe"},
      "total": 99.99,
      "currency": "USD",
      "items": [
        {"name": "Product", "quantity": 1, "price": 99.99}
      ]
    }
  }'
```

### Test Subscription
```bash
curl -X POST http://localhost:8000/api/webhooks/fourthwall \
  -H "Content-Type: application/json" \
  -d '{
    "type": "subscription.created",
    "data": {
      "id": "sub_123",
      "customer": {"name": "Jane Doe"},
      "tier": "Premium",
      "amount": 9.99,
      "currency": "USD"
    }
  }'
```

### Test Donation
```bash
curl -X POST http://localhost:8000/api/webhooks/fourthwall \
  -H "Content-Type: application/json" \
  -d '{
    "type": "donation.created",
    "data": {
      "donor": {"name": "Anonymous"},
      "amount": 25.00,
      "currency": "USD",
      "message": "Keep it up!"
    }
  }'
```

## Testing with Real Fourthwall

To test with actual Fourthwall webhooks, you need to expose your local server to the internet.

### Option 1: ngrok (Recommended)
```bash
# Install ngrok: https://ngrok.com/download
ngrok http 8000

# Use the provided URL in Fourthwall webhook settings
# Example: https://abc123.ngrok.io/api/webhooks/fourthwall
```

### Option 2: localtunnel
```bash
npm install -g localtunnel
lt --port 8000
```

### Option 3: Deploy to a Test Server
Deploy your code to a public server and use that URL in Fourthwall.

## Troubleshooting

### Webhook returns 401 (Invalid signature)
- Check if `FOURTHWALL_WEBHOOK_SECRET` is set in `.env`
- If testing locally, leave it empty or remove signature verification temporarily

### Messages not appearing in Discord
- Verify `DISCORD_WEBHOOK_URL` is correct
- Check the webhook hasn't been deleted in Discord
- Review Laravel logs for errors
- Test the Discord webhook directly:
  ```bash
  curl -X POST "YOUR_DISCORD_WEBHOOK_URL" \
    -H "Content-Type: application/json" \
    -d '{"content": "Test message"}'
  ```

### Server not starting
- Check if port 8000 is already in use
- Try a different port: `php artisan serve --port=8001`
- Check for PHP errors in the console

### Logs not showing anything
- Ensure `LOG_LEVEL=debug` in `.env`
- Check file permissions on `storage/logs/`
- Clear cache: `php artisan cache:clear`

## Customizing Messages

Edit the formatting in:
```
app/Services/MessageFormatterService.php
```

Each event type has its own method:
- `formatOrderCreated()` - Order events
- `formatSubscriptionCreated()` - Subscription events
- `formatDonationCreated()` - Donation events
- `formatGenericEvent()` - Fallback for unknown events

## Webhook Endpoint

**Local:** `http://localhost:8000/api/webhooks/fourthwall`

**Production:** `https://yourdomain.com/api/webhooks/fourthwall`

Configure this URL in your Fourthwall dashboard under Webhooks settings.
