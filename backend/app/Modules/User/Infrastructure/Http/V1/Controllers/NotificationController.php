<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Http\V1\Controllers;

use App\Modules\User\Application\DTOs\NotificationPreferencesResponseDTO;
use App\Modules\User\Application\UseCases\Notifications\GetNotificationPreferencesUseCase;
use App\Modules\User\Application\UseCases\Notifications\SendEmailVerificationUseCase;
use App\Modules\User\Application\UseCases\Notifications\UpdateNotificationChannelUseCase;
use App\Modules\User\Application\UseCases\Notifications\UpdateUserEmailUseCase;
use App\Modules\User\Application\UseCases\Notifications\VerifyEmailUseCase;
use App\Modules\User\Infrastructure\Http\V1\Requests\Notifications\UpdateChannelRequest;
use App\Modules\User\Infrastructure\Http\V1\Requests\Notifications\UpdateEmailRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationController
{
    public function status(GetNotificationPreferencesUseCase $useCase): NotificationPreferencesResponseDTO
    {
        return $useCase->execute();
    }

    public function updateChannel(UpdateChannelRequest $request, UpdateNotificationChannelUseCase $useCase): JsonResponse
    {
        /** @var array{channel: ?string} $validated */
        $validated = $request->validated();

        try {
            $useCase->execute($validated['channel'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['data' => ['channel' => $validated['channel'] ?? null]]);
    }

    public function updateEmail(UpdateEmailRequest $request, UpdateUserEmailUseCase $useCase): JsonResponse
    {
        /** @var array{email: string} $validated */
        $validated = $request->validated();
        $useCase->execute($validated['email']);

        return response()->json(['data' => ['email' => $validated['email']]]);
    }

    public function sendEmailVerification(SendEmailVerificationUseCase $useCase): JsonResponse
    {
        try {
            $useCase->execute();
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['data' => ['sent' => true]]);
    }

    public function verifyEmail(Request $request, VerifyEmailUseCase $useCase): \Illuminate\Http\RedirectResponse
    {
        $frontend = rtrim($this->frontendUrl(), '/');

        if (! $request->hasValidSignature()) {
            return redirect()->away($frontend.'/dashboard?email_verified=0');
        }

        $token = (string) $request->query('token', '');
        $email = (string) $request->query('email', '');

        if ($token === '' || $email === '') {
            return redirect()->away($frontend.'/dashboard?email_verified=0');
        }

        try {
            $useCase->execute($token, $email);
        } catch (\InvalidArgumentException) {
            return redirect()->away($frontend.'/dashboard?email_verified=0');
        }

        return redirect()->away($frontend.'/dashboard?email_verified=1');
    }

    private function frontendUrl(): string
    {
        /** @var mixed $configured */
        $configured = config('app.frontend_url');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        /** @var mixed $url */
        $url = config('app.url');

        return is_string($url) ? $url : '';
    }
}
