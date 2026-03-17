<?php

namespace App\Service;

use App\Entity\Usuario;
use App\Entity\UsuarioPersonaje;
use App\Repository\PersonajeRepository;
use App\Repository\UsuarioPersonajeRepository;
use Doctrine\ORM\EntityManagerInterface;

class PersonajeSeleccionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PersonajeRepository $personajeRepository,
        private UsuarioPersonajeRepository $usuarioPersonajeRepository,
    ) {}

    public function seleccionarPersonaje(Usuario $usuario, string $personajeId): array
    {
        $personaje = $this->personajeRepository->find($personajeId);
        if (!$personaje) {
            throw new \Exception('Personaje no encontrado');
        }

        $existente = $this->usuarioPersonajeRepository->findOneBy([
            'usuario' => $usuario,
            'personaje' => $personaje,
        ]);

        if ($existente) {
            throw new \Exception('Este personaje ya está seleccionado');
        }

        $personajesActuales = $this->usuarioPersonajeRepository->findBy(['usuario' => $usuario]);

        if (count($personajesActuales) >= 6) {
            throw new \Exception('Tu equipo ya tiene 6 héroes. Deselecciona uno antes de agregar otro.');
        }

        $usuarioPersonaje = new UsuarioPersonaje();
        $usuarioPersonaje->setUsuario($usuario);
        $usuarioPersonaje->setPersonaje($personaje);

        $this->entityManager->persist($usuarioPersonaje);
        $this->entityManager->flush();

        $validacionCompleta = $this->validarEquipoCompleto($usuario);

        return [
            'success' => true,
            'message' => 'Personaje seleccionado correctamente',
            'personaje' => [
                'id' => $personaje->getId(),
                'name' => $personaje->getName(),
                'role' => $personaje->getRole(),
            ],
            'validacion' => $validacionCompleta,
        ];
    }

    public function deseleccionarPersonaje(Usuario $usuario, string $personajeId): array
    {
        $personaje = $this->personajeRepository->find($personajeId);
        if (!$personaje) {
            throw new \Exception('Personaje no encontrado');
        }

        $usuarioPersonaje = $this->usuarioPersonajeRepository->findOneBy([
            'usuario' => $usuario,
            'personaje' => $personaje,
        ]);

        if (!$usuarioPersonaje) {
            throw new \Exception('Este personaje no está seleccionado');
        }

        $this->entityManager->remove($usuarioPersonaje);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => 'Personaje deseleccionado correctamente',
        ];
    }

    public function obtenerPersonajesSeleccionados(Usuario $usuario): array
    {
        $usuarioPersonajes = $this->usuarioPersonajeRepository->findBy(
            ['usuario' => $usuario],
            ['añadidoEn' => 'DESC']
        );

        return array_map(fn(UsuarioPersonaje $up) => [
            'id' => $up->getPersonaje()->getId(),
            'name' => $up->getPersonaje()->getName(),
            'role' => $up->getPersonaje()->getRole(),
            'difficulty' => $up->getPersonaje()->getDifficulty(),
            'imageUrl' => $up->getPersonaje()->getImageUrl(),
            'seleccionadoEn' => $up->getAñadidoEn()->format('Y-m-d H:i:s'),
        ], $usuarioPersonajes);
    }

    public function validarEquipoCompleto(Usuario $usuario): array
    {
        $personajesActuales = $this->usuarioPersonajeRepository->findBy(['usuario' => $usuario]);

        $roles = array_map(
            fn(UsuarioPersonaje $up) => $up->getPersonaje()->getRole(),
            $personajesActuales
        );

        $totalPersonajes = count($roles);
        $strategists = count(array_filter($roles, fn($role) => $role === 'Strategist'));
        $vanguards = count(array_filter($roles, fn($role) => $role === 'Vanguard'));

        $errores = [];

        if ($totalPersonajes !== 6) {
            $errores[] = 'Tu equipo debe tener exactamente 6 héroes (actualmente tienes ' . $totalPersonajes . ')';
        }

        if ($strategists < 2) {
            $errores[] = 'Necesitas al menos 2 Strategists (actualmente tienes ' . $strategists . ')';
        }

        if ($vanguards < 1) {
            $errores[] = 'Necesitas al menos 1 Vanguard (actualmente tienes ' . $vanguards . ')';
        }

        return [
            'valido' => empty($errores),
            'total' => $totalPersonajes,
            'strategists' => $strategists,
            'vanguards' => $vanguards,
            'errores' => $errores,
            'advertencias' => [],
        ];
    }
}
