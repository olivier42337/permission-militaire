<?php

namespace App\Entity;

use App\Repository\ProgrammeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProgrammeRepository::class)]
class Programme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateFin = null;

    #[ORM\ManyToOne(inversedBy: 'programmes')]
    private ?User $user = null;

    /**
     * @var Collection<int, Conflit>
     */
    #[ORM\OneToMany(targetEntity: Conflit::class, mappedBy: 'programme')]
    private Collection $conflits;

    public function __construct()
    {
        $this->conflits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

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

    public function getDateDebut(): ?\DateTime
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTime $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTime $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Conflit>
     */
    public function getConflits(): Collection
    {
        return $this->conflits;
    }

    public function addConflit(Conflit $conflit): static
    {
        if (!$this->conflits->contains($conflit)) {
            $this->conflits->add($conflit);
            $conflit->setProgramme($this);
        }

        return $this;
    }

    public function removeConflit(Conflit $conflit): static
    {
        if ($this->conflits->removeElement($conflit)) {
            // set the owning side to null (unless already changed)
            if ($conflit->getProgramme() === $this) {
                $conflit->setProgramme(null);
            }
        }

        return $this;
    }
    #[ORM\Column(type: Types::TEXT, nullable: true)]
private ?string $commentaire = null;

public function getCommentaire(): ?string
{
    return $this->commentaire;
}

public function setCommentaire(?string $commentaire): static
{
    $this->commentaire = $commentaire;
    return $this;
}

}
