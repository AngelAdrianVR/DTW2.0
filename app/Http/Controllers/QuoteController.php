<?php
namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Quote;
use App\Models\Project;
use App\Models\ClientPayment;
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
        $filters = $request->only(['search', 'sortField', 'sortOrder']);

        $sortField = $request->input('sortField', 'id');
        $sortOrder = $request->input('sortOrder', -1);
        $direction = ($sortOrder == 1 || $sortOrder === 'asc') ? 'asc' : 'desc';

        $search = $request->input('search');
        
        $quotes = Quote::query()
            ->select('quotes.*')
            ->with(['client:id,name,tax_id', 'project:id,name,quote_id'])
            ->withSum('payments as total_paid', 'amount')
            ->when($search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('quotes.id', 'like', "%{$search}%")
                    ->orWhere('quotes.status', 'like', "%{$search}%")
                    ->orWhere('quotes.title', 'like', "%{$search}%")
                    ->orWhere('quotes.amount', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhere('quotes.client_name', 'like', "%{$search}%")
                    ->orWhereHas('project', function ($projectQuery) use ($search) {
                        $projectQuery->where('name', 'like', "%{$search}%");
                    });
                });
            });

        $finalAmountSql = '(CASE 
            WHEN quotes.percentage_discount > 0 THEN quotes.amount - (quotes.amount * (quotes.percentage_discount / 100)) 
            WHEN quotes.needs_invoice = 1 THEN quotes.amount * 1.16 
            ELSE quotes.amount 
        END)';

        if ($sortField === 'client') {
            $clientTable = (new Client)->getTable();
            $quotes->orderByRaw("COALESCE((SELECT name FROM {$clientTable} WHERE {$clientTable}.id = quotes.client_id), quotes.client_name) $direction");
        } elseif ($sortField === 'final_amount' || $sortField === 'amount') {
            $quotes->orderByRaw("$finalAmountSql $direction");
        } elseif ($sortField === 'total_paid') {
            $quotes->orderBy('total_paid', $direction);
        } elseif ($sortField === 'balance') {
            $paymentTable = (new ClientPayment)->getTable();
            $quotes->orderByRaw("($finalAmountSql - COALESCE((SELECT SUM(amount) FROM {$paymentTable} WHERE {$paymentTable}.quote_id = quotes.id), 0)) $direction");
        } elseif ($sortField === 'project.name') {
            $projectTable = (new Project)->getTable();
            $quotes->orderByRaw("(SELECT name FROM {$projectTable} WHERE {$projectTable}.quote_id = quotes.id) $direction");
        } else {
            $validColumns = ['id', 'title', 'status', 'created_at', 'updated_at'];
            if (in_array($sortField, $validColumns)) {
                $quotes->orderBy('quotes.'.$sortField, $direction);
            } else {
                $quotes->orderBy('quotes.id', 'desc');
            }
        }

        $quotes = $quotes->paginate(15)->withQueryString();

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
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'amount_usd' => 'nullable|numeric|min:0',
            'valid_until' => 'required|date',
            'payment_type' => 'required|string|max:255',
            'work_days' => 'required|integer|min:1',
            'budgeted_hours' => 'nullable|integer|min:0',
            'percentage_discount' => 'nullable|numeric|min:0|max:100',
            'show_process' => 'required|boolean',
            'show_benefits' => 'required|boolean',
            'show_bank_info' => 'required|boolean',
            'needs_invoice' => 'required|boolean',
        ]);

        $quote = Quote::create([
            'client_id' => $validated['client_id'],
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'amount_usd' => $validated['amount_usd'] ?? null,
            'valid_until' => $validated['valid_until'],
            'payment_type' => $validated['payment_type'],
            'work_days' => $validated['work_days'],
            'budgeted_hours' => $validated['budgeted_hours'] ?? null,
            'percentage_discount' => $validated['percentage_discount'] ?? 0,
            'show_process' => $validated['show_process'],
            'show_benefits' => $validated['show_benefits'],
            'show_bank_info' => $validated['show_bank_info'],
            'needs_invoice' => $validated['needs_invoice'],
            'quote_code' => $this->generateQuoteCode(),
            'status' => 'Pendiente',
            'origin' => 'Interno',
        ]);

        return redirect()->route('quotes.show', $quote->id)->with('flash', [
            'message' => 'Cotización creada con éxito.',
            'type' => 'success'
        ]);
    }

    public function print(Quote $quote)
    {
        $quote->load('client');

        return Inertia::render('Quote/Print', [
            'quote' => $quote,
        ]);
    }

    public function show(Quote $quote)
    {
        $quote->load('client', 'media', 'payments.media');

        return Inertia::render('Quote/Show', [
            'quote' => $quote,
        ]);
    }

    public function edit(Quote $quote)
    {
        return Inertia::render('Quote/Edit', [
            'quote' => $quote,
            'clients' => Client::select('id', 'name', 'tax_id', 'address')->get()
        ]);
    }

    public function update(Request $request, Quote $quote)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'amount_usd' => 'nullable|numeric|min:0',
            'valid_until' => 'required|date',
            'payment_type' => 'required|string|max:255',
            'work_days' => 'required|integer|min:1',
            'budgeted_hours' => 'nullable|integer|min:0',
            'percentage_discount' => 'nullable|numeric|min:0|max:100',
            'show_process' => 'required|boolean',
            'show_benefits' => 'required|boolean',
            'show_bank_info' => 'required|boolean',
            'needs_invoice' => 'required|boolean',
        ]);
        
        $quote->update($validated);

        return redirect()->route('quotes.show', $quote->id)->with('flash', [
            'message' => 'Cotización actualizada con éxito.',
            'type' => 'success'
        ]);
    }

    public function destroy(Quote $quote)
    {
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
            'status' => ['required', Rule::in(['Enviado', 'Aceptado', 'Rechazado', 'Pagado'])],
            'date'   => ['nullable', 'date'],
        ]);

        $canUpdate = false;

        // Si es un pase directo a "Pagado" desde cualquier estado activo (Pendiente, Enviado, Aceptado)
        if ($validated['status'] === 'Pagado' && in_array($quote->status, ['Pendiente', 'Enviado', 'Aceptado'])) {
            $canUpdate = true;
            // Si nos envían la fecha desde Vue (del último pago), la usamos.
            if (!empty($validated['date'])) {
                $quote->paid_at = $validated['date'];
            } else {
                // Si no, buscamos el último pago registrado en base de datos.
                $latestPayment = $quote->payments()->latest('payment_date')->first();
                $quote->paid_at = $latestPayment ? $latestPayment->payment_date : now();
            }
            
            // Si pasó a pagado directo sin ser aceptado (e.g. Enviado -> Pagado), automáticamente aceptamos también.
            if (!$quote->accepted_at) {
                $quote->accepted_at = $quote->paid_at;
            }
        } 
        // Lógica de transiciones regulares
        else {
            switch ($quote->status) {
                case 'Pendiente':
                    if ($validated['status'] === 'Enviado') {
                        $canUpdate = true;
                        if (!$quote->sent_at) $quote->sent_at = now();
                    }
                    break;
                case 'Enviado':
                    if (in_array($validated['status'], ['Aceptado', 'Rechazado'])) {
                        $canUpdate = true;
                        if ($validated['status'] === 'Aceptado' && !$quote->accepted_at) {
                            $quote->accepted_at = now();
                        } elseif ($validated['status'] === 'Rechazado' && !$quote->rejected_at) {
                            $quote->rejected_at = now();
                        }
                    }
                    break;
                case 'Rechazado':
                    if (in_array($validated['status'], ['Aceptado', 'Enviado'])) {
                        $canUpdate = true;
                        if ($validated['status'] === 'Aceptado' && !$quote->accepted_at) {
                            $quote->accepted_at = now();
                        }
                    }
                    break;
                case 'Aceptado':
                    if (in_array($validated['status'], ['Enviado', 'Rechazado'])) {
                        $canUpdate = true;
                        if ($validated['status'] === 'Rechazado' && !$quote->rejected_at) {
                            $quote->rejected_at = now();
                        }
                    }
                    break;
                case 'Pagado':
                    if (in_array($validated['status'], ['Aceptado'])) {
                        $canUpdate = true; // Permite regresar a aceptado si se revierte un pago
                    }
                    break;
            }
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

    public function updateDates(Request $request, Quote $quote)
    {
        $validated = $request->validate([
            'field' => ['required', Rule::in(['created_at', 'sent_at', 'accepted_at', 'rejected_at', 'paid_at'])],
            'date' => 'required|date',
        ]);

        $field = $validated['field'];
        $quote->$field = $validated['date'];
        
        $quote->timestamps = false;
        $quote->save();

        return back()->with('flash', [
            'message' => 'Fecha actualizada correctamente.',
            'type' => 'success'
        ]);
    }

    public function handleWebRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_name' => 'required|string|max:255',
            'client_email' => 'required|email|max:255',
            'client_phone' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

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
            Log::error('Error al crear la cotización: ' . $e->getMessage());
            return back()->with('flash', [
                'message' => 'Hubo un problema al enviar tu solicitud. Por favor, intenta de nuevo más tarde.',
                'type' => 'error'
            ]);
        }

        return back()->with('flash', [
            'message' => '¡Tu solicitud ha sido enviada con éxito!',
            'type' => 'success'
        ]);
    }

    private function generateQuoteCode()
    {
        $year = date('Y');
        $lastQuote = Quote::whereYear('created_at', $year)->latest('id')->first();
        $nextNumber = $lastQuote ? (int)substr($lastQuote->quote_code, -4) + 1 : 1;
        
        return 'COT-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function storeInvoice(Request $request, Quote $quote)
    {
        $request->validate([
            'invoice_file' => 'required|file|mimes:pdf,jpg,jpeg,png,xml|max:5120',
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

    public function destroyInvoice(Quote $quote, Media $media)
    {
        if ($media->model_id !== $quote->id || $media->model_type !== Quote::class) {
            return back()->with('flash', ['message' => 'Acción no autorizada.', 'type' => 'error']);
        }
        $media->delete();
        return back()->with('flash', ['message' => 'Archivo eliminado correctamente.', 'type' => 'success']);
    }
}