<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
#[ORM\Entity]
#[\Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity('nom')]
class Technique {
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;
    #[ORM\Column(length: 255, unique: true), Assert\NotBlank, Assert\Length(max: 255)]
    private ?string $nom = null;
    public function getId(): ?int { return $this->id; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }
    public function __construct() { $this->oeuvres = new ArrayCollection(); }
    public function __toString(): string { return $this->nom ?? 'Technique'; }

    #[ORM\ManyToMany(targetEntity: Oeuvre::class, mappedBy: 'techniques')]
    private Collection $oeuvres;
    public function getOeuvres(): Collection { return $this->oeuvres; }
    public function addOeuvre(Oeuvre $item): static { if (!$this->oeuvres->contains($item)) { $this->oeuvres->add($item); $item->addTechnique($this); } return $this; }
    public function removeOeuvre(Oeuvre $item): static { if ($this->oeuvres->removeElement($item)) { $item->removeTechnique($this); } return $this; }
}
