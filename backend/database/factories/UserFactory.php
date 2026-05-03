<?php

namespace Database\Factories;

use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'telegram_id' => fake()->randomNumber(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function apiUser(): self
    {
        return $this->state(fn (array $attributes) => [
            'telegram_id' => null,
        ]);
    }

    public function telegramUser(): self
    {
        return $this->state(fn (array $attributes) => [
            'name' => null,
            'email' => null,
            'password' => null,
            'remember_token' => null,
        ]);
    }
}
