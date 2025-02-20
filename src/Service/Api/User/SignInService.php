<?php

namespace App\Service\Api\User;

use App\Common\Constants\Response\ErrorsConstant;
use App\Common\Constants\Response\SuccessConstants;
use App\Common\Constants\Response\VerificationConstant;
use App\Repository\User\UserRepository;
use App\Service\Mail\MailerService;
use App\Service\SmsService;
use App\Utils\Tools;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Serializer\SerializerInterface;

readonly class SignInService extends UserCommonService
{
    public function __construct(
        SerializerInterface                          $serializer,
        SmsService                                   $sms,
        EntityManagerInterface                       $em,
        MailerService                                $mailerService,
        private readonly UserRepository              $repository,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly Tools                       $tools,
    )
    {
        parent::__construct($serializer, $sms, $em, $mailerService);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    public function initiateLoginEmail(Request $request): JsonResponse
    {
        try {
            $data = self::getPostedData($request);

            $user = $this->repository->findOneBy(['email' => $data['email']]);
            if (!$user) {
                return new JsonResponse(['message' => ErrorsConstant::NOT_FOUND], Response::HTTP_NOT_FOUND);
            }

            if (!$this->hasher->isPasswordValid($user, $data['password'])) {
                return new JsonResponse(['message' => ErrorsConstant::INVALID_CREDENTIALS], Response::HTTP_UNAUTHORIZED);
            }

            $this->tools->checkUserNeedReset($user);

            // create verification code and send it to user's email
            $this->sendMailCode($user, $data['email'], VerificationConstant::SIGN_IN_VER);

            $userData = json_decode($this->serializer->serialize($user, 'jsonld', ['groups' => ['user:read']]), true);

            return new JsonResponse(
                $userData,
                Response::HTTP_OK,
                ['Content-Type' => 'application/ld+json']
            );
        } catch (CustomUserMessageAuthenticationException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }

    }

    /**
     * @throws NonUniqueResultException
     */
    public function initiateLoginSms(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->repository->findOneBy(['phone' => $data['phone']]);
        if (!$user) {
            return new JsonResponse(['message' => ErrorsConstant::NOT_FOUND], Response::HTTP_NOT_FOUND);
        }

        // create verification code and send it to user's phone number
        $this->sendSMSCode($user, $data['phone'], VerificationConstant::SIGN_IN_VER, $data['appSignature']);

        $userData = json_decode($this->serializer->serialize($user, 'jsonld', ['groups' => ['user:read']]), true);

        return new JsonResponse(
            $userData,
            Response::HTTP_OK,
            ['Content-Type' => 'application/ld+json']
        );

    }


}
