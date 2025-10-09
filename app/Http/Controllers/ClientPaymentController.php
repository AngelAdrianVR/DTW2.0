<?php

namespace App\Http\Controllers;

use App\Models\ClientPayment;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

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
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'quote_id' => 'nullable|exists:quotes,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        // Create the payment record
        ClientPayment::create($validated);

        // If a quote_id is provided, check if it's fully paid
        if (!empty($validated['quote_id'])) {
            $quote = Quote::find($validated['quote_id']);

            if ($quote) {
                // To check the balance, we need to sum all payments associated with this quote.
                // This assumes your ClientPayment model has a 'quote_id' column that can be null.
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
        //
    }

    public function destroy(ClientPayment $clientPayment)
    {
        //
    }
}
