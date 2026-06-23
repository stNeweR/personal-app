<?php

namespace Database\Factories;

use App\Modules\Pomodoro\Domain\Enums\PomodoroStatusValue;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PomodoroSession>
 */
final class PomodoroSessionFactory extends Factory
{
    protected $model = PomodoroSession::class;

    public function definition(): array
    {
        return [
            'user_id' => UserFactory::new(),
            'current_status' => fake()->randomElement(PomodoroStatusValue::cases()),
            'previous_status' => null,
            'start_at' => now(),
            'end_at' => null,
            'current_cycle' => 1,
            'settings' => null,
            'phase_started_at' => null,
            'time_left' => null,
        ];
    }

    public function finished(): self
    {
        return $this->state(fn (array $attributes) => [
            'current_status' => PomodoroStatusValue::FINISHED,
            'end_at' => now(),
        ]);
    }

    public function paused(): self
    {
        return $this->state(fn (array $attributes) => [
            'current_status' => PomodoroStatusValue::PAUSED,
            'previous_status' => PomodoroStatusValue::WORK,
            'time_left' => fake()->numberBetween(1, 3600),
        ]);
    }
}
