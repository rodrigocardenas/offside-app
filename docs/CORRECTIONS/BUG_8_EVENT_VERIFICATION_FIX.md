# BUG #8: Event Verification Timing Issue - Fix Report

**Date**: April 8, 2026
**Root Cause**: Questions were verified before match event data was available in database
**Impact**: 16 questions across 9 groups marked with incorrect options as "correct"
**Status**: ✅ RESOLVED

---

## Issue Description

When the verification jobs ran for matches 2014 (Sporting CP vs Arsenal) and 2018 (Real Madrid vs Bayern Munich), the event data from the API had not yet been inserted into the database. This caused `evaluateLastGoal()` and `evaluateShotsOnTarget()` to:

1. Return empty arrays `[]` (no events found)
2. All question options were marked as `is_correct = 0`
3. Users who answered correctly received 0 points instead of 300

## Root Cause Timeline

1. **14:30-14:40** - Cron jobs started running with new `tries=3` config
2. **14:45ish** - Events from API were processed and inserted into database
3. **14:50-15:00** - Scheduled verification jobs ran, but some questions had already been verified (with missing events)
4. **Result** - 16 questions mistakenly had all options marked incorrect

## Questions Affected (16 total)

### Match 2014: Sporting CP 0-1 Arsenal
**Last Goal Question** - Correct Answer: **Arsenal**

| Question ID | Group | Status |
|-------------|-------|--------|
| Q1591 | 129 | ✅ Fixed (3 users awarded 300pts each) |
| Q1597 | 138 | ✅ Fixed (0 users had correct answer) |
| Q1617 | 139 | ✅ Fixed (2 users awarded 300pts each) |
| Q1623 | 118 | ✅ Fixed (0 users had correct answer) |
| Q1628 | 114 | ✅ Fixed (0 users had correct answer) |
| Q1634 | 112 | ✅ Fixed (0 users had correct answer) |
| Q1639 | 119 | ✅ Fixed (0 users had correct answer) |
| Q1645 | 137 | ✅ Fixed (0 users had correct answer) |

### Match 2018: Real Madrid 1-2 Bayern Munich
**Shots on Target Question** - Correct Answer: **Igual cantidad** (both had 20 shots)

| Question ID | Group | Status |
|-------------|-------|--------|
| Q1592 | 129 | ✅ Fixed (0 users had correct answer) |
| Q1598 | 138 | ✅ Fixed (0 users had correct answer) |
| Q1618 | 139 | ✅ Fixed (0 users had correct answer) |
| Q1624 | 118 | ✅ Fixed (0 users had correct answer) |
| Q1629 | 114 | ✅ Fixed (0 users had correct answer) |
| Q1635 | 112 | ✅ Fixed (0 users had correct answer) |
| Q1640 | 119 | ✅ Fixed (0 users had correct answer) |
| Q1646 | 137 | ✅ Fixed (0 users had correct answer) |

---

## Corrections Applied

### 1. Code Improvements (Commit: fe1b532)
**File**: `app/Services/QuestionEvaluationService.php` - `evaluateLastGoal()` method

Added enhanced logging to detect when:
- Event data is missing during verification
- "Ninguno" is marked as correct due to no goals
- Matching process finds correct options

This will help diagnose similar issues in the future.

### 2. Database Corrections (Manual in Production)

For each of the 16 questions:
1. ✅ Marked the correct option as `is_correct = 1`
2. ✅ Updated all answers with that option: `is_correct = 1`, `points_earned = 300`

**Total Impact**:
- **Users who received points**: 5
  - Q1591 (Group 129): 3 users × 300pts = 900pts
  - Q1617 (Group 139): 2 users × 300pts = 600pts
  
- **Total points distributed**: 1,500pts
- **Questions fixed**: 16
- **Groups affected**: 9

---

## Prevention Strategy

To prevent this in the future:

1. **Enhanced Logging** (✅ Done)
   - `evaluateLastGoal()` now logs when events are missing
   - Makes it visible in error monitoring

2. **Job Sequencing** (Already in place)
   - `UpdateFinishedMatchesJob` runs at `:00`
   - `VerifyFinishedMatchesHourlyJob` runs at `:15` (gives 15 min for data to be processed)
   - `BatchExtractEventsJob` must complete before verification

3. **Re-verification Command**
   - Available: `php artisan app:force-verify-questions --match-id=ID --re-verify`
   - Can be used manually if similar issues detected

---

## Verification

All corrected questions now show:
```
✅ Q1591: 1 correct option (Arsenal)
✅ Q1592: 1 correct option (Igual cantidad)
✅ ... etc for all 16 questions
```

Users can verify their scores in the app, and leaderboards will reflect the corrected points.

---

## Related Issues

- **BUG #7**: Job retry configuration - Fixed with `tries=3`
- **BUG #8 (This)**: Event timing during verification - Fixed with logging + manual corrections
- **Potential BUG**: Other matches may have similar timing issues if cron/event pipeline isn't stable

**Next steps**: Monitor event extraction timing and consider adding a delay before running verification if timing continues to be marginal.
