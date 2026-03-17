<?php

namespace App\Controller;

use App\Repository\TeamUpRepository;
use App\Service\TeamUpService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/teamups', name: 'api_teamups_')]
class TeamUpController extends AbstractController
{
    public function __construct(
        private TeamUpService $teamUpService,
        private TeamUpRepository $teamUpRepository,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $teamUps = $this->teamUpService->obtenerTodosLosTeamUps();

            return $this->json([
                'success' => true,
                'teamups' => $teamUps,
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route('/personaje/{personajeId}', name: 'by_personaje', methods: ['GET'])]
    public function getByPersonaje(string $personajeId): JsonResponse
    {
        try {
            $teamUps = $this->teamUpService->obtenerTeamUpsPorPersonaje($personajeId);

            return $this->json([
                'success' => true,
                'teamups' => $teamUps,
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data || !isset($data['nombre']) || !isset($data['personaje1Id']) || !isset($data['personaje2Id']) || !isset($data['descripcion'])) {
                return $this->json(
                    ['message' => 'Nombre, personaje1Id, personaje2Id y descripcion son requeridos'],
                    JsonResponse::HTTP_BAD_REQUEST
                );
            }

            $teamUp = $this->teamUpService->crearTeamUp(
                $data['nombre'],
                $data['personaje1Id'],
                $data['personaje2Id'],
                $data['descripcion']
            );

            return $this->json([
                'success' => true,
                'message' => 'TeamUp creado correctamente',
                'teamup' => $teamUp,
            ], JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $teamUp = $this->teamUpRepository->find($id);
            if (!$teamUp) {
                return $this->json(
                    ['message' => 'TeamUp no encontrado'],
                    JsonResponse::HTTP_NOT_FOUND
                );
            }

            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json(
                    ['message' => 'Datos inválidos'],
                    JsonResponse::HTTP_BAD_REQUEST
                );
            }

            $response = $this->teamUpService->actualizarTeamUp(
                $teamUp,
                $data['nombre'] ?? null,
                $data['personaje1Id'] ?? null,
                $data['personaje2Id'] ?? null,
                $data['descripcion'] ?? null
            );

            return $this->json([
                'success' => true,
                'message' => 'TeamUp actualizado correctamente',
                'teamup' => $response,
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): JsonResponse
    {
        try {
            $teamUp = $this->teamUpRepository->find($id);
            if (!$teamUp) {
                return $this->json(
                    ['message' => 'TeamUp no encontrado'],
                    JsonResponse::HTTP_NOT_FOUND
                );
            }

            $this->teamUpService->eliminarTeamUp($teamUp);

            return $this->json([
                'success' => true,
                'message' => 'TeamUp eliminado correctamente',
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }
}
