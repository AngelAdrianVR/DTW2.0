<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Eventos
    |--------------------------------------------------------------------------
    |
    | Mensajes del formulario público de registro a un evento. Se resuelven con
    | trans() porque el controlador responde en el idioma activo del visitante
    | (el middleware SetLocale toma el locale de la sesión).
    |
    */

    'registered' => '¡Listo! Tu registro quedó guardado. Te contactamos si ganas.',
    'closed' => 'El registro para este evento está cerrado.',
    'phone_taken' => 'Este teléfono ya está registrado en este evento.',
    'phone_invalid' => 'Escribe un teléfono válido de al menos 10 dígitos.',

];
