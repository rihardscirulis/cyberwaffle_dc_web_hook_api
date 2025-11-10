#!/bin/bash

# Test script for Fourthwall webhooks
# Usage: ./test-webhook.sh [event-type]

API_URL="http://localhost:8000/api/webhooks/fourthwall"

# Color codes for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}Testing Fourthwall Webhook${NC}\n"

# Determine which event to test
EVENT_TYPE=${1:-order.created}

case $EVENT_TYPE in
  order.created)
    echo -e "${GREEN}Testing: Order Created${NC}"
    PAYLOAD='{
      "type": "order.created",
      "data": {
        "id": "order_123456",
        "customer": {
          "name": "John Doe",
          "email": "john@example.com"
        },
        "total": 149.99,
        "currency": "USD",
        "items": [
          {
            "name": "T-Shirt (Large)",
            "quantity": 2,
            "price": 29.99
          },
          {
            "name": "Hoodie (Medium)",
            "quantity": 1,
            "price": 89.99
          }
        ],
        "status": "pending"
      }
    }'
    ;;

  order.updated)
    echo -e "${GREEN}Testing: Order Updated${NC}"
    PAYLOAD='{
      "type": "order.updated",
      "data": {
        "id": "order_123456",
        "customer": {
          "name": "John Doe"
        },
        "status": "processing",
        "updated_at": "2025-11-10T12:00:00Z"
      }
    }'
    ;;

  order.fulfilled)
    echo -e "${GREEN}Testing: Order Fulfilled${NC}"
    PAYLOAD='{
      "type": "order.fulfilled",
      "data": {
        "id": "order_123456",
        "customer": {
          "name": "John Doe"
        },
        "tracking_number": "1Z999AA10123456784",
        "carrier": "UPS"
      }
    }'
    ;;

  subscription.created)
    echo -e "${GREEN}Testing: Subscription Created${NC}"
    PAYLOAD='{
      "type": "subscription.created",
      "data": {
        "id": "sub_987654",
        "customer": {
          "name": "Jane Smith"
        },
        "tier": "Premium Tier",
        "amount": 9.99,
        "currency": "USD",
        "interval": "monthly"
      }
    }'
    ;;

  subscription.updated)
    echo -e "${GREEN}Testing: Subscription Updated${NC}"
    PAYLOAD='{
      "type": "subscription.updated",
      "data": {
        "id": "sub_987654",
        "customer": {
          "name": "Jane Smith"
        },
        "tier": "Platinum Tier",
        "amount": 19.99,
        "currency": "USD",
        "interval": "monthly"
      }
    }'
    ;;

  subscription.cancelled)
    echo -e "${GREEN}Testing: Subscription Cancelled${NC}"
    PAYLOAD='{
      "type": "subscription.cancelled",
      "data": {
        "id": "sub_987654",
        "customer": {
          "name": "Jane Smith"
        },
        "cancelled_at": "2025-11-10T12:00:00Z",
        "reason": "Customer requested cancellation"
      }
    }'
    ;;

  donation.created)
    echo -e "${GREEN}Testing: Donation Created${NC}"
    PAYLOAD='{
      "type": "donation.created",
      "data": {
        "id": "donation_555",
        "donor": {
          "name": "Anonymous Supporter"
        },
        "amount": 50.00,
        "currency": "USD",
        "message": "Keep up the great work! Love your content!"
      }
    }'
    ;;

  all)
    echo -e "${GREEN}Testing: ALL Event Types${NC}\n"
    for event in order.created order.updated order.fulfilled subscription.created subscription.updated subscription.cancelled donation.created; do
      echo -e "${BLUE}Testing $event...${NC}"
      $0 $event
      sleep 1
      echo ""
    done
    exit 0
    ;;

  *)
    echo -e "${RED}Unknown event type: $EVENT_TYPE${NC}"
    echo "Available types:"
    echo "  - order.created"
    echo "  - order.updated"
    echo "  - order.fulfilled"
    echo "  - subscription.created"
    echo "  - subscription.updated"
    echo "  - subscription.cancelled"
    echo "  - donation.created"
    echo "  - all (test all event types)"
    exit 1
    ;;
esac

echo -e "\nSending webhook to: ${BLUE}${API_URL}${NC}\n"

# Calculate signature if secret is available
WEBHOOK_SECRET=$(grep FOURTHWALL_WEBHOOK_SECRET .env 2>/dev/null | cut -d '=' -f2)
if [ ! -z "$WEBHOOK_SECRET" ]; then
  SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$WEBHOOK_SECRET" | sed 's/^.* //')
  echo -e "Using signature: ${BLUE}${SIGNATURE:0:20}...${NC}\n"

  # Send the request with signature
  RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$API_URL" \
    -H "Content-Type: application/json" \
    -H "X-Fourthwall-Signature: $SIGNATURE" \
    -d "$PAYLOAD")
else
  echo -e "${BLUE}No webhook secret found, sending without signature${NC}\n"

  # Send the request without signature
  RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$API_URL" \
    -H "Content-Type: application/json" \
    -d "$PAYLOAD")
fi

# Extract status code and body
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | head -n-1)

# Display results
echo -e "Response Code: ${GREEN}${HTTP_CODE}${NC}"
echo -e "Response Body: ${BODY}\n"

if [ "$HTTP_CODE" = "200" ]; then
  echo -e "${GREEN}✓ Webhook sent successfully!${NC}"
  echo -e "${BLUE}Check your Discord channel for the message.${NC}"
else
  echo -e "${RED}✗ Webhook failed!${NC}"
  echo -e "${RED}Check the Laravel logs for more details:${NC}"
  echo -e "${BLUE}tail -f storage/logs/laravel.log${NC}"
fi
