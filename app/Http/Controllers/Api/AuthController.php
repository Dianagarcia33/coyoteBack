<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Asignar rol de cliente por defecto
        $user->assignRole('cliente');

        // Cargar roles para incluirlos en la respuesta
        $user->load('roles');

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Usuario registrado exitosamente',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // Verificar si el usuario está activo
        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Tu cuenta ha sido desactivada. Contacta al administrador.'],
            ]);
        }

        // Revoke all previous tokens
        $user->tokens()->delete();

        // Cargar roles
        $user->load('roles');

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('roles'),
        ]);
    }

    /**
     * Get all users (admin only)
     */
    public function getAllUsers()
    {
        $users = User::with('roles')->get();

        return response()->json([
            'users' => $users,
        ]);
    }

    /**
     * Get all users except admins (public or authenticated)
     */
    public function getPublicUsers(Request $request)
    {
        $query = User::with(['roles'])
            ->whereHas('roles', function ($q) {
                $q->where('name', '!=', 'admin');
            });

        // Filtrar por rol si se especifica
        if ($request->has('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Cargar perfiles según el rol
        $users = $query->get()->map(function ($user) {
            if ($user->hasRole('gimnasio')) {
                $user->load('gymProfile');
            } elseif ($user->hasRole('entrenador')) {
                $user->load('trainerProfile', 'receivedReviews');
                $user->average_rating = $user->receivedReviews->avg('rating');
            } elseif ($user->hasRole('nutricionista')) {
                $user->load('nutritionistProfile', 'receivedReviews');
                $user->average_rating = $user->receivedReviews->avg('rating');
            } elseif ($user->hasRole('cliente')) {
                $user->load('clientProfile');
            }

            // Ocultar datos sensibles
            $user->makeHidden(['email']);

            return $user;
        });

        return response()->json([
            'users' => $users,
        ]);
    }

    /**
     * Toggle user active status (admin only)
     */
    public function toggleUserStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'message' => $user->is_active ? 'Usuario activado exitosamente' : 'Usuario desactivado exitosamente',
            'user' => $user->load('roles'),
        ]);
    }
}
