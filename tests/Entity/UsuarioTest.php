<?php

namespace App\Tests\Entity;

use App\Entity\Usuario;
use App\Enum\RoleEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Usuario::class)]
class UsuarioTest extends TestCase
{
    public function testGetRolesReturnsAdminAndUserForAdminRole(): void
    {
        $usuario = new Usuario();
        $usuario->setRol(RoleEnum::ADMIN);

        $roles = $usuario->getRoles();

        self::assertContains('ROLE_ADMIN', $roles);
        self::assertContains('ROLE_USER', $roles);
    }

    public function testHasPremiumActivaReturnsTrueWhenPremiumIsInFuture(): void
    {
        $usuario = new Usuario();
        $usuario->setPremiumUntil(new \DateTimeImmutable('+1 day'));

        self::assertTrue($usuario->hasPremiumActiva());
    }

    public function testHasPremiumActivaReturnsFalseWhenPremiumIsExpired(): void
    {
        $usuario = new Usuario();
        $usuario->setPremiumUntil(new \DateTimeImmutable('-1 day'));

        self::assertFalse($usuario->hasPremiumActiva());
    }
}
