<?php

namespace App\Controller;

use App\Entity\Usuario;
use App\Service\PremiumService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/premium', name: 'api_premium_')]
class PremiumController extends AbstractController
{
    public function __construct(
        private PremiumService $premiumService,
    ) {}

    #[Route('/estado', name: 'estado', methods: ['GET'])]
    public function estado(#[CurrentUser] Usuario $usuario): JsonResponse
    {
        try {
            return $this->json([
                'success' => true,
                'usuario' => $this->premiumService->obtenerEstadoPremium($usuario),
            ]);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/comprar', name: 'comprar', methods: ['POST'])]
    public function comprar(Request $request, #[CurrentUser] Usuario $usuario): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            $meses = isset($data['meses']) ? (int) $data['meses'] : 1;

            $resultado = $this->premiumService->comprarPremium($usuario, $meses);

            return $this->json([
                'success' => true,
                ...$resultado,
            ], JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}
