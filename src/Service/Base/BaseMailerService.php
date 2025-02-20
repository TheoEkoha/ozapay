<?php

namespace App\Service\Base;

use App\Email\BrevoEmail;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use SendinBlue\Client\Api\TransactionalEmailsApi;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Model\SendSmtpEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

readonly class BaseMailerService
{
    public function __construct(
        private MailerInterface       $mailer,
        private ParameterBagInterface $bag
    ) {
    }

    /**
     * @param string $to
     * @param string $sender
     * @param string $subject
     * @param string $templateId
     * @param array $params
     * @throws TransportExceptionInterface
     */
    public function sendEmail(string $to, string $sender, string $subject, string $templateId, array $params): void
    {
        $email = new BrevoEmail();
        $email
            ->from($sender)
            ->to($to)
            ->subject($subject)
            ->setTemplateId($templateId)
            ->setTemplateVars($params);
        $this->mailer->send($email);
    }

    /**
     * @param string $subject
     * @param array $sender
     * @param array $to
     * @param int|null $templateId
     * @param array $vars
     * @param string|null $htmlContent
     * @throws Exception
     */
    public function sendSmtpEmail(string $subject, array $sender, array $to, int|null $templateId, array $vars = [], string $htmlContent = null): void
    {
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->bag->get('sendinblue_api_key'));
        // Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
        // $config = Brevo\Client\Configuration::getDefaultConfiguration()->setApiKeyPrefix('api-key', 'Bearer');

        // Configure API key authorization: partner-key
        //        $config = Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey('partner-key', 'YOUR_API_KEY');
        // Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
        // $config = Brevo\Client\Configuration::getDefaultConfiguration()->setApiKeyPrefix('partner-key', 'Bearer');

        $apiInstance = new TransactionalEmailsApi(
            // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
            // This is optional, `GuzzleHttp\Client` will be used as default.
            new GuzzleClient(),
            $config
        );
        $sendSmtpEmail = new SendSmtpEmail([
            'subject' => $subject,
            'sender' => $sender,
            'to' => [$to]
        ]);
        // \Brevo\Client\Model\SendSmtpEmail | Values to send a transactional email

        if (!empty($vars)) {
            $sendSmtpEmail->setParams((object)$vars);
        }

        if (!is_null($templateId)) {
            $sendSmtpEmail->setTemplateId($templateId);
        }

        if (!is_null($htmlContent)) {
            $sendSmtpEmail->setHtmlContent($htmlContent);
        }

        try {
            $apiInstance->sendTransacEmail($sendSmtpEmail);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }


}
