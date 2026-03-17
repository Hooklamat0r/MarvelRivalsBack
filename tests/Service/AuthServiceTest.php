<?php

namespace App\Tests\Service;

use App\Entity\Usuario;
use App\Repository\UsuarioRepository;
use App\Service\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[CoversClass(AuthService::class)]
class AuthServiceTest extends TestCase
{
    public function testRegisterThrowsWhenEmailAlreadyExists(): void
    {
        $usuarioRepository = $this->createMock(UsuarioRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $jwtManager = $this->createMock(JWTTokenManagerInterface::class);

        $usuarioRepository
            ->method('findOneBy')
            ->with(['email' => 'existing@example.com'])
            ->willReturn(new Usuario());

        $service = new AuthService($usuarioRepository, $entityManager, $passwordHasher, $jwtManager);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('El email ya está registrado');

        $service->register('existing@example.com', 'secret123', 'Test', 'User');
    }

    public function testRegisterPersistsAndReturnsTokenPayload(): void
    {
        $usuarioRepository = $this->createMock(UsuarioRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $jwtManager = $this->createMock(JWTTokenManagerInterface::class);

        $usuarioRepository
            ->method('findOneBy')
            ->with(['email' => 'new@example.com'])
            ->willReturn(null);

        $passwordHasher
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $jwtManager
            ->method('create')
            ->willReturn('jwt_token');

        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $service = new AuthService($usuarioRepository, $entityManager, $passwordHasher, $jwtManager);

        $result = $service->register('new@example.com', 'secret123', 'Test', 'User');

        self::assertArrayHasKey('token', $result);
        self::assertSame('jwt_token', $result['token']);
        self::assertArrayHasKey('usuario', $result);
        self::assertSame('new@example.com', $result['usuario']['email']);
        self::assertSame('Test', $result['usuario']['nombre']);
        self::assertSame('User', $result['usuario']['apellido']);
    }

    public function testLoginThrowsWhenUserIsInactive(): void
    {
        $usuario = new Usuario();
        $usuario->setEmail('inactive@example.com');
        $usuario->setContrasena('hashed_password');
        $usuario->setNombre('Inactive');
        $usuario->setApellido('User');
        $usuario->setActive(false);

        $usuarioRepository = $this->createMock(UsuarioRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $jwtManager = $this->createMock(JWTTokenManagerInterface::class);

        $usuarioRepository
            ->method('findOneBy')
            ->with(['email' => 'inactive@example.com'])
            ->willReturn($usuario);

        $passwordHasher
            ->method('isPasswordValid')
            ->with($usuario, 'secret123')
            ->willReturn(true);

        $service = new AuthService($usuarioRepository, $entityManager, $passwordHasher, $jwtManager);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Tu cuenta ha sido desactivada. Contacta con el administrador.');

        $service->login('inactive@example.com', 'secret123');
    }
}
