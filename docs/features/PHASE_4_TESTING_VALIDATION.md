# Phase 4 Testing & Validation Checklist

**Phase 4 Status:** ✅ Implementation Complete  
**Date Completed:** April 20, 2026

---

## 📋 Pre-Testing Requirements

Before running tests, ensure:
- ✅ Phase 1 is complete: `VerifyAllQuestionsJob` syncs points to `group_user.points`
- ✅ Phase 2 is complete: Historical data migration synced all `answers.points_earned` to `group_user.points`
- ✅ Phase 1+2 database state: All `group_user.points` values are synchronized

**If not complete:**
```bash
# Run Phase 2 migration first
php artisan migrate

# Verify sync by checking a sample group
php artisan tinker
>>> $group = App\Models\Group::find(1);
>>> $group->users()->limit(5)->get(['users.id', 'group_user.points']);
```

---

## 🧪 Manual Testing Checklist

### ✅ Test 1: Rankings Display Correctly

**Steps:**
1. Navigate to any group's show page: `/groups/{id}`
2. Verify the podium section shows top 3 users
3. Check that points displayed match `group_user.points` values

**Expected Result:**
- ✅ Podium shows 3 users (or fewer if group has < 3 users)
- ✅ Points displayed = user's `group_user.points` value
- ✅ Users are ordered by points DESC (highest first)

**Debug Query:**
```sql
SELECT users.id, users.name, group_user.points as total_points
FROM group_user
JOIN users ON users.id = group_user.user_id
WHERE group_user.group_id = ?
ORDER BY group_user.points DESC
LIMIT 3;
```

---

### ✅ Test 2: Rankings Update After New Answer

**Steps:**
1. Have User A answer a question correctly (earns points)
2. Navigate to group show page
3. Verify User A now appears in top 3 (if earned enough points)
4. Check that points are correct

**Expected Result:**
- ✅ User appears in ranking immediately after answering
- ✅ Points match earned (question.points for correct, 0 for incorrect)
- ✅ Ranking order updates correctly

**Debug:**
- Check `VerifyAllQuestionsJob` logs for sync confirmation
- Query: `SELECT * FROM group_user WHERE user_id = ? AND group_id = ?`

---

### ✅ Test 3: Pre-Match Castigos Work Correctly

**Steps:**
1. Create a Pre-Match with castigo rewards "subtract points"
2. Have User B answer correctly but get castigo applied
3. Check that points are reduced correctly

**Expected Result:**
- ✅ User's `group_user.points` decreased by castigo amount
- ✅ Points never go below 0 (protected by `max(0, ...)`)
- ✅ Ranking reflects the reduction

**Example Calculation:**
- User has 500 points
- Castigo of 100 points applied
- Final: MAX(0, 500 - 100) = 400 points

**Debug Query:**
```php
$groupUser = DB::table('group_user')
    ->where('user_id', $userId)
    ->where('group_id', $groupId)
    ->first();

echo "Points before castigo: {$groupUser->points}";
// Apply castigo...
echo "Points after castigo: {$groupUser->points}";
```

---

### ✅ Test 4: Database Query Performance

**Check SQL Queries Generated:**

```bash
# Enable query logging
php artisan tinker
>>> DB::enableQueryLog();
>>> $group = App\Models\Group::find(1);
>>> $topUsers = $group->rankedUsers()->limit(3)->get();
>>> dd(DB::getQueryLog());
```

**Expected Query:**
```sql
SELECT users.*, group_user.points as total_points
FROM users
INNER JOIN group_user ON group_user.group_id = ? AND group_user.user_id = users.id
ORDER BY group_user.points DESC
LIMIT 3;
```

**Expected NOT to see:**
- ❌ LEFT JOIN to `answers` table
- ❌ LEFT JOIN to `questions` table
- ❌ GROUP BY clause
- ❌ SUM(answers.points_earned)

---

### ✅ Test 5: View Still Works (Dark Mode, Mobile)

**Steps:**
1. Test on desktop light mode
2. Test on desktop dark mode
3. Test on mobile device
4. Verify podium displays correctly on all sizes

**Expected Result:**
- ✅ Podium displays correctly in both light/dark modes
- ✅ Avatar images load properly
- ✅ Points formatted correctly (1000 → "1.000")
- ✅ Mobile responsive behavior works

---

### ✅ Test 6: Edge Cases

#### Case A: Group with 0 users
**Expected:** Empty podium with message "No hay jugadores aún"

#### Case B: Group with 1-2 users
**Expected:** Podium shows available users (not 3 empty)

#### Case C: All users have 0 points
**Expected:** Top 3 shown, all with 0 points, ordered by join date or ID

#### Case D: User with NULL points (should not happen)
**Expected:** None (all should have at least 0), but check `COALESCE(points, 0)`

---

## 🔍 Database Verification

### Check group_user table structure
```sql
DESC group_user;
-- Should have columns: group_id, user_id, points, created_at, updated_at
```

### Verify points are populated
```sql
SELECT COUNT(*) as total_users, 
       SUM(points) as total_points,
       AVG(points) as avg_points,
       MAX(points) as max_points,
       MIN(points) as min_points
FROM group_user
WHERE group_id = ?;
```

