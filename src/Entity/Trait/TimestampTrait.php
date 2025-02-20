<?php

namespace App\Entity\Trait;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

trait TimestampTrait
{
    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    #[Groups([
        'user:read'
    ])]
    private ?\DateTimeInterface $created = null;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    #[Groups([
            'user:read'
        ])]
    private ?\DateTimeInterface $updated = null;

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    #[ORM\PrePersist]
    public function setCreatedValue(): void
    {
        $this->created = new \DateTime();
    }

    public function getUpdated(): ?\DateTimeInterface
    {
        return $this->updated;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setUpdatedValue(): void
    {
        $date = new \DateTime();
        $date->setTimezone(new \DateTimeZone('UTC'));
        $this->updated = $date;
    }
}
