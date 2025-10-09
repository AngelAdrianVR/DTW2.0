<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\HostingClient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HostingClientController extends Controller
{
    public function index()
    {
        // Obtenemos todos los registros, cargando la relación con el cliente
        // para evitar problemas de N+1.
        $hostingClients = HostingClient::with('client')->latest()->get();

        // Renderizamos la vista de Inertia, pasando los datos.
        return Inertia::render('HostingClient/Index', [
            'hostingClients' => $hostingClients
        ]);
    }

     public function create()
    {
        // Obtenemos los clientes para el dropdown.
        // Seleccionamos solo los campos necesarios para optimizar.
        $clients = Client::orderBy('name')->get(['id', 'name']);

        return Inertia::render('HostingClient/Create', [
            'clients' => $clients,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'service_provider' => 'required|string|max:255',
            'start_date' => 'required|date',
            'payment_amount' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:Mensual,Anual',
            'hosted_urls' => 'nullable|array',
            'hosted_urls.*.url' => 'nullable|url|max:255',
            'status' => 'required|in:Activo,Suspendido,Cancelado',
            'notes' => 'nullable|string',
        ]);
        
        // Calcula la próxima fecha de pago basado en la fecha de inicio.
        $startDate = Carbon::parse($validated['start_date']);
        $validated['next_payment_date'] = $validated['billing_cycle'] === 'Anual' 
            ? $startDate->addYear() 
            : $startDate->addMonth();

        if (isset($validated['hosted_urls'])) {
            $validated['hosted_urls'] = array_column($validated['hosted_urls'], 'url');
        }

        HostingClient::create($validated);

        return redirect()->route('hosting-clients.index')->with('success', 'Servicio de hosting creado correctamente.');
    }

    public function show(HostingClient $hostingClient)
    {
        // Cargar las relaciones necesarias:
        // - 'client.contacts': Carga el cliente y sus contactos asociados.
        // - 'payments': Carga el historial de pagos ordenado por fecha descendente.
        $hostingClient->load(['client.contacts', 'payments' => function ($query) {
            $query->orderBy('payment_date', 'desc');
        }]);

        return Inertia::render('HostingClient/Show', [
            'hostingClient' => $hostingClient,
        ]);
    }

    public function edit(HostingClient $hostingClient)
    {
        return Inertia::render('HostingClient/Edit', [
            'hostingClient' => $hostingClient,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, HostingClient $hostingClient)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'service_provider' => 'required|string|max:255',
            'start_date' => 'required|date',
            'next_payment_date' => 'required|date|after_or_equal:start_date',
            'payment_amount' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:Mensual,Anual',
            'hosted_urls' => 'nullable|array',
            'hosted_urls.*.url' => 'nullable|url|max:255',
            'status' => 'required|in:Activo,Suspendido,Cancelado',
            'notes' => 'nullable|string',
        ]);

        if (isset($validated['hosted_urls'])) {
            $validated['hosted_urls'] = array_column($validated['hosted_urls'], 'url');
        } else {
            $validated['hosted_urls'] = [];
        }

        $hostingClient->update($validated);

        return redirect()->route('hosting-clients.index')->with('success', 'Servicio de hosting actualizado.');
    }

    /**
     * Update the status of the specified resource.
     */
    public function updateStatus(Request $request, HostingClient $hostingClient)
    {
        $validated = $request->validate([
            'status' => 'required|in:Activo,Suspendido,Cancelado',
        ]);

        $hostingClient->update(['status' => $validated['status']]);

        return back()->with('success', 'Estado del servicio actualizado.');
    }

    public function destroy(HostingClient $hostingClient)
    {
        $hostingClient->delete();

        return redirect()->route('hosting-clients.index')->with('success', 'Servicio de hosting eliminado.');
    }

    /**
     * Store a new payment for the hosting service and update the next payment date.
     */
    public function storePayment(Request $request, HostingClient $hostingClient)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        // Registrar el pago
        $hostingClient->payments()->create($validated);

        // Calcular y actualizar la próxima fecha de pago
        $currentNextPaymentDate = Carbon::parse($hostingClient->next_payment_date);
        $newNextPaymentDate = $hostingClient->billing_cycle === 'Anual'
            ? $currentNextPaymentDate->addYear()
            : $currentNextPaymentDate->addMonth();

        $hostingClient->update(['next_payment_date' => $newNextPaymentDate]);

        return back()->with('success', 'Pago registrado y fecha actualizada.');
    }
}
