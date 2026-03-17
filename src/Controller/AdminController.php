<?php

namespace App\Controller;

use App\Entity\Personaje;
use App\Entity\Usuario;
use App\Repository\PersonajeRepository;
use App\Repository\UsuarioRepository;
use App\Service\PersonajeService;
use App\Service\UsuarioService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin', name: 'app_admin_')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private UsuarioRepository $usuarioRepository,
        private PersonajeRepository $personajeRepository,
        private UsuarioService $usuarioService,
        private PersonajeService $personajeService,
    ) {}

    #[Route('/usuarios', name: 'usuarios_list', methods: ['GET'])]
    public function listUsuarios(): JsonResponse
    {
        $usuarios = $this->usuarioRepository->findAll();

        return $this->json([
            'success' => true,
            'usuarios' => array_map(fn(Usuario $u) => [
                'id' => $u->getId(),
                'email' => $u->getEmail(),
                'nombre' => $u->getNombre(),
                'apellido' => $u->getApellido(),
                'rol' => $u->getRol()->value,
                'active' => $u->isActive(),
            ], $usuarios),
        ]);
    }

    #[Route('/usuarios/{id}', name: 'usuario_update', methods: ['PUT'])]
    public function updateUsuario(int $id, Request $request): JsonResponse
    {
        $usuario = $this->usuarioRepository->find($id);
        if (!$usuario) {
            return $this->json(
                ['message' => 'Usuario no encontrado'],
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

        try {
            $response = $this->usuarioService->actualizarUsuarioAdmin(
                $usuario,
                $data['nombre'] ?? null,
                $data['apellido'] ?? null,
                $data['email'] ?? null,
                $data['password'] ?? null,
                $data['rol'] ?? null
            );

            return $this->json([
                'success' => true,
                'message' => 'Usuario actualizado correctamente',
                'usuario' => $response,
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route('/usuarios/{id}/toggle-active', name: 'usuario_toggle_active', methods: ['PATCH'])]
    public function toggleUsuarioActive(int $id): JsonResponse
    {
        $usuario = $this->usuarioRepository->find($id);
        if (!$usuario) {
            return $this->json(
                ['message' => 'Usuario no encontrado'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        try {
            $response = $this->usuarioService->toggleActive($usuario);

            return $this->json([
                'success' => true,
                'message' => $response['active'] ? 'Usuario activado correctamente' : 'Usuario desactivado correctamente',
                'usuario' => $response,
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route('/personajes/{id}', name: 'personaje_update', methods: ['PUT'])]
    public function updatePersonaje(string $id, Request $request): JsonResponse
    {
        $personaje = $this->personajeRepository->find($id);
        if (!$personaje) {
            return $this->json(
                ['message' => 'Personaje no encontrado'],
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

        try {
            $response = $this->personajeService->actualizarPersonaje(
                $personaje,
                $data['name'] ?? null,
                $data['role'] ?? null,
                $data['difficulty'] ?? null,
                $data['imageUrl'] ?? null
            );

            return $this->json([
                'success' => true,
                'message' => 'Personaje actualizado correctamente',
                'personaje' => $response,
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route('/personajes/{id}', name: 'personaje_delete', methods: ['DELETE'])]
    public function deletePersonaje(string $id): JsonResponse
    {
        $personaje = $this->personajeRepository->find($id);
        if (!$personaje) {
            return $this->json(
                ['message' => 'Personaje no encontrado'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        try {
            $this->personajeService->eliminarPersonaje($personaje);

            return $this->json([
                'success' => true,
                'message' => 'Personaje eliminado correctamente',
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }
}
