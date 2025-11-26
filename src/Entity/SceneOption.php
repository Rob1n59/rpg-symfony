<?php

namespace App\Entity;

use App\Repository\SceneOptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SceneOptionRepository::class)]
class SceneOption
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $text = null; // Le texte que le joueur verra (ex: "Aller à la forêt", "Fouiller le corps")

    #[ORM\ManyToOne(targetEntity: Location::class, inversedBy: 'options')] // La Location à laquelle cette option appartient
    #[ORM\JoinColumn(nullable: false)] // Une option doit toujours être liée à une Location
    private ?Location $location = null;

    #[ORM\ManyToOne(targetEntity: Location::class)] // La Location vers laquelle cette option mène (la prochaine scène)
    #[ORM\JoinColumn(nullable: true)] // Peut être null si l'option mène à un combat ou autre événement sans changer de Location directement
    private ?Location $nextLocation = null;

    // NOUVEAU: Si une option déclenche un combat, on peut lier à un Enemy (si tu veux des ennemis spécifiques par option)
    #[ORM\ManyToOne(targetEntity: Enemy::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Enemy $enemyToFight = null;

    // NOUVEAU: Si une option donne un objet (ex: fouiller un coffre)
    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Item $itemToGain = null;

    // NOUVEAU: Pour gérer les conditions d'accès à l'option (ex: niveau requis)
    #[ORM\Column(nullable: true)]
    private ?int $requiredLevel = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getNextLocation(): ?Location
    {
        return $this->nextLocation;
    }

    public function setNextLocation(?Location $nextLocation): static
    {
        $this->nextLocation = $nextLocation;

        return $this;
    }

    public function getEnemyToFight(): ?Enemy
    {
        return $this->enemyToFight;
    }

    public function setEnemyToFight(?Enemy $enemyToFight): static
    {
        $this->enemyToFight = $enemyToFight;

        return $this;
    }

    public function getItemToGain(): ?Item
    {
        return $this->itemToGain;
    }

    public function setItemToGain(?Item $itemToGain): static
    {
        $this->itemToGain = $itemToGain;

        return $this;
    }

    public function getRequiredLevel(): ?int
    {
        return $this->requiredLevel;
    }

    public function setRequiredLevel(?int $requiredLevel): static
    {
        $this->requiredLevel = $requiredLevel;

        return $this;
    }
}