<?php

namespace App\Service\Mail;

use App\Common\Constants\BrevoMailTemplateConstants;
use App\Entity\User\User;
use App\Service\Base\BaseMailerService;
use App\Utils\Tools;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Repository\User\UserRepository;

readonly class MailerService
{
    public function __construct(
        private BaseMailerService     $mailer,
        private EntityManagerInterface        $em,
        private TranslatorInterface   $translator,
        private ParameterBagInterface $bag,
        private Tools                         $tools,
        protected UserPasswordHasherInterface $passwordHasher,
        private UserRepository                $repository
    ) {
    }

    /**
     * @param string $email
     * @param int $code
     * @throws TransportExceptionInterface
     */
    public function sendCodeValidation(string $email, int $code): void
    {
        $vars = ['code' => $code];
        $this->mailer->sendEmail(
            $email,
            $this->bag->get('admin_email'),
            $this->translator->trans('email.confirmation.title'),
            BrevoMailTemplateConstants::REGISTRATION_CONFIRMATION,
            $vars
        );
    }

    /**
     * @throws \Exception
     */
    public function sendCodeValidationSmtp(User $user, string $email, int $code): void
    {
        $this->mailer->sendSmtpEmail(
            $this->translator->trans('email.confirmation.title'),
            ['name' => 'Ozapay', 'email' => $this->bag->get('admin_email')],
            ['name' => $user->getFullName(), 'email' => $email],
            (int)BrevoMailTemplateConstants::REGISTRATION_CONFIRMATION,
            ['USERNAME' => $user->getFullName(), 'CODE' => $code],
            null
        );
    }

    /**
     * @throws \Exception
     */
    public function sendCodeValidationSmtpHtml(User $user, string $email, int $code): void
    {
        $htmlContent = $this->generateVerificationEmailHtml($user, $code);

        $this->mailer->sendSmtpEmail(
            $this->translator->trans('email.confirmation.title'),
            ['name' => 'Ozapay', 'email' => $this->bag->get('admin_email')],
            ['name' => $user->getFullName(), 'email' => $email],
            null, // Remove the template ID
            ['code' => $code],
            $htmlContent // Pass the HTML content
        );
    }

    private function generateVerificationEmailHtml(User $user, int $code): string
    {
        return sprintf(
            '<html lang="en">
                    <body>
                        <h1>Verification Code</h1>
                        <p>Hello %s,</p>
                        <p>Your verification code is: <strong>%d</strong></p>
                        <p>Please use this code to complete your registration.</p>
                    </body>
                </html>',
            htmlspecialchars($user->getFullName()),
            $code
        );
    }

    /**
     * @throws \Exception
     */
    public function sendWelcomeAfterRegistration(User $user, string $generatedPassword): void
    {
        $this->mailer->sendSmtpEmail(
            $this->translator->trans('email.complete_registration.title'),
            ['name' => 'Ozapay', 'email' => $this->bag->get('admin_email')],
            ['name' => $user->getFullName(), 'email' => $user->getEmail()],
            (int)BrevoMailTemplateConstants::REGISTRATION_WELCOME,
            ['USERNAME' => $user->getFullName() , 'PASSWORD' => $generatedPassword, 'AFFILIATE' => $user->getCode()],
            null
        );

    }

    public function sendMailToResetPass(User $user, string $url): void
    {
        // $htmlContent = sprintf(
        //     '<html lang="en">
        //             <body>
        //                 <h1>Reinitialize password</h1>
        //                 <p>Dear %s,</p>
        //                 <p>Clik on link bellow to rest your pass.</p>
        //                 <p><a href="%s">Cliquer ici</a></p>
        //             </body>
        //         </html>',
        //     htmlspecialchars($user->getFullName()),
        //     htmlspecialchars($url)
        // );

        $generatedPassword = $this->tools->generateRandomString();
        $user->setPassword($this->passwordHasher->hashPassword($user, $generatedPassword));

        $dateFinal = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->add(new \DateInterval('PT30M'));

        $user
        ->setGeneratedPassUpdated(false)
        ->setGeneratedPassExpired($dateFinal);
        
        $this->em->persist($user);
        $this->em->flush();
        $this->repository->save($user);


        $this->mailer->sendSmtpEmail(
            $this->translator->trans('email.reset_password.title'),
            ['name' => 'Ozapay', 'email' => $this->bag->get('admin_email')],
            ['name' => $user->getFullName(), 'email' => $user->getEmail()],
            (int)BrevoMailTemplateConstants::FORGOT_PASSWORD,
            ['USERNAME' => $user->getFullName() , 'PASSWORD' => $generatedPassword],
            null
        );
    }


}
