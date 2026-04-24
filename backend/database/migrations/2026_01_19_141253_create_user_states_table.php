<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE_NAME = 'user_states';

    public function up(): void
    {
        Schema::create(self::TABLE_NAME, function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->comment('Идентификатор пользователя')
                ->references('id')
                ->on('users');

            $table->string('state_value')
                ->index()
                ->comment('Текущее состояние пользователя');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE_NAME);
    }
};
