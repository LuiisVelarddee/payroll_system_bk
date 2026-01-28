<?php

namespace App\Http\Controllers;

use App\Models\CatEmployee;
use App\Models\CatUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CatEmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = CatEmployee::with(['role', 'user']);

        // Filter by status if provided
        if ($request->has('statusEmployee')) {
            $statusEmployee = filter_var($request->statusEmployee, FILTER_VALIDATE_BOOLEAN);
            $query->where('statusEmployee', $statusEmployee);
        }

        // Filter by role if provided
        if ($request->has('roleID')) {
            $query->where('roleID', $request->roleID);
        }

        // Search by name or employee number
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nameEmployee', 'like', "%{$search}%")
                  ->orWhere('employeeNumber', 'like', "%{$search}%");
            });
        }

        // Get all employees or paginate
        $employees = $request->has('paginate') && $request->paginate == 'false'
            ? $query->orderBy('dateCreation', 'desc')->get()
            : $query->orderBy('dateCreation', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $employees
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employeeNumber' => 'required|string|max:50|unique:cat_employees,employeeNumber|unique:cat_users,employeeNumber',
            'nameEmployee' => 'required|string|max:150',
            'roleID' => 'required|exists:cat_roles,id',
            'password' => 'required|string|min:6',
            'is_admin' => 'nullable|boolean',
            'userCreation' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // 1. Primero crear el usuario en cat_users
            $user = CatUser::create([
                'employeeNumber' => $request->employeeNumber,
                'password' => Hash::make('DefaultSystem123'), // Contraseña por defecto hasheada
                'attempts' => 0,
                'isBlock' => false,
                'changePass' => $request->changePass ?? false, //Podemos Forzar cambio de contraseña al primer login con true
                'is_admin' => $request->is_admin ?? false,
                'userCreation' => $request->userCreation,
                'dateCreation' => now(),
                'statusUser' => true,
            ]);

            // Guardar cambios inmediatamente para asegurar que el ID está disponible
            $user->save();

            // 2. Buscar el ID del usuario recién creado por employeeNumber
            $userFound = CatUser::where('employeeNumber', $request->employeeNumber)->first();
            
            if (!$userFound) {
                throw new \Exception('No se pudo encontrar el usuario creado, no se pudo registrar el empleado.');
            }

            // 3. Luego crear el empleado con el userID obtenido
            $employee = CatEmployee::create([
                'employeeNumber' => $request->employeeNumber,
                'nameEmployee' => $request->nameEmployee,
                'roleID' => $request->roleID,
                'userID' => $userFound->id, // Usar el ID del usuario encontrado
                'userCreation' => $request->userCreation,
                'dateCreation' => now(),
                'statusEmployee' => true,
            ]);

            // Load relationships
            $employee->load(['role', 'user']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Empleado y usuario creados exitosamente',
                'data' => $employee
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el empleado',
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
            $employee = CatEmployee::with(['role', 'user'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $employee
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Empleado no encontrado',
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
            'employeeNumber' => 'sometimes|required|string|max:50|unique:cat_employees,employeeNumber,' . $id,
            'nameEmployee' => 'sometimes|required|string|max:150',
            'roleID' => 'sometimes|required|exists:cat_roles,id',
            'userID' => 'sometimes|required|exists:cat_users,id',
            'userUpdate' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $employee = CatEmployee::findOrFail($id);

            $dataToUpdate = $request->only([
                'employeeNumber',
                'nameEmployee',
                'roleID',
                'userID',
            ]);

            $dataToUpdate['userUpdate'] = $request->userUpdate;
            $dataToUpdate['dateUpdate'] = now();

            $employee->update($dataToUpdate);

            // Load relationships
            $employee->load(['role', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Empleado actualizado exitosamente',
                'data' => $employee
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el empleado',
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
            $employee = CatEmployee::findOrFail($id);

            // Soft delete - only deactivate
            $employee->update([
                'statusEmployee' => false,
                'userUpdate' => $request->userUpdate ?? null,
                'dateUpdate' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Empleado desactivado exitosamente',
                'data' => $employee
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al desactivar el empleado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a deactivated employee.
     */
    public function restore(Request $request, $id)
    {
        try {
            $employee = CatEmployee::findOrFail($id);

            $employee->update([
                'statusEmployee' => true,
                'userUpdate' => $request->userUpdate ?? null,
                'dateUpdate' => now(),
            ]);

            // Load relationships
            $employee->load(['role', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Empleado restaurado exitosamente',
                'data' => $employee
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar el empleado',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
