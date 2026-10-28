<?php

namespace App\Entity;

use App\Repository\ArtisteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: ArtisteRepository::class)]
#[\Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity('pseudonyme')]
class Artiste
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $pseudonyme = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $nomReel = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $bio = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $nationalite = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPseudonyme(): ?string
    {
        return $this->pseudonyme;
    }

    public function setPseudonyme(string $pseudonyme): static
    {
        $this->pseudonyme = $pseudonyme;

        return $this;
    }

    public function getNomReel(): ?string
    {
        return $this->nomReel;
    }

    public function setNomReel(?string $nomReel): static
    {
        $this->nomReel = $nomReel;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function getNationalite(): ?string
    {
        return $this->nationalite;
    }

    public function setNationalite(?string $nationalite): static
    {
        $this->nationalite = $nationalite;

        return $this;
    }

    #[ORM\ManyToMany(targetEntity: Oeuvre::class, mappedBy: 'artistes')]
    private Collection $oeuvres;
    public function getOeuvres(): Collection { return $this->oeuvres; }
    public function addOeuvre(Oeuvre $item): static { if (!$this->oeuvres->contains($item)) { $this->oeuvres->add($item); $item->addArtiste($this); } return $this; }
    public function removeOeuvre(Oeuvre $item): static { if ($this->oeuvres->removeElement($item)) { $item->removeArtiste($this); } return $this; }
    public function __construct() { $this->oeuvres = new ArrayCollection(); }
    public function __toString(): string { return $this->pseudonyme ?? "Artiste"; }
}
