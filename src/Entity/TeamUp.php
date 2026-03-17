<?php

namespace App\Entity;

use App\Repository\TeamUpRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamUpRepository::class)]
#[ORM\Table(name: 'teamups')]
class TeamUp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\ManyToOne(targetEntity: Personaje::class)]
    #[ORM\JoinColumn(name: 'personaje1_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Personaje $personaje1 = null;

    #[ORM\ManyToOne(targetEntity: Personaje::class)]
    #[ORM\JoinColumn(name: 'personaje2_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Personaje $personaje2 = null;

    #[ORM\Column(type: 'text')]
    private ?string $descripcion = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;
        return $this;
    }

    public function getPersonaje1(): ?Personaje
    {
        return $this->personaje1;
    }

    public function setPersonaje1(?Personaje $personaje1): static
    {
        $this->personaje1 = $personaje1;
        return $this;
    }

    public function getPersonaje2(): ?Personaje
    {
        return $this->personaje2;
    }

    public function setPersonaje2(?Personaje $personaje2): static
    {
        $this->personaje2 = $personaje2;
        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): static
    {
        $this->descripcion = $descripcion;
        return $this;
    }

    public function setUpdatedAt(): static
    {
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }
}
