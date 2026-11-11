<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

Artisan::command('demo:token', function (): int {
    if (! app()->environment('local')) {
        $this->error('This command is available only in the local environment.');

        return 1;
    }

    $user = User::firstOrCreate(['email' => 'demo@example.test'], [
        'name' => 'Demo user',
        'password' => Str::random(64),
    ]);
    $this->line($user->createToken('local-demo', ['*'], now()->addHour())->plainTextToken);

    return 0;
})->purpose('Create a local demo Bearer token that expires in one hour');
