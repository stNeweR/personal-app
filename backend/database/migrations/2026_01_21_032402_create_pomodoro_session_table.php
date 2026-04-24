<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE_NAME = 'pomodoro_session';

    public function up(): void
    {
        Schema::create(self::TABLE_NAME, function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->comment('Идентификатор пользователя');

            $table->string('current_status')
                ->comment('Текущее состояние помодоро таймера');

            $table->timestamp('start_at')
                ->comment('Время начала сессии');
            $table->timestamp('end_at')
                ->default(null)
                ->nullable()
                ->comment('Время конца сессии');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE_NAME);
    }
};
