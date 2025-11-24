<?php

namespace App\Entity;

use App\Repository\ItemRepository;
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

    #[ORM\Column]
    private ?int $attackBonus = null;

    #[ORM\Column]
    private ?int $hpBonus = null;

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

    public function setAttackBonus(int $attackBonus): static
    {
        $this->attackBonus = $attackBonus;

        return $this;
    }

    public function getHpBonus(): ?int
    {
        return $this->hpBonus;
    }

    public function setHpBonus(int $hpBonus): static
    {
        $this->hpBonus = $hpBonus;

        return $this;
    }
}
