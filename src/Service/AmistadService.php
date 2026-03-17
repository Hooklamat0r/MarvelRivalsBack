<?php

namespace App\Service;

use App\Entity\Amistad;
use App\Entity\Usuario;
use App\Enum\EstadoAmistadEnum;
use App\Repository\AmistadRepository;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;

class AmistadService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AmistadRepository $amistadRepository,
        private UsuarioRepository $usuarioRepository,
    ) {}

    public function buscarUsuarios(Usuario $usuarioActual, ?string $query): array
    {
        $this->assertPremiumActiva($usuarioActual);

        $q = mb_strtolower(trim((string) $query));
        $usuarios = $this->usuarioRepository->findBy(['active' => true]);

        $resultados = [];
        foreach ($usuarios as $usuario) {
            if ($usuario->getId() === $usuarioActual->getId()) {
                continue;
            }

            if ($q !== '') {
                $nombre = mb_strtolower($usuario->getNombre() ?? '');
                $apellido = mb_strtolower($usuario->getApellido() ?? '');
                $email = mb_strtolower($usuario->getEmail() ?? '');

                if (!str_contains($nombre, $q) && !str_contains($apellido, $q) && !str_contains($email, $q)) {
                    continue;
                }
            }

            $relacion = $this->amistadRepository->findRelacionEntreUsuarios($usuarioActual, $usuario);
            $resultados[] = [
                'id' => $usuario->getId(),
                'nombre' => $usuario->getNombre(),
                'apellido' => $usuario->getApellido(),
                'email' => $usuario->getEmail(),
                'isPremium' => $usuario->hasPremiumActiva(),
                'relacion' => $relacion ? $this->buildRelacionMeta($relacion, $usuarioActual) : null,
            ];
        }

        return $resultados;
    }

    public function listarAmigos(Usuario $usuarioActual): array
    {
        $this->assertPremiumActiva($usuarioActual);

        $amistades = $this->amistadRepository->findAmistadesAceptadas($usuarioActual);

        return array_map(function (Amistad $amistad) use ($usuarioActual) {
            $amigo = $amistad->getSolicitante()?->getId() === $usuarioActual->getId()
                ? $amistad->getReceptor()
                : $amistad->getSolicitante();

            return [
                'amistadId' => $amistad->getId(),
                'usuario' => [
                    'id' => $amigo?->getId(),
                    'nombre' => $amigo?->getNombre(),
                    'apellido' => $amigo?->getApellido(),
                    'email' => $amigo?->getEmail(),
                ],
                'estado' => $amistad->getEstado()->value,
                'updatedAt' => $amistad->getUpdatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $amistades);
    }

    public function listarSolicitudesRecibidas(Usuario $usuarioActual): array
    {
        $this->assertPremiumActiva($usuarioActual);

        $amistades = $this->amistadRepository->findSolicitudesRecibidasPendientes($usuarioActual);

        return array_map(fn(Amistad $amistad) => $this->buildSolicitudResponse($amistad), $amistades);
    }

    public function listarSolicitudesEnviadas(Usuario $usuarioActual): array
    {
        $this->assertPremiumActiva($usuarioActual);

        $amistades = $this->amistadRepository->findSolicitudesEnviadasPendientes($usuarioActual);

        return array_map(fn(Amistad $amistad) => $this->buildSolicitudResponse($amistad), $amistades);
    }

    public function solicitarAmistad(Usuario $usuarioActual, int $usuarioDestinoId): array
    {
        $this->assertPremiumActiva($usuarioActual);

        $destino = $this->usuarioRepository->find($usuarioDestinoId);
        if (!$destino || !$destino->isActive()) {
            throw new \Exception('Usuario destino no encontrado');
        }

        $this->assertPremiumActiva($destino);

        if ($destino->getId() === $usuarioActual->getId()) {
            throw new \Exception('No puedes enviarte una solicitud a ti mismo');
        }

        $relacionExistente = $this->amistadRepository->findRelacionEntreUsuarios($usuarioActual, $destino);
        if ($relacionExistente) {
            throw new \Exception('Ya existe una relación de amistad con este usuario');
        }

        $amistad = new Amistad();
        $amistad->setSolicitante($usuarioActual);
        $amistad->setReceptor($destino);
        $amistad->setEstado(EstadoAmistadEnum::PENDIENTE);

        $this->entityManager->persist($amistad);
        $this->entityManager->flush();

        return $this->buildSolicitudResponse($amistad);
    }

    public function aceptarSolicitud(Usuario $usuarioActual, int $amistadId): array
    {
        $this->assertPremiumActiva($usuarioActual);

        $amistad = $this->getAmistadOrFail($amistadId);

        if ($amistad->getReceptor()?->getId() !== $usuarioActual->getId()) {
            throw new \Exception('Solo el receptor puede aceptar la solicitud');
        }

        if ($amistad->getEstado() !== EstadoAmistadEnum::PENDIENTE) {
            throw new \Exception('La solicitud ya fue gestionada');
        }

        $solicitante = $amistad->getSolicitante();
        if (!$solicitante || !$solicitante->hasPremiumActiva()) {
            throw new \Exception('La solicitud no es válida porque el solicitante no tiene premium activo');
        }

        $amistad->setEstado(EstadoAmistadEnum::ACEPTADA);
        $amistad->setRespondedAt(new \DateTimeImmutable());
        $amistad->setUpdatedAt();

        $this->entityManager->flush();

        return $this->buildSolicitudResponse($amistad);
    }

    public function rechazarSolicitud(Usuario $usuarioActual, int $amistadId): array
    {
        $this->assertPremiumActiva($usuarioActual);

        $amistad = $this->getAmistadOrFail($amistadId);

        if ($amistad->getReceptor()?->getId() !== $usuarioActual->getId()) {
            throw new \Exception('Solo el receptor puede rechazar la solicitud');
        }

        if ($amistad->getEstado() !== EstadoAmistadEnum::PENDIENTE) {
            throw new \Exception('La solicitud ya fue gestionada');
        }

        $amistad->setEstado(EstadoAmistadEnum::RECHAZADA);
        $amistad->setRespondedAt(new \DateTimeImmutable());
        $amistad->setUpdatedAt();

        $this->entityManager->flush();

        return $this->buildSolicitudResponse($amistad);
    }

    public function eliminarAmistad(Usuario $usuarioActual, int $amigoId): void
    {
        $this->assertPremiumActiva($usuarioActual);

        $amigo = $this->usuarioRepository->find($amigoId);
        if (!$amigo) {
            throw new \Exception('Usuario no encontrado');
        }

        $amistad = $this->amistadRepository->findRelacionEntreUsuarios($usuarioActual, $amigo);
        if (!$amistad || $amistad->getEstado() !== EstadoAmistadEnum::ACEPTADA) {
            throw new \Exception('No existe una amistad activa con ese usuario');
        }

        $this->entityManager->remove($amistad);
        $this->entityManager->flush();
    }

    public function sonAmigosAceptados(Usuario $usuarioA, Usuario $usuarioB): bool
    {
        if (!$usuarioA->hasPremiumActiva() || !$usuarioB->hasPremiumActiva()) {
            return false;
        }

        $amistad = $this->amistadRepository->findRelacionEntreUsuarios($usuarioA, $usuarioB);

        return $amistad?->getEstado() === EstadoAmistadEnum::ACEPTADA;
    }

    private function assertPremiumActiva(Usuario $usuario): void
    {
        if (!$usuario->hasPremiumActiva()) {
            throw new \Exception('La funcionalidad de amigos está disponible solo para usuarios premium');
        }
    }

    private function getAmistadOrFail(int $amistadId): Amistad
    {
        $amistad = $this->amistadRepository->find($amistadId);

        if (!$amistad) {
            throw new \Exception('Solicitud de amistad no encontrada');
        }

        return $amistad;
    }

    private function buildSolicitudResponse(Amistad $amistad): array
    {
        return [
            'id' => $amistad->getId(),
            'estado' => $amistad->getEstado()->value,
            'solicitante' => [
                'id' => $amistad->getSolicitante()?->getId(),
                'nombre' => $amistad->getSolicitante()?->getNombre(),
                'apellido' => $amistad->getSolicitante()?->getApellido(),
                'email' => $amistad->getSolicitante()?->getEmail(),
            ],
            'receptor' => [
                'id' => $amistad->getReceptor()?->getId(),
                'nombre' => $amistad->getReceptor()?->getNombre(),
                'apellido' => $amistad->getReceptor()?->getApellido(),
                'email' => $amistad->getReceptor()?->getEmail(),
            ],
            'createdAt' => $amistad->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $amistad->getUpdatedAt()->format('Y-m-d H:i:s'),
            'respondedAt' => $amistad->getRespondedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    private function buildRelacionMeta(Amistad $amistad, Usuario $usuarioActual): array
    {
        $esSolicitante = $amistad->getSolicitante()?->getId() === $usuarioActual->getId();

        return [
            'id' => $amistad->getId(),
            'estado' => $amistad->getEstado()->value,
            'rolActual' => $esSolicitante ? 'solicitante' : 'receptor',
        ];
    }
}
