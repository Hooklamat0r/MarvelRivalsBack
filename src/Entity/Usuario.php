<?php

namespace App\Entity;

use App\Repository\UsuarioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\RoleEnum;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UsuarioRepository::class)]
class Usuario implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\Column(length: 255)]
    private ?string $apellido = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $contrasena = null;

    #[ORM\Column(type: 'string', enumType: RoleEnum::class)]
    private RoleEnum $rol = RoleEnum::USER;

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $premiumUntil = null;

    #[ORM\OneToMany(targetEntity: UsuarioPersonaje::class, mappedBy: 'usuario', cascade: ['remove'], orphanRemoval: true)]
    private Collection $personajes;

    public function __construct()
    {
        $this->personajes = new ArrayCollection();
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

    public function getApellido(): ?string
    {
        return $this->apellido;
    }

    public function setApellido(string $apellido): static
    {
        $this->apellido = $apellido;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function setContrasena(string $contrasena): static
    {
        $this->contrasena = $contrasena;

        return $this;
    }

    public function getRol(): RoleEnum
    {
        return $this->rol;
    }

    public function setRol(RoleEnum $rol): self
    {
        $this->rol = $rol;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getPremiumUntil(): ?\DateTimeImmutable
    {
        return $this->premiumUntil;
    }

    public function setPremiumUntil(?\DateTimeImmutable $premiumUntil): static
    {
        $this->premiumUntil = $premiumUntil;

        return $this;
    }

    public function hasPremiumActiva(): bool
    {
        return $this->premiumUntil !== null && $this->premiumUntil > new \DateTimeImmutable();
    }

    public function getPersonajes(): Collection
    {
        return $this->personajes;
    }

    public function addPersonaje(UsuarioPersonaje $personaje): static
    {
        if (!$this->personajes->contains($personaje)) {
            $this->personajes->add($personaje);
            $personaje->setUsuario($this);
        }

        return $this;
    }

    public function removePersonaje(UsuarioPersonaje $personaje): static
    {
        if ($this->personajes->removeElement($personaje)) {
            if ($personaje->getUsuario() === $this) {
                $personaje->setUsuario(null);
            }
        }

        return $this;
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_' . strtoupper($this->rol->value)];

        if ($this->rol->value === 'admin') {
            $roles[] = 'ROLE_USER';
        }

        return array_unique($roles);
    }

    public function getPassword(): string
    {
        return $this->contrasena;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
    }
}
