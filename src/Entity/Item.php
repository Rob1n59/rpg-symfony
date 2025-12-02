<?php

namespace App\Entity;

use App\Repository\ItemRepository;
use Doctrine\Common\Collections\ArrayCollection; 
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ItemRepository::class)]
class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)] 
    private ?int $attackBonus = null;

    #[ORM\Column(nullable: true)] 
    private ?int $hpBonus = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    // NOUVEAU CHAMP: Pour identifier le type d'arme spécifique (sword, staff, bow, axe...)
    #[ORM\Column(length: 50, nullable: true)] 
    private ?string $weaponType = null;
    
    #[ORM\Column(nullable: true)]
    private ?int $healingAmount = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\OneToMany(targetEntity: PlayerItem::class, mappedBy: 'item', orphanRemoval: true)]
    private Collection $playerItems;

    public function __construct()
    {
        $this->playerItems = new ArrayCollection();
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

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getAttackBonus(): ?int
    {
        return $this->attackBonus;
    }

    public function setAttackBonus(?int $attackBonus): static
    {
        $this->attackBonus = $attackBonus;
        return $this;
    }

    public function getHpBonus(): ?int
    {
        return $this->hpBonus;
    }

    public function setHpBonus(?int $hpBonus): static
    {
        $this->hpBonus = $hpBonus;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    // NOUVEAU GETTER/SETTER pour weaponType
    public function getWeaponType(): ?string
    {
        return $this->weaponType;
    }

    public function setWeaponType(?string $weaponType): static
    {
        $this->weaponType = $weaponType;
        return $this;
    }

    public function getHealingAmount(): ?int
    {
        return $this->healingAmount;
    }

    public function setHealingAmount(?int $healingAmount): static
    {
        $this->healingAmount = $healingAmount;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    /**
     * @return Collection<int, PlayerItem>
     */
    public function getPlayerItems(): Collection
    {
        return $this->playerItems;
    }

    public function addPlayerItem(PlayerItem $playerItem): static
    {
        if (!$this->playerItems->contains($playerItem)) {
            $this->playerItems->add($playerItem);
            $playerItem->setItem($this);
        }
        return $this;
    }

    public function removePlayerItem(PlayerItem $playerItem): static
    {
        if ($this->playerItems->removeElement($playerItem)) {
            // set the owning side to null (unless already changed)
            if ($playerItem->getItem() === $this) {
                $playerItem->setItem(null);
            }
        }
        return $this;
    }
}