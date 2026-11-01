<?php

namespace Tests\Feature;

use App\Mail\LeadNotification;
use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LeadRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Payload válido base (sin calculadora).
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'client_name' => 'Ana Torres',
            'company' => 'Ferretería del Norte',
            'client_email' => 'ana@ferreteria.mx',
            'client_phone' => '3312345678',
            'industry' => 'Retail y comercio',
            'employees' => '6 a 20 personas',
            'title' => 'Diagnóstico gratuito · Retail y comercio',
            'description' => 'No damos seguimiento a las cotizaciones y perdemos ventas.',
            'locale' => 'es',
        ], $overrides);
    }

    public function test_a_lead_with_calculator_data_is_stored_and_notified(): void
    {
        Mail::fake();

        $response = $this->from('/')->post('/quote-request', $this->payload([
            'calculator' => [
                'scenario' => 'sales',
                'scenario_label' => 'Ventas perdidas por falta de seguimiento',
                'monthly_cost' => 40000,
                'recoverable' => 12000,
                'annual_saving' => 144000,
                'improvement' => 30,
                'inputs' => ['quotes' => 40, 'noFollowUpPercent' => 40],
            ],
        ]));

        $response->assertRedirect('/');
        $response->assertSessionHasNoErrors();

        $quote = Quote::firstOrFail();

        $this->assertSame('Ferretería del Norte', $quote->company);
        $this->assertSame('Web', $quote->origin);
        $this->assertSame('Pendiente', $quote->status);
        $this->assertSame('es', $quote->locale);
        $this->assertSame('sales', $quote->calculator_payload['scenario']);
        $this->assertEqualsWithDelta(12000, $quote->calculator_payload['recoverable'], 0.01);
        $this->assertSame(40, $quote->calculator_payload['inputs']['quotes']);

        // El resumen legible se anexa a la descripción que ve el equipo.
        $this->assertStringContainsString('No damos seguimiento', $quote->description);
        $this->assertStringContainsString('Resumen de la calculadora financiera', $quote->description);
        $this->assertStringContainsString('12,000.00 MXN', $quote->description);

        Mail::assertSent(LeadNotification::class, function (LeadNotification $mail) use ($quote) {
            return $mail->hasTo(config('dtw.lead_email')) && $mail->quote->is($quote);
        });
    }

    public function test_a_lead_can_be_sent_without_calculator_data(): void
    {
        Mail::fake();

        $this->from('/')->post('/quote-request', $this->payload())->assertSessionHasNoErrors();

        $quote = Quote::firstOrFail();

        $this->assertNull($quote->calculator_payload);
        $this->assertSame('Ferretería del Norte', $quote->company);
        Mail::assertSent(LeadNotification::class);
    }

    public function test_calculator_percentages_are_clamped_before_saving(): void
    {
        Mail::fake();

        $this->from('/')->post('/quote-request', $this->payload([
            'calculator' => [
                'scenario' => 'operations',
                'scenario_label' => 'Operación manual y tareas repetitivas',
                'monthly_cost' => 1000,
                'recoverable' => 900,
                'annual_saving' => 10800,
                // Porcentaje fuera de rango: se acota a 100 en el backend.
                'improvement' => 150,
                'inputs' => ['employees' => 4],
            ],
        ]))->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(100, Quote::firstOrFail()->calculator_payload['improvement'], 0.01);
    }

    public function test_leads_with_invalid_data_are_rejected(): void
    {
        Mail::fake();

        $response = $this->from('/')->post('/quote-request', [
            'client_name' => '',
            'company' => '',
            'client_email' => 'esto-no-es-un-correo',
            'client_phone' => '',
            'industry' => '',
            'description' => '',
        ]);

        $response->assertSessionHasErrors([
            'client_name',
            'company',
            'client_email',
            'client_phone',
            'industry',
            'description',
        ]);

        $this->assertDatabaseCount('quotes', 0);
        Mail::assertNothingSent();
    }

    public function test_a_failing_mail_does_not_lose_the_lead(): void
    {
        // Se fuerza un fallo del correo: el prospecto debe guardarse igual.
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP caído'));

        $this->from('/')->post('/quote-request', $this->payload())->assertSessionHasNoErrors();

        $this->assertDatabaseCount('quotes', 1);
    }

    private function latestQuote(): Quote
    {
        return Quote::latest('id')->firstOrFail();
    }
}
