<?php

namespace Tests\Feature;

use App\Models\LabNetwork;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InitializeLaboratoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_empty_installation_can_be_initialized_without_a_default_password(): void
    {
        $this->artisan('app:initialize-laboratory', ['email' => 'owner@example.test', 'name' => 'Lab Owner', 'lab' => 'Example Lab'])->assertSuccessful();
        $user = User::where('email', 'owner@example.test')->firstOrFail();
        $lab = VAPLab::firstOrFail();
        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse(Hash::check('password', $user->password));
        $this->assertSame($lab->id, LabNetwork::firstOrFail()->main_lab_id);
        $this->assertDatabaseHas('lab_user', ['user_id' => $user->id, 'lab_id' => $lab->id, 'can_view_network' => true]);
    }

    public function test_initialization_never_overwrites_existing_users(): void
    {
        $user = User::factory()->create();
        $password = $user->password;
        $this->artisan('app:initialize-laboratory', ['email' => $user->email, 'name' => 'Overwrite', 'lab' => 'Example Lab'])->assertFailed();
        $this->assertSame($password, $user->fresh()->password);
        $this->assertDatabaseCount('labs', 0);
    }
}
