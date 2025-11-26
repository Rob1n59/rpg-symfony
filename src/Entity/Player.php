<?php

namespace App\Entity;

use App\Repository\PlayerRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;    // AJOUTÉ
use Doctrine\Common\Collections\Collection;         // AJOUTÉ

#[ORM\Entity(repositoryClass: PlayerRepository::class)]
class Player
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 25)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $hp = null;

    #[ORM\Column]
    private ?int $attack = null;

    #[ORM\Column]
    private ?int $defense = null;

    #[ORM\Column]
    private ?int $gold = null;

    #[ORM\Column]
    private ?int $experience = null;

    #[ORM\Column]
    private ?int $level = 1;

    #[ORM\ManyToOne(targetEntity: Location::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Location $currentLocation = null;

    // Cette propriété a été ajoutée. Elle doit être initialisée dans le constructeur.
    #[ORM\OneToMany(targetEntity: PlayerItem::class, mappedBy: 'player', orphanRemoval: true)]
    private Collection $playerItems;

    // NOUVEAU CONSTRUCTEUR AJOUTÉ ICI
    public function __construct()
    {
        $this->playerItems = new ArrayCollection(); // Initialisation de la collection
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

    public function getHp(): ?int
    {
        return $this->hp;
    }

    public function setHp(int $hp): static
    {
        $this->hp = $hp;

        return $this;
    }

    public function getAttack(): ?int
    {
        return $this->attack;
    }

    public function setAttack(int $attack): static
    {
        $this->attack = $attack;

        return $this;
    }

    public function getDefense(): ?int
    {
        return $this->defense;
    }

    public function setDefense(int $defense): static
    {
        $this->defense = $defense;

        return $this;
    }

    public function getGold(): ?int
    {
        return $this->gold;
    }

    public function setGold(int $gold): static
    {
        $this->gold = $gold;

        return $this;
    }

    public function getExperience(): ?int
    {
        return $this->experience;
    }

    public function setExperience(int $experience): static
    {
        $this->experience = $experience;

        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getCurrentLocation(): ?Location
    {
        return $this->currentLocation;
    }

    public function setCurrentLocation(?Location $currentLocation): static
    {
        $this->currentLocation = $currentLocation;

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
            $playerItem->setPlayer($this);
        }

        return $this;
    }

    public function removePlayerItem(PlayerItem $playerItem): static
    {
        if ($this->playerItems->removeElement($playerItem)) {
            if ($playerItem->getPlayer() === $this) {
                $playerItem->setPlayer(null);
            }
        }

        return $this;
    }
}