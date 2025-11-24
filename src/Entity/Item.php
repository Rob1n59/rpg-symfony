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

    #[ORM\Column(length: 50)]
    private string $name;

    #[ORM\Column(length: 200)]
    private string $description;

    #[ORM\Column(nullable: true)]
    private ?int $attackBonus = 0;

    #[ORM\Column(nullable: true)]
    private ?int $defenseBonus = 0;

    #[ORM\Column(nullable: true)]
    private ?int $hpBonus = 0;
}
