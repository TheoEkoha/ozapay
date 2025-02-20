<?php

namespace App\Entity;

use App\Entity\Trait\StatusTrait;
use App\Entity\Trait\TimestampTrait;
use App\Entity\User\User;
use App\Repository\AdviceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdviceRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Advice
{
    use TimestampTrait;
    use StatusTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'advice')]
    private ?User $advisee = null;

    #[ORM\Column(nullable: true)]
    private ?int $star = null;

    #[ORM\Column(length: 255)]
    private ?string $content = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdvisee(): ?User
    {
        return $this->advisee;
    }

    public function setAdvisee(?User $advisee): static
    {
        $this->advisee = $advisee;

        return $this;
    }

    public function getStar(): ?int
    {
        return $this->star;
    }

    public function setStar(?int $star): static
    {
        $this->star = $star;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }
}
