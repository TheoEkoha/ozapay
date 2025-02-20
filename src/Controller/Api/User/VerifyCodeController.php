<?php

namespace App\Controller\Api\User;

use App\Common\Constants\Response\VerificationConstant;
use App\Entity\User\User;
use App\Entity\User\VerificationCode;
use App\Service\Api\User\UserService;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

class VerifyCodeController extends AbstractController
{
    public function __construct(
        protected UserService         $service,
        protected SerializerInterface $serializer,
    ) {
    }

    /**
     * @throws RandomException
     * @throws JWTEncodeFailureException
     */
    public function __invoke(User $user, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $dataVerified = $this->service->verifyCode($user, (int)$data['code'], $data['type'], $data['for']);

        if ($dataVerified instanceof VerificationCode) {
            $dataSerialized = json_decode($this->serializer->serialize($dataVerified, 'jsonld', ['groups' => ['verification:read','user:read']]));
        } else {
            $dataSerialized = $dataVerified;
        }

        if ($dataVerified) {
            return $this->json($dataSerialized, Response::HTTP_OK, ['Content-Type' => 'application/ld+json', 'message' => VerificationConstant::VERIFICATION_SUCCESS]);
        }
        return $this->json(null, Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/ld+json', 'message' => VerificationConstant::VERIFICATION_FAILED]);

    }

}
