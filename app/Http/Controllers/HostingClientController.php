<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\HostingClient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class HostingClientController extends Controller
{
    public function index()
    {
        $hostingClients = HostingClient::with('client')->latest()->get();

        return Inertia::render('HostingClient/Index', [
            'hostingClients' => $hostingClients
        ]);
    }

    public function create()
    {
        $clients = Client::orderBy('name')->get(['id', 'name']);

        return Inertia::render('HostingClient/Create', [
            'clients' => $clients,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'hosting_type' => 'required|in:Interno,Externo',
            'service_provider' => 'required|string|max:255',
            'support_user' => 'nullable|string|max:255',
            'support_password' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'payment_amount' => 'nullable|numeric|min:0', // Nullable para Externos
            'billing_cycle' => 'nullable|in:Mensual,Anual',
            'hosted_urls' => 'nullable|array',
            'hosted_urls.*.url' => 'nullable|url|max:255',
            'status' => 'required|in:Activo,Suspendido,Cancelado',
            'notes' => 'nullable|string',
        ]);
        
        $startDate = Carbon::parse($validated['start_date']);
        
        // Solo calculamos próximo pago si es Interno
        if ($validated['hosting_type'] === 'Interno' && isset($validated['billing_cycle'])) {
            $validated['next_payment_date'] = $validated['billing_cycle'] === 'Anual' 
                ? $startDate->addYear() 
                : $startDate->addMonth();
        } else {
            $validated['next_payment_date'] = null;
            $validated['payment_amount'] = null;
            $validated['billing_cycle'] = null;
        }

        if (isset($validated['hosted_urls'])) {
            $validated['hosted_urls'] = array_column($validated['hosted_urls'], 'url');
        }

        HostingClient::create($validated);

        return redirect()->route('hosting-clients.index')->with('success', 'Servicio de hosting creado correctamente.');
    }

    public function show(HostingClient $hostingClient)
    {
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

    public function update(Request $request, HostingClient $hostingClient)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'hosting_type' => 'required|in:Interno,Externo',
            'service_provider' => 'required|string|max:255',
            'support_user' => 'nullable|string|max:255',
            'support_password' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'next_payment_date' => 'nullable|date',
            'payment_amount' => 'nullable|numeric|min:0',
            'billing_cycle' => 'nullable|in:Mensual,Anual',
            'hosted_urls' => 'nullable|array',
            'hosted_urls.*.url' => 'nullable|url|max:255',
            'status' => 'required|in:Activo,Suspendido,Cancelado',
            'notes' => 'nullable|string',
        ]);

        if ($validated['hosting_type'] === 'Externo') {
            $validated['next_payment_date'] = null;
            $validated['payment_amount'] = null;
            $validated['billing_cycle'] = null;
        }

        if (isset($validated['hosted_urls'])) {
            $validated['hosted_urls'] = array_column($validated['hosted_urls'], 'url');
        } else {
            $validated['hosted_urls'] = [];
        }

        $hostingClient->update($validated);

        return redirect()->route('hosting-clients.index')->with('success', 'Servicio de hosting actualizado.');
    }

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

    public function storePayment(Request $request, HostingClient $hostingClient)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // Max 5MB
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            // Guarda en storage/app/public/receipts
            $receiptPath = $request->file('receipt')->store('receipts', 'public');
        }

        $hostingClient->payments()->create([
            'amount' => $validated['amount'],
            'payment_date' => $validated['payment_date'],
            'notes' => $validated['notes'] ?? null,
            'receipt_path' => $receiptPath,
        ]);

        // Si es un hosting interno, actualizamos la fecha del próximo pago
        if ($hostingClient->hosting_type === 'Interno' && $hostingClient->next_payment_date) {
            $currentNextPaymentDate = Carbon::parse($hostingClient->next_payment_date);
            $newNextPaymentDate = $hostingClient->billing_cycle === 'Anual'
                ? $currentNextPaymentDate->addYear()
                : $currentNextPaymentDate->addMonth();

            $hostingClient->update(['next_payment_date' => $newNextPaymentDate]);
        }

        return back()->with('success', 'Pago y comprobante registrados correctamente.');
    }
}