<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ManagerActivityNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ManagerActivityNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_recipients_are_active_users_with_the_three_admin_roles_without_duplicates(): void
    {
        foreach (['admin', 'assistant_admin', 'super_admin', 'driver'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $admin = User::factory()->create(['active' => true]);
        $admin->assignRole('admin');

        $assistant = User::factory()->create(['active' => true]);
        $assistant->assignRole('assistant_admin');

        $superAdmin = User::factory()->create(['active' => true]);
        $superAdmin->assignRole(['super_admin', 'admin']);

        $inactiveAdmin = User::factory()->create(['active' => false]);
        $inactiveAdmin->assignRole('admin');

        $driver = User::factory()->create(['active' => true]);
        $driver->assignRole('driver');

        $recipients = app(ManagerActivityNotificationService::class)->recipientUserIds();

        $this->assertEqualsCanonicalizing(
            [$admin->id, $assistant->id, $superAdmin->id],
            $recipients
        );
        $this->assertSame($recipients, array_values(array_unique($recipients)));
        $this->assertNotContains($inactiveAdmin->id, $recipients);
        $this->assertNotContains($driver->id, $recipients);
    }
}
