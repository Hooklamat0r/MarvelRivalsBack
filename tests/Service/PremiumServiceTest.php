<?php

namespace App\Tests\Service;

use App\Entity\Usuario;
use App\Service\PremiumService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PremiumService::class)]
class PremiumServiceTest extends TestCase
{
    public function testComprarPremiumThrowsWhenMesesOutOfRange(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $service = new PremiumService($entityManager);

        $usuario = new Usuario();
        $usuario->setEmail('test@example.com');
        $usuario->setNombre('Test');
        $usuario->setApellido('User');
        $usuario->setContrasena('hash');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('El número de meses debe estar entre 1 y 12');

        $service->comprarPremium($usuario, 0);
    }

    public function testObtenerEstadoPremiumReturnsExpectedShape(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $service = new PremiumService($entityManager);

        $usuario = new Usuario();
        $usuario->setEmail('premium@example.com');
        $usuario->setNombre('Premium');
        $usuario->setApellido('User');
        $usuario->setContrasena('hash');

        $result = $service->obtenerEstadoPremium($usuario);

        self::assertSame('premium@example.com', $result['email']);
        self::assertArrayHasKey('isPremium', $result);
        self::assertArrayHasKey('premiumUntil', $result);
    }
}
