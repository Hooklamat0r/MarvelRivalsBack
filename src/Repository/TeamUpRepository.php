<?php

namespace App\Repository;

use App\Entity\TeamUp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TeamUpRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamUp::class);
    }

    public function findByPersonaje(string $personajeId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.personaje1 = :personajeId OR t.personaje2 = :personajeId')
            ->setParameter('personajeId', $personajeId)
            ->getQuery()
            ->getResult();
    }

    public function findExistingTeamUp(string $personaje1Id, string $personaje2Id, ?int $excludeId = null): ?TeamUp
    {
        $qb = $this->createQueryBuilder('t')
            ->where('(t.personaje1 = :p1 AND t.personaje2 = :p2) OR (t.personaje1 = :p2 AND t.personaje2 = :p1)')
            ->setParameter('p1', $personaje1Id)
            ->setParameter('p2', $personaje2Id);

        if ($excludeId !== null) {
            $qb->andWhere('t.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()
            ->getOneOrNullResult();
    }
}
