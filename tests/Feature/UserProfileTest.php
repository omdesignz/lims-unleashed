<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use DatabaseTransactions;

    public function test_authenticated_user_can_open_account_security_settings(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('security'))
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Show')
                ->has('sessions')
                ->has('passkeys')
                ->has('confirmsTwoFactorAuthentication')
            );
    }

    public function test_guest_cannot_delete_an_account(): void
    {
        $this->delete(route('current-user.destroy'), ['password' => 'password'])
            ->assertRedirect(route('login'));
    }

    public function test_account_deletion_requires_the_current_password(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->delete(route('current-user.destroy'), ['password' => 'incorrect-password'])
            ->assertSessionHasErrors('password');

        $this->assertAuthenticatedAs($user);
        $this->assertNotSoftDeleted($user);
    }

    public function test_user_can_permanently_close_their_account(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->delete(route('current-user.destroy'), ['password' => 'password'])
            ->assertRedirect('/')
            ->assertSessionHas('status', 'account-deleted');

        $this->assertGuest();
        $this->assertSoftDeleted($user);
    }
}
