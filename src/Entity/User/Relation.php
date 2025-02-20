<?php

namespace App\Entity\User;

use App\Entity\Trait\TimestampTrait;
use App\Repository\User\RelationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RelationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Relation
{
    use TimestampTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'relations')]
    #[ORM\JoinColumn(name: 'user_invited', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?User $userInvited = null;

    #[ORM\ManyToOne(inversedBy: 'relations')]
    #[ORM\JoinColumn(name: 'user_parent', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?User $userParent = null;

    #[ORM\Column(length: 45)]
    private ?string $code = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserInvited(): ?User
    {
        return $this->userInvited;
    }

    public function setUserInvited(?User $userInvited): static
    {
        $this->userInvited = $userInvited;

        return $this;
    }

    public function getUserParent(): ?User
    {
        return $this->userParent;
    }

    public function setUserParent(?User $userParent): static
    {
        $this->userParent = $userParent;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

}
