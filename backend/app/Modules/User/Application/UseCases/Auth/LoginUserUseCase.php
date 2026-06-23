<?php

declare(strict_types=1);

namespace App\Modules\User\Application\UseCases\Auth;

use App\Modules\User\Application\DTOs\AuthUserResponseDTO;
use App\Modules\User\Application\DTOs\LoginUserDTO;
use App\Modules\User\Domain\Exceptions\ActiveSessionException;
use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final readonly class LoginUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function execute(LoginUserDTO $dto): AuthUserResponseDTO
    {
        $user = $this->userRepository->findByEmail($dto->email);

        if ($user === null || $user->password === null || ! Hash::check($dto->password, $user->password)) {
            throw new UnauthorizedHttpException('', 'Invalid credentials');
        }

        if ($user->tokens()->exists()) {
            throw new ActiveSessionException;
        }

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
