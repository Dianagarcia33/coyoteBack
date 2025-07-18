<?php

namespace App\Http\Controllers;

use App\Models\User;
use Dotenv\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
     public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Credenciales inválidas'], 401);
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => auth('api')->user(),
        ]);
    }

    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Sesión cerrada']);
    }

    public function me()
    {
        return response()->json(auth('api')->user());
    }

     public function register(Request $request)
    {
        // Validaciones base
        $rules = [
            'role' => 'required|in:cliente,entrenador,nutricionista,gimnasio',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
        ];

        // Validaciones condicionales por tipo
        switch ($request->role) {
            case 'cliente':
                $rules['objetivo'] = 'required|string|max:255';
                break;

            case 'entrenador':
            case 'nutricionista':
                $rules['especialidades'] = 'required|array';
                $rules['tarifa'] = 'required|numeric';
                $rules['moneda'] = 'required|string';
                $rules['periodo_facturacion'] = 'required|string';
                // $rules['documento'] = 'nullable|file'; // si implementas upload
                break;

            case 'gimnasio':
                $rules['telefono'] = 'required|string';
                $rules['direccion'] = 'required|string';
                $rules['descripcion'] = 'required|string';
                $rules['horario'] = 'required|string';
                $rules['instalaciones'] = 'required|string';
                $rules['ubicacion.latitude'] = 'required|numeric';
                $rules['ubicacion.longitude'] = 'required|numeric';
                break;
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Creación del usuario
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,

            // cliente
            'objetivo' => $request->objetivo ?? null,

            // entrenador/nutricionista
            'especialidades' => $request->has('especialidades') ? json_encode($request->especialidades) : null,
            'tarifa' => $request->tarifa ?? null,
            'moneda' => $request->moneda ?? null,
            'periodo_facturacion' => $request->periodo_facturacion ?? null,

            // gimnasio
            'telefono' => $request->telefono ?? null,
            'direccion' => $request->direccion ?? null,
            'descripcion' => $request->descripcion ?? null,
            'horario' => $request->horario ?? null,
            'instalaciones' => $request->instalaciones ?? null,
            'lat' => $request->ubicacion['latitude'] ?? null,
            'lng' => $request->ubicacion['longitude'] ?? null,
        ]);

        // Autenticación con JWT
        $token = auth()->login($user);

        return response()->json([
            'message' => 'Usuario registrado correctamente',
            'user' => $user,
            'token' => $token
        ], 201);
    }
}
