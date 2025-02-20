<?php

namespace App\Entity\Subscription;

use App\Repository\Subscription\PlanItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlanItemRepository::class)]
class PlanItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $valueType = null;

    /**
     * @var Collection<int, PlanOffer>
     */
    #[ORM\OneToMany(mappedBy: 'planItem', targetEntity: PlanOffer::class)]
    private Collection $planOffers;

    public function __construct()
    {
        $this->planOffers = new ArrayCollection();
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

    public function getValueType(): ?string
    {
        return $this->valueType;
    }

    public function setValueType(string $valueType): static
    {
        $this->valueType = $valueType;

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
            $planOffer->setPlanItem($this);
        }

        return $this;
    }

    public function removePlanOffer(PlanOffer $planOffer): static
    {
        if ($this->planOffers->removeElement($planOffer)) {
            // set the owning side to null (unless already changed)
            if ($planOffer->getPlanItem() === $this) {
                $planOffer->setPlanItem(null);
            }
        }

        return $this;
    }
}
