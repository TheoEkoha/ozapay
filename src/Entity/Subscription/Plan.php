<?php

namespace App\Entity\Subscription;

use App\Repository\Subscription\PlanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlanRepository::class)]
class Plan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, PlanOffer>
     */
    #[ORM\OneToMany(mappedBy: 'plan', targetEntity: PlanOffer::class)]
    private Collection $planOffers;

    /**
     * @var Collection<int, Subscription>
     */
    #[ORM\OneToMany(mappedBy: 'plan', targetEntity: Subscription::class)]
    private Collection $subscriptions;

    public function __construct()
    {
        $this->planOffers = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
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
     * @return Collection<int, PlanOffer>
     */
    public function getPlanOffers(): Collection
    {
        return $this->planOffers;
    }

    public function addPlanOffer(PlanOffer $planOffer): static
    {
        if (!$this->planOffers->contains($planOffer)) {
            $this->planOffers->add($planOffer);
            $planOffer->setPlan($this);
        }

        return $this;
    }

    public function removePlanOffer(PlanOffer $planOffer): static
    {
        if ($this->planOffers->removeElement($planOffer)) {
            // set the owning side to null (unless already changed)
            if ($planOffer->getPlan() === $this) {
                $planOffer->setPlan(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(Subscription $subscription): static
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
            $subscription->setPlan($this);
        }

        return $this;
    }

    public function removeSubscription(Subscription $subscription): static
    {
        if ($this->subscriptions->removeElement($subscription)) {
            // set the owning side to null (unless already changed)
            if ($subscription->getPlan() === $this) {
                $subscription->setPlan(null);
            }
        }

        return $this;
    }
}
