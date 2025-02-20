<?php

namespace App\Service\Api\User;

use App\Common\Constants\Response\ErrorsConstant;
use App\Common\Constants\Response\VerificationConstant;
use App\Common\Constants\UserConstants as MAPPING;
use App\Common\Constants\VerificationTypeConstant;
use App\Entity\User\User;
use App\Entity\User\VerificationCode;
use App\Service\Base\BaseService;
use App\Service\Mail\MailerService;
use App\Service\SmsService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

abstract readonly class UserCommonService extends BaseService
{
    private int $code;

    public function __construct(
        SerializerInterface            $serializer,
        protected SmsService           $sms,
        private EntityManagerInterface $em,
        protected MailerService        $mailerService,
    ) {
        parent::__construct($serializer);
        $this->code = mt_rand(100000, 999999);
    }

    public static function generateRole(string $type): array
    {
        $role = [];
        foreach (MAPPING::ROLES as $key => $value) {
            if (strtolower($type) === $key) {
                $role[] = $value;
                break;
            }
        }

        return $role;
    }

    /**
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    public function sendSMSCode(User $user, string $phone, string $for, ?string $signature = 'default_signature'): void
    {
        if ($this->existValidation($user, $this->code, VerificationTypeConstant::TYPE_SMS)) {
            throw new \Exception(ErrorsConstant::VALIDATION_EXIST);
        }
    
        // Si $signature est null, utilise la valeur par défaut
        $signatureToUse = $signature ?? 'default_signature'; // Remplace 'default_signature' par la valeur souhaitée
    
        $this->sms->sendSms($phone, (string)$this->code, $signatureToUse);
        $this->createVerificationCode($user, $this->code, VerificationTypeConstant::TYPE_SMS, $for);
    }

    /**
     * @param User $user
     * @param string $email
     * @param string $for
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    public function sendMailCode(User $user, string $email, string $for): void
    {
        if ($this->existValidation($user, $this->code, VerificationTypeConstant::TYPE_MAIL)) {
            throw new \Exception(ErrorsConstant::VALIDATION_EXIST);
        }
        //        $this->mailerService->sendCodeValidation($email, $this->code);
        $this->mailerService->sendCodeValidationSmtp($user, $email, $this->code);
        $this->createVerificationCode($user, $this->code, VerificationTypeConstant::TYPE_MAIL, $for);
    }

    /**
     * @param User $user
     * @param int $code
     * @param string $type
     * @param string $verificationFor
     * @return JsonResponse|void
     * @throws NonUniqueResultException
     */
    public function createVerificationCode(User $user, int $code, string $type, string $verificationFor = '')
    {
        if ($this->existValidation($user, $code, $type)) {
            return new JsonResponse(['message' => ErrorsConstant::VALIDATION_EXIST]);
        }

        $date = new \DateTimeImmutable();
        $dateTimezone = $date->setTimezone(new \DateTimeZone('UTC'));
        $dateFinal = $dateTimezone->add(new \DateInterval('PT3M'));
        $verificationObject = (new VerificationCode())
            ->setResponsible($user)
            ->setType($type)
            ->setVerified(false)
            ->setExpiredAt($dateFinal)
            ->setCode((string)$code);

        $mailVerify = match ($verificationFor) {
            VerificationConstant::SIGN_UP_VER => VerificationConstant::VERIFICATION_FOR_SIGN_UP,
            VerificationConstant::SIGN_IN_VER => VerificationConstant::VERIFICATION_FOR_SIGN_IN
        };
        $verificationObject->setVerificationFor($mailVerify);

        $this->em->persist($verificationObject);
        $this->em->flush();
    }


    /**
     * @throws NonUniqueResultException
     */
    public function existValidation(User $user, int $code, string $type): bool
    {
        $verification = $this->searchVerification($user, $code, $type);
        return $verification !== null;
    }

    /**
     * @throws NonUniqueResultException
     */
    protected function searchVerification(User $user, int $code, string $type): VerificationCode|null
    {
        $date = new \DateTime();
        $dateToAdd = $date->setTimezone(new \DateTimeZone('UTC'));
        return $this->em->getRepository(VerificationCode::class)
            ->createQueryBuilder('v')
            ->where('v.responsible = :user')
            ->andWhere('v.code = :code')
            ->andWhere('v.type = :type')
            ->andWhere('v.expiredAt > :now')
            ->setParameter('user', $user)
            ->setParameter('code', $code)
            ->setParameter('type', $type)
            ->setParameter('now', $dateToAdd)
            ->getQuery()
            ->getOneOrNullResult();
    }

}
