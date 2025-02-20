<?php

namespace App\Entity\Article;

use App\Entity\Heritage\KeyItem;
use App\Entity\Heritage\Offer;
use App\Repository\Article\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $content = null;

    /**
     * @var Collection<int, Media>
     */
    #[ORM\ManyToMany(targetEntity: Media::class, inversedBy: 'products')]
    private Collection $medias;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class, mappedBy: 'products')]
    private Collection $categories;

    /**
     * @var Collection<int, Offer>
     */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: Offer::class)]
    private Collection $offers;

    /**
     * @var Collection<int, KeyItem>
     */
    #[ORM\ManyToMany(targetEntity: KeyItem::class, inversedBy: 'products')]
    private Collection $keyItems;

    public function __construct()
    {
        $this->medias = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->offers = new ArrayCollection();
        $this->keyItems = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return Collection<int, Media>
     */
    public function getMedias(): Collection
    {
        return $this->medias;
    }

    public function addMedia(Media $media): static
    {
        if (!$this->medias->contains($media)) {
            $this->medias->add($media);
        }

        return $this;
    }

    public function removeMedia(Media $media): static
    {
        $this->medias->removeElement($media);

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            $category->addProduct($this);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        if ($this->categories->removeElement($category)) {
            $category->removeProduct($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Offer>
     */
    public function getOffers(): Collection
    {
        return $this->offers;
    }

    public function addOffer(Offer $offer): static
    {
        if (!$this->offers->contains($offer)) {
            $this->offers->add($offer);
            $offer->setProduct($this);
        }

        return $this;
    }

    public function removeOffer(Offer $offer): static
    {
        if ($this->offers->removeElement($offer)) {
            // set the owning side to null (unless already changed)
            if ($offer->getProduct() === $this) {
                $offer->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, KeyItem>
     */
    public function getKeyItems(): Collection
    {
        return $this->keyItems;
    }

    public function addKeyItem(KeyItem $keyItem): static
    {
        if (!$this->keyItems->contains($keyItem)) {
            $this->keyItems->add($keyItem);
        }

        return $this;
    }

    public function removeKeyItem(KeyItem $keyItem): static
    {
        $this->keyItems->removeElement($keyItem);

        return $this;
    }
}
