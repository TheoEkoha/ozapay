<?php

namespace App\Utils;

use App\Common\Constants\Response\ErrorsConstant;
use App\Entity\User\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class Tools
{
    /**
     * @param string $url
     * @return mixed
     */
    public static function getDomainFromURL(string $url): mixed
    {
        $url = str_replace("www.", "", $url);
        return parse_url($url)["host"] ?? parse_url($url)["path"];
    }

    /**
     * @return string
     */
    public static function getProjectDir(): string
    {
        return dirname(__DIR__);
    }

    /**
     * @param string|null $referer
     * @return mixed|null
     */
    public static function parseUrl(string $referer = null): mixed
    {
        return parse_url($referer)['host'] ?? null;
    }

    public function generateRandomString($length = 16): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public function checkUserNeedReset(User $user): void
    {
        if (!$user->isGeneratedPassUpdated()) {

            $dateNow = new \DateTimeImmutable();
            $diffInSeconds = abs($dateNow->getTimestamp() - $user->getGeneratedPassExpired()->getTimestamp());
            $diffInMinutes = $diffInSeconds / 60;

            if ((!is_null($user->getGeneratedPassExpired())) && $diffInMinutes >= 30) {

                throw new CustomUserMessageAuthenticationException(
                    ErrorsConstant::USER_PASSWORD_NOT_CHANGED,
                    ['code' => Response::HTTP_UNAUTHORIZED]
                );
            }
        }
    }
}
