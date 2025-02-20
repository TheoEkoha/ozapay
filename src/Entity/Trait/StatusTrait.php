<?php

namespace App\Entity\Trait;

use App\Entity\Enum\Status;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

trait StatusTrait
{
    #[ORM\Column(type: "string", enumType: Status::class, options: ['default' => Status::Published])]
    #[Groups([
        'user:read', 'user:write'
    ])]
    private Status $status;

    #[ORM\PrePersist]
    public function setStatusValue(): void
    {
        $this->status = Status::Published;
    }

    public function setStatus(Status $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

}
