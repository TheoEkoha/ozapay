<?php

namespace App\Entity\User;

use App\Repository\User\BankRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: BankRepository::class)]
class Bank
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 45)]
    #[Groups(['bank:read'])]
    private ?string $uid = null;

    #[ORM\Column(length: 45, nullable:true)]
    #[Groups(['bank:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 50, nullable:true)]
    private ?string $number = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expiration = null;

    #[ORM\Column(nullable: true)]
    private ?int $securityCode = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $code_pin = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 0, nullable: true)]
    private ?string $paymentDailyLimit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 0, nullable: true)]
    private ?string $paymentDailyWithDrawal = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 0, nullable: true)]
    private ?string $paymentDailyWithoutContact = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $linkcyProfileId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $linkcyLedgerId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function setUid(string $uid): static
    {
        $this->uid = $uid;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getExpiration(): ?\DateTimeInterface
    {
        return $this->expiration;
    }

    public function setExpiration(?\DateTimeInterface $expiration): static
    {
        $this->expiration = $expiration;

        return $this;
    }

    public function getSecurityCode(): ?int
    {
        return $this->securityCode;
    }

    public function setSecurityCode(?int $securityCode): static
    {
        $this->securityCode = $securityCode;

        return $this;
    }

    public function getCodePin(): ?string
    {
        return $this->code_pin;
    }

    public function setCodePin(?string $code_pin): static
    {
        $this->code_pin = $code_pin;

        return $this;
    }

    public function getPaymentDailyLimit(): ?string
    {
        return $this->paymentDailyLimit;
    }

    public function setPaymentDailyLimit(?string $paymentDailyLimit): static
    {
        $this->paymentDailyLimit = $paymentDailyLimit;

        return $this;
    }

    public function getPaymentDailyWithDrawal(): ?string
    {
        return $this->paymentDailyWithDrawal;
    }

    public function setPaymentDailyWithDrawal(?string $paymentDailyWithDrawal): static
    {
        $this->paymentDailyWithDrawal = $paymentDailyWithDrawal;

        return $this;
    }

    public function getPaymentDailyWithoutContact(): ?string
    {
        return $this->paymentDailyWithoutContact;
    }

    public function setPaymentDailyWithoutContact(?string $paymentDailyWithoutContact): static
    {
        $this->paymentDailyWithoutContact = $paymentDailyWithoutContact;

        return $this;
    }

    public function getLinkcyProfileId(): ?string
    {
        return $this->linkcyProfileId;
    }

    public function setLinkcyProfileId(?string $linkcyProfileId): static
    {
        $this->linkcyProfileId = $linkcyProfileId;

        return $this;
    }

    public function getLinkcyLedgerId(): ?string
    {
        return $this->linkcyLedgerId;
    }

    public function setLinkcyLedgerId(?string $linkcyLedgerId): static
    {
        $this->linkcyLedgerId = $linkcyLedgerId;

        return $this;
    }
}
