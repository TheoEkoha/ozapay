<?php

namespace App\Entity\Subscription;

use App\Repository\Subscription\PlanOfferRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlanOfferRepository::class)]
class PlanOffer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'planOffers')]
    private ?Plan $plan = null;

    #[ORM\ManyToOne(inversedBy: 'planOffers')]
    private ?PlanItem $planItem = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $value = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlan(): ?Plan
    {
        return $this->plan;
    }

    public function setPlan(?Plan $plan): static
    {
        $this->plan = $plan;

        return $this;
    }

    public function getPlanItem(): ?PlanItem
    {
        return $this->planItem;
    }

    public function setPlanItem(?PlanItem $planItem): static
    {
        $this->planItem = $planItem;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;

        return $this;
    }
}
