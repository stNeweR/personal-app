<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Http\V1\Controllers;

use App\Modules\User\Application\DTOs\AuthUserResponseDTO;
use App\Modules\User\Application\DTOs\LoginUserDTO;
use App\Modules\User\Application\DTOs\LogoutResponseDTO;
use App\Modules\User\Application\DTOs\RegisterUserDTO;
use App\Modules\User\Application\DTOs\UserProfileDTO;
use App\Modules\User\Application\UseCases\Auth\GetAuthenticatedUserUseCase;
use App\Modules\User\Application\UseCases\Auth\LoginUserUseCase;
use App\Modules\User\Application\UseCases\Auth\LogoutUserUseCase;
use App\Modules\User\Application\UseCases\Auth\RegisterUserUseCase;
use App\Modules\User\Infrastructure\Http\V1\Requests\Auth\LoginRequest;
use App\Modules\User\Infrastructure\Http\V1\Requests\Auth\RegisterRequest;

final class AuthController
{
    public function register(RegisterRequest $request, RegisterUserUseCase $useCase): AuthUserResponseDTO
    {
        return $useCase->execute(RegisterUserDTO::from($request->validated()));
    }

    public function login(LoginRequest $request, LoginUserUseCase $useCase): AuthUserResponseDTO
    {
        return $useCase->execute(LoginUserDTO::from($request->validated()));
    }

    public function logout(LogoutUserUseCase $useCase): LogoutResponseDTO
    {
        $useCase->execute();

        return new LogoutResponseDTO('Logged out successfully');
    }

    public function me(GetAuthenticatedUserUseCase $useCase): UserProfileDTO
    {
        return UserProfileDTO::from($useCase->execute());
    }
}
