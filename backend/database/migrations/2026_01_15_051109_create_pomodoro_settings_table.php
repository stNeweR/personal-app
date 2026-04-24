<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE_NAME = 'pomodoro_settings';

    public function up(): void
    {
        Schema::create(self::TABLE_NAME, function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->comment('Идентификатор пользователя')
                ->references('id')
                ->on('users');

            $table->unsignedInteger('work_duration')
                ->default(25)
                ->comment('Время рабочего промежутка в минутах');
            $table->unsignedInteger('break_duration')
                ->default(5)
                ->comment('Время перерыва');
            $table->unsignedTinyInteger('repeats_count')
                ->default(3)
                ->comment('Количество повторений');

            $table->unsignedInteger('long_break_duration')
                ->nullable()
                ->default(10)
                ->comment('Длительность длинного перерыва (минуты)');
            $table->unsignedSmallInteger('cycles_before_long_break')
                ->nullable()
                ->default(2)
                ->comment('Количество циклов перед длинным перерывом');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE_NAME);
    }
};
