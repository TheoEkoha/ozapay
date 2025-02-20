<?php

namespace App\Entity\User;

use App\Repository\User\VerificationCodeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: VerificationCodeRepository::class)]
class VerificationCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['verification:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    #[Groups(['verification:read'])]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    #[Groups(['verification:read'])]
    private ?string $type = null;

    #[ORM\Column]
    #[Groups(['verification:read'])]
    private ?\DateTimeImmutable $expiredAt = null;

    #[ORM\Column]
    #[Groups(['verification:read'])]
    private ?bool $isVerified = null;

    #[ORM\ManyToOne(inversedBy: 'verificationCodes')]
    #[ORM\JoinColumn(name: 'responsible_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Groups(['user:read'])]
    private ?User $responsible = null;

    #[ORM\Column(length: 45, nullable: true)]
    #[Groups(['verification:read'])]
    private string $verificationFor = '';

    public function getId(): ?int
    {
        return $this->id;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getExpiredAt(): ?\DateTimeImmutable
    {
        return $this->expiredAt;
    }

    public function setExpiredAt(\DateTimeImmutable $expiredAt): static
    {
        $this->expiredAt = $expiredAt;

        return $this;
    }

    public function isVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getResponsible(): ?User
    {
        return $this->responsible;
    }

    public function setResponsible(?User $responsible): static
    {
        $this->responsible = $responsible;

        return $this;
    }

    public function setVerificationFor(string $verificationFor): VerificationCode
    {
        $this->verificationFor = $verificationFor;
        return $this;
    }

    public function getVerificationFor(): string
    {
        return $this->verificationFor;
    }
}
