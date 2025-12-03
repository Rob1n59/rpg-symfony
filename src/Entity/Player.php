<?php

namespace App\Entity;

use App\Repository\PlayerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManagerInterface; // IMPORTANT: Ajout pour le calcul des stats via BDD

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

    // Bonus d'attaque et de défense provenant de l'équipement (mis à jour par le contrôleur)
    #[ORM\Column(nullable: true)]
    private ?int $equippedAttackBonus = 0; 
    
    #[ORM\Column(nullable: true)]
    private ?int $equippedDefenseBonus = 0; 

    #[ORM\Column]
    private ?int $gold = null;

    #[ORM\Column]
    private ?int $experience = null;

    #[ORM\Column]
    private ?int $level = null;

    #[ORM\ManyToOne(targetEntity: Location::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Location $currentLocation = null;

    #[ORM\Column(nullable: true)]
    private ?int $playerClassId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $playerClassName = null;

    #[ORM\OneToMany(targetEntity: PlayerItem::class, mappedBy: 'player', orphanRemoval: true)]
    private Collection $playerItems;

    public function __construct()
    {
        $this->playerItems = new ArrayCollection();
    }
    
    // --- NOUVELLES MÉTHODES DE CALCUL DES STATS TOTALES ---

    /**
     * Calcule l'Attaque totale (Base + Bonus Equipés).
     * @param EntityManagerInterface $em L'EntityManager est nécessaire pour l'injection via service/contrôleur.
     */
    public function calculateTotalAttack(EntityManagerInterface $em): int
    {
        // On retourne l'attaque de base PLUS le bonus qui est géré et persisté par le contrôleur.
        return ($this->attack ?? 0) + ($this->equippedAttackBonus ?? 0);
    }

    /**
     * Calcule la Défense totale (Base + Bonus Equipés).
     * @param EntityManagerInterface $em L'EntityManager est nécessaire pour l'injection via service/contrôleur.
     */
    public function calculateTotalDefense(EntityManagerInterface $em): int
    {
        // On retourne la défense de base PLUS le bonus qui est géré et persisté par le contrôleur.
        return ($this->defense ?? 0) + ($this->equippedDefenseBonus ?? 0);
    }
    
    // --- FIN DES MÉTHODES DE CALCUL ---
    
    // ... (Reste des getters et setters, qui sont inchangés) ...

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
}