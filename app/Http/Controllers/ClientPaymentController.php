<?php

namespace App\Http\Controllers;

use App\Models\ClientPayment;
use Illuminate\Http\Request;

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
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $payment = ClientPayment::create($validated);

        return response()->json([
            'message' => 'Pago registrado con éxito',
            'payment' => $payment
        ], 201);
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
