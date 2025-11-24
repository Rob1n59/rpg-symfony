<?php

namespace App\Entity;

use App\Repository\PlayerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerRepository::class)]
class Player
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $name;

    #[ORM\Column]
    private int $hp = 100;

    #[ORM\Column]
    private int $maxHp = 100;

    #[ORM\Column]
    private int $attack = 10;

    #[ORM\Column]
    private int $defense = 5;

    #[ORM\Column]
    private int $gold = 0;

    #[ORM\Column]
    private int $experience = 0;

    #[ORM\Column]
    private int $level = 1;

    public function getId(): ?int { return $this->id; }

    // --- getters/setters automatiques (tu peux les générer via VSCode) ---
}
