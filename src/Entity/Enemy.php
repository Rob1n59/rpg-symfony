<?php

namespace App\Entity;

use App\Repository\EnemyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EnemyRepository::class)]
class Enemy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 25)]
    private ?string $name = null;
    
    // NOUVEAU CHAMP AJOUTÉ POUR TWIG:
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;
    // FIN NOUVEAU CHAMP

    #[ORM\Column]
    private ?int $hp = null;
    
    // Ajout du HP Max pour la barre de vie
    #[ORM\Column(nullable: true)]
    private ?int $hpMax = null; 

    #[ORM\Column]
    private ?int $attack = null;

    #[ORM\Column]
    private ?int $defense = null;

    #[ORM\Column]
    private ?int $goldReward = null;

    #[ORM\Column]
    private ?int $xpReward = null;

    // Niveau de danger pour filtrer l'apparition par zone
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $dangerLevel = null; 

    // Pour stocker le chemin d'une image de l'ennemi
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    // Relation ManyToOne vers l'entité Item pour le loot
    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(nullable: true)] 
    private ?Item $lootItem = null;


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
    
    // NOUVEAU GETTER/SETTER pour description
    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }
    // FIN NOUVEAU GETTER/SETTER

    public function getHpMax(): ?int
    {
        return $this->hpMax;
    }

    public function setHpMax(?int $hpMax): static
    {
        $this->hpMax = $hpMax;

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
    // ... (Reste des getters et setters inchangés) ...
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

    public function getGoldReward(): ?int
    {
        return $this->goldReward;
    }

    public function setGoldReward(int $goldReward): static
    {
        $this->goldReward = $goldReward;

        return $this;
    }

    public function getXpReward(): ?int
    {
        return $this->xpReward;
    }

    public function setXpReward(int $xpReward): static
    {
        $this->xpReward = $xpReward;

        return $this;
    }

    public function getDangerLevel(): ?string
    {
        return $this->dangerLevel;
    }

    public function setDangerLevel(?string $dangerLevel): static
    {
        $this->dangerLevel = $dangerLevel;

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

    public function getLootItem(): ?Item
    {
        return $this->lootItem;
    }

    public function setLootItem(?Item $lootItem): static
    {
        $this->lootItem = $lootItem;

        return $this;
    }
    public function calculateDamage(): int
{
    // L'attaque de base doit être au moins 1 pour infliger des dégâts
    $maxAttack = max(1, $this->attack ?? 1); 

    // Retourne un nombre aléatoire entre 1 et l'attaque maximum.
    return random_int(1, $maxAttack);
}
}
    