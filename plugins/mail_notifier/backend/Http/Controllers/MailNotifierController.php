<?php

declare(strict_types=1);

namespace Plugins\MailNotifier\Http\Controllers;

use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\MailNotifier\Services\MailNotifierService;

final class MailNotifierController
{
    public function __construct(
        private readonly MailNotifierService $mailNotifierService,
    ) {}

    public function status(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json([
            'connected' => $this->mailNotifierService->isConnected($user),
            'verified' => $this->mailNotifierService->isVerified($user),
            'email' => $user->email,
        ]);
    }

    public function connect(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $email = (string) $request->input('email');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'Valid email is required'], 422);
        }

        try {
            $this->mailNotifierService->connect($user, $email);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => 'Email saved. Please verify your email.']);
    }

    public function disconnect(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $this->mailNotifierService->disconnect($user);

        return response()->json(['message' => 'Mail notifier disconnected']);
    }

    public function sendVerification(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $this->mailNotifierService->sendVerificationEmail($user);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => 'Verification email sent']);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $code = (string) $request->input('code');

        if ($code === '') {
            return response()->json(['message' => 'Code is required'], 422);
        }

        try {
            $this->mailNotifierService->verifyEmail($user, $code);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => 'Email verified successfully']);
    }
}
