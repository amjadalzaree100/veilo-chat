<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_creation_generates_public_id_recovery_secret_and_device(): void
    {
        $response = $this->postJson('/api/accounts', [
            'name_display' => 'First User',
            'device_identifier' => 'browser-device-1',
            'platform' => 'web',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.name_display', 'First User')
            ->assertJsonPath('device.device_identifier', 'browser-device-1')
            ->assertJsonStructure(['user.public_id', 'device.id', 'recovery_secret']);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('devices', 1);
        $this->assertNotNull(User::first()->getRawOriginal('recovery_secret'));
    }

    public function test_account_creation_requires_name_and_device_identifier(): void
    {
        $this->postJson('/api/accounts', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name_display', 'device_identifier']);
    }
}
