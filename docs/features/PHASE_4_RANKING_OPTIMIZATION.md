# Phase 4: Ranking Optimization - Use Cached group_user.points

**Status:** 🟡 In Progress  
**Date Started:** April 20, 2026  
**Phase Dependency:** Phases 1 & 2 must be complete (points synced to group_user.points)

---

## 📋 Overview

After Phase 1 & 2 synchronize points from `answers.points_earned` → `group_user.points`, ranking queries should use the cached `group_user.points` column instead of recalculating expensive `SUM()` joins on every page load.

**Current Problem:**
```php
// GroupController::show() - Line 324
->withSum(['answers as total_points' => function ($query) use ($group) {
    $query->whereHas('question', function ($questionQuery) use ($group) {
        $questionQuery->where('group_id', $group->id);
    });
}], 'points_earned');

// groups.show.blade.php - Line 35
$topUsers = $group->users->sortByDesc('total_points')->take(3)->values();
```

**Issues:**
1. ❌ `withSum()` recalculates SUM every page load (expensive LEFT JOIN to answers table)
2. ❌ View sorts entire collection in-memory (inefficient if 1000+ users)
3. ❌ Top 3 selection happens AFTER sorting entire collection (wasteful)

---

## 🎯 Solution

Use `group_user.points` (cached in pivot table) instead of calculating on-the-fly.

### File Changes Required

#### 1️⃣ app/Models/Group.php - Method `rankedUsers()`
**Location:** Line ~199-210  
**Current Implementation:** Uses `selectRaw('COALESCE(SUM(answers.points_earned), 0) as total_points')`  
**Change:** Replace with:
```php
->select('users.*', 'group_user.points as total_points')
->orderBy('group_user.points', 'desc')
```

**Before:**
```php
public function rankedUsers()
{
    return $this->users()
        ->selectRaw('COALESCE(SUM(answers.points_earned), 0) as total_points')
        ->leftJoin('answers', function ($join) {
            $join->on('users.id', '=', 'answers.user_id');
        })
        ->leftJoin('questions', function ($join) {
            $join->on('answers.question_id', '=', 'questions.id')
                ->where('questions.group_id', $this->id);
        })
        ->groupBy('users.id')
        ->orderBy('total_points', 'desc');
}
```

**After:**
```php
public function rankedUsers()
{
    return $this->users()
        ->select('users.*', 'group_user.points as total_points')
        ->orderBy('group_user.points', 'desc');
}
```

**Benefits:**
- ✅ No LEFT JOINs to answers/questions
- ✅ Direct pivot table read (indexed)
- ✅ Ordered at database level

---

#### 2️⃣ app/Http/Controllers/GroupController.php - Method `show()`
**Locations:** Line 324 (cache block) and Line 377 (auto-add user block)  

**Current:**
```php
->withSum(['answers as total_points' => function ($query) use ($group) {
    $query->whereHas('question', function ($questionQuery) use ($group) {
        $questionQuery->where('group_id', $group->id);
    });
}], 'points_earned');
```

**After:** Replace `withSum()` with direct group_user.points selection:
```php
->select('users.id', 'users.name', 'users.avatar')
->selectRaw('group_user.points as total_points')
```

**Note:** Keep the relationship loads, just change how total_points is calculated.

---

#### 3️⃣ resources/views/groups/show.blade.php - Line 35
**Current:**
```php
$topUsers = $group->users->sortByDesc('total_points')->take(3)->values();
```

**After:** Pass pre-sorted from controller instead:
```php
$topUsers = $group->rankedUsers()->limit(3)->get();
```

Or if keeping the controller structure, simply pass the top 3:
```php
$topUsers = $group->users->sortByDesc(function($user) {
    return $user->group_user->points ?? 0;
})->take(3)->values();
```

**Better approach:** Modify controller to prepare the ranked users:

In `GroupController::show()`, add before returning to view:
```php
// Get top 3 ranked users for podium
$topUsers = $group->rankedUsers()->limit(3)->get();

// Pass to view
return view('groups.show', array_merge($cachedData, [
    'currentMatchday' => null,
    'topUsers' => $topUsers  // Pre-sorted, pre-limited
]));
```

Then in view:
```php
$topUsers = $topUsers ?? $group->users->sortByDesc('total_points')->take(3)->values();
```

---

## 📊 Performance Comparison

### Before (Current - with withSum)
```
SELECT users.* 
LEFT JOIN group_user ON group_user.group_id = ? AND group_user.user_id = users.id
LEFT JOIN answers ON answers.user_id = users.id
LEFT JOIN questions ON questions.id = answers.question_id AND questions.group_id = ?
GROUP BY users.id
ORDER BY SUM(answers.points_earned) DESC
LIMIT 3;
```

**Cost:** 3 LEFT JOINs + GROUP BY + SUM calculation

### After (using group_user.points)
```
SELECT users.*, group_user.points as total_points
FROM users
INNER JOIN group_user ON group_user.group_id = ? AND group_user.user_id = users.id
ORDER BY group_user.points DESC
LIMIT 3;
```

**Cost:** 1 INNER JOIN (indexed pivot) + simple ORDER BY

---

## 🔍 Additional Optimization Opportunities

### 1. Check Other Controllers Using withSum

Files to search:
- `RankingController.php` - Line 21, 106, 121
- Other places using `withSum` on answers

These might also benefit from using `group_user.points` where applicable.

### 2. Verify group_user.points Has Index

```sql
-- Verify points column is indexed
SHOW INDEX FROM group_user WHERE Column_name = 'points';

-- If not indexed, add:
ALTER TABLE group_user ADD INDEX idx_points (points);
```

---

## 🧪 Testing Checklist

- [ ] Ranking displays top 3 users correctly
- [ ] Points match the cached `group_user.points` value
- [ ] Pre-Match castigos still work correctly
- [ ] Rankings update in real-time after new answers
- [ ] No SQL errors or N+1 queries
- [ ] Dark mode still works in ranking podium
- [ ] Mobile responsive on ranking section

---

## ✅ Execution Order

1. ✅ Phase 1: Real-time sync (VerifyAllQuestionsJob) - COMPLETE
2. ✅ Phase 2: Historical sync migration - COMPLETE  
3. 🟡 Phase 3: Validate featured questions - COMPLETE
4. 🟡 **Phase 4: Optimize rankings** (THIS PHASE)
   - Step 1: Update Group::rankedUsers() method
   - Step 2: Update GroupController::show() query
   - Step 3: Update groups.show.blade.php view
   - Step 4: Test and verify
5. ⏳ Phase 5: Optimize other ranking queries (RankingController, etc.)
6. ⏳ Phase 6: Comprehensive testing & staging verification
7. ⏳ Phase 7: Production deployment

---

## 📝 Notes

- This phase has **zero impact** until Phases 1 & 2 are complete and migration is run
- After migration, `group_user.points` will contain the synced user points
- This optimization primarily benefits the `groups.show` view (main interface)
- RankingController may need similar optimizations (Phase 5)
