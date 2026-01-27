<?php

namespace App\Http\Controllers;

use App\Models\CatUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CatUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = CatUser::query();

        // Filter by status if provided
        if ($request->has('statusUser')) {
            $query->where('statusUser', $request->statusUser);
        }

        // Filter by blocked status
        if ($request->has('isBlock')) {
            $query->where('isBlock', $request->isBlock);
        }

        // Filter by admin status
        if ($request->has('is_admin')) {
            $query->where('is_admin', $request->is_admin);
        }

        // Search by employee number
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('employeeNumber', 'like', "%{$search}%");
        }

        // Get all users or paginate
        $users = $request->has('paginate') && $request->paginate == 'false'
            ? $query->orderBy('dateCreation', 'desc')->get()
            : $query->orderBy('dateCreation', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $users
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employeeNumber' => 'required|string|max:50|unique:cat_users,employeeNumber',
            'password' => 'required|string|min:6',
            'is_admin' => 'nullable|boolean',
            'changePass' => 'nullable|boolean',
            'userCreation' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = CatUser::create([
                'employeeNumber' => $request->employeeNumber,
                'password' => $request->password, // Se hasheará automáticamente en el modelo
                'attempts' => 0,
                'isBlock' => false,
                'changePass' => $request->changePass ?? false,
                'is_admin' => $request->is_admin ?? false,
                'userCreation' => $request->userCreation,
                'dateCreation' => now(),
                'statusUser' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'data' => $user
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $user = CatUser::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'employeeNumber' => 'sometimes|required|string|max:50|unique:cat_users,employeeNumber,' . $id,
            'password' => 'nullable|string|min:6',
            'is_admin' => 'nullable|boolean',
            'changePass' => 'nullable|boolean',
            'userUpdate' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = CatUser::findOrFail($id);

            $dataToUpdate = $request->only([
                'employeeNumber',
                'is_admin',
                'changePass',
            ]);

            // Only update password if provided
            if ($request->filled('password')) {
                $dataToUpdate['password'] = $request->password;
            }

            $dataToUpdate['userUpdate'] = $request->userUpdate;
            $dataToUpdate['dateUpdate'] = now();

            $user->update($dataToUpdate);

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente',
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage (soft delete - only deactivates).
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = CatUser::findOrFail($id);

            // Soft delete - only deactivate
            $user->update([
                'statusUser' => false,
                'userUpdate' => $request->userUpdate ?? null,
                'dateUpdate' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Usuario desactivado exitosamente',
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al desactivar el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a deactivated user.
     */
    public function restore(Request $request, $id)
    {
        try {
            $user = CatUser::findOrFail($id);

            $user->update([
                'statusUser' => true,
                'userUpdate' => $request->userUpdate ?? null,
                'dateUpdate' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Usuario restaurado exitosamente',
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change user password.
     */
    public function changePassword(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'newPassword' => 'required|string|min:6',
            'userUpdate' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = CatUser::findOrFail($id);

            $user->update([
                'password' => $request->newPassword,
                'changePass' => false, // Reset change password flag
                'attempts' => 0, // Reset attempts
                'userUpdate' => $request->userUpdate,
                'dateUpdate' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente',
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar la contraseña',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Block/Unblock a user.
     */
    public function toggleBlock(Request $request, $id)
    {
        try {
            $user = CatUser::findOrFail($id);

            $user->update([
                'isBlock' => !$user->isBlock,
                'attempts' => 0, // Reset attempts when unblocking
                'userUpdate' => $request->userUpdate ?? null,
                'dateUpdate' => now(),
            ]);

            $status = $user->isBlock ? 'bloqueado' : 'desbloqueado';

            return response()->json([
                'success' => true,
                'message' => "Usuario {$status} exitosamente",
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado de bloqueo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset user attempts.
     */
    public function resetAttempts(Request $request, $id)
    {
        try {
            $user = CatUser::findOrFail($id);

            $user->update([
                'attempts' => 0,
                'userUpdate' => $request->userUpdate ?? null,
                'dateUpdate' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Intentos reiniciados exitosamente',
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reiniciar intentos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
