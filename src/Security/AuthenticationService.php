<?php

namespace App\Security;

use App\Common\Constants\Response\ErrorsConstant;
use App\Entity\User\User;
use App\Repository\User\VerificationCodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AuthenticationService
{
    private const TEMP_TOKEN_EXPIRY = 3600;
    private const MAX_VERIFICATION_ATTEMPTS = 3;

    public function __construct(
        protected VerificationCodeRepository $codeRepository,
        protected EntityManagerInterface $entityManager,
        protected UserPasswordHasherInterface $passwordHasher,
        protected JWTEncoderInterface $JWTEncoder,
        protected JWTTokenManagerInterface $JWTManager,
        protected RefreshTokenGeneratorInterface $refreshTokenManager,
    ) {
    }

    /**
     * @throws JWTEncodeFailureException
     */
    public function storeAuthenticationSession(string $sessionId, ?int $userId): string
    {
        $payload = [
            'sessionId' => $sessionId,
            'userId' => $userId,
            'exp' => time() + self::TEMP_TOKEN_EXPIRY,
            'iat' => time(),
            'attempts' => 0
        ];

        return $this->JWTEncoder->encode($payload);
    }

    public function getAuthenticationSession(string $tempToken): ?array
    {
        try {
            return $this->JWTEncoder->decode($tempToken);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function incrementAuthenticationAttempts(array $sessionData): bool
    {
        if (!isset($sessionData['attempts'])) {
            return false;
        }

        return $sessionData['attempts'] < self::MAX_VERIFICATION_ATTEMPTS;
    }

    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!array_key_exists('token', $data)) {
            throw new BadRequestHttpException(ErrorsConstant::TOKEN_NOT_FOUND);
        }

        $sessionData = $this->getAuthenticationSession($data['token']);

        if (!$sessionData || !isset($sessionData['userId'])) {
            throw new AuthenticationException(ErrorsConstant::TOKEN_EXPIRED);
        }

        $user = $this->entityManager->getRepository(User::class)->find($sessionData['userId']);

        if (!$this->incrementAuthenticationAttempts($sessionData)) {
            throw new AuthenticationException(ErrorsConstant::TOO_MANY_VERIFICATION_ATTEMPTS);
        }

        // Generate final JWT token for authenticated session
        $token = $this->JWTManager->create($user);
        $refreshToken = $this->refreshTokenManager->createForUserWithTtl($user, 2592000);
        // Explicitly persist the refresh token
        $this->entityManager->persist($refreshToken);
        $this->entityManager->flush();

        return new JsonResponse([
            'token' => $token,
            'refresh_token' => $refreshToken->getRefreshToken()
        ], Response::HTTP_OK);
    }

}
