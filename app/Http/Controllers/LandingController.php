<?php

namespace App\Http\Controllers;

use App\Models\RegistroLanding;
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

    RegistroLanding::create($request->only('nombre', 'email', 'tipo'));

    return redirect('/')->with('success', '¡Gracias por registrarte! Muy pronto te contactaremos.');
    }
}
