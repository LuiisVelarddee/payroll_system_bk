<?php

namespace App\Http\Controllers;

use App\Models\CatRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CatRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = CatRole::query();

        // Filter by status if provided
        if ($request->has('statusRole')) {
            $statusRole = filter_var($request->statusRole, FILTER_VALIDATE_BOOLEAN);
            $query->where('statusRole', $statusRole);
        }

        // Get all roles or paginate
        $roles = $request->has('paginate') && $request->paginate == 'false'
            ? $query->orderBy('dateCreation', 'desc')->get()
            : $query->orderBy('dateCreation', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $roles
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nameRole' => 'required|string|max:100',
            'salaryBase' => 'required|numeric|min:0',
            'bonusRole' => 'nullable|numeric|min:0',
            'bonusHours' => 'nullable|numeric|min:0',
            'bonusDeliveries' => 'nullable|numeric|min:0',
            'userCreation' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $role = CatRole::create([
                'nameRole' => $request->nameRole,
                'salaryBase' => $request->salaryBase,
                'bonusRole' => $request->bonusRole ?? 0,
                'bonusHours' => $request->bonusHours ?? 0,
                'bonusDeliveries' => $request->bonusDeliveries ?? 0,
                'userCreation' => $request->userCreation,
                'dateCreation' => now(),
                'statusRole' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Role creado exitosamente',
                'data' => $role
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el role',
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
            $role = CatRole::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $role
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role no encontrado',
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
            'nameRole' => 'sometimes|required|string|max:100',
            'salaryBase' => 'sometimes|required|numeric|min:0',
            'bonusRole' => 'nullable|numeric|min:0',
            'bonusHours' => 'nullable|numeric|min:0',
            'bonusDeliveries' => 'nullable|numeric|min:0',
            'userUpdate' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $role = CatRole::findOrFail($id);

            $dataToUpdate = $request->only([
                'nameRole',
                'salaryBase',
                'bonusRole',
                'bonusHours',
                'bonusDeliveries',
            ]);

            $dataToUpdate['userUpdate'] = $request->userUpdate;
            $dataToUpdate['dateUpdate'] = now();

            $role->update($dataToUpdate);

            return response()->json([
                'success' => true,
                'message' => 'Role actualizado exitosamente',
                'data' => $role
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el role',
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
            $role = CatRole::findOrFail($id);

            // Soft delete - only deactivate
            $role->update([
                'statusRole' => false,
                'userUpdate' => $request->userUpdate ?? null,
                'dateUpdate' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Role desactivado exitosamente',
                'data' => $role
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al desactivar el role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a deactivated role.
     */
    public function restore(Request $request, $id)
    {
        try {
            $role = CatRole::findOrFail($id);

            $role->update([
                'statusRole' => true,
                'userUpdate' => $request->userUpdate ?? null,
                'dateUpdate' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Role restaurado exitosamente',
                'data' => $role
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar el role',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
