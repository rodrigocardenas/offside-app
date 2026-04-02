<?php
// Test modal create pre-match flow
require __DIR__ . '/bootstrap/app.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$response = $kernel->handle(
    $request = \Illuminate\Http\Request::capture()
);

use App\Models\Group;
use App\Models\User;
use App\Models\Competition;

echo "Finding or creating test group...\n";

$group = Group::with(['competition', 'users'])->first();

if ($group) {
    echo "✓ Grupo encontrado!\n";
    echo "  ID: " . $group->id . "\n";
    echo "  Code: " . $group->code . "\n";
    echo "  URL: http://offsideclub.test/groups/" . $group->code . "\n";
} else {
    echo "Creating group...\n";

    $comp = Competition::first();
    if (!$comp) {
        $comp = Competition::create(['name' => 'Test 2024', 'year' => 2024]);
        echo "  Created competition: " . $comp->id . "\n";
    }

    $user = User::first();
    if (!$user) {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@offsideclub.test',
            'password' => bcrypt('password123')
        ]);
        echo "  Created user: " . $user->id . "\n";
    }

    $code = 'TEST' . strtoupper(substr(uniqid(), -4));
    $group = Group::create([
        'competition_id' => $comp->id,
        'created_by' => $user->id,
        'code' => $code
    ]);

    $group->users()->attach($user->id);

    echo "✓ Nuevo grupo creado!\n";
    echo "  ID: " . $group->id . "\n";
    echo "  Code: " . $group->code . "\n";
    echo "  URL: http://offsideclub.test/groups/" . $group->code . "\n";
    echo "  User: " . $user->email . "\n";
}

echo "\nNow you can:\n";
echo "1. Open browser to the group URL above\n";
echo "2. Look for 'Pre Matches' section\n";
echo "3. Click 'Crear Pre Match'\n";
echo "4. The modal should open and allow you to search for matches\n";
?>
