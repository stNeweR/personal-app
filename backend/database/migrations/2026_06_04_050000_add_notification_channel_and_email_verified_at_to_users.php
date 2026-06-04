<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('notification_channel')
                ->nullable()
                ->after('telegram_id');

            $table->timestamp('email_verified_at')
                ->nullable()
                ->after('email');
        });

        \Illuminate\Support\Facades\DB::table('users')
            ->where('telegram_notifications_enabled', true)
            ->update(['notification_channel' => 'telegram']);

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('telegram_notifications_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('telegram_notifications_enabled')
                ->default(false)
                ->after('telegram_id');
        });

        \Illuminate\Support\Facades\DB::table('users')
            ->where('notification_channel', 'telegram')
            ->update(['telegram_notifications_enabled' => true]);

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('notification_channel');
            $table->dropColumn('email_verified_at');
        });
    }
};
