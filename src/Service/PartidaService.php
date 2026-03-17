<?php

namespace App\Service;

use App\Entity\Partida;
use App\Entity\Usuario;
use App\Enum\EstadoPartidaEnum;
use App\Repository\PartidaRepository;
use App\Repository\TeamUpRepository;
use App\Repository\UsuarioPersonajeRepository;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;

class PartidaService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UsuarioRepository $usuarioRepository,
        private UsuarioPersonajeRepository $usuarioPersonajeRepository,
        private TeamUpRepository $teamUpRepository,
        private PartidaRepository $partidaRepository,
        private PersonajeSeleccionService $personajeSeleccionService,
        private AmistadService $amistadService,
    ) {}

    public function crearReto(Usuario $retador, ?int $retadoId = null): array
    {
        $this->assertEquipoCompleto($retador, 'Tu equipo debe estar completo para crear una partida');
        $esAleatoria = $retadoId === null;

        if ($retadoId !== null && !$retador->hasPremiumActiva()) {
            throw new \Exception('Necesitas premium activo para elegir rival manualmente');
        }

        if ($retadoId !== null) {
            $retado = $this->usuarioRepository->find($retadoId);

            if (!$retado || !$retado->isActive()) {
                throw new \Exception('Usuario retado no encontrado');
            }

            if ($retador->getId() === $retado->getId()) {
                throw new \Exception('No puedes crear una partida contra ti mismo');
            }

            if (!$this->amistadService->sonAmigosAceptados($retador, $retado)) {
                throw new \Exception('Solo puedes seleccionar usuarios que sean amigos aceptados');
            }

            $this->assertRetadoElegible($retador, $retado);
        } else {
            $retado = $this->seleccionarRetadoAleatorio($retador);
        }

        $partida = new Partida();
        $partida->setRetador($retador);
        $partida->setRetado($retado);
        $partida->setTipoReto($esAleatoria ? 'aleatoria' : 'amigo');

        if ($esAleatoria) {
            $partida->setEquipoRetador($this->obtenerSnapshotEquipo($retador));
            $partida->setEquipoRetado($this->obtenerSnapshotEquipo($retado));
            $partida->setEstado(EstadoPartidaEnum::ACEPTADA);
            $partida->setRespondedAt(new \DateTimeImmutable());
        } else {
            $partida->setEstado(EstadoPartidaEnum::PENDIENTE);
        }

        $this->entityManager->persist($partida);
        $this->entityManager->flush();

        return $this->buildPartidaResponse($partida);
    }

    public function aceptarReto(Usuario $usuarioActual, int $partidaId): array
    {
        if (!$usuarioActual->hasPremiumActiva()) {
            throw new \Exception('Solo usuarios premium pueden gestionar retos entre amigos');
        }

        $partida = $this->getPartidaOrFail($partidaId);

        if ($partida->getRetado()?->getId() !== $usuarioActual->getId()) {
            throw new \Exception('Solo el usuario retado puede aceptar esta partida');
        }

        if ($partida->getEstado() !== EstadoPartidaEnum::PENDIENTE) {
            throw new \Exception('La partida no está en estado pendiente');
        }

        $retador = $partida->getRetador();
        $retado = $partida->getRetado();

        $this->assertEquipoCompleto($retador, 'El retador ya no tiene un equipo válido para jugar');
        $this->assertEquipoCompleto($retado, 'Tu equipo debe estar completo para aceptar la partida');

        $partida->setEquipoRetador($this->obtenerSnapshotEquipo($retador));
        $partida->setEquipoRetado($this->obtenerSnapshotEquipo($retado));
        $partida->setEstado(EstadoPartidaEnum::ACEPTADA);
        $partida->setRespondedAt(new \DateTimeImmutable());
        $partida->setUpdatedAt();

        $this->entityManager->flush();

        return $this->buildPartidaResponse($partida);
    }

    public function rechazarReto(Usuario $usuarioActual, int $partidaId): array
    {
        if (!$usuarioActual->hasPremiumActiva()) {
            throw new \Exception('Solo usuarios premium pueden gestionar retos entre amigos');
        }

        $partida = $this->getPartidaOrFail($partidaId);

        if ($partida->getRetado()?->getId() !== $usuarioActual->getId()) {
            throw new \Exception('Solo el usuario retado puede rechazar esta partida');
        }

        if ($partida->getEstado() !== EstadoPartidaEnum::PENDIENTE) {
            throw new \Exception('La partida no está en estado pendiente');
        }

        $partida->setEstado(EstadoPartidaEnum::RECHAZADA);
        $partida->setRespondedAt(new \DateTimeImmutable());
        $partida->setUpdatedAt();

        $this->entityManager->flush();

        return $this->buildPartidaResponse($partida);
    }

    public function cancelarReto(Usuario $usuarioActual, int $partidaId): array
    {
        if (!$usuarioActual->hasPremiumActiva()) {
            throw new \Exception('Solo usuarios premium pueden gestionar retos entre amigos');
        }

        $partida = $this->getPartidaOrFail($partidaId);

        if ($partida->getRetador()?->getId() !== $usuarioActual->getId()) {
            throw new \Exception('Solo el retador puede cancelar esta partida');
        }

        if ($partida->getEstado() !== EstadoPartidaEnum::PENDIENTE) {
            throw new \Exception('Solo se pueden cancelar partidas pendientes');
        }

        $partida->setEstado(EstadoPartidaEnum::CANCELADA);
        $partida->setRespondedAt(new \DateTimeImmutable());
        $partida->setUpdatedAt();

        $this->entityManager->flush();

        return $this->buildPartidaResponse($partida);
    }

    public function resolverPartida(Usuario $usuarioActual, int $partidaId): array
    {
        $partida = $this->getPartidaOrFail($partidaId);

        $esParticipante =
            $partida->getRetador()?->getId() === $usuarioActual->getId()
            || $partida->getRetado()?->getId() === $usuarioActual->getId();

        if (!$esParticipante) {
            throw new \Exception('Solo los participantes pueden resolver la partida');
        }

        if ($partida->getEstado() !== EstadoPartidaEnum::ACEPTADA) {
            throw new \Exception('Solo se pueden resolver partidas aceptadas');
        }

        $equipoRetador = $partida->getEquipoRetador() ?? $this->obtenerSnapshotEquipo($partida->getRetador());
        $equipoRetado = $partida->getEquipoRetado() ?? $this->obtenerSnapshotEquipo($partida->getRetado());

        $resultadoRetador = $this->calcularPuntuacionEquipo($equipoRetador);
        $resultadoRetado = $this->calcularPuntuacionEquipo($equipoRetado);

        $ganador = $this->resolverGanador($partida, $resultadoRetador, $resultadoRetado);

        $partida->setPuntuacionRetador($resultadoRetador['puntuacion']);
        $partida->setPuntuacionRetado($resultadoRetado['puntuacion']);
        $partida->setDetalleRetador($resultadoRetador);
        $partida->setDetalleRetado($resultadoRetado);
        $partida->setGanador($ganador);
        $partida->setEstado(EstadoPartidaEnum::RESUELTA);
        $partida->setResolvedAt(new \DateTimeImmutable());
        $partida->setUpdatedAt();

        $this->entityManager->flush();

        return $this->buildPartidaResponse($partida);
    }

    public function obtenerPendientesRecibidas(Usuario $usuario): array
    {
        if (!$usuario->hasPremiumActiva()) {
            return [];
        }

        $partidas = $this->partidaRepository->findPendientesRecibidas($usuario);

        return array_map(fn(Partida $partida) => $this->buildPartidaResponse($partida), $partidas);
    }

    public function obtenerPendientesEnviadas(Usuario $usuario): array
    {
        if (!$usuario->hasPremiumActiva()) {
            return [];
        }

        $partidas = $this->partidaRepository->findPendientesEnviadas($usuario);

        return array_map(fn(Partida $partida) => $this->buildPartidaResponse($partida), $partidas);
    }

    public function obtenerHistorial(Usuario $usuario): array
    {
        $partidas = $this->partidaRepository->findHistorialUsuario($usuario);

        return array_map(fn(Partida $partida) => $this->buildPartidaResponse($partida), $partidas);
    }

    public function obtenerUsuariosDisponibles(Usuario $usuarioActual): array
    {
        if (!$usuarioActual->hasPremiumActiva()) {
            return [];
        }

        $amistades = $this->amistadService->listarAmigos($usuarioActual);
        $disponibles = [];

        foreach ($amistades as $amistad) {
            $amigoData = $amistad['usuario'] ?? null;
            if (!isset($amigoData['id'])) {
                continue;
            }

            $amigo = $this->usuarioRepository->find((int) $amigoData['id']);
            if (!$amigo || !$amigo->isActive()) {
                continue;
            }

            if ($this->partidaRepository->existePendienteEntreUsuarios($usuarioActual, $amigo)) {
                continue;
            }

            $validacion = $this->personajeSeleccionService->validarEquipoCompleto($amigo);
            if (!$validacion['valido']) {
                continue;
            }

            $disponibles[] = $this->normalizarUsuarioSimple($amigo);
        }

        return $disponibles;
    }

    private function getPartidaOrFail(int $partidaId): Partida
    {
        $partida = $this->partidaRepository->find($partidaId);

        if (!$partida) {
            throw new \Exception('Partida no encontrada');
        }

        return $partida;
    }

    private function assertEquipoCompleto(Usuario $usuario, string $mensajeError): void
    {
        $validacion = $this->personajeSeleccionService->validarEquipoCompleto($usuario);

        if (!$validacion['valido']) {
            throw new \Exception($mensajeError . ': ' . implode('. ', $validacion['errores']));
        }
    }

    private function assertRetadoElegible(Usuario $retador, Usuario $retado): void
    {
        if ($this->partidaRepository->existePendienteEntreUsuarios($retador, $retado)) {
            throw new \Exception('Ya existe una partida pendiente entre estos usuarios');
        }

        $this->assertEquipoCompleto($retado, 'El usuario retado no tiene un equipo válido actualmente');
    }

    private function seleccionarRetadoAleatorio(Usuario $retador): Usuario
    {
        $usuarios = $this->usuarioRepository->findBy(['active' => true]);

        $candidatos = [];
        foreach ($usuarios as $usuario) {
            if ($usuario->getId() === $retador->getId()) {
                continue;
            }

            if ($this->partidaRepository->existePendienteEntreUsuarios($retador, $usuario)) {
                continue;
            }

            $validacion = $this->personajeSeleccionService->validarEquipoCompleto($usuario);
            if (!$validacion['valido']) {
                continue;
            }

            $candidatos[] = $usuario;
        }

        if (count($candidatos) === 0) {
            throw new \Exception('No hay rivales disponibles ahora mismo para emparejamiento aleatorio');
        }

        $indice = random_int(0, count($candidatos) - 1);

        return $candidatos[$indice];
    }

    private function normalizarUsuarioSimple(Usuario $usuario): array
    {
        return [
            'id' => $usuario->getId(),
            'nombre' => $usuario->getNombre(),
            'apellido' => $usuario->getApellido(),
            'email' => $usuario->getEmail(),
        ];
    }

    private function obtenerSnapshotEquipo(Usuario $usuario): array
    {
        $selecciones = $this->usuarioPersonajeRepository->findBy(['usuario' => $usuario]);

        return array_map(fn($seleccion) => [
            'id' => $seleccion->getPersonaje()->getId(),
            'name' => $seleccion->getPersonaje()->getName(),
            'imageUrl' => $seleccion->getPersonaje()->getImageUrl(),
            'role' => $seleccion->getPersonaje()->getRole(),
            'difficulty' => $seleccion->getPersonaje()->getDifficulty(),
        ], $selecciones);
    }

    private function calcularPuntuacionEquipo(array $equipo): array
    {
        $roleWeights = [
            'Strategist' => 12,
            'Vanguard' => 10,
            'Duelist' => 8,
        ];

        $bonusRoles = 0;
        $roleCount = [
            'Strategist' => 0,
            'Vanguard' => 0,
            'Duelist' => 0,
        ];
        $personajeIds = [];
        $dificultadTotal = 0;
        foreach ($equipo as $heroe) {
            $role = $heroe['role'] ?? null;
            $bonusRoles += $roleWeights[$role] ?? 1;

            if (isset($roleCount[$role])) {
                $roleCount[$role]++;
            }

            if (!empty($heroe['id'])) {
                $personajeIds[] = $heroe['id'];
            }

            $difficulty = (int) ($heroe['difficulty'] ?? 0);
            $dificultadTotal += $difficulty;
        }

        $bonusComposicion = 0;
        if ($roleCount['Strategist'] >= 2) {
            $bonusComposicion += 24;
        }
        if ($roleCount['Vanguard'] >= 1) {
            $bonusComposicion += 18;
        }
        if ($roleCount['Duelist'] >= 1) {
            $bonusComposicion += 14;
        }

        $rolesConPresencia = count(array_filter($roleCount, fn(int $cantidad) => $cantidad > 0));
        $bonusDiversidad = match ($rolesConPresencia) {
            3 => 18,
            2 => 8,
            default => 0,
        };

        $bonusTeamUps = $this->calcularBonusTeamUps($personajeIds);
        $factorMomento = random_int($dificultadTotal * -1,$dificultadTotal);

        $puntuacionFinal = $bonusRoles
            + $bonusComposicion
            + $bonusDiversidad
            + $bonusTeamUps
            + $factorMomento;

        return [
            'puntuacion' => max(0, $puntuacionFinal),
            'bonusRoles' => $bonusRoles,
            'bonusComposicion' => $bonusComposicion,
            'bonusDiversidad' => $bonusDiversidad,
            'bonusTeamUps' => $bonusTeamUps,
            'factorMomento' => $factorMomento,
            'roleCount' => $roleCount,
        ];
    }

    private function calcularBonusTeamUps(array $personajeIds): int
    {
        if (count($personajeIds) < 2) {
            return 0;
        }

        $teamUps = $this->teamUpRepository->findAll();
        $ids = array_flip($personajeIds);
        $bonus = 0;

        foreach ($teamUps as $teamUp) {
            $personaje1 = $teamUp->getPersonaje1()?->getId();
            $personaje2 = $teamUp->getPersonaje2()?->getId();

            if (isset($ids[$personaje1]) && isset($ids[$personaje2])) {
                $bonus += 12;
            }
        }

        return $bonus;
    }

    private function resolverGanador(Partida $partida, array $resultadoRetador, array $resultadoRetado): Usuario
    {
        if ($resultadoRetador['puntuacion'] > $resultadoRetado['puntuacion']) {
            return $partida->getRetador();
        }

        if ($resultadoRetado['puntuacion'] > $resultadoRetador['puntuacion']) {
            return $partida->getRetado();
        }

        if ($resultadoRetador['bonusTeamUps'] > $resultadoRetado['bonusTeamUps']) {
            return $partida->getRetador();
        }

        if ($resultadoRetado['bonusTeamUps'] > $resultadoRetador['bonusTeamUps']) {
            return $partida->getRetado();
        }

        return $partida->getRetador()->getId() <= $partida->getRetado()->getId()
            ? $partida->getRetador()
            : $partida->getRetado();
    }

    private function buildPartidaResponse(Partida $partida): array
    {
        return [
            'id' => $partida->getId(),
            'estado' => $partida->getEstado()->value,
            'tipoReto' => $partida->getTipoReto(),
            'retador' => [
                'id' => $partida->getRetador()?->getId(),
                'nombre' => $partida->getRetador()?->getNombre(),
                'apellido' => $partida->getRetador()?->getApellido(),
            ],
            'retado' => [
                'id' => $partida->getRetado()?->getId(),
                'nombre' => $partida->getRetado()?->getNombre(),
                'apellido' => $partida->getRetado()?->getApellido(),
            ],
            'ganador' => $partida->getGanador() ? [
                'id' => $partida->getGanador()->getId(),
                'nombre' => $partida->getGanador()->getNombre(),
                'apellido' => $partida->getGanador()->getApellido(),
            ] : null,
            'equipoRetador' => $this->normalizarEquipoSnapshot($partida->getEquipoRetador()),
            'equipoRetado' => $this->normalizarEquipoSnapshot($partida->getEquipoRetado()),
            'puntuacionRetador' => $partida->getPuntuacionRetador(),
            'puntuacionRetado' => $partida->getPuntuacionRetado(),
            'detalleResultado' => [
                'retador' => $partida->getDetalleRetador(),
                'retado' => $partida->getDetalleRetado(),
            ],
            'createdAt' => $partida->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $partida->getUpdatedAt()->format('Y-m-d H:i:s'),
            'respondedAt' => $partida->getRespondedAt()?->format('Y-m-d H:i:s'),
            'resolvedAt' => $partida->getResolvedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    private function normalizarEquipoSnapshot(?array $equipo): ?array
    {
        if ($equipo === null) {
            return null;
        }

        return array_map(fn(array $heroe) => [
            'id' => (string) ($heroe['id'] ?? ''),
            'name' => (string) ($heroe['name'] ?? ''),
            'role' => $heroe['role'] ?? null,
            'imageUrl' => $heroe['imageUrl'] ?? null,
            'difficulty' => $heroe['difficulty'] ?? null,
        ], $equipo);
    }
}
