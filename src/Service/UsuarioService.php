<?php

namespace App\Service;

use App\Entity\Usuario;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Enum\RoleEnum;

class UsuarioService
{
    public function __construct(
        private UsuarioRepository $usuarioRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function actualizarUsuario(
        Usuario $usuario,
        ?string $nombre = null,
        ?string $apellido = null,
        ?string $email = null,
        ?string $password = null
    ): array {
        if ($nombre !== null && trim($nombre) !== '') {
            $usuario->setNombre(trim($nombre));
        }

        if ($apellido !== null && trim($apellido) !== '') {
            $usuario->setApellido(trim($apellido));
        }

        if ($email !== null && trim($email) !== '' && $email !== $usuario->getEmail()) {
            $usuarioExistente = $this->usuarioRepository->findOneBy(['email' => trim($email)]);
            if ($usuarioExistente && $usuarioExistente->getId() !== $usuario->getId()) {
                throw new \Exception('El email ya está en uso por otro usuario');
            }
            $usuario->setEmail(trim($email));
        }

        if ($password !== null && trim($password) !== '') {
            if (strlen(trim($password)) < 6) {
                throw new \Exception('La contraseña debe tener al menos 6 caracteres');
            }
            $usuario->setContrasena($this->passwordHasher->hashPassword($usuario, trim($password)));
        }

        $this->entityManager->flush();

        return $this->getUserResponse($usuario);
    }

    public function actualizarUsuarioAdmin(
        Usuario $usuario,
        ?string $nombre = null,
        ?string $apellido = null,
        ?string $email = null,
        ?string $password = null,
        ?string $rol = null
    ): array {
        if ($nombre !== null && trim($nombre) !== '') {
            $usuario->setNombre(trim($nombre));
        }

        if ($apellido !== null && trim($apellido) !== '') {
            $usuario->setApellido(trim($apellido));
        }

        if ($email !== null && trim($email) !== '' && $email !== $usuario->getEmail()) {
            $usuarioExistente = $this->usuarioRepository->findOneBy(['email' => trim($email)]);
            if ($usuarioExistente && $usuarioExistente->getId() !== $usuario->getId()) {
                throw new \Exception('El email ya está en uso por otro usuario');
            }
            $usuario->setEmail(trim($email));
        }

        if ($password !== null && trim($password) !== '') {
            if (strlen(trim($password)) < 6) {
                throw new \Exception('La contraseña debe tener al menos 6 caracteres');
            }
            $usuario->setContrasena($this->passwordHasher->hashPassword($usuario, trim($password)));
        }

        if ($rol !== null) {
            try {
                $rolEnum = RoleEnum::from($rol);
                $usuario->setRol($rolEnum);
            } catch (\ValueError $e) {
                throw new \Exception('Rol inválido');
            }
        }

        $this->entityManager->flush();

        return $this->getUserResponse($usuario);
    }

    public function toggleActive(Usuario $usuario): array
    {
        $usuario->setActive(!$usuario->isActive());
        $this->entityManager->flush();

        return $this->getUserResponse($usuario);
    }

    private function getUserResponse(Usuario $usuario): array
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

