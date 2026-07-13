<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\GlobalNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationInboxTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedUser(?int $exceptId = null): User
    {
        return User::query()
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->whereNotNull('email_verified_at')
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createNotification(User $user, array $data = [], bool $isRead = false): DatabaseNotification
    {
        return $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => GlobalNotification::class,
            'data' => array_merge([
                'title' => 'Atualização operacional',
                'message' => 'Existe uma tarefa do laboratório que requer atenção.',
                'type' => 'info',
            ], $data),
            'read_at' => $isRead ? now() : null,
        ]);
    }

    public function test_user_can_open_their_paginated_notification_inbox(): void
    {
        $user = $this->verifiedUser();
        $user->notifications()->delete();
        $this->createNotification($user, ['title' => 'Resultado disponível']);
        $this->createNotification($user, ['title' => 'Revisão concluída'], true);

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Notifications/Index')
            ->has('notifications', 2)
            ->where('pagination.total', 2));
    }

    public function test_user_can_clear_read_notifications_without_deleting_unread_notifications(): void
    {
        $user = $this->verifiedUser();
        $readNotification = $this->createNotification($user, [], true);
        $unreadNotification = $this->createNotification($user);

        $response = $this->actingAs($user)->delete(route('notifications.clear-read'));

        $response->assertRedirect();
        $this->assertDatabaseMissing('notifications', ['id' => $readNotification->id]);
        $this->assertDatabaseHas('notifications', ['id' => $unreadNotification->id]);
    }

    public function test_user_can_clear_only_their_notification_inbox(): void
    {
        $user = $this->verifiedUser();
        $otherUser = $this->verifiedUser($user->id);
        $ownNotification = $this->createNotification($user);
        $otherNotification = $this->createNotification($otherUser);

        $response = $this->actingAs($user)->delete(route('notifications.clear-all'));

        $response->assertRedirect();
        $this->assertDatabaseMissing('notifications', ['id' => $ownNotification->id]);
        $this->assertDatabaseHas('notifications', ['id' => $otherNotification->id]);
    }

    public function test_user_cannot_modify_another_users_notification(): void
    {
        $user = $this->verifiedUser();
        $otherUser = $this->verifiedUser($user->id);
        $notification = $this->createNotification($otherUser);

        $this->actingAs($user)
            ->post(route('notifications.read', $notification))
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('notifications.delete', $notification))
            ->assertForbidden();

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'read_at' => null,
        ]);
    }
}
