<?php

namespace App\Controller;

use App\Entity\Usuario;
use App\Service\PartidaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/partidas', name: 'api_partidas_')]
class PartidaController extends AbstractController
{
    public function __construct(
        private PartidaService $partidaService,
    ) {}

    #[Route('/retar', name: 'retar', methods: ['POST'])]
    public function retar(
        Request $request,
        #[CurrentUser] Usuario $usuario,
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            $retadoId = isset($data['retadoId']) ? (int) $data['retadoId'] : null;

            $partida = $this->partidaService->crearReto($usuario, $retadoId);

            $esAleatoria = $retadoId === null;

            return $this->json([
                'success' => true,
                'message' => $esAleatoria
                    ? 'Partida aleatoria iniciada al instante'
                    : 'Reto a amigo creado correctamente',
                'partida' => $partida,
            ], JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route('/pendientes-recibidas', name: 'pendientes_recibidas', methods: ['GET'])]
    public function pendientesRecibidas(
        #[CurrentUser] Usuario $usuario,
    ): JsonResponse {
        try {
            $partidas = $this->partidaService->obtenerPendientesRecibidas($usuario);

            return $this->json([
                'success' => true,
                'total' => count($partidas),
                'partidas' => $partidas,
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route('/usuarios-disponibles', name: 'usuarios_disponibles', methods: ['GET'])]
    public function usuariosDisponibles(
        #[CurrentUser] Usuario $usuario,
    ): JsonResponse {
        try {
            $usuarios = $this->partidaService->obtenerUsuariosDisponibles($usuario);

            return $this->json([
                'success' => true,
                'premium' => $usuario->hasPremiumActiva(),
                'total' => count($usuarios),
                'usuarios' => $usuarios,
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route('/pendientes-enviadas', name: 'pendientes_enviadas', methods: ['GET'])]
    public function pendientesEnviadas(
        #[CurrentUser] Usuario $usuario,
    ): JsonResponse {
        try {
            $partidas = $this->partidaService->obtenerPendientesEnviadas($usuario);

            return $this->json([
                'success' => true,
                'total' => count($partidas),
                'partidas' => $partidas,
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route('/historial', name: 'historial', methods: ['GET'])]
    public function historial(
        #[CurrentUser] Usuario $usuario,
    ): JsonResponse {
        try {
            $partidas = $this->partidaService->obtenerHistorial($usuario);

            return $this->json([
                'success' => true,
                'total' => count($partidas),
                'partidas' => $partidas,
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route('/{id}/aceptar', name: 'aceptar', methods: ['POST'])]
    public function aceptar(
        int $id,
        #[CurrentUser] Usuario $usuario,
    ): JsonResponse {
        try {
            $partida = $this->partidaService->aceptarReto($usuario, $id);

            return $this->json([
                'success' => true,
                'message' => 'Partida aceptada correctamente',
                'partida' => $partida,
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route('/{id}/rechazar', name: 'rechazar', methods: ['POST'])]
    public function rechazar(
        int $id,
        #[CurrentUser] Usuario $usuario,
    ): JsonResponse {
        try {
            $partida = $this->partidaService->rechazarReto($usuario, $id);

            return $this->json([
                'success' => true,
                'message' => 'Partida rechazada correctamente',
                'partida' => $partida,
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route('/{id}/cancelar', name: 'cancelar', methods: ['POST'])]
    public function cancelar(
        int $id,
        #[CurrentUser] Usuario $usuario,
    ): JsonResponse {
        try {
            $partida = $this->partidaService->cancelarReto($usuario, $id);

            return $this->json([
                'success' => true,
                'message' => 'Partida cancelada correctamente',
                'partida' => $partida,
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route('/{id}/resolver', name: 'resolver', methods: ['POST'])]
    public function resolver(
        int $id,
        #[CurrentUser] Usuario $usuario,
    ): JsonResponse {
        try {
            $partida = $this->partidaService->resolverPartida($usuario, $id);

            return $this->json([
                'success' => true,
                'message' => 'Partida resuelta correctamente',
                'partida' => $partida,
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }
}
