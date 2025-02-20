<?php

namespace App\Components;

use App\Entity\User\User;
use App\Repository\User\UserRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('user_detail_modal')]
class UserDetailModalComponent
{
    use DefaultActionTrait;

    #[LiveProp]
    public bool $isOpen = false;

    #[LiveProp]
    public ?int $userId = null;

    public ?User $user = null;

    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public function load(): void
    {
        if ($this->userId) {
            $this->user = $this->userRepository->find($this->userId);
        }
    }

    public function showUser(int $userId): void
    {
        $this->userId = $userId;
        $this->isOpen = true;
        $this->load();
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->userId = null;
        $this->user = null;
    }

}
