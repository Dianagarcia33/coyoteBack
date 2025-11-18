<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GymProfile;
use App\Models\TrainerProfile;
use App\Models\NutritionistProfile;
use App\Models\ClientProfile;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Obtener perfil del usuario autenticado
     */
    public function show()
    {
        $user = auth()->user()->load('roles');
        
        // Cargar el perfil según el rol
        if ($user->hasRole('gimnasio')) {
            $user->load('gymProfile');
        } elseif ($user->hasRole('entrenador')) {
            $user->load('trainerProfile', 'receivedReviews');
        } elseif ($user->hasRole('nutricionista')) {
            $user->load('nutritionistProfile', 'receivedReviews');
        } elseif ($user->hasRole('cliente')) {
            $user->load('clientProfile', 'goals', 'measurements');
        }

        return response()->json($user);
    }

    /**
     * Actualizar perfil de gimnasio
     */
    public function updateGymProfile(Request $request)
    {
        $request->validate([
            'business_name' => 'required|string|max:255',
            'gym_type' => 'nullable|string',
            'specialties' => 'nullable|array',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'opening_hours' => 'nullable|string',
            'machines' => 'nullable|array',
            'classes' => 'nullable|array',
            'description' => 'nullable|string',
            'photos' => 'nullable|array',
            'social_media' => 'nullable|array',
        ]);

        $profile = auth()->user()->gymProfile()->updateOrCreate(
            ['user_id' => auth()->id()],
            $request->all()
        );

        return response()->json([
            'message' => 'Perfil actualizado exitosamente',
            'profile' => $profile,
        ]);
    }

    /**
     * Actualizar perfil de entrenador
     */
    public function updateTrainerProfile(Request $request)
    {
        $request->validate([
            'specialization' => 'nullable|string',
            'hourly_rate' => 'nullable|numeric|min:0',
            'bio' => 'nullable|string',
            'certifications' => 'nullable|array',
            'years_experience' => 'nullable|integer|min:0',
            'availability' => 'nullable|array',
            'photo' => 'nullable|string',
        ]);

        $profile = auth()->user()->trainerProfile()->updateOrCreate(
            ['user_id' => auth()->id()],
            $request->all()
        );

        return response()->json([
            'message' => 'Perfil actualizado exitosamente',
            'profile' => $profile,
        ]);
    }

    /**
     * Actualizar perfil de nutricionista
     */
    public function updateNutritionistProfile(Request $request)
    {
        $request->validate([
            'specialization' => 'nullable|string',
            'hourly_rate' => 'nullable|numeric|min:0',
            'bio' => 'nullable|string',
            'certifications' => 'nullable|array',
            'years_experience' => 'nullable|integer|min:0',
            'availability' => 'nullable|array',
            'photo' => 'nullable|string',
        ]);

        $profile = auth()->user()->nutritionistProfile()->updateOrCreate(
            ['user_id' => auth()->id()],
            $request->all()
        );

        return response()->json([
            'message' => 'Perfil actualizado exitosamente',
            'profile' => $profile,
        ]);
    }

    /**
     * Actualizar perfil de cliente
     */
    public function updateClientProfile(Request $request)
    {
        $request->validate([
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'height' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'activity_level' => 'nullable|string',
            'dietary_preferences' => 'nullable|array',
            'allergies' => 'nullable|string',
            'medical_conditions' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relationship' => 'nullable|string',
            'photo' => 'nullable|string',
        ]);

        $profile = auth()->user()->clientProfile()->updateOrCreate(
            ['user_id' => auth()->id()],
            $request->all()
        );

        return response()->json([
            'message' => 'Perfil actualizado exitosamente',
            'profile' => $profile,
        ]);
    }
}
