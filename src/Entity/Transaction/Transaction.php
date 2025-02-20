<?php

namespace App\Entity\Transaction;

use App\Entity\Trait\TimestampTrait;
use App\Entity\User\User;
use App\Repository\Transaction\TransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Transaction
{
    use TimestampTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $num = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    private ?User $userSender = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    private ?User $userRecipient = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    private ?Exchange $exchange = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 0)]
    private ?string $amount = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $DateTransaction = null;

    #[ORM\Column(length: 255)]
    private ?string $TransactionType = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cryptos $crypto = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNum(): ?string
    {
        return $this->num;
    }

    public function setNum(string $num): static
    {
        $this->num = $num;

        return $this;
    }

    public function getUserSender(): ?User
    {
        return $this->userSender;
    }

    public function setUserSender(?User $userSender): static
    {
        $this->userSender = $userSender;

        return $this;
    }

    public function getUserRecipient(): ?User
    {
        return $this->userRecipient;
    }

    public function setUserRecipient(?User $userRecipient): static
    {
        $this->userRecipient = $userRecipient;

        return $this;
    }

    public function getExchange(): ?Exchange
    {
        return $this->exchange;
    }

    public function setExchange(?Exchange $exchange): static
    {
        $this->exchange = $exchange;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getDateTransaction(): ?\DateTimeImmutable
    {
        return $this->DateTransaction;
    }

    public function setDateTransaction(\DateTimeImmutable $DateTransaction): static
    {
        $this->DateTransaction = $DateTransaction;

        return $this;
    }

    public function getTransactionType(): ?string
    {
        return $this->TransactionType;
    }

    public function setTransactionType(string $TransactionType): static
    {
        $this->TransactionType = $TransactionType;

        return $this;
    }

    public function getCrypto(): ?Cryptos
    {
        return $this->crypto;
    }

    public function setCrypto(?Cryptos $crypto): static
    {
        $this->crypto = $crypto;

        return $this;
    }
}
