<?php

namespace App\Entity;

use App\Enum\EstadoPartidaEnum;
use App\Repository\PartidaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PartidaRepository::class)]
#[ORM\Table(name: 'partidas')]
class Partida
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Usuario::class)]
    #[ORM\JoinColumn(name: 'retador_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Usuario $retador = null;

    #[ORM\ManyToOne(targetEntity: Usuario::class)]
    #[ORM\JoinColumn(name: 'retado_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Usuario $retado = null;

    #[ORM\Column(type: 'string', enumType: EstadoPartidaEnum::class)]
    private EstadoPartidaEnum $estado = EstadoPartidaEnum::PENDIENTE;

    #[ORM\Column(length: 20, options: ['default' => 'amigo'])]
    private string $tipoReto = 'amigo';

    #[ORM\ManyToOne(targetEntity: Usuario::class)]
    #[ORM\JoinColumn(name: 'ganador_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Usuario $ganador = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $equipoRetador = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $equipoRetado = null;

    #[ORM\Column(nullable: true)]
    private ?int $puntuacionRetador = null;

    #[ORM\Column(nullable: true)]
    private ?int $puntuacionRetado = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $detalleRetador = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $detalleRetado = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $respondedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $resolvedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRetador(): ?Usuario
    {
        return $this->retador;
    }

    public function setRetador(?Usuario $retador): static
    {
        $this->retador = $retador;

        return $this;
    }

    public function getRetado(): ?Usuario
    {
        return $this->retado;
    }

    public function setRetado(?Usuario $retado): static
    {
        $this->retado = $retado;

        return $this;
    }

    public function getEstado(): EstadoPartidaEnum
    {
        return $this->estado;
    }

    public function setEstado(EstadoPartidaEnum $estado): static
    {
        $this->estado = $estado;

        return $this;
    }

    public function getTipoReto(): string
    {
        return $this->tipoReto;
    }

    public function setTipoReto(string $tipoReto): static
    {
        $this->tipoReto = $tipoReto;

        return $this;
    }

    public function getGanador(): ?Usuario
    {
        return $this->ganador;
    }

    public function setGanador(?Usuario $ganador): static
    {
        $this->ganador = $ganador;

        return $this;
    }

    public function getEquipoRetador(): ?array
    {
        return $this->equipoRetador;
    }

    public function setEquipoRetador(?array $equipoRetador): static
    {
        $this->equipoRetador = $equipoRetador;

        return $this;
    }

    public function getEquipoRetado(): ?array
    {
        return $this->equipoRetado;
    }

    public function setEquipoRetado(?array $equipoRetado): static
    {
        $this->equipoRetado = $equipoRetado;

        return $this;
    }

    public function getPuntuacionRetador(): ?int
    {
        return $this->puntuacionRetador;
    }

    public function setPuntuacionRetador(?int $puntuacionRetador): static
    {
        $this->puntuacionRetador = $puntuacionRetador;

        return $this;
    }

    public function getPuntuacionRetado(): ?int
    {
        return $this->puntuacionRetado;
    }

    public function setPuntuacionRetado(?int $puntuacionRetado): static
    {
        $this->puntuacionRetado = $puntuacionRetado;

        return $this;
    }

    public function getDetalleRetador(): ?array
    {
        return $this->detalleRetador;
    }

    public function setDetalleRetador(?array $detalleRetador): static
    {
        $this->detalleRetador = $detalleRetador;

        return $this;
    }

    public function getDetalleRetado(): ?array
    {
        return $this->detalleRetado;
    }

    public function setDetalleRetado(?array $detalleRetado): static
    {
        $this->detalleRetado = $detalleRetado;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(): static
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getRespondedAt(): ?\DateTimeImmutable
    {
        return $this->respondedAt;
    }

    public function setRespondedAt(?\DateTimeImmutable $respondedAt): static
    {
        $this->respondedAt = $respondedAt;

        return $this;
    }

    public function getResolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?\DateTimeImmutable $resolvedAt): static
    {
        $this->resolvedAt = $resolvedAt;

        return $this;
    }
}
