<?php

namespace App\Controller;

use App\Entity\Usuario;
use App\Service\AuthService;
use App\Service\UsuarioService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/auth', name: 'app_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private AuthService $authService,
        private UsuarioService $usuarioService,
    ) {}

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json(
                ['message' => 'Email y contraseña son requeridos'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        try {
            $response = $this->authService->login($data['email'], $data['password']);
            return $this->json($response);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password']) || !isset($data['nombre']) || !isset($data['apellido'])) {
            return $this->json(
                ['message' => 'Email, contraseña, nombre y apellido son requeridos'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        try {
            $response = $this->authService->register(
                $data['email'],
                $data['password'],
                $data['nombre'],
                $data['apellido']
            );
            return $this->json(
                ['message' => 'Usuario registrado exitosamente', ...$response],
                JsonResponse::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_CONFLICT
            );
        }
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function me(#[CurrentUser] Usuario $usuario): JsonResponse
    {
        return $this->json([
            'email' => $usuario->getEmail(),
            'nombre' => $usuario->getNombre(),
            'apellido' => $usuario->getApellido(),
            'active' => $usuario->isActive(),
            'isPremium' => $usuario->hasPremiumActiva(),
            'premiumUntil' => $usuario->getPremiumUntil()?->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/me', name: 'update_me', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function updateMe(Request $request, #[CurrentUser] Usuario $usuario): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(
                ['message' => 'Datos inválidos'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        try {
            $response = $this->usuarioService->actualizarUsuario(
                $usuario,
                $data['nombre'] ?? null,
                $data['apellido'] ?? null,
                $data['email'] ?? null,
                $data['password'] ?? null
            );

            return $this->json([
                'success' => true,
                'message' => 'Usuario actualizado correctamente',
                'usuario' => [
                    'email' => $response['email'] ?? $usuario->getEmail(),
                    'nombre' => $response['nombre'] ?? $usuario->getNombre(),
                    'apellido' => $response['apellido'] ?? $usuario->getApellido(),
                    'active' => $response['active'] ?? $usuario->isActive(),
                    'isPremium' => $response['isPremium'] ?? $usuario->hasPremiumActiva(),
                    'premiumUntil' => $response['premiumUntil'] ?? $usuario->getPremiumUntil()?->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }
}
