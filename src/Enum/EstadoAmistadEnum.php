<?php

namespace App\Enum;

enum EstadoAmistadEnum: string
{
    case PENDIENTE = 'pendiente';
    case ACEPTADA = 'aceptada';
    case RECHAZADA = 'rechazada';
}
