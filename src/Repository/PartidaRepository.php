<?php

namespace App\Repository;

use App\Entity\Partida;
use App\Entity\Usuario;
use App\Enum\EstadoPartidaEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PartidaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Partida::class);
    }

    public function findPendientesRecibidas(Usuario $usuario): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.retado = :usuario')
            ->andWhere('p.estado = :estado')
            ->setParameter('usuario', $usuario)
            ->setParameter('estado', EstadoPartidaEnum::PENDIENTE)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendientesEnviadas(Usuario $usuario): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.retador = :usuario')
            ->andWhere('p.estado = :estado')
            ->setParameter('usuario', $usuario)
            ->setParameter('estado', EstadoPartidaEnum::PENDIENTE)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findHistorialUsuario(Usuario $usuario): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.retador = :usuario OR p.retado = :usuario')
            ->setParameter('usuario', $usuario)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function existePendienteEntreUsuarios(Usuario $usuarioA, Usuario $usuarioB): bool
    {
        $total = (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.estado = :estado')
            ->andWhere('(p.retador = :usuarioA AND p.retado = :usuarioB) OR (p.retador = :usuarioB AND p.retado = :usuarioA)')
            ->setParameter('estado', EstadoPartidaEnum::PENDIENTE)
            ->setParameter('usuarioA', $usuarioA)
            ->setParameter('usuarioB', $usuarioB)
            ->getQuery()
            ->getSingleScalarResult();

        return $total > 0;
    }
}
