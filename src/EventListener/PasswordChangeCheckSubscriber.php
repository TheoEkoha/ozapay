<?php

namespace App\EventListener;

use App\Common\Constants\Response\ErrorsConstant;
use App\Entity\User\User;
use App\Utils\Tools;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class PasswordChangeCheckSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected Tools        $tools,
        protected RequestStack $requestStack
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'lexik_jwt_authentication.on_authentication_success' => 'onAuthenticationSuccess'
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request->getPathInfo() === '/api/login_check') {
            /** @var User $user */
            $user = $event->getUser();

            try {
                $this->tools->checkUserNeedReset($user);
            } catch (CustomUserMessageAuthenticationException $e) {
                // Clear the success data
                $event->setData([
                    'code' => Response::HTTP_UNAUTHORIZED,
                    'message' => $e->getMessage()
                ]);

                // Get the response object and set the status code
                $response = $event->getResponse();
                $response->setStatusCode(Response::HTTP_UNAUTHORIZED);

                $event->stopPropagation();
            }
        }
    }
}