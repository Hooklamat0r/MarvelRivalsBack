<?php

namespace App\Service;

use App\Entity\Personaje;
use Doctrine\ORM\EntityManagerInterface;

class PersonajeService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function actualizarPersonaje(
        Personaje $personaje,
        ?string $name = null,
        ?string $role = null,
        ?string $difficulty = null,
        ?string $imageUrl = null
    ): array {
        if ($name !== null && trim($name) !== '') {
            $personaje->setName(trim($name));
        }

        if ($role !== null) {
            $personaje->setRole(trim($role) !== '' ? trim($role) : null);
        }

        if ($difficulty !== null) {
            $personaje->setDifficulty(trim($difficulty) !== '' ? trim($difficulty) : null);
        }

        if ($imageUrl !== null) {
            $personaje->setImageUrl(trim($imageUrl) !== '' ? trim($imageUrl) : null);
        }

        $personaje->setUpdatedAt();
        $this->entityManager->flush();

        return $this->getPersonajeResponse($personaje);
    }

    public function eliminarPersonaje(Personaje $personaje): void
    {
        $this->entityManager->remove($personaje);
        $this->entityManager->flush();
    }

    private function getPersonajeResponse(Personaje $personaje): array
    {
        return [
            'id' => $personaje->getId(),
            'name' => $personaje->getName(),
            'role' => $personaje->getRole(),
            'difficulty' => $personaje->getDifficulty(),
            'imageUrl' => $personaje->getImageUrl(),
        ];
    }
}

