<?php

namespace App\Service;

use App\Entity\Pago;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;

class PremiumService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function comprarPremium(Usuario $usuario, int $meses = 1): array
    {
        if ($meses < 1 || $meses > 12) {
            throw new \Exception('El número de meses debe estar entre 1 y 12');
        }

        if ($usuario->hasPremiumActiva()) {
            throw new \Exception('Ya tienes una suscripción premium activa. Podrás renovarla cuando caduque');
        }

        usleep(1800000);

        $ahora = new \DateTimeImmutable();
        $premiumUntil = $ahora->modify(sprintf('+%d month', $meses));

        $pago = new Pago();
        $pago->setUsuario($usuario);
        $pago->setMeses($meses);
        $pago->setPrecio($meses * 499);
        $pago->setPremiumUntil($premiumUntil);

        $usuario->setPremiumUntil($premiumUntil);

        $this->entityManager->persist($pago);
        $this->entityManager->flush();

        return [
            'message' => 'Pago completado correctamente',
            'pago' => [
                'id' => $pago->getId(),
                'plan' => 'premium',
                'meses' => $pago->getMeses(),
                'precio' => $pago->getPrecio(),
                'estado' => 'completado',
                'createdAt' => $ahora->format('Y-m-d H:i:s'),
                'premiumUntil' => $pago->getPremiumUntil()->format('Y-m-d H:i:s'),
            ],
            'usuario' => $this->buildUsuarioResponse($usuario),
        ];
    }

    public function obtenerEstadoPremium(Usuario $usuario): array
    {
        return $this->buildUsuarioResponse($usuario);
    }

    private function buildUsuarioResponse(Usuario $usuario): array
    {
        return [
            'id' => $usuario->getId(),
            'email' => $usuario->getEmail(),
            'nombre' => $usuario->getNombre(),
            'apellido' => $usuario->getApellido(),
            'rol' => $usuario->getRol()->value,
            'active' => $usuario->isActive(),
            'isPremium' => $usuario->hasPremiumActiva(),
            'premiumUntil' => $usuario->getPremiumUntil()?->format('Y-m-d H:i:s'),
        ];
    }
}
