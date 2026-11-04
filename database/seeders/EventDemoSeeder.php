<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

/**
 * Datos de ejemplo para probar el módulo de eventos sin capturar todo a mano.
 *
 * Es opcional y no se ejecuta desde DatabaseSeeder: córrelo cuando quieras
 * verlo funcionando en local.
 *
 *     php artisan db:seed --class=EventDemoSeeder
 */
class EventDemoSeeder extends Seeder
{
    public function run(): void
    {
        $open = Event::updateOrCreate(
            ['slug' => 'reto-automatizacion-2026'],
            [
                'spanish_title' => 'Reto de automatización 2026',
                'english_title' => 'Automation challenge 2026',
                'spanish_description' => 'Cuéntanos qué proceso te quita más tiempo y te decimos cómo automatizarlo.',
                'english_description' => 'Tell us which process takes the most time and we will show you how to automate it.',
                'spanish_challenge' => 'Describe el proceso manual que más tiempo le cuesta a tu negocio. '
                    . 'Buscamos ideas que se puedan resolver con software, integraciones o hardware.',
                'english_challenge' => 'Describe the manual process that costs your business the most time. '
                    . 'We are looking for ideas that can be solved with software, integrations or hardware.',
                'spanish_prizes_summary' => "1er lugar: sitio web completo + 3 meses de soporte.\n"
                    . "2do lugar: auditoría de procesos y manual de operación.\n"
                    . '3er lugar: diagnóstico financiero.',
                'english_prizes_summary' => "1st place: a full website + 3 months of support.\n"
                    . "2nd place: a process audit and an operations manual.\n"
                    . '3rd place: a financial diagnosis.',
                'event_date' => now()->addWeeks(3)->toDateString(),
                'registration_starts_at' => now()->subDays(5),
                'registration_ends_at' => now()->addWeeks(2),
                'custom_field_label_es' => '¿Qué proceso quieres automatizar?',
                'custom_field_label_en' => 'Which process do you want to automate?',
                'custom_field_placeholder_es' => 'Ej. Llevamos el inventario en cuadernos y perdemos ventas',
                'custom_field_placeholder_en' => 'E.g. We track inventory on paper and lose sales',
                'custom_field_required' => true,
                'is_published' => true,
                'sort_order' => 0,
            ],
        );

        $open->registrations()->updateOrCreate(
            ['phone' => '3312345678'],
            [
                'name' => 'Ana Torres',
                'business_name' => 'Ferretería del Norte',
                'custom_field_label' => '¿Qué proceso quieres automatizar?',
                'custom_answer' => 'Llevamos el inventario en cuadernos y perdemos ventas.',
                'locale' => 'es',
            ],
        );

        $open->registrations()->updateOrCreate(
            ['phone' => '3387654321'],
            [
                'name' => 'Luis Ramírez',
                'business_name' => 'Abarrotes La Esquina',
                'custom_field_label' => '¿Qué proceso quieres automatizar?',
                'custom_answer' => 'Los pedidos de proveedores se hacen por WhatsApp y se nos olvidan.',
                'locale' => 'es',
            ],
        );

        $finished = Event::updateOrCreate(
            ['slug' => 'reto-punto-de-venta-2025'],
            [
                'spanish_title' => 'Reto de punto de venta 2025',
                'english_title' => 'Point of sale challenge 2025',
                'spanish_description' => 'Buscamos el comercio con la peor fila de cobro.',
                'english_description' => 'We looked for the shop with the worst checkout line.',
                'spanish_challenge' => 'Documenta cómo cobras hoy y cuánto tardas por cliente.',
                'english_challenge' => 'Document how you charge today and how long each customer takes.',
                'spanish_prizes_summary' => 'Sistema punto de venta instalado y configurado, sin costo.',
                'english_prizes_summary' => 'A point of sale system installed and configured, free of charge.',
                'event_date' => now()->subMonths(2)->toDateString(),
                'registration_starts_at' => now()->subMonths(4),
                'registration_ends_at' => now()->subMonths(3),
                'custom_field_label_es' => '¿Cuánto tardas en cobrar a un cliente?',
                'custom_field_label_en' => 'How long does it take you to charge a customer?',
                'custom_field_required' => true,
                'is_published' => true,
                'sort_order' => 1,
            ],
        );

        $finished->winners()->updateOrCreate(
            ['name' => 'María González'],
            [
                'business_name' => 'Café Central',
                'spanish_prize' => 'Sistema punto de venta completo',
                'english_prize' => 'Full point of sale system',
                'spanish_note' => 'Documentó 6 minutos por cobro y filas de 12 personas.',
                'english_note' => 'Documented 6 minutes per checkout and 12-person lines.',
                'awarded_at' => now()->subMonths(2)->toDateString(),
                'is_published' => true,
                'sort_order' => 0,
            ],
        );

        $finished->winners()->updateOrCreate(
            ['name' => 'Jorge Ibarra'],
            [
                'business_name' => 'Farmacia San José',
                'spanish_prize' => 'Capacitación y auditoría de inventario',
                'english_prize' => 'Training and inventory audit',
                'awarded_at' => now()->subMonths(2)->toDateString(),
                'is_published' => true,
                'sort_order' => 1,
            ],
        );

        Event::updateOrCreate(
            ['slug' => 'reto-de-inventario-2026'],
            [
                'spanish_title' => 'Reto de inventario 2026',
                'english_title' => 'Inventory challenge 2026',
                'spanish_description' => 'Borrador: todavía no se publica.',
                'english_description' => 'Draft: not published yet.',
                'is_published' => false,
                'sort_order' => 2,
            ],
        );
    }
}
