<?php

declare(strict_types=1);

namespace App\Modules\User\Application\UseCases\Auth;

use App\Modules\User\Application\DTOs\AuthUserResponseDTO;
use App\Modules\User\Application\DTOs\RegisterUserDTO;
use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use Illuminate\Validation\ValidationException;

final readonly class RegisterUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function execute(RegisterUserDTO $dto): AuthUserResponseDTO
    {
        if ($this->userRepository->findByEmail($dto->email) !== null) {
            throw ValidationException::withMessages([
                'email' => ['User with this email already exists'],
            ]);
        }

        $user = $this->userRepository->createApiUser(
            name: $dto->name,
            email: $dto->email,
            password: $dto->password,
        );

        $token = $user->createToken('auth-token')->plainTextToken;

        return new AuthUserResponseDTO(
            token: $token,
            user: [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        );
    }
}
