<?php

namespace App\Enum;

enum EstadoPartidaEnum: string
{
    case PENDIENTE = 'pendiente';
    case ACEPTADA = 'aceptada';
    case RECHAZADA = 'rechazada';
    case CANCELADA = 'cancelada';
    case RESUELTA = 'resuelta';
}
