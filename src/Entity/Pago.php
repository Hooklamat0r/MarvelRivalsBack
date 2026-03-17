<?php

namespace App\Entity;

use App\Repository\PagoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PagoRepository::class)]
#[ORM\Table(name: 'pagos')]
class Pago
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Usuario::class)]
    #[ORM\JoinColumn(name: 'usuario_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Usuario $usuario = null;

    #[ORM\Column]
    private int $meses = 1;

    #[ORM\Column]
    private int $precio = 499;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $premiumUntil;

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

    public function getMeses(): int
    {
        return $this->meses;
    }

    public function setMeses(int $meses): static
    {
        $this->meses = $meses;

        return $this;
    }

    public function getPrecio(): int
    {
        return $this->precio;
    }

    public function setPrecio(int $precio): static
    {
        $this->precio = $precio;

        return $this;
    }

    public function getPremiumUntil(): \DateTimeImmutable
    {
        return $this->premiumUntil;
    }

    public function setPremiumUntil(\DateTimeImmutable $premiumUntil): static
    {
        $this->premiumUntil = $premiumUntil;

        return $this;
    }

}
