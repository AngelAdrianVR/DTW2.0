<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Datos de contacto de DTW
    |--------------------------------------------------------------------------
    |
    | Se usan en la landing (navbar, footer, botón de WhatsApp) y como destino
    | de las notificaciones internas cuando entra un lead desde el sitio web.
    | Todos los valores se pueden sobreescribir por variables de entorno.
    |
    */

    'contact_email' => env('DTW_CONTACT_EMAIL', 'contacto@dtw.com.mx'),

    // Correo interno que recibe el aviso de cada nuevo lead del formulario.
    'lead_email' => env('DTW_LEAD_EMAIL', 'contacto@dtw.com.mx'),

    // Número de WhatsApp en formato internacional, solo dígitos (para wa.me).
    'whatsapp' => env('DTW_WHATSAPP', '523315314045'),

    // Versión legible del número para mostrarla en pantalla.
    'whatsapp_display' => env('DTW_WHATSAPP_DISPLAY', '+52 33 1531 4045'),

    /*
    |--------------------------------------------------------------------------
    | Soluciones (sección "Servicios" de la landing)
    |--------------------------------------------------------------------------
    |
    | Fuente única de datos de las soluciones que se muestran en el bloque de
    | servicios de la landing. La galería /soluciones ya no las pinta: ahí se
    | listan los proyectos de clientes del back-office (WebContent `portfolio`).
    |
    | Los campos `*_key` contienen la clave de traducción (por convención del
    | proyecto la clave es la frase en inglés) que Vue resuelve con t(), así
    | que el contenido se traduce automáticamente a ES/EN.
    |
    | `gallery` conserva imágenes adicionales por solución. Hoy no se pinta en
    | ninguna vista; se mantiene como material disponible por si se retoma.
    |
    */

    'solutions' => [
        [
            'slug' => 'apps-windows',
            'icon' => 'pi pi-desktop',
            'accent' => '#A855F7',
            'image' => '/images/sistemas.webp',
            'gallery' => [
                '/images/sistemas.webp',
                '/images/POS.webp',
                '/images/app3.webp',
            ],
            'title_key' => 'Desktop apps for Windows',
            'tagline_key' => 'Software that works in your plant or office, even without internet.',
            'summary_key' => 'Point of sale, inventory, production or billing: a system installed on your own equipment that does not depend on the cloud or on your connection speed.',
            'bullet_keys' => [
                'Works offline and syncs when there is a connection',
                'Connects scales, printers, readers and PLCs',
                'Installs in your office or plant, on your equipment',
            ],
        ],
        [
            'slug' => 'apps-android',
            'icon' => 'pi pi-mobile',
            'accent' => '#3B82F6',
            'image' => '/images/app3.webp',
            'gallery' => [
                '/images/app3.webp',
                '/images/home_IoT.webp',
                '/images/POS.webp',
            ],
            'title_key' => 'Android apps for field work',
            'tagline_key' => 'Inventory, deliveries and payments from any phone.',
            'summary_key' => 'Your team works on the street, in the warehouse or on the route with an app that captures data on the spot and leaves it ready to bill.',
            'bullet_keys' => [
                'Offline capture with automatic sync',
                'QR and barcode scanning',
                'Photo evidence and digital signatures',
            ],
        ],
        [
            'slug' => 'apps-web',
            'icon' => 'pi pi-globe',
            'accent' => '#17EDF4',
            'image' => '/images/web.webp',
            'gallery' => [
                '/images/web.webp',
                '/images/IA.webp',
                '/images/POS.webp',
            ],
            'title_key' => 'Web platforms and dashboards',
            'tagline_key' => 'Your operation visible from anywhere, in real time.',
            'summary_key' => 'Customer portals, control panels and internal systems that anyone can use without long training.',
            'bullet_keys' => [
                'Real-time dashboards',
                'Roles and permissions per team',
                'Works on any device, no installs',
            ],
        ],
        [
            'slug' => 'hardware-industrial',
            'icon' => 'pi pi-microchip',
            'accent' => '#EAB308',
            'image' => '/images/IoT.webp',
            'gallery' => [
                '/images/IoT.webp',
                '/images/home_IoT.webp',
                '/images/IOT2.png',
                '/images/sistemas.webp',
            ],
            'title_key' => 'Industrial hardware connected to software',
            'tagline_key' => 'Sensors, PLCs and scales reporting to your system in real time.',
            'summary_key' => 'We connect the machine with the system: piece counting, weight, temperature, downtime and consumption, without manual entries.',
            'bullet_keys' => [
                'Real-time reading of sensors and PLCs',
                'Automatic alerts when something goes out of range',
                'Traces and reports per shift or machine',
            ],
        ],
        [
            'slug' => 'inteligencia-artificial',
            'icon' => 'pi pi-sparkles',
            'accent' => '#C084FC',
            'image' => '/images/IA.webp',
            'gallery' => [
                '/images/IA.webp',
                '/images/web.webp',
                '/images/app3.webp',
            ],
            'title_key' => 'Applied artificial intelligence',
            'tagline_key' => 'Documents, forecasts and assistants that remove repetitive work.',
            'summary_key' => 'We automate document reading, information classification and the forecasts that someone is calculating by hand today.',
            'bullet_keys' => [
                'Document and invoice reading',
                'Demand and inventory forecasting',
                'Assistants trained on your own information',
            ],
        ],
    ],

];
