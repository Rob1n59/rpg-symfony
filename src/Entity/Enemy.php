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

    #[ORM\Column]
    private ?int $hp = null;

    #[ORM\Column]
    private ?int $attack = null;

    #[ORM\Column]
    private ?int $defense = null;

    #[ORM\Column]
    private ?int $goldReward = null;

    #[ORM\Column]
    private ?int $xpReward = null;

    // NOUVEAU CHAMP : Pour stocker le chemin d'une image de l'ennemi
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    // NOUVEAU CHAMP : Relation ManyToOne vers l'entité Item pour le loot
    // Un ennemi peut dropper un type d'objet spécifique (ou aucun si null).
    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(nullable: true)] // L'ennemi peut ne pas dropper d'objet
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

    // NOUVEAU GETTER/SETTER pour image
    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    // NOUVEAU GETTER/SETTER pour lootItem
    public function getLootItem(): ?Item
    {
        return $this->lootItem;
    }

    public function setLootItem(?Item $lootItem): static
    {
        $this->lootItem = $lootItem;

        return $this;
    }
}