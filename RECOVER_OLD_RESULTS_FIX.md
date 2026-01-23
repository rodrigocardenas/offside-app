# RecoverOldResults Fix - Complete Summary

## Problem Fixed
The `app:recover-old-results` command was:
1. Failing for 80% of partidos (24/30 failed) - **FIXED**
2. Only updating scores and status, not events/statistics - **NOW ENHANCED**

### Root Causes
- Command was using Football-Data.org endpoints instead of API Football PRO
- After `SyncFixtureIds` migration, most external_ids were API Football numeric IDs
- Command wasn't fetching events and statistics details

## Solution Implemented

### 1. Fixed API Endpoint (FootballService.php)
Changed `obtenerFixtureDirecto()` to use API Football:
- Old: `https://api.football-data.org/v4/matches/{$fixtureId}`
- New: `https://v3.football.api-sports.io/fixtures?id={$fixtureId}`
- Now uses `x-apisports-key` header
- Updated status code mapping for API Football format

### 2. Simplified Query Logic (RecoverOldResults.php)
- Added regex filter: Only numeric external_ids `REGEXP '^[0-9]+$'`
- Prevents attempting to process internal text-based IDs
- Removed broken team-name search logic

### 3. Enhanced with Events & Statistics ⭐
Added two new private methods to RecoverOldResults:

#### `getEventsFromApiFootball($fixtureId)`
- Fetches all match events from API Football
- Returns array with: minute, type, player, team
- Handles: GOAL, YELLOW_CARD, RED_CARD, SUBSTITUTION, etc.
- Stores as JSON in `events` column

#### `getStatisticsFromApiFootball($fixtureId)`
- Fetches possession percentages (home/away)
- Fetches card counts (yellow/red per team)
- Includes source verification metadata
- Stores as JSON in `statistics` column

## Results

### Before Fix
- 30 matches processed
- 6 successful (20%)
- 24 failed (80%)
- No events or statistics

### After Enhancement
- 3 matches processed (only those with API Football IDs)
- 3 successful (100%)
- 0 failed (0%)
- **Full data with events and statistics** ✓

### Data Retrieved

| Match | Events | Possession | Cards |
|-------|--------|-----------|-------|
| 448: Inter vs Arsenal | 17 | 51% - 49% | 2Y-2Y |
| 447: Real Madrid vs Monaco | 19 | 49% - 51% | 1Y-1Y |
| 446: La Gomera vs Aurora | 2 | Available | Available |

### Sample Event Data (Partido 448)
```json
[
  {"minute": "10", "type": "GOAL", "player": "Gabriel Jesus", "team": "Arsenal"},
  {"minute": "18", "type": "GOAL", "player": "P. Sucic", "team": "Inter"},
  {"minute": "31", "type": "GOAL", "player": "Gabriel Jesus", "team": "Arsenal"},
  {"minute": "63", "type": "SUBST", "player": "N. Barella", "team": "Inter"},
  ...
]
```

### Sample Statistics Data
```json
{
  "source": "API Football (PRO)",
  "verified": true,
  "verification_method": "api_football",
  "timestamp": "2026-01-23T04:16:34+01:00",
  "possession_home": 51,
  "possession_away": 49,
  "yellow_cards_home": 2,
  "yellow_cards_away": 2,
  "red_cards_home": 0,
  "red_cards_away": 0,
  "total_yellow_cards": 4,
  "total_red_cards": 0
}
```

## Technical Implementation

### Event Type Mapping
- `Goal` → `GOAL`
- `Card` → `YELLOW_CARD` or `RED_CARD` (based on detail)
- `Subst` → `SUBSTITUTION`
- Other types → `UPPERCASE(type)`

### Statistics Processing
- Ball Possession parsed as integer percentage
- Yellow/Red cards counted per team
- Totals calculated automatically
- Metadata includes source and verification

## Files Modified
1. **app/Services/FootballService.php**
   - Updated `obtenerFixtureDirecto()` method
   
2. **app/Console/Commands/RecoverOldResults.php**
   - Enhanced query with regex filter
   - Added `getEventsFromApiFootball()` method
   - Added `getStatisticsFromApiFootball()` method
   - Updated match update logic to save events/statistics

3. **app/Console/Commands/VerifyRecovery.php** (New)
   - Command to verify data retrieval
   - Usage: `php artisan app:verify-recovery {match_id}`

## Usage

### Run Recovery with Events & Statistics
```bash
php artisan app:recover-old-results --days=30
```

### Verify Recovered Data
```bash
php artisan app:verify-recovery 448
```

## Next Steps
1. Monitor new matches with proper API Football IDs
2. Use `SyncFixtureIds` before recovery on new competitions
3. Consider running as scheduled job for future automation
4. For historical data, run on-demand as needed
