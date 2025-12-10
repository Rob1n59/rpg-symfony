<?php

namespace App\Entity;

use App\Repository\PlayerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
// use Doctrine\ORM\EntityManagerInterface; // Assurez-vous que cette ligne est bien retirée si elle est en commentaire dans votre original

#[ORM\Entity(repositoryClass: PlayerRepository::class)]
class Player
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $hp = null;

    #[ORM\Column(nullable: true)]
    private ?int $hpMax = null;

    #[ORM\Column]
    private ?int $attack = null;

    #[ORM\Column]
    private ?int $defense = null;

    // Champs existants pour les bonus d'équipement (maintenus)
    #[ORM\Column(nullable: true)]
    private ?int $equippedAttackBonus = 0; 
    
    #[ORM\Column(nullable: true)]
    private ?int $equippedDefenseBonus = 0; 
    // FIN Champs existants

    #[ORM\Column]
    private ?int $gold = null;

    #[ORM\Column]
    private ?int $experience = null;

    #[ORM\Column]
    private ?int $level = null;
    
    // Relation vers Location (maintenue)
    #[ORM\ManyToOne(targetEntity: Location::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Location $currentLocation = null;

    #[ORM\Column(nullable: true)]
    private ?int $playerClassId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $playerClassName = null;

    #[ORM\OneToMany(targetEntity: PlayerItem::class, mappedBy: 'player', orphanRemoval: true)]
    private Collection $playerItems;
    
    // NOUVEAU CHAMP : Points d'Augure disponibles
    #[ORM\Column(nullable: true)]
    private ?int $augurPoints = 0; 
    
    // Suppression du champ 'baseStats' inutile car nous utilisons directement $attack et $defense comme base.


    public function __construct()
    {
        $this->playerItems = new ArrayCollection();
    }
    
    // --- MÉTHODES DE CALCUL DES STATS TOTALES (Utilisation des bonus persistés) ---

    public function calculateTotalAttack(): int
    {
        // Utilise l'attaque de base PLUS le bonus équipé (votre logique actuelle)
        return ($this->attack ?? 0) + ($this->equippedAttackBonus ?? 0);
    }

    public function calculateTotalDefense(): int
    {
        // Utilise la défense de base PLUS le bonus équipé (votre logique actuelle)
        return ($this->defense ?? 0) + ($this->equippedDefenseBonus ?? 0);
    }
    
    // --- NOUVELLE LOGIQUE DE PROGRESSION ---

    /**
     * Applique une augmentation permanente à la stat de base.
     */
    public function increaseStat(string $statName, int $value): void
    {
        switch (strtolower($statName)) {
            case 'attack':
                $this->setAttack($this->getAttack() + $value);
                break;
            case 'defense':
                $this->setDefense($this->getDefense() + $value);
                break;
            case 'hpmax':
                // Les PV max augmentent, et les PV actuels sont soignés à la même hauteur
                $this->setHpMax($this->getHpMax() + $value);
                $this->setHp($this->getHp() + $value); 
                break;
        }
    }
    
    // --- GETTERS & SETTERS (Harmonisation) ---

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

    public function getHpMax(): ?int
    {
        return $this->hpMax;
    }

    public function setHpMax(int $hpMax): static
    {
        $this->hpMax = $hpMax;
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
    
    public function getEquippedAttackBonus(): ?int
    {
        return $this->equippedAttackBonus;
    }

    public function setEquippedAttackBonus(?int $equippedAttackBonus): static
    {
        $this->equippedAttackBonus = $equippedAttackBonus;
        return $this;
    }

    public function getEquippedDefenseBonus(): ?int
    {
        return $this->equippedDefenseBonus;
    }

    public function setEquippedDefenseBonus(?int $equippedDefenseBonus): static
    {
        $this->equippedDefenseBonus = $equippedDefenseBonus;
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

    public function getPlayerClassId(): ?int
    {
        return $this->playerClassId;
    }

    public function setPlayerClassId(int $playerClassId): static
    {
        $this->playerClassId = $playerClassId;
        return $this;
    }

    public function getPlayerClassName(): ?string
    {
        return $this->playerClassName;
    }

    public function setPlayerClassName(string $playerClassName): static
    {
        $this->playerClassName = $playerClassName;
        return $this;
    }
    
    // NOUVEAU GETTER/SETTER pour Augur Points
    public function getAugurPoints(): ?int
    {
        return $this->augurPoints;
    }

    public function setAugurPoints(?int $augurPoints): static
    {
        $this->augurPoints = $augurPoints;
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
            // set the owning side to null (unless already changed)
            if ($playerItem->getPlayer() === $this) {
                $playerItem->setPlayer(null);
            }
        }
        return $this;
    }

    public function getCriticalChance(): int
    {
        return 5; 
    }
}