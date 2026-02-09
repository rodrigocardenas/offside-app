# Matches Calendar View - Testing Guide

## 🧪 Testing Checklist

### Backend API Testing

#### 1. GET /api/matches/calendar
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "http://localhost/api/matches/calendar?from_date=2024-02-05&to_date=2024-02-12"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "2024-02-05": [
      {
        "id": 1,
        "status": "SCHEDULED",
        "kick_off_time": "20:00",
        "competition": {"id": 1, "name": "Premier League"},
        "home_team": {"id": 1, "name": "Man United", "crest_url": "..."},
        "away_team": {"id": 2, "name": "Liverpool", "crest_url": "..."},
        "score": {"home": null, "away": null}
      }
    ]
  }
}
```

---

#### 2. GET /api/matches/competitions
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "http://localhost/api/matches/competitions"
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {"id": 1, "name": "Premier League"},
    {"id": 2, "name": "La Liga"}
  ]
}
```

---

#### 3. GET /api/matches/statistics
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "http://localhost/api/matches/statistics?from_date=2024-02-05&to_date=2024-02-12"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "total": 10,
    "scheduled": 8,
    "live": 1,
    "finished": 1
  }
}
```

---

### Frontend View Testing

#### 1. Access Main View
**URL:** `http://localhost/matches/calendar`

**Expected Behavior:**
- ✅ View loads successfully
- ✅ Header with logo appears
- ✅ Filters section visible
- ✅ Matches grouped by date
- ✅ Statistics panel at bottom
- ✅ Bottom navigation shows

#### 2. Theme Support
**Test Light Mode:**
- Open as user with `theme_mode = 'light'`
- Verify background colors are light
- Verify text is dark for contrast

**Test Dark Mode:**
- Open as user with `theme_mode = 'dark'`
- Verify background colors are dark
- Verify text is light for contrast

#### 3. Filter by Competition
**Steps:**
1. Click on a competition badge in the filters
2. API request should be made with `competition_id` parameter
3. Matches should be filtered accordingly

**Expected Request:**
```
GET /api/matches/calendar?from_date=2024-02-05&to_date=2024-02-12&competition_id=1
```

#### 4. Match Card Display
**Verify Each Match Card Shows:**
- ✅ Competition name badge
- ✅ Kick-off time
- ✅ Home team crest and name
- ✅ Away team crest and name
- ✅ Score or status (vs / EN VIVO / 2-1)
- ✅ Predict and Details buttons (if not finished)

#### 5. Date Badges
**Test Cases:**
- Today's date → Shows "HOY" badge
- Tomorrow's date → Shows "MAÑANA" badge
- Other dates → Shows "DD MMM" format

#### 6. Live Match Indicator
**Test Case:**
- Match with status = "LIVE"
- Should show red pulsing dot
- Should display "EN VIVO" text in red

#### 7. Finished Match Display
**Test Case:**
- Match with status = "FINISHED"
- Should display score (e.g., "2 - 1")
- Should NOT show Predict/Details buttons

#### 8. Responsive Design
**Mobile (< 768px):**
- ✅ Filters scroll horizontally
- ✅ Match cards full width
- ✅ Action buttons in 2-column layout
- ✅ Stats grid 2 columns

**Tablet/Desktop:**
- ✅ Proper margins
- ✅ Filters horizontal scroll
- ✅ Stats grid 4 columns

---

## 🔧 Database Verification

### Check Football Matches Table
```sql
SELECT id, match_date, competition_id, home_team_id, away_team_id, 
       status, kick_off_time, score, created_at 
FROM football_matches 
WHERE match_date >= DATE(NOW())
ORDER BY match_date, kick_off_time
LIMIT 5;
```

### Check Competitions
```sql
SELECT id, name, code, season 
FROM competitions 
LIMIT 10;
```

### Check Teams with Crests
```sql
SELECT id, name, crest_url 
FROM teams 
WHERE crest_url IS NOT NULL 
LIMIT 5;
```

---

## 📊 Performance Testing

