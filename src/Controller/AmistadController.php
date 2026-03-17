<?php

namespace App\Controller;

use App\Entity\Usuario;
use App\Service\AmistadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/amigos', name: 'api_amigos_')]
class AmistadController extends AbstractController
{
    public function __construct(
        private AmistadService $amistadService,
    ) {}

    #[Route('', name: 'listar', methods: ['GET'])]
    public function listar(#[CurrentUser] Usuario $usuario): JsonResponse
    {
        try {
            $amigos = $this->amistadService->listarAmigos($usuario);

            return $this->json([
                'success' => true,
                'total' => count($amigos),
                'amigos' => $amigos,
            ]);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/buscar', name: 'buscar', methods: ['GET'])]
    public function buscar(Request $request, #[CurrentUser] Usuario $usuario): JsonResponse
    {
        try {
            $usuarios = $this->amistadService->buscarUsuarios($usuario, $request->query->get('q'));

            return $this->json([
                'success' => true,
                'total' => count($usuarios),
                'usuarios' => $usuarios,
            ]);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/solicitudes-recibidas', name: 'solicitudes_recibidas', methods: ['GET'])]
    public function solicitudesRecibidas(#[CurrentUser] Usuario $usuario): JsonResponse
    {
        try {
            $solicitudes = $this->amistadService->listarSolicitudesRecibidas($usuario);

            return $this->json([
                'success' => true,
                'total' => count($solicitudes),
                'solicitudes' => $solicitudes,
            ]);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/solicitudes-enviadas', name: 'solicitudes_enviadas', methods: ['GET'])]
    public function solicitudesEnviadas(#[CurrentUser] Usuario $usuario): JsonResponse
    {
        try {
            $solicitudes = $this->amistadService->listarSolicitudesEnviadas($usuario);

            return $this->json([
                'success' => true,
                'total' => count($solicitudes),
                'solicitudes' => $solicitudes,
            ]);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/solicitar', name: 'solicitar', methods: ['POST'])]
    public function solicitar(int $id, #[CurrentUser] Usuario $usuario): JsonResponse
    {
        try {
            $amistad = $this->amistadService->solicitarAmistad($usuario, $id);

            return $this->json([
                'success' => true,
                'message' => 'Solicitud de amistad enviada',
                'solicitud' => $amistad,
            ], JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/solicitudes/{id}/aceptar', name: 'aceptar', methods: ['POST'])]
    public function aceptar(int $id, #[CurrentUser] Usuario $usuario): JsonResponse
    {
        try {
            $amistad = $this->amistadService->aceptarSolicitud($usuario, $id);

            return $this->json([
                'success' => true,
                'message' => 'Solicitud de amistad aceptada',
                'solicitud' => $amistad,
            ]);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/solicitudes/{id}/rechazar', name: 'rechazar', methods: ['POST'])]
    public function rechazar(int $id, #[CurrentUser] Usuario $usuario): JsonResponse
    {
        try {
            $amistad = $this->amistadService->rechazarSolicitud($usuario, $id);

            return $this->json([
                'success' => true,
                'message' => 'Solicitud de amistad rechazada',
                'solicitud' => $amistad,
            ]);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'eliminar', methods: ['DELETE'])]
    public function eliminar(int $id, #[CurrentUser] Usuario $usuario): JsonResponse
    {
        try {
            $this->amistadService->eliminarAmistad($usuario, $id);

            return $this->json([
                'success' => true,
                'message' => 'Amistad eliminada correctamente',
            ]);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}
