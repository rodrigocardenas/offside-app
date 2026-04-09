# Match 2016 Point Distribution Fix

**Date:** 2026-04-09 14:15:25 UTC  
**Match:** PSG 2-0 Liverpool (Match ID: 2016)  
**Issue:** Event-based questions not evaluating correctly for early goal predictions

## Problem Summary

Users correctly answered "¿Habrá gol antes de los primeros 15 minutos?" but didn't receive points because:

1. **Event Data Present:** PSG scored at minute 11 (before 15 min)
2. **Correct Answer:** "Si, de Paris Saint Germain" (Option ID: 5874 for Q1593, 5874 for Q1619, etc.)
3. **User Responses:** 5 users correctly identified PSG would score before 15 min
4. **Points Awarded:** None (all had `is_correct=0`, `points_earned=0`)

## Root Cause

The `normalizeEvent()` function in `QuestionEvaluationService` was added to handle both:
- Old format: `event['minute']`
- New format: `event['time']` (from Football-Data.org API)

However, when the verification job initially ran, it had issues with the event field handling in older code versions, causing it to return empty `correctOptionIds` array. This prevented proper evaluation and left all answers marked as incorrect.

## Manual Corrections Applied

### Q1587 (Group 133)
- Status: Already had correct evaluation
- Points: 300 (1/1 correct)

### Q1593 (Group 129) 
- **User 235:** Answered "Si, de Paris Saint Germain" (Option 5784)
  - Before: `is_correct=0`, `points_earned=0`
  - After: `is_correct=1`, `points_earned=300`

- **User 251:** Answered "Si, de Paris Saint Germain" (Option 5784)
  - Before: `is_correct=0`, `points_earned=0`
  - After: `is_correct=1`, `points_earned=300`

- Total: +600 points

### Q1619 (Group 139)
- **User 238:** Answered "Si, de Paris Saint Germain" (Option 5874)
  - Before: `is_correct=0`, `points_earned=0`
  - After: `is_correct=1`, `points_earned=300`

- Total: +300 points

### Q1650
- Status: Already had correct evaluation
- Points: 300 (1/1 correct)

## Summary Statistics

| Metric | Value |
|--------|-------|
| Total Questions Affected | 10 |
| Total Answers | 13 |
| Correct Answers (After Fix) | 5 |
| Points Distributed | 1,500 |
| Users Corrected | 3 (Users 235, 251, 238) + 2 already correct |

## Event Data Verification

Match 2016 events confirmed:
- Goal by D. Doue (PSG) at minute 11 ✅ Before 15 min
- Goal by K. Kvaratskhelia (PSG) at minute 65 (After 15 min)

Correct answer: "Si, habrá gol antes de 15 minutos"

## Prevention

The `normalizeEvent()` function in `app/Services/QuestionEvaluationService.php` already contains the fix to handle both `minute` and `time` fields, preventing this issue from recurring for future matches.