### API Response Time
```bash
time curl -H "Authorization: Bearer TOKEN" \
  "http://localhost/api/matches/calendar?from_date=2024-02-05&to_date=2024-02-12"
```

**Target:** < 500ms

### View Load Time
Use Chrome DevTools → Network tab
- Page Load Time: < 2 seconds
- API Request Time: < 500ms
- JavaScript Parsing: < 100ms

---

## 🐛 Error Handling

### Test Missing Token
```bash
curl "http://localhost/api/matches/calendar"
```

**Expected:** 401 Unauthorized

### Test Invalid Date Format
```bash
curl -H "Authorization: Bearer TOKEN" \
  "http://localhost/api/matches/calendar?from_date=invalid&to_date=2024-02-12"
```

**Expected:** 422 Unprocessable Entity with validation errors

### Test Non-existent Competition
```bash
curl -H "Authorization: Bearer TOKEN" \
  "http://localhost/api/matches/calendar?competition_id=9999"
```

**Expected:** 422 Unprocessable Entity

---

## 🔐 Security Testing

### CSRF Protection
- View should include `@csrf` token
- POST requests should include token

### Authorization
- User should NOT see matches from teams not in their database
- User should NOT access `/matches/calendar` without authentication

### SQL Injection
```bash
curl -H "Authorization: Bearer TOKEN" \
  "http://localhost/api/matches/calendar?competition_id=1 OR 1=1"
```

**Expected:** Should not execute SQL injection

---

## 🎯 Feature Testing

### [ ] Filter by Competition
- [x] API endpoint returns filtered results
- [x] Frontend displays only selected competition matches
- [ ] Filter persists on page reload (localStorage)

### [ ] Date Range Selection
- [x] "Esta Semana" shows next 7 days
- [x] "Este Mes" shows rest of current month
- [ ] Custom date picker (future feature)

### [ ] Live Match Updates
- [ ] WebSocket updates score in real-time
- [ ] Live badge pulses correctly
- [ ] Finished matches disappear when completed

### [ ] Empty States
- [x] Shows message when no matches found
- [x] Shows error message on API failure

---

## 📋 User Acceptance Testing

### Scenario 1: View Upcoming Matches
1. User logs in
2. Clicks on "Calendario de Partidos" (or navigates to /matches/calendar)
3. Should see next 7 days of matches from their competitions/teams
4. ✅ Verify matches shown are only from their database

### Scenario 2: Filter by Competition
1. User on calendar view
2. Clicks on "Premier League" filter
3. Should see only Premier League matches
4. ✅ Other competitions hidden

### Scenario 3: Check Match Details
1. User sees a match
2. Clicks "Detalles" button
3. Should open modal with:
   - Formation
   - Head-to-head stats
   - Team news
   - Recent form

### Scenario 4: Make Prediction
1. User on calendar view
2. Clicks "Predecir" on a scheduled match
3. Should open modal to select:
   - Home win / Draw / Away win
   - Multiple options for scorelines
4. ✅ Prediction saved to database

### Scenario 5: View Live Match
1. Match is ongoing (status = LIVE)
2. User refreshes page
3. Should see:
   - Red "EN VIVO" indicator
   - Current score
   - Pulsing red dot

---

## 📱 Mobile Testing

### Testing on iPhone
- [ ] Safari - View displays correctly
- [ ] Chrome - View displays correctly
- [ ] Touch interactions work
- [ ] Filters scroll horizontally

### Testing on Android
- [ ] Chrome - View displays correctly
- [ ] Firefox - View displays correctly
- [ ] Touch interactions work
- [ ] Theme detection works

---

## 🚀 Deployment Checklist

Before deploying to production:

- [ ] All tests passing
- [ ] Database migrations executed
- [ ] Cache cleared
- [ ] API keys configured
- [ ] Theme support working
- [ ] Mobile responsive verified
- [ ] Error handling tested
- [ ] Security tests passed
- [ ] Performance benchmarks met

---

## 📝 Test Results

### Date: [Today's Date]
**Tester:** [Your Name]
**Status:** ✅ / ❌

**Notes:**
[Add any issues or observations here]

---

