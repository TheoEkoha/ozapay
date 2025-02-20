<?php

namespace App\Entity\Transaction;

use App\Entity\Trait\StatusTrait;
use App\Entity\Trait\TimestampTrait;
use App\Entity\User\User;
use App\Repository\Transaction\ExchangeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExchangeRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Exchange
{
    use StatusTrait;
    use TimestampTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'exchanges')]
    private ?User $aim = null;

    #[ORM\Column(length: 255)]
    private ?string $uid = null;

    #[ORM\Column(length: 255)]
    private ?string $num = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 0)]
    private ?string $amountOrigin = null;

    #[ORM\ManyToOne(inversedBy: 'exchanges')]
    private ?Cryptos $cryptosOrigin = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 0)]
    private ?string $amountConversion = null;

    #[ORM\ManyToOne(inversedBy: 'exchanges')]
    private ?Cryptos $cryptosConversion = null;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(mappedBy: 'exchange', targetEntity: Transaction::class)]
    private Collection $transactions;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAim(): ?User
    {
        return $this->aim;
    }

    public function setAim(?User $aim): static
    {
        $this->aim = $aim;

        return $this;
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

    public function getNum(): ?string
    {
        return $this->num;
    }

    public function setNum(string $num): static
    {
        $this->num = $num;

        return $this;
    }

    public function getAmountOrigin(): ?string
    {
        return $this->amountOrigin;
    }

    public function setAmountOrigin(string $amountOrigin): static
    {
        $this->amountOrigin = $amountOrigin;

        return $this;
    }

    public function getCryptosOrigin(): ?Cryptos
    {
        return $this->cryptosOrigin;
    }

    public function setCryptosOrigin(?Cryptos $cryptosOrigin): static
    {
        $this->cryptosOrigin = $cryptosOrigin;

        return $this;
    }

    public function getAmountConversion(): ?string
    {
        return $this->amountConversion;
    }

    public function setAmountConversion(string $amountConversion): static
    {
        $this->amountConversion = $amountConversion;

        return $this;
    }

    public function getCryptosConversion(): ?Cryptos
    {
        return $this->cryptosConversion;
    }

    public function setCryptosConversion(?Cryptos $cryptosConversion): static
    {
        $this->cryptosConversion = $cryptosConversion;

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setExchange($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getExchange() === $this) {
                $transaction->setExchange(null);
            }
        }

        return $this;
    }
}
