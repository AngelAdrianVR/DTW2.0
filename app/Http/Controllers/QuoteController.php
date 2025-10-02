<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QuoteController extends Controller
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
        //
    }

    public function show(Quote $quote)
    {
        //
    }

    public function edit(Quote $quote)
    {
        //
    }

    public function update(Request $request, Quote $quote)
    {
        //
    }

    public function destroy(Quote $quote)
    {
        //
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
}
