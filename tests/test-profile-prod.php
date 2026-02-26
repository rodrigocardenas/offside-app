<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Test 1: Obtener usuario 243
    $user = App\Models\User::find(243);
    if (!$user) {
        echo "ERROR: User 243 not found\n";
        exit(1);
    }
    
    echo "✓ User found: " . $user->email . "\n";
    
    // Test 2: Cargar roles
    $user->load('roles');
    echo "✓ Roles loaded: " . $user->roles->count() . "\n";
    
    // Test 3: Obtener competiciones
    $competitions = App\Models\Competition::orderBy('name')->get();
    echo "✓ Competitions fetched: " . $competitions->count() . "\n";
    
    // Test 4: Si hay competition_id, cargar equipos
    if ($user->favorite_competition_id) {
        $clubs = App\Models\Team::where('type', 'club')
            ->whereHas('competitions', function ($query) use ($user) {
                $query->where('competitions.id', $user->favorite_competition_id);
            })
            ->orderBy('name')
            ->get();
        echo "✓ Clubs loaded: " . $clubs->count() . "\n";
    } else {
        echo "✓ No favorite competition set (NULL) - that's OK\n";
    }
    
    echo "\n✅ All profile edit operations successful!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
?>
