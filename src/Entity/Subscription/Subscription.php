<?php

namespace App\Entity\Subscription;

use App\Entity\Trait\StatusTrait;
use App\Entity\Trait\TimestampTrait;
use App\Entity\User\User;
use App\Repository\Subscription\SubscriptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Subscription
{
    use TimestampTrait;
    use StatusTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'subscriptions')]
    private ?User $consumer = null;

    #[ORM\ManyToOne(inversedBy: 'subscriptions')]
    private ?Plan $plan = null;

    #[ORM\Column(length: 255)]
    private ?string $uid = null;

    #[ORM\Column(length: 255)]
    private ?string $price = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConsumer(): ?User
    {
        return $this->consumer;
    }

    public function setConsumer(?User $consumer): static
    {
        $this->consumer = $consumer;

        return $this;
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

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function setUid(string $uid): static
    {
        $this->uid = $uid;

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
}
