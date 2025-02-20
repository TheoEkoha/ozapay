<?php

namespace App\Entity\Heritage;

use App\Entity\Article\Product;
use App\Repository\Heritage\OfferRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OfferRepository::class)]
class Offer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $expirationDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $webSite = null;

    #[ORM\Column(length: 255)]
    private ?string $contactCountry = null;

    #[ORM\Column(length: 255)]
    private ?string $contactPhone = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 0)]
    private ?string $price = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 0, nullable: true)]
    private ?string $cashback = null;

    #[ORM\ManyToOne(inversedBy: 'offers')]
    private ?StatusItem $statusItem = null;

    #[ORM\ManyToOne(inversedBy: 'offers')]
    private ?Product $product = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getExpirationDate(): ?\DateTimeImmutable
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(\DateTimeImmutable $expirationDate): static
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    public function getWebSite(): ?string
    {
        return $this->webSite;
    }

    public function setWebSite(?string $webSite): static
    {
        $this->webSite = $webSite;

        return $this;
    }

    public function getContactCountry(): ?string
    {
        return $this->contactCountry;
    }

    public function setContactCountry(string $contactCountry): static
    {
        $this->contactCountry = $contactCountry;

        return $this;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(string $contactPhone): static
    {
        $this->contactPhone = $contactPhone;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getCashback(): ?string
    {
        return $this->cashback;
    }

    public function setCashback(?string $cashback): static
    {
        $this->cashback = $cashback;

        return $this;
    }

    public function getStatusItem(): ?StatusItem
    {
        return $this->statusItem;
    }

    public function setStatusItem(?StatusItem $statusItem): static
    {
        $this->statusItem = $statusItem;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }
}
