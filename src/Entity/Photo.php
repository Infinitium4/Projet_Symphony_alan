<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;
#[ORM\Entity]
class Photo {
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE), Assert\NotNull]
    private ?\DateTimeImmutable $datePriseVue = null;
    #[ORM\Column(length: 255)]
    private ?string $nomFichier = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $etatOeuvre = null;
    #[ORM\ManyToOne(inversedBy: 'photos'), ORM\JoinColumn(nullable: false), Assert\NotNull]
    private ?Oeuvre $oeuvre = null;
    #[ORM\ManyToOne(inversedBy: 'photos'), ORM\JoinColumn(nullable: false), Assert\NotNull]
    private ?User $auteur = null;
    public function __construct() { $this->datePriseVue = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getDatePriseVue(): ?\DateTimeImmutable { return $this->datePriseVue; }
    public function setDatePriseVue(\DateTimeImmutable $date): static { $this->datePriseVue = $date; return $this; }
    public function getNomFichier(): ?string { return $this->nomFichier; }
    public function setNomFichier(string $nom): static { $this->nomFichier = $nom; return $this; }
    public function getEtatOeuvre(): ?string { return $this->etatOeuvre; }
    public function setEtatOeuvre(?string $etat): static { $this->etatOeuvre = $etat; return $this; }
    public function getOeuvre(): ?Oeuvre { return $this->oeuvre; }
    public function setOeuvre(?Oeuvre $oeuvre): static { if ($this->oeuvre !== $oeuvre) { $old = $this->oeuvre; $this->oeuvre = $oeuvre; $old?->removePhoto($this); $oeuvre?->addPhoto($this); } return $this; }
    public function getAuteur(): ?User { return $this->auteur; }
    public function setAuteur(?User $auteur): static { if ($this->auteur !== $auteur) { $old = $this->auteur; $this->auteur = $auteur; $old?->removePhoto($this); $auteur?->addPhoto($this); } return $this; }
    public function __toString(): string { return 'Photo #'.$this->id; }
}
