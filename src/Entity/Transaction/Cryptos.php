<?php

namespace App\Entity\Transaction;

use App\Repository\Transaction\CryptosRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CryptosRepository::class)]
class Cryptos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, Exchange>
     */
    #[ORM\OneToMany(mappedBy: 'cryptosOrigin', targetEntity: Exchange::class)]
    private Collection $exchanges;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(mappedBy: 'crypto', targetEntity: Transaction::class)]
    private Collection $transactions;

    public function __construct()
    {
        $this->exchanges = new ArrayCollection();
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Exchange>
     */
    public function getExchanges(): Collection
    {
        return $this->exchanges;
    }

    public function addExchange(Exchange $exchange): static
    {
        if (!$this->exchanges->contains($exchange)) {
            $this->exchanges->add($exchange);
            $exchange->setCryptosOrigin($this);
        }

        return $this;
    }

    public function removeExchange(Exchange $exchange): static
    {
        if ($this->exchanges->removeElement($exchange)) {
            // set the owning side to null (unless already changed)
            if ($exchange->getCryptosOrigin() === $this) {
                $exchange->setCryptosOrigin(null);
            }
        }

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
            $transaction->setCrypto($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getCrypto() === $this) {
                $transaction->setCrypto(null);
            }
        }

        return $this;
    }
}
