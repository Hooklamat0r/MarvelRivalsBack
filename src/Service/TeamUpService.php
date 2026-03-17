<?php

namespace App\Service;

use App\Entity\TeamUp;
use App\Repository\TeamUpRepository;
use App\Repository\PersonajeRepository;
use Doctrine\ORM\EntityManagerInterface;

class TeamUpService
{
    public function __construct(
        private TeamUpRepository $teamUpRepository,
        private PersonajeRepository $personajeRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    public function crearTeamUp(
        string $nombre,
        string $personaje1Id,
        string $personaje2Id,
        string $descripcion
    ): array {
        if ($personaje1Id === $personaje2Id) {
            throw new \Exception('Un personaje no puede tener TeamUp consigo mismo');
        }

        $personaje1 = $this->personajeRepository->find($personaje1Id);
        $personaje2 = $this->personajeRepository->find($personaje2Id);

        if (!$personaje1 || !$personaje2) {
            throw new \Exception('Uno o ambos personajes no existen');
        }

        $existente = $this->teamUpRepository->findExistingTeamUp($personaje1Id, $personaje2Id);

        if ($existente) {
            throw new \Exception('Este TeamUp ya existe');
        }

        $teamUp = new TeamUp();
        $teamUp->setNombre($nombre);
        $teamUp->setPersonaje1($personaje1);
        $teamUp->setPersonaje2($personaje2);
        $teamUp->setDescripcion($descripcion);

        $this->entityManager->persist($teamUp);
        $this->entityManager->flush();

        return $this->getTeamUpResponse($teamUp);
    }

    public function actualizarTeamUp(
        TeamUp $teamUp,
        ?string $nombre = null,
        ?string $personaje1Id = null,
        ?string $personaje2Id = null,
        ?string $descripcion = null
    ): array {
        if ($nombre !== null && trim($nombre) !== '') {
            $teamUp->setNombre(trim($nombre));
        }

        if ($personaje1Id !== null) {
            $personaje1 = $this->personajeRepository->find($personaje1Id);
            if (!$personaje1) {
                throw new \Exception('Personaje 1 no encontrado');
            }
            $teamUp->setPersonaje1($personaje1);
        }

        if ($personaje2Id !== null) {
            $personaje2 = $this->personajeRepository->find($personaje2Id);
            if (!$personaje2) {
                throw new \Exception('Personaje 2 no encontrado');
            }
            $teamUp->setPersonaje2($personaje2);
        }

        if ($descripcion !== null && trim($descripcion) !== '') {
            $teamUp->setDescripcion(trim($descripcion));
        }

        if ($teamUp->getPersonaje1() && $teamUp->getPersonaje2()) {
            if ($teamUp->getPersonaje1()->getId() === $teamUp->getPersonaje2()->getId()) {
                throw new \Exception('Un personaje no puede tener TeamUp consigo mismo');
            }

            $existente = $this->teamUpRepository->findExistingTeamUp(
                $teamUp->getPersonaje1()->getId(),
                $teamUp->getPersonaje2()->getId(),
                $teamUp->getId()
            );

            if ($existente) {
                throw new \Exception('Este TeamUp ya existe');
            }
        }

        $teamUp->setUpdatedAt();
        $this->entityManager->flush();

        return $this->getTeamUpResponse($teamUp);
    }

    public function eliminarTeamUp(TeamUp $teamUp): void
    {
        $this->entityManager->remove($teamUp);
        $this->entityManager->flush();
    }

    public function obtenerTeamUpsPorPersonaje(string $personajeId): array
    {
        $teamUps = $this->teamUpRepository->findByPersonaje($personajeId);

        return array_map(fn(TeamUp $tu) => $this->getTeamUpResponse($tu), $teamUps);
    }

    public function obtenerTodosLosTeamUps(): array
    {
        $teamUps = $this->teamUpRepository->findAll();

        return array_map(fn(TeamUp $tu) => $this->getTeamUpResponse($tu), $teamUps);
    }

    private function getTeamUpResponse(TeamUp $teamUp): array
    {
        return [
            'id' => $teamUp->getId(),
            'nombre' => $teamUp->getNombre(),
            'personaje1' => [
                'id' => $teamUp->getPersonaje1()->getId(),
                'name' => $teamUp->getPersonaje1()->getName(),
                'role' => $teamUp->getPersonaje1()->getRole(),
            ],
            'personaje2' => [
                'id' => $teamUp->getPersonaje2()->getId(),
                'name' => $teamUp->getPersonaje2()->getName(),
                'role' => $teamUp->getPersonaje2()->getRole(),
            ],
            'descripcion' => $teamUp->getDescripcion(),
        ];
    }
}
