<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE_NAME = 'pomodoro_sessions';

    public function up(): void
    {
        Schema::table(self::TABLE_NAME, function (Blueprint $table) {
            $table->string('previous_status')
                ->nullable()
                ->after('current_status')
                ->comment('Предыдущий статус (для paused)');

            $table->timestamp('phase_started_at')
                ->nullable()
                ->after('current_cycle')
                ->comment('Время начала текущей фазы (с учётом пауз)');

            $table->unsignedInteger('time_left')
                ->nullable()
                ->after('phase_started_at')
                ->comment('Оставшееся время в секундах (для paused)');
        });
    }

    public function down(): void
    {
        Schema::table(self::TABLE_NAME, function (Blueprint $table) {
            $table->dropColumn(['previous_status', 'phase_started_at', 'time_left']);
        });
    }
};
