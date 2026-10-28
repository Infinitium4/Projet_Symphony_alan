<?php

namespace App\Entity;

use App\Repository\OeuvreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: OeuvreRepository::class)]
#[\Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity(fields: ['oeuvreRecouverte'], message: 'Cette œuvre est déjà recouverte par une autre œuvre.')]
class Oeuvre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $description = null;

    #[ORM\Column(options: ['default' => 0])]
    private ?bool $estDetruite = false;

    #[ORM\ManyToOne(inversedBy: 'oeuvres')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    private ?Lieu $lieu = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function setDateCreation(?\DateTimeImmutable $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

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

    public function isEstDetruite(): ?bool
    {
        return $this->estDetruite;
    }

    public function setEstDetruite(bool $estDetruite): static
    {
        $this->estDetruite = $estDetruite;

        return $this;
    }

    public function getLieu(): ?Lieu
    {
        return $this->lieu;
    }

    public function setLieu(?Lieu $lieu): static
    {
        if ($this->lieu !== $lieu) {
            $old = $this->lieu;
            $this->lieu = $lieu;
            $old?->removeOeuvre($this);
            $lieu?->addOeuvre($this);
        }
        return $this;
    }

    #[ORM\ManyToMany(targetEntity: Artiste::class, inversedBy: 'oeuvres')]
    private Collection $artistes;
    public function getArtistes(): Collection { return $this->artistes; }
    public function addArtiste(Artiste $item): static { if (!$this->artistes->contains($item)) { $this->artistes->add($item); $item->addOeuvre($this); } return $this; }
    public function removeArtiste(Artiste $item): static { if ($this->artistes->removeElement($item)) { $item->removeOeuvre($this); } return $this; }

    #[ORM\ManyToMany(targetEntity: Technique::class, inversedBy: 'oeuvres')]
    #[Assert\Count(min: 1, minMessage: 'Choisissez au moins une technique.')]
    private Collection $techniques;
    public function getTechniques(): Collection { return $this->techniques; }
    public function addTechnique(Technique $item): static { if (!$this->techniques->contains($item)) { $this->techniques->add($item); $item->addOeuvre($this); } return $this; }
    public function removeTechnique(Technique $item): static { if ($this->techniques->removeElement($item)) { $item->removeOeuvre($this); } return $this; }

    #[ORM\OneToMany(targetEntity: Photo::class, mappedBy: 'oeuvre')]
    private Collection $photos;
    public function getPhotos(): Collection { return $this->photos; }
    public function addPhoto(Photo $item): static { if (!$this->photos->contains($item)) { $this->photos->add($item); $item->setOeuvre($this); } return $this; }
    public function removePhoto(Photo $item): static { if ($this->photos->removeElement($item) && $item->getOeuvre() === $this) { $item->setOeuvre(null); } return $this; }
    #[ORM\OneToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'NO ACTION')]
    private ?self $oeuvreRecouverte = null;
    public function getOeuvreRecouverte(): ?self { return $this->oeuvreRecouverte; }
    public function setOeuvreRecouverte(?self $oeuvre): static { $this->oeuvreRecouverte = $oeuvre; return $this; }
    #[Assert\IsTrue(message: 'Une œuvre ne peut pas se recouvrir elle-même.')]
    public function isRecouvrementValide(): bool { return $this->oeuvreRecouverte !== $this; }
    public function __construct() { $this->artistes = new ArrayCollection(); $this->techniques = new ArrayCollection(); $this->photos = new ArrayCollection(); }
    public function __toString(): string { return $this->titre ?? 'Œuvre'; }

}
