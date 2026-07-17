<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Planning;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DestinationReplacementTest extends TestCase
{
    use RefreshDatabase;

    public function test_selected_destinations_are_merged_and_plannings_are_preserved(): void
    {
        $user = $this->authorizedUser();
        $correct = Destination::create(['name' => 'Casablanca']);
        $duplicateOne = Destination::create(['name' => 'Casa']);
        $duplicateTwo = Destination::create(['name' => 'Casablanca ville']);
        $first = Planning::create(['date_du' => '2026-07-17', 'ref_dossier' => 'DEST-1', 'destination_id' => $duplicateOne->id, 'budget' => 900]);
        $second = Planning::create(['date_du' => '2026-07-18', 'ref_dossier' => 'DEST-2', 'destination_id' => $duplicateTwo->id, 'budget' => 1200]);

        $this->actingAs($user)->post(route('destinations.replace-selected'), [
            'selected_ids' => [$duplicateOne->id, $duplicateTwo->id],
            'replacement_destination_id' => $correct->id,
        ])->assertRedirect(route('destinations.index'))->assertSessionHas('success');

        $this->assertSame($correct->id, $first->fresh()->destination_id);
        $this->assertSame($correct->id, $second->fresh()->destination_id);
        $this->assertSame('900.00', $first->fresh()->budget);
        $this->assertDatabaseMissing('destinations', ['id' => $duplicateOne->id]);
        $this->assertDatabaseMissing('destinations', ['id' => $duplicateTwo->id]);
        $this->assertDatabaseHas('destinations', ['id' => $correct->id]);
    }

    public function test_destination_cannot_be_replaced_only_by_itself(): void
    {
        $user = $this->authorizedUser();
        $destination = Destination::create(['name' => 'Marrakech']);

        $this->actingAs($user)->post(route('destinations.replace-selected'), [
            'selected_ids' => [$destination->id],
            'replacement_destination_id' => $destination->id,
        ])->assertSessionHasErrors('selected_ids');

        $this->assertDatabaseHas('destinations', ['id' => $destination->id]);
    }

    public function test_user_without_manage_permission_cannot_replace_destinations(): void
    {
        Permission::firstOrCreate(['name' => 'destinations.manage', 'guard_name' => 'web']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $correct = Destination::create(['name' => 'Agadir']);
        $duplicate = Destination::create(['name' => 'Agadir City']);

        $this->actingAs($user)->post(route('destinations.replace-selected'), [
            'selected_ids' => [$duplicate->id],
            'replacement_destination_id' => $correct->id,
        ])->assertStatus(303)->assertSessionHas('authorization_error');

        $this->assertDatabaseHas('destinations', ['id' => $duplicate->id]);
    }

    private function authorizedUser(): User
    {
        $permission = Permission::firstOrCreate(['name' => 'destinations.manage', 'guard_name' => 'web']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->givePermissionTo($permission);

        return $user;
    }
}
