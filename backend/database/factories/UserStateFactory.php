<?php

namespace Database\Factories;

use App\Modules\Pomodoro\Domain\Enums\StateValue;
use App\Modules\User\Infrastructure\Models\User;
use App\Modules\User\Infrastructure\Models\UserState;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserState>
 */
final class UserStateFactory extends Factory
{
    protected $model = UserState::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'state_value' => StateValue::values()[array_rand(StateValue::values())],
        ];
    }
}
