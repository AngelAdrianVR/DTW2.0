<?php

namespace App\Http\Controllers;

use App\Models\ClientPayment;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class ClientPaymentController extends Controller
{
    public function index()
    {
        //
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        // 1. Validamos los datos, incluyendo el archivo 'receipt'
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'quote_id' => 'nullable|exists:quotes,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // Máximo 5MB
        ]);

        // 2. Si el request trae un archivo, lo guardamos en la carpeta public/receipts
        if ($request->hasFile('receipt')) {
            $validated['receipt'] = $request->file('receipt')->store('receipts', 'public');
        }

        // 3. Creamos el registro del pago con la ruta del archivo ya incluida
        ClientPayment::create($validated);

        // 4. Lógica para actualizar el estado de la cotización
        if (!empty($validated['quote_id'])) {
            $quote = Quote::find($validated['quote_id']);

            if ($quote) {
                // Sumar todos los pagos asociados a esta cotización
                $totalPaidForQuote = ClientPayment::where('quote_id', $quote->id)->sum('amount');

                if ($totalPaidForQuote >= $quote->amount) {
                    $quote->status = 'Pagado';
                    $quote->save();
                }
            }
        }

        return Redirect::back()->with('success', 'Pago registrado con éxito.');
    }

    public function show(ClientPayment $clientPayment)
    {
        //
    }

    public function edit(ClientPayment $clientPayment)
    {
        //
    }

    public function update(Request $request, ClientPayment $clientPayment)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($request->hasFile('receipt')) {
            // Eliminar archivo anterior si existe
            if ($clientPayment->receipt) {
                Storage::disk('public')->delete($clientPayment->receipt);
            }
            $validated['receipt'] = $request->file('receipt')->store('receipts', 'public');
        }

        $clientPayment->update($validated);

        // Actualizar el estado de la cotización si es necesario
        if ($clientPayment->quote_id) {
            $quote = Quote::find($clientPayment->quote_id);
            if ($quote) {
                $totalPaidForQuote = ClientPayment::where('quote_id', $quote->id)->sum('amount');
                
                if ($totalPaidForQuote >= $quote->amount && $quote->status !== 'Pagado') {
                    $quote->status = 'Pagado';
                    $quote->save();
                } elseif ($totalPaidForQuote < $quote->amount && $quote->status === 'Pagado') {
                    $quote->status = 'Aceptado'; // O el estado previo correspondiente
                    $quote->save();
                }
            }
        }

        return Redirect::back()->with('success', 'Pago actualizado con éxito.');
    }

    public function destroy(ClientPayment $clientPayment)
    {
        $quoteId = $clientPayment->quote_id;

        if ($clientPayment->receipt) {
            Storage::disk('public')->delete($clientPayment->receipt);
        }

        $clientPayment->delete();

        // Actualizar el estado de la cotización si es necesario
        if ($quoteId) {
            $quote = Quote::find($quoteId);
            if ($quote && $quote->status === 'Pagado') {
                $totalPaidForQuote = ClientPayment::where('quote_id', $quote->id)->sum('amount');
                if ($totalPaidForQuote < $quote->amount) {
                    $quote->status = 'Aceptado'; // O el estado previo correspondiente
                    $quote->save();
                }
            }
        }

        return Redirect::back()->with('success', 'Pago eliminado con éxito.');
    }
}