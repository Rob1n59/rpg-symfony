<?php

namespace App\Entity;

use App\Repository\LocationRepository;
use Doctrine\Common\Collections\ArrayCollection; 
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
class Location
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
    private ?int $dangerLevel = null;

    // NOUVEAU CHAMP : Pour stocker le chemin d'une image de fond spécifique à cette location
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    // NOUVEAU CHAMP : Relation OneToMany avec SceneOption
    // Une Location peut avoir plusieurs options, et ces options "appartiennent" à cette Location.
    #[ORM\OneToMany(targetEntity: SceneOption::class, mappedBy: 'location', orphanRemoval: true)]
    private Collection $options;

    public function __construct()
    {
        $this->options = new ArrayCollection(); // Initialise la collection d'options
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDangerLevel(): ?int
    {
        return $this->dangerLevel;
    }

    public function setDangerLevel(int $dangerLevel): static
    {
        $this->dangerLevel = $dangerLevel;

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

    /**
     * @return Collection<int, SceneOption>
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function addOption(SceneOption $option): static
    {
        if (!$this->options->contains($option)) {
            $this->options->add($option);
            $option->setLocation($this);
        }

        return $this;
    }

    public function removeOption(SceneOption $option): static
    {
        if ($this->options->removeElement($option)) {
            // set the owning side to null (unless already changed)
            if ($option->getLocation() === $this) {
                $option->setLocation(null);
            }
        }

        return $this;
    }
}