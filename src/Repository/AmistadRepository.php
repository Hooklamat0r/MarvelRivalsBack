<?php

namespace App\Repository;

use App\Entity\Amistad;
use App\Entity\Usuario;
use App\Enum\EstadoAmistadEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AmistadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Amistad::class);
    }

    public function findRelacionEntreUsuarios(Usuario $usuarioA, Usuario $usuarioB): ?Amistad
    {
        return $this->createQueryBuilder('a')
            ->where('(a.solicitante = :usuarioA AND a.receptor = :usuarioB) OR (a.solicitante = :usuarioB AND a.receptor = :usuarioA)')
            ->setParameter('usuarioA', $usuarioA)
            ->setParameter('usuarioB', $usuarioB)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findSolicitudesRecibidasPendientes(Usuario $usuario): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.receptor = :usuario')
            ->andWhere('a.estado = :estado')
            ->setParameter('usuario', $usuario)
            ->setParameter('estado', EstadoAmistadEnum::PENDIENTE)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findSolicitudesEnviadasPendientes(Usuario $usuario): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.solicitante = :usuario')
            ->andWhere('a.estado = :estado')
            ->setParameter('usuario', $usuario)
            ->setParameter('estado', EstadoAmistadEnum::PENDIENTE)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAmistadesAceptadas(Usuario $usuario): array
    {
        return $this->createQueryBuilder('a')
            ->where('(a.solicitante = :usuario OR a.receptor = :usuario)')
            ->andWhere('a.estado = :estado')
            ->setParameter('usuario', $usuario)
            ->setParameter('estado', EstadoAmistadEnum::ACEPTADA)
            ->orderBy('a.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
