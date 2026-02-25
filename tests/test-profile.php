<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $user = App\Models\User::where('email', 'admin@example.com')->first();
    if (!$user) {
        echo "User not found\n";
        exit(1);
    }
    
    echo "User found: " . $user->email . "\n";
    echo "Favorite competition ID: " . ($user->favorite_competition_id ?? 'NULL') . "\n";
    
    // Try to access the ProfileController
    $controller = new App\Http\Controllers\ProfileController();
    echo "ProfileController instantiated\n";
    
    // Create a request
    $request = Illuminate\Http\Request::create('/profile', 'GET');
    $request->setUserResolver(function() use ($user) {
        return $user;
    });
    
    echo "Request created\n";
    
    // Try to call the edit method
    $response = $controller->edit();
    echo "ProfileController::edit() executed successfully\n";
    echo "Response type: " . get_class($response) . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nTrace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
?>
