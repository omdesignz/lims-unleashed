<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\PortalServiceInvitation;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PortalServiceInvitation>
 */
class PortalServiceInvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lab_id' => VAPLab::factory(),
            'issued_by_id' => User::factory(),
            'customer_id' => fn (): int => Customer::query()->create(['name' => fake()->company()])->id,
            'warehouse_id' => fn (array $attributes): int => Warehouse::query()->create([
                'customer_id' => $attributes['customer_id'], 'name' => fake()->company(),
                'email' => fake()->unique()->safeEmail(), 'email_verified_at' => now(),
            ])->id,
            'token' => (string) Str::uuid(),
            'expires_at' => now()->addDays(30),
        ];
    }
}
