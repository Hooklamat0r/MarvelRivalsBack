<?php

namespace App\Entity;

use App\Repository\UsuarioPersonajeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UsuarioPersonajeRepository::class)]
#[ORM\Table(name: 'usuarios_personajes')]
#[ORM\UniqueConstraint(name: 'UNIQUE_usuario_personaje', columns: ['usuario_id', 'personaje_id'])]
class UsuarioPersonaje
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Usuario::class)]
    #[ORM\JoinColumn(name: 'usuario_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Usuario $usuario = null;

    #[ORM\ManyToOne(targetEntity: Personaje::class)]
    #[ORM\JoinColumn(name: 'personaje_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Personaje $personaje = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $añadidoEn;

    public function __construct()
    {
        $this->añadidoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsuario(): ?Usuario
    {
        return $this->usuario;
    }

    public function setUsuario(?Usuario $usuario): static
    {
        $this->usuario = $usuario;

        return $this;
    }

    public function getPersonaje(): ?Personaje
    {
        return $this->personaje;
    }

    public function setPersonaje(?Personaje $personaje): static
    {
        $this->personaje = $personaje;

        return $this;
    }

    public function getAñadidoEn(): \DateTimeImmutable
    {
        return $this->añadidoEn;
    }
}