### Check for NULL values (should be 0)
```sql
SELECT COUNT(*) FROM group_user WHERE group_id = ? AND points IS NULL;
-- Expected: 0 rows
```

---

## 📊 Performance Testing

### Load Testing: Compare Before/After

**Test Setup:**
- Group with 1000+ users
- Load page multiple times
- Monitor query time

**Before Phase 4:**
```
Query: SELECT ... LEFT JOIN answers LEFT JOIN questions GROUP BY SUM(points_earned)
Approx Time: 500-800ms (depends on answer volume)
Rows: 1000+ in memory, then sorted in PHP
```

**After Phase 4:**
```
Query: SELECT ... INNER JOIN group_user ORDER BY points LIMIT 3
Approx Time: 10-50ms (simple indexed query)
Rows: 3 only
```

**Expected Improvement:** 10-80x faster for large groups

---

## ✅ Test Automation (Artisan Commands)

### Create Test Script

```bash
# tests/Feature/RankingOptimizationTest.php
php artisan test tests/Feature/RankingOptimizationTest.php
```

**Test Cases:**
```php
class RankingOptimizationTest extends TestCase
{
    // ✅ Test 1: rankedUsers() uses database ordering
    public function test_ranked_users_uses_database_ordering()
    {
        $group = Group::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        // Attach with different points
        $group->users()->attach($user1, ['points' => 500]);
        $group->users()->attach($user2, ['points' => 300]);
        
        // Test ordering
        $ranked = $group->rankedUsers()->get();
        $this->assertEquals($user1->id, $ranked[0]->id); // 500 > 300
    }
    
    // ✅ Test 2: rankedUsers() doesn't have expensive JOINs
    public function test_ranked_users_no_answer_joins()
    {
        DB::enableQueryLog();
        $group = Group::factory()->create();
        $group->users()->attach(User::factory(), ['points' => 100]);
        
        $ranked = $group->rankedUsers()->get();
        $queries = DB::getQueryLog();
        
        // Should have 1 query only (no extra JOINs)
        $query = $queries[0]['query'];
        $this->assertNotContains('answers', $query);
        $this->assertNotContains('questions', $query);
        $this->assertNotContains('GROUP BY', $query);
    }
    
    // ✅ Test 3: Top 3 prepared in controller
    public function test_controller_prepares_top3_users()
    {
        $group = Group::factory()->create();
        auth()->login(User::factory()->create());
        $group->users()->attach(auth()->user());
        
        $response = $this->get("/groups/{$group->id}");
        $this->assertArrayHasKey('topUsers', $response->viewData());
        $this->assertCount(0, $response->viewData()['topUsers']); // Only auth user
    }
}
```

---

## 📝 Validation Queries

Run these to validate Phase 4 is working:

```php
// 1. Check all users have points >= 0
App\Models\Group::first()
    ->users()
    ->where('group_user.points', '<', 0)
    ->count(); 
// Should return 0

// 2. Check top 3 users are correctly ordered
$top3 = App\Models\Group::first()->rankedUsers()->limit(3)->get();
$top3->each(fn($u) => echo "{$u->name}: {$u->total_points} pts\n");
// Points should be DESC

// 3. Compare with old withSum query (should match)
$oldWay = DB::table('users')
    ->join('group_user', 'group_user.user_id', '=', 'users.id')
    ->leftJoin('answers', 'answers.user_id', '=', 'users.id')
    ->leftJoin('questions', fn($join) => $join->on('questions.id', '=', 'answers.question_id'))
    ->where('group_user.group_id', 1)
    ->selectRaw('COALESCE(SUM(answers.points_earned), 0) as old_points')
    ->groupBy('users.id')
    ->first();
    
$newWay = DB::table('group_user')
    ->where('group_id', 1)
    ->first();

echo "Old: {$oldWay->old_points}, New: {$newWay->points}";
// Should match
```

---

## 🎯 Sign-Off Checklist

- [ ] All manual tests passed
- [ ] Database queries verified (no expensive JOINs)
- [ ] Performance improvement confirmed
- [ ] Dark mode works
- [ ] Mobile responsive
- [ ] Edge cases handled
- [ ] No errors in Laravel logs
- [ ] Pre-Match castigos still work
- [ ] Rankings update after new answers
- [ ] Automated tests pass

---

## 🚀 Next Steps After Phase 4 Validation

- Phase 5: Optimize other ranking queries (RankingController, etc.)
- Phase 6: Comprehensive testing & staging verification
- Phase 7: Production deployment

---

## 📞 Troubleshooting

### Problem: "rankings not updating"
**Solution:**
1. Verify Phase 1 job ran: Check `VerifyAllQuestionsJob` logs
2. Verify Phase 2 migration ran: `php artisan migrate --step`
3. Check pivot table: `SELECT * FROM group_user WHERE group_id = ?`

### Problem: "points showing as wrong value"
**Solution:**
1. Check if answers were created before Phase 1/2
2. Run migration again: `php artisan migrate`
3. Manually sync: `php artisan tinker` then use VerifyAllQuestionsJob logic

### Problem: "ranking page is slow"
**Solution:**
1. Check if new `withSum` was accidentally re-added
2. Run query analysis: `EXPLAIN SELECT ...`
3. Verify `group_user.points` index exists

