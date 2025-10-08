<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only('search');
        
        $quotes = Quote::query()
            ->with(['client:id,name', 'project:id,name,quote_id']) // Carga la relación con cliente para eficiencia
            ->withSum('payments as total_paid', 'amount') // Calcula el total pagado por cotización
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                      ->orWhere('status', 'like', "%{$search}%")
                      ->orWhereHas('client', function ($clientQuery) use ($search) {
                          $clientQuery->where('name', 'like', "%{$search}%");
                      })
                      ->orWhere('client_name', 'like', "%{$search}%"); // Para clientes de origen 'Web'
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Quote/Index', [
            'quotes' => $quotes,
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        return Inertia::render('Quote/Create', [
            'clients' => Client::select('id', 'name', 'tax_id', 'address')->get()
        ]);
    }

    public function store(Request $request)
    {
        // --- Validación actualizada ---
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'valid_until' => 'required|date',
            'payment_type' => 'required|string|max:255',
            'work_days' => 'required|integer|min:1',
            'show_process' => 'required|boolean',
            'show_benefits' => 'required|boolean',
            'show_bank_info' => 'required|boolean',
        ]);

        // --- Creación de la cotización actualizada ---
        Quote::create([
            'client_id' => $validated['client_id'],
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'valid_until' => $validated['valid_until'],
            'payment_type' => $validated['payment_type'],
            'work_days' => $validated['work_days'],
            'show_process' => $validated['show_process'],
            'show_benefits' => $validated['show_benefits'],
            'show_bank_info' => $validated['show_bank_info'],
            'quote_code' => $this->generateQuoteCode(),
            'status' => 'Pendiente',
            'origin' => 'Interno'
        ]);

        return redirect()->route('quotes.index')->with('flash', [
            'message' => 'Cotización creada con éxito.',
            'type' => 'success'
        ]);
    }

    public function print(Quote $quote)
    {
        // Carga la relación con el cliente para tener todos sus datos disponibles en la vista
        $quote->load('client');

        return Inertia::render('Quote/Print', [
            'quote' => $quote,
        ]);
    }

    public function show(Quote $quote)
    {
        // Carga la relación con el cliente y los archivos multimedia para la vista de detalles
        $quote->load('client', 'media');

        return Inertia::render('Quote/Show', [
            'quote' => $quote,
        ]);
    }

    public function edit(Quote $quote)
    {
        // Carga la cotización que se va a editar y la lista de todos los clientes
        return Inertia::render('Quote/Edit', [
            'quote' => $quote,
            'clients' => Client::select('id', 'name', 'tax_id', 'address')->get()
        ]);
    }

    public function update(Request $request, Quote $quote)
    {
        // Validación de los datos de entrada
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'valid_until' => 'required|date',
            'payment_type' => 'required|string|max:255',
            'work_days' => 'required|integer|min:1',
            'percentage_discount' => 'nullable|numeric|min:0|max:100',
            'show_process' => 'required|boolean',
            'show_benefits' => 'required|boolean',
            'show_bank_info' => 'required|boolean',
        ]);
        
        // Actualiza la cotización con los datos validados
        $quote->update($validated);

        // Redirige al índice con un mensaje de éxito
        return redirect()->route('quotes.index')->with('flash', [
            'message' => 'Cotización actualizada con éxito.',
            'type' => 'success'
        ]);
    }

    public function destroy(Quote $quote)
    {
        // Opcional: Agregar lógica para no permitir eliminar cotizaciones aceptadas o pagadas
        if (in_array($quote->status, ['Aceptado', 'Pagado'])) {
             return back()->with('flash', [
                'message' => 'No se puede eliminar una cotización aceptada o pagada.', 
                'type' => 'error'
            ]);
        }
        
        $quote->delete();

        return back()->with('flash', [
            'message' => 'Cotización eliminada correctamente.',
            'type' => 'success'
        ]);
    }

    public function updateStatus(Request $request, Quote $quote)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['Enviado', 'Aceptado', 'Rechazado'])],
        ]);

        // Lógica de transición de estados
        $canUpdate = false;
        switch ($quote->status) {
            case 'Pendiente':
                if ($validated['status'] === 'Enviado') $canUpdate = true;
                break;
            case 'Enviado':
                if (in_array($validated['status'], ['Aceptado', 'Rechazado'])) $canUpdate = true;
                break;
        }

        if (!$canUpdate) {
            return back()->with('flash', [
                'message' => 'No se puede cambiar al estado seleccionado.', 
                'type' => 'error'
            ]);
        }
        
        $quote->status = $validated['status'];
        $quote->save();

        return back()->with('flash', [
            'message' => 'Estado de la cotización actualizado.',
            'type' => 'success'
        ]);
    }

    /**
     * Maneja la solicitud de cotización desde el formulario web público.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleWebRequest(Request $request)
    {
        // 1. Validación de los datos
        $validator = Validator::make($request->all(), [
            'client_name' => 'required|string|max:255',
            'client_email' => 'required|email|max:255',
            'client_phone' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
        ]);

        // if ($validator->fails()) {
        //     // Si la validación falla, regresamos con los errores y los datos enviados.
        //     // Laravel e Inertia se encargarán de mostrar los errores automáticamente.
        //     return back()->withErrors($validator)->withInput();
        // }

        // 2. Creación de la cotización
        try {
            Quote::create([
                'user_id' => null,
                'client_name' => $request->input('client_name'),
                'client_email' => $request->input('client_email'),
                'client_phone' => $request->input('client_phone'),
                'quote_code' => $this->generateQuoteCode(),
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'amount' => 0.00,
                'status' => 'Pendiente',
                'origin' => 'Web',
                'valid_until' => now()->addDays(30),
            ]);
        } catch (\Exception $e) {
            // Es una buena práctica registrar el error para depuración.
            Log::error('Error al crear la cotización: ' . $e->getMessage());

            // En caso de un error en la base de datos, enviamos un mensaje flash de error.
            return back()->with('flash', [
                'message' => 'Hubo un problema al enviar tu solicitud. Por favor, intenta de nuevo más tarde.',
                'type' => 'error'
            ]);
        }

        // 3. Redirección con mensaje de éxito
        // Si todo sale bien, enviamos el mensaje flash de éxito.
        // El layout de Vue escuchará este evento y lo mostrará en el notch.
        return back()->with('flash', [
            'message' => '¡Tu solicitud ha sido enviada con éxito!',
            'type' => 'success'
        ]);
    }

    /**
     * Genera un código único para la cotización.
     * Ejemplo: COT-2024-XXXX
     *
     * @return string
     */
    private function generateQuoteCode()
    {
        $year = date('Y');
        $lastQuote = Quote::whereYear('created_at', $year)->latest('id')->first();
        $nextNumber = $lastQuote ? (int)substr($lastQuote->quote_code, -4) + 1 : 1;
        
        return 'COT-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * NEW: Adjunta un archivo de factura a una cotización.
     */
    public function storeInvoice(Request $request, Quote $quote)
    {
        $request->validate([
            'invoice_file' => 'required|file|mimes:pdf,jpg,jpeg,png,xml|max:5120', // Max 5MB
        ]);

        try {
            $quote->addMediaFromRequest('invoice_file')
                  ->toMediaCollection('invoices');
        } catch (\Exception $e) {
            Log::error('Error al subir archivo de factura: ' . $e->getMessage());
            return back()->with('flash', [
                'message' => 'Hubo un problema al subir el archivo.',
                'type' => 'error'
            ]);
        }

        return back()->with('flash', [
            'message' => 'Factura adjuntada con éxito.',
            'type' => 'success'
        ]);
    }

    /**
     * NEW: Elimina un archivo de factura de la cotización.
     */
    public function destroyInvoice(Quote $quote, Media $media)
    {
        if ($media->model_id !== $quote->id || $media->model_type !== Quote::class) {
            return back()->with('flash', ['message' => 'Acción no autorizada.', 'type' => 'error']);
        }

        $media->delete();

        return back()->with('flash', ['message' => 'Archivo eliminado correctamente.', 'type' => 'success']);
    }
}
