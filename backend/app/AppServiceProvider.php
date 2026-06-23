<?php

namespace App;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        VerifyEmail::createUrlUsing(function (object $notifiable): string {
            /** @var string $frontendUrl */
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

            /** @var \App\Modules\User\Infrastructure\Models\User $notifiable */
            /** @var int|string $key */
            $key = $notifiable->getKey();
            /** @var string $email */
            $email = $notifiable->getEmailForVerification();

            $signedUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                [
                    'id' => (string) $key,
                    'hash' => sha1($email),
                ]
            );

            /** @var string $appUrl */
            $appUrl = config('app.url');

            return str_replace($appUrl, $frontendUrl, (string) $signedUrl);
        });
    }
}
