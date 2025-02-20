<?php

namespace App\EventListener;

use App\Entity\User\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Translation\LocaleSwitcher;

final readonly class UserLocalListener
{
    public function __construct(
        private Security       $security,
        private LocaleSwitcher $localeSwitcher
    ) {
    }

    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onKernelRequest(RequestEvent $event): void
    {
        $user = $this->security->getUser();
        if ($user instanceof User) {
            $this->localeSwitcher->setLocale($user->getLocal());
        }
    }
}
