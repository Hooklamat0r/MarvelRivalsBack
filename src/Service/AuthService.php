<?php

namespace App\Service;

use App\Entity\Usuario;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class AuthService
{
    public function __construct(
        private UsuarioRepository $usuarioRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager,
    ) {}

    public function register(string $email, string $password, string $nombre, string $apellido): array
    {
        if ($this->usuarioRepository->findOneBy(['email' => $email])) {
            throw new \Exception('El email ya está registrado');
        }

        $usuario = new Usuario();
        $usuario->setEmail($email);
        $usuario->setNombre($nombre);
        $usuario->setApellido($apellido);
        $usuario->setContrasena($this->passwordHasher->hashPassword($usuario, $password));

        $this->entityManager->persist($usuario);
        $this->entityManager->flush();

        return $this->getUserResponse($usuario);
    }

    public function login(string $email, string $password): array
    {
        $usuario = $this->usuarioRepository->findOneBy(['email' => $email]);

        if (!$usuario || !$this->passwordHasher->isPasswordValid($usuario, $password)) {
            throw new \Exception('Credenciales inválidas');
        }

        if (!$usuario->isActive()) {
            throw new \Exception('Tu cuenta ha sido desactivada. Contacta con el administrador.');
        }

        return $this->getUserResponse($usuario);
    }

    private function getUserResponse(Usuario $usuario): array
    {
        return [
            'token' => $this->jwtManager->create($usuario),
            'usuario' => [
                'id' => $usuario->getId(),
                'email' => $usuario->getEmail(),
                'nombre' => $usuario->getNombre(),
                'apellido' => $usuario->getApellido(),
                'rol' => $usuario->getRol()->value,
                'active' => $usuario->isActive(),
                'isPremium' => $usuario->hasPremiumActiva(),
                'premiumUntil' => $usuario->getPremiumUntil()?->format('Y-m-d H:i:s'),
            ],
        ];
    }
}
