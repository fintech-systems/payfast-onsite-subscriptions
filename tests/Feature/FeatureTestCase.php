<?php

namespace Tests\Feature;

use Tests\Fixtures\User;
use Orchestra\Testbench\TestCase;
use Livewire\LivewireServiceProvider;
use FintechSystems\PayFast\PayFastServiceProvider;

abstract class FeatureTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations();

        $this->artisan('migrate')->run();
    }

    protected function createBillable($description = 'taylor', array $options = []): User
    {
        $user = $this->createUser($description);

        $user->createAsCustomer($options);

        return $user;
    }

    protected function createUser($description = 'taylor', array $options = []): User
    {
        return User::create(array_merge([
            'email' => "{$description}@payfast-test.com",
            'name' => 'Taylor Otwell',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        ], $options));
    }
    
    protected function getPackageProviders($app)
    {
        return [
            PayFastServiceProvider::class,
            LivewireServiceProvider::class,            
        ];
    }
}