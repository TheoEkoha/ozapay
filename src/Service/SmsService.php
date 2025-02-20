<?php

namespace App\Service;

use SendinBlue\Client\Api\TransactionalSMSApi;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Model\SendTransacSms;

readonly class SmsService
{
    private readonly TransactionalSMSApi $smsApi;
    public function __construct(private string $apiKey, private string $sender)
    {
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->apiKey);
        $this->smsApi = new TransactionalSMSApi(null, $config);
    }

    /**
     * @throws \Exception
     */
    public function sendSms(string $to, string $code, string $signature): object
    {
        $message = "$code est votre code de validation Ozapay" . "\r\n";
        $message .= "$signature";
        $sms = new SendTransacSms();
        $sms->setSender($this->sender);
        $sms->setRecipient($to);
        $sms->setContent($message);

        try {
            return $this->smsApi->sendTransacSms($sms);
        } catch (\Exception $e) {
            throw new \Exception("Error sending SMS: ". $e->getMessage());
        }
    }

}
