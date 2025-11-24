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

    #[ORM\Column(length: 50)]
    private string $name;

    #[ORM\Column]
    private int $hp;

    #[ORM\Column]
    private int $attack;

    #[ORM\Column]
    private int $defense;

    #[ORM\Column]
    private int $goldReward;

    #[ORM\Column]
    private int $xpReward;

    // getters/setters
}
