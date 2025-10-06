<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        // Carga los clientes y calcula la suma de pagos y cotizaciones
        // de forma eficiente para evitar problemas de N+1 queries.
        $clients = Client::query()
            ->withSum('payments as total_paid', 'amount')
            ->withSum(['quotes' => function ($query) {
                // Suma solo las cotizaciones con estado 'Aceptada' o 'Pagado'.
                $query->whereIn('status', ['Aceptado', 'Pagado']);
            }], 'amount')
            // Carga las cotizaciones relevantes (con saldo pendiente) para el modal de pago.
            ->with(['quotes' => function ($query) {
                // Para seleccionar columnas específicas, usamos ->select() dentro del closure.
                // ¡Es crucial incluir siempre la clave foránea (client_id) en el select!
                $query->select('id', 'client_id', 'quote_code', 'percentage_discount', 'title', 'amount', 'status', 'origin')
                      ->whereIn('status', ['Aceptado', 'Pagado'])
                      ->withSum('payments as total_paid', 'amount');
            }])
            ->get();

        // Renombra la propiedad de la suma para que coincida con lo que espera el frontend ('total_billed')
        $clients->each(function ($client) {
            $client->total_billed = $client->quotes_sum_amount ?? 0;
            unset($client->quotes_sum_amount);
        });

        // Retorna la vista de Inertia pasándole los clientes como 'props'.
        return Inertia::render('Client/Index', [
            'clients' => $clients,
        ]);
    }

    public function create()
    {
        return Inertia::render('Client/Create');
    }

     public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tax_id' => 'nullable|string|max:255|unique:clients,tax_id',
            'address' => 'nullable|string',
            'status' => 'required|in:Cliente,Prospecto',
            'source' => 'nullable|string|max:255',
            'contacts' => 'present|array', // Valida que 'contacts' esté presente y sea un array
            'contacts.*.name' => 'required|string|max:255',
            'contacts.*.email' => 'nullable|email|max:255',
            'contacts.*.phone' => 'nullable|string|max:255',
            'contacts.*.position' => 'nullable|string|max:255',
        ]);

        // Usamos una transacción para asegurar la integridad de los datos.
        // Si falla la creación de un contacto, se revierte la creación del cliente.
        DB::transaction(function () use ($validated) {
            $client = Client::create([
                'name' => $validated['name'],
                'tax_id' => $validated['tax_id'],
                'address' => $validated['address'],
                'status' => $validated['status'],
                'source' => $validated['source'],
            ]);

            if (!empty($validated['contacts'])) {
                // El método createMany es perfecto para esto.
                $client->contacts()->createMany($validated['contacts']);
            }
        });

        return Redirect::route('clients.index')->with('success', 'Cliente creado con éxito.');
    }

    public function show(Client $client)
    {
        // Carga las relaciones del cliente de forma eficiente (eager loading)
        // Ordenamos los pagos y cotizaciones por fecha descendente para ver lo más reciente primero.
        $client->load([
            'contacts',
            'payments' => function ($query) {
                $query->orderBy('payment_date', 'desc');
            },
            'quotes' => function ($query) {
                $query->orderBy('created_at', 'desc')
                      // Agregamos la suma de pagos por cotización para calcular saldos.
                      ->withSum('payments as total_paid', 'amount');
            }
        ]);

        // Calculamos el total facturado solo de cotizaciones con estado 'Aceptado' o 'Pagado'.
        $total_billed = $client->quotes()
            ->whereIn('status', ['Aceptado', 'Pagado'])
            ->sum('amount');
        
        // El total pagado es la suma de todos los pagos del cliente.
        $total_paid = $client->payments()->sum('amount');

        // Retornamos la vista de Inertia con los datos del cliente y sus totales
        return Inertia::render('Client/Show', [
            'client' => $client,
            'total_billed' => $total_billed,
            'total_paid' => $total_paid,
        ]);
    }

    public function edit(Client $client)
    {
        // Cargamos la relación de contactos para que estén disponibles en el prop.
        $client->load('contacts');

        // Renderizamos la vista de edición y pasamos el cliente como prop.
        return Inertia::render('Client/Edit', [
            'client' => $client,
        ]);
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // Al validar el tax_id único, debemos ignorar el del cliente actual.
            'tax_id' => 'nullable|string|max:255|unique:clients,tax_id,' . $client->id,
            'address' => 'nullable|string',
            'status' => 'required|in:Cliente,Prospecto',
            'source' => 'nullable|string|max:255',
            'contacts' => 'present|array',
            'contacts.*.name' => 'required|string|max:255',
            'contacts.*.email' => 'nullable|email|max:255',
            'contacts.*.phone' => 'nullable|string|max:255',
            'contacts.*.position' => 'nullable|string|max:255',
        ]);

        // Usamos una transacción para garantizar la integridad de los datos.
        DB::transaction(function () use ($validated, $client) {
            // 1. Actualizamos los datos principales del cliente.
            $client->update([
                'name' => $validated['name'],
                'tax_id' => $validated['tax_id'],
                'address' => $validated['address'],
                'status' => $validated['status'],
                'source' => $validated['source'],
            ]);

            // 2. Sincronizamos los contactos.
            // Un método simple y robusto es eliminar los anteriores y crear los nuevos.
            $client->contacts()->delete();

            if (!empty($validated['contacts'])) {
                // Filtramos para asegurar que no se envíen IDs u otros campos extra.
                $contactsData = array_map(function($contact) {
                    return [
                        'name' => $contact['name'],
                        'email' => $contact['email'],
                        'phone' => $contact['phone'],
                        'position' => $contact['position'],
                    ];
                }, $validated['contacts']);
                
                $client->contacts()->createMany($contactsData);
            }
        });

        return Redirect::route('clients.index')->with('success', 'Cliente actualizado con éxito.');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        // Usamos Redirect::route() para asegurar que la redirección sea correcta
        return Redirect::route('clients.index')->with('success', 'Cliente eliminado correctamente.');
    }
}
