<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LandingController extends Controller
{
     public function registro(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'required|email',
            'tipo' => 'required|in:profesional,cliente',
        ]);

        return back()->with('success', '¡Gracias por registrarte! Muy pronto te contactaremos.');
    }
}
