<?php

namespace App\Controller;

use App\Service\PersonajeSeleccionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\Usuario;

#[Route('/api/usuario-personajes', name: 'api_usuario_personajes_')]
class UsuarioPersonajeController extends AbstractController
{
    public function __construct(
        private PersonajeSeleccionService $personajeSeleccionService,
    ) {}

    #[Route('/seleccionar', name: 'seleccionar', methods: ['POST'])]
    public function seleccionar(
        Request $request,
        #[CurrentUser] Usuario $usuario,
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['personajeId'])) {
                return $this->json(
                    ['message' => 'El ID del personaje es requerido'],
                    JsonResponse::HTTP_BAD_REQUEST
                );
            }

            $response = $this->personajeSeleccionService->seleccionarPersonaje(
                $usuario,
                $data['personajeId']
            );

            return $this->json($response);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route('/deseleccionar', name: 'deseleccionar', methods: ['POST'])]
    public function deseleccionar(
        Request $request,
        #[CurrentUser] Usuario $usuario,
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['personajeId'])) {
                return $this->json(
                    ['message' => 'El ID del personaje es requerido'],
                    JsonResponse::HTTP_BAD_REQUEST
                );
            }

            $response = $this->personajeSeleccionService->deseleccionarPersonaje(
                $usuario,
                $data['personajeId']
            );

            return $this->json($response);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route('/mis-personajes', name: 'mis_personajes', methods: ['GET'])]
    public function misPersonajes(
        #[CurrentUser] Usuario $usuario,
    ): JsonResponse {
        try {
            $personajes = $this->personajeSeleccionService->obtenerPersonajesSeleccionados($usuario);
            $validacion = $this->personajeSeleccionService->validarEquipoCompleto($usuario);

            return $this->json([
                'success' => true,
                'personajes' => $personajes,
                'total' => count($personajes),
                'validacion' => $validacion,
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route('/validar-equipo', name: 'validar_equipo', methods: ['GET'])]
    public function validarEquipo(
        #[CurrentUser] Usuario $usuario,
    ): JsonResponse {
        try {
            $validacion = $this->personajeSeleccionService->validarEquipoCompleto($usuario);

            return $this->json([
                'success' => true,
                'validacion' => $validacion,
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }
}
