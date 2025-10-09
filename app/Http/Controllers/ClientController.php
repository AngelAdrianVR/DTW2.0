<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        /**
         * MODIFICACIÓN: Se ha cambiado la forma de calcular el 'total_billed'.
         * Ahora se usa una subconsulta para sumar los montos de las cotizaciones
         * APLICANDO el descuento correspondiente a cada una, asegurando que el total sea correcto.
         */
        $totalBilledSubQuery = Quote::from('quotes as q_sub')
            ->selectRaw('SUM(q_sub.amount * (1 - COALESCE(q_sub.percentage_discount, 0) / 100))')
            ->whereColumn('q_sub.client_id', 'clients.id')
            ->whereIn('q_sub.status', ['Aceptado', 'Pagado']);

        $clients = Client::query()
            ->select('clients.*') // Importante para no perder las columnas del cliente
            ->selectSub($totalBilledSubQuery, 'total_billed') // Se agrega el total facturado ya calculado
            ->withSum('payments as total_paid', 'amount') // El total pagado no cambia
            // Carga las cotizaciones para el modal de pago. El accesor 'final_amount' estará disponible.
            ->with(['quotes' => function ($query) {
                $query->select('id', 'client_id', 'quote_code', 'percentage_discount', 'title', 'amount', 'status', 'origin')
                      ->whereIn('status', ['Aceptado', 'Pagado'])
                      ->withSum('payments as total_paid', 'amount');
            }])
            ->get();

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
        $client->load([
            'contacts',
            'payments' => function ($query) {
                $query->orderBy('payment_date', 'desc');
            },
            'quotes' => function ($query) {
                $query->orderBy('created_at', 'desc')
                      ->withSum('payments as total_paid', 'amount');
            }
        ]);

        /**
         * MODIFICACIÓN: Se calcula el total facturado usando una expresión RAW de SQL
         * para aplicar el descuento directamente en la consulta a la base de datos.
         */
        $total_billed = $client->quotes()
            ->whereIn('status', ['Aceptado', 'Pagado'])
            ->sum(DB::raw('amount * (1 - COALESCE(percentage_discount, 0) / 100)'));
        
        $total_paid = $client->payments()->sum('amount');

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
