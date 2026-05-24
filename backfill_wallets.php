<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \App\Models\User::all();
foreach ($users as $user) {
    if ($user->wallets()->count() == 0) {
        $user->wallets()->create([
            'name' => 'Dompet Tunai',
            'type' => 'cash',
            'balance' => 0
        ]);
        echo "Created wallet for " . $user->email . "\n";
    }
}
echo "Done.\n";
