<?php

namespace App\Controller;

use App\Entity\Personaje;
use App\Repository\PersonajeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api', name: 'api_')]
class PersonajeController extends AbstractController
{
    #[Route('/personajes', name: 'personajes_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(PersonajeRepository $personajeRepository): JsonResponse
    {
        $personajes = $personajeRepository->findAll();

        return $this->json([
            'success' => true,
            'personajes' => array_map(fn(Personaje $p) => [
                'id' => $p->getId(),
                'name' => $p->getName(),
                'role' => $p->getRole(),
                'difficulty' => $p->getDifficulty(),
                'imageUrl' => $p->getImageUrl(),
            ], $personajes),
        ]);
    }
}
