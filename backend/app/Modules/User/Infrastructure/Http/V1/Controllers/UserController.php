<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Http\V1\Controllers;

use App\Modules\Plugin\Infrastructure\Models\Plugin;
use App\Modules\User\Application\DTOs\UserProfileDTO;
use App\Modules\User\Application\UseCases\Auth\GetAuthenticatedUserUseCase;
use App\Modules\User\Domain\Enums\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UserController
{
    public function updatePlan(Request $request, GetAuthenticatedUserUseCase $getUser): JsonResponse
    {
        /** @var \App\Modules\User\Infrastructure\Models\User $user */
        $user = $request->user();
        /** @var array{plan: string} $validated */
        $validated = $request->validate([
            'plan' => 'required|string|in:junior,middle,senior',
        ]);
        $plan = Plan::from($validated['plan']);

        $user->update(['plan' => $plan]);

        if ($plan === Plan::Junior) {
            Plugin::where('enabled', true)->update(['enabled' => false]);
        }

        if ($plan === Plan::Middle) {
            $enabledCount = Plugin::where('enabled', true)->count();
            if ($enabledCount > 2) {
                $excess = Plugin::where('enabled', true)
                    ->orderBy('updated_at', 'asc')
                    ->skip(2)
                    ->get();
                foreach ($excess as $plugin) {
                    $plugin->update(['enabled' => false]);
                }
            }
        }

        return response()->json(UserProfileDTO::from($getUser->execute()));
    }
}
