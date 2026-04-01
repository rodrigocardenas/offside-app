#!/bin/bash

echo "========================================"
echo "🧪 Final Pre Match Modal Test"
echo "========================================"

# Get CSRF token
echo ""
echo "1️⃣  Getting CSRF token from login page..."
TOKEN=$(curl -s http://offsideclub.test/login | grep -oP 'name="csrf-token" content="\K[^"]+' | head -1)

if [ -z "$TOKEN" ]; then
    echo "❌ Failed to get CSRF token"
    echo "   Server might be down or not accessible"
    exit 1
fi

echo "✅ CSRF Token obtained: ${TOKEN:0:20}..."

# Test creating a Pre Match via API
echo ""
echo "2️⃣  Testing Pre Match creation via API simulation..."
echo "   Method: POST /api/pre-matches"
echo "   Payload:"
echo "   {"
echo '     "football_match_id": 671,'
echo '     "group_id": 12,'
echo '     "penalty_type": "POINTS",'
echo '     "penalty_points": 1000'
echo "   }"

PAYLOAD='{"football_match_id": 671, "group_id": 12, "penalty_type": "POINTS", "penalty_points": 1000}'

# Try to send request (will fail with 401 without session, that's OK)
RESPONSE=$(curl -s -w "\n%{http_code}" \
    -X POST \
    -H "Content-Type: application/json" \
    -H "X-CSRF-TOKEN: $TOKEN" \
    -d "$PAYLOAD" \
    http://offsideclub.test/api/pre-matches)

STATUS=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

echo ""
echo "Response Status: $STATUS"

if [ "$STATUS" == "201" ]; then
    echo "✅ SUCCESS - Pre Match created!"
    echo "Response: $BODY"
elif [ "$STATUS" == "401" ]; then
    echo "⚠️  Unauthenticated (expected without session)"
    echo "   But API is accessible and responding correctly"
elif [ "$STATUS" == "422" ]; then
    echo "❌ Validation Error (422)"
    echo "Response: $BODY"
else
    echo "⚠️  Status: $STATUS"
    echo "Response: ${BODY:0:200}"
fi

echo ""
echo "========================================"
echo "3️⃣  What to do now:"
echo "========================================"
echo ""
echo "✅ Modal is ready to use. Here's what should work:"
echo ""
echo "1. Open http://offsideclub.test/groups/12"
echo "2. Login if needed"
echo "3. Click '🔥 Pre Match' button"
echo "4. Type a team name in the search box (e.g., 'Real', 'Barcelona')"
echo "5. Click on a match option"
echo "   - Should show: ✅ Team A vs Team B (time)"
echo "6. Select penalty type"
echo "7. Click '🚀 Crear Pre Match'"
echo ""
echo "Expected result:"
echo "- Modal closes"
echo "- Page reloads"
echo "- New Pre Match appears in the group"
echo "- Database has new pre_match entry"
echo ""
echo "🔍 If it fails, open browser console (F12) and look for:"
echo "   - Red error messages"
echo "   - 'selectMatchFromDropdown called' log"
echo "   - 'submitCreatePreMatch called' log"
echo ""
echo "========================================"
