# Diagnostic Report: Question Verification Group 129 vs Group 103

## Problem Discovery

### Symptom
- **Group 103**: 103 of 132 questions verified (78%) ✅
- **Group 129**: Only 2 of 13 questions verified (15%) ❌

The job to verify questions works for Group 103 but fails for Group 129.

## Root Cause Analysis

### Verified vs Not Verified - Match Source Correlation

Queried: `questions` joined with `football_matches` looking at `statistics.source`

**Result:**
- **Group 103 + Questions with `source = NULL`**:
  - Total: 12 questions
  - Verified: 7 questions ✅
  
- **Group 129 + Questions with `source = NULL`**:
  - Total: 5 questions  
  - Verified: 0 questions ❌

### Verification Timing Analysis

```sql
SELECT group_id, COUNT(*), MIN(result_verified_at), MAX(result_verified_at)
FROM questions WHERE group_id IN (129, 103) AND result_verified_at IS NOT NULL
GROUP BY group_id;
```

**Results:**
- **Group 103**: First verified 2026-02-16 01:56:56, Last: 2026-03-10 06:16:02
- **Group 129**: First verified 2026-03-10 06:16:02, Last: 2026-03-10 06:16:02 (same timestamp - single batch!)

**Interpretation:**
Group 103 has been verified over multiple runs since Feb 16. Group 129 was attempted in a SINGLE recent run (Mar 10) and only 2 questions succeeded.

## Code-Level Issue

### File: `app/Services/QuestionEvaluationService.php`

**Lines 105-185:** The evaluator uses `$hasVerifiedData` as a PREREQUISITE gate to evaluate ANY event-based questions:

```php
$hasVerifiedData = $this->hasVerifiedMatchData($match);

// Then checks like:
elseif ($hasVerifiedData && $this->isQuestionAbout(...'primer gol'...)) {
    // ❌ SKIPPED entirely if hasVerifiedData = FALSE
```

**Method: `hasVerifiedMatchData()`** (Lines 257-290)

```php
private function hasVerifiedMatchData(FootballMatch $match): bool
{
    $statsJson = json_decode($match->statistics ?? '{}', true);
    $source = $statsJson['source'] ?? false;
    
    // Returns FALSE unless source explicitly contains "API Football" or "Gemini"
    if ($source) {
        return stripos($source, 'API Football') !== false || 
               stripos($source, 'Gemini') !== false;
    }
    return false;
}
```

### Why Group 129 Failed

Matches in Group 129 with `source = NULL`:
- Match 2000: Bayer Leverkusen vs Arsenal (Not Started)
- Match 1999: Paris SG vs Chelsea (Not Started)
- Match 2001: Bodo/Glimt vs Sporting CP (Not Started)  
- Match 2004: Real Madrid vs Manchester City (Not Started)
- Match 1836: Torino vs Parma (Not Started)

When `hasVerifiedMatchData()` returns FALSE:
1. ❌ Evaluators for event-based questions are **skipped entirely**
2. ❌ Falls through to `attemptGeminiFallback()`
3. ❌ Gemini evaluation **fails or gives wrong answers**
4. ❌ Questions remain unverified or are marked incorrect

### Why Group 103 Partially Succeeded

Group 103's matches with `source = NULL` still had 7 out of 12 verified because:
- Those 7 may have been verified in different runs (Feb 16) when data was available OR
- `statistics` JSON had event data even without explicit "API Football" source indicator

## Solution Approach

### User Intent (from conversation)
> "está usando gemini para verificar, pero debería usar la api de football como primera opción"

### Recommended Fix

Change the evaluation flow from:
```
IF hasVerifiedData THEN try_deterministic ELSE skip
→ attemptGeminiFallback()
```

To:
```
TRY: try_deterministic (regardless of source flag)
IF result IS NULL/EMPTY THEN: attemptGeminiFallback()
```

This means:
1. **Always attempt** to evaluate using available `events` and `statistics` data
2. **Only fall back** to Gemini if deterministic evaluation returns empty result
3. Remove the `$hasVerifiedData &&` prerequisite gates

### Files to Modify
- `app/Services/QuestionEvaluationService.php`
  - Lines 129, 133, 137, 141, 145, 149, 153, 157, 161, 165, 169, 173, 177: Remove `$hasVerifiedData &&` conditions
  - Lines 105-115: Can remove the logging warning or make it informational instead

## Impact Assessment

- **Risk**: Low - Gemini fallback still exists if deterministic evaluation truly fails
- **Benefit**: High - Allows evaluation of questions even with partial/unverified data
- **Testing**: Re-run verification for Group 129 should now verify all 13 questions

## Rollback Plan

If needed, revert the commits that removed `$hasVerifiedData &&` gates
