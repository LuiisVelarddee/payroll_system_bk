<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\CatEmployee;
use App\Models\CatRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    // Constants for payroll calculation
    const HOURLY_BASE_RATE = 30.00;
    const HOURS_PER_DAY = 8;
    const DAYS_PER_WEEK = 6;
    const WEEKS_PER_MONTH = 4;
    const DELIVERY_BONUS = 5.00;
    const BASE_ISR_RATE = 0.09;
    const ADDITIONAL_ISR_THRESHOLD = 10000.00;
    const ADDITIONAL_ISR_RATE = 0.03;
    const FOOD_VOUCHER_RATE = 0.04;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Payroll::with(['employee.role']);

        // Filter by status if provided
        if ($request->has('statusPayroll')) {
            $statusPayroll = filter_var($request->statusPayroll, FILTER_VALIDATE_BOOLEAN);
            $query->where('statusPayroll', $statusPayroll);
        }

        // Filter by month and year
        if ($request->has('month')) {
            $query->where('month', $request->month);
        }

        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        // Filter by employee
        if ($request->has('employeeID')) {
            $query->where('employeeID', $request->employeeID);
        }

        // Get all payroll records or paginate
        $payrolls = $request->has('paginate') && $request->paginate == 'false'
            ? $query->orderBy('dateCreation', 'desc')->get()
            : $query->orderBy('dateCreation', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $payrolls
        ], 200);
    }

    /**
     * Calculate payroll for an employee.
     */
    private function calculatePayroll($employee, $deliveries)
    {
        // Get employee role to determine hourly bonus
        $role = CatRole::find($employee->roleID);
        
        // Calculate monthly hours
        $monthlyHours = self::HOURS_PER_DAY * self::DAYS_PER_WEEK * self::WEEKS_PER_MONTH;
        
        // Calculate base salary
        $baseSalary = $monthlyHours * self::HOURLY_BASE_RATE;
        
        // Calculate hour bonus based on role
        $hourBonusRate = $role->bonusHours ?? 0;
        $hourBonus = $monthlyHours * $hourBonusRate;
        
        // Calculate delivery bonus
        $deliveryBonus = $deliveries * self::DELIVERY_BONUS;
        
        // Calculate gross salary
        $grossSalary = $baseSalary + $hourBonus + $deliveryBonus;
        
        // Calculate ISR (9% base + 3% additional if > 10,000)
        $isrRate = self::BASE_ISR_RATE;
        if ($grossSalary > self::ADDITIONAL_ISR_THRESHOLD) {
            $isrRate += self::ADDITIONAL_ISR_RATE;
        }
        $isr = $grossSalary * $isrRate;
        
        // Calculate food vouchers (4% of gross salary)
        $foodVouchers = $grossSalary * self::FOOD_VOUCHER_RATE;
        
        // Calculate net salary
        $netSalary = $grossSalary - $isr + $foodVouchers;
        
        return [
            'baseSalary' => round($baseSalary, 2),
            'hourBonus' => round($hourBonus, 2),
            'deliveryBonus' => round($deliveryBonus, 2),
            'grossSalary' => round($grossSalary, 2),
            'isr' => round($isr, 2),
            'foodVouchers' => round($foodVouchers, 2),
            'netSalary' => round($netSalary, 2),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employeeID' => 'required|exists:cat_employees,id',
            'month' => 'required|string|max:20',
            'year' => 'required|integer|min:2000|max:2100',
            'deliveries' => 'required|integer|min:0',
            'userCreation' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if payroll already exists for this employee, month, and year
            $existingPayroll = Payroll::where('employeeID', $request->employeeID)
                ->where('month', $request->month)
                ->where('year', $request->year)
                ->first();

            if ($existingPayroll) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un registro de nómina para este empleado en el mes especificado'
                ], 422);
            }

            // Get employee with role information
            $employee = CatEmployee::with('role')->findOrFail($request->employeeID);

            // Calculate payroll
            $calculations = $this->calculatePayroll($employee, $request->deliveries);

            // Create payroll record
            $payroll = Payroll::create([
                'employeeID' => $request->employeeID,
                'month' => $request->month,
                'year' => $request->year,
                'deliveries' => $request->deliveries,
                'baseSalary' => $calculations['baseSalary'],
                'hourBonus' => $calculations['hourBonus'],
                'deliveryBonus' => $calculations['deliveryBonus'],
                'grossSalary' => $calculations['grossSalary'],
                'isr' => $calculations['isr'],
                'foodVouchers' => $calculations['foodVouchers'],
                'netSalary' => $calculations['netSalary'],
                'userCreation' => $request->userCreation,
                'dateCreation' => now(),
                'statusPayroll' => true,
            ]);

            // Load relationships
            $payroll->load(['employee.role']);

            return response()->json([
                'success' => true,
                'message' => 'Nómina creada exitosamente',
                'data' => $payroll
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la nómina',
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
            $payroll = Payroll::with(['employee.role'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $payroll
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Nómina no encontrada',
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
            'deliveries' => 'required|integer|min:0',
            'userUpdate' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $payroll = Payroll::findOrFail($id);

            // Get employee with role information
            $employee = CatEmployee::with('role')->findOrFail($payroll->employeeID);

            // Recalculate payroll with new deliveries
            $calculations = $this->calculatePayroll($employee, $request->deliveries);

            // Update payroll record
            $payroll->update([
                'deliveries' => $request->deliveries,
                'baseSalary' => $calculations['baseSalary'],
                'hourBonus' => $calculations['hourBonus'],
                'deliveryBonus' => $calculations['deliveryBonus'],
                'grossSalary' => $calculations['grossSalary'],
                'isr' => $calculations['isr'],
                'foodVouchers' => $calculations['foodVouchers'],
                'netSalary' => $calculations['netSalary'],
                'userUpdate' => $request->userUpdate,
                'dateUpdate' => now(),
            ]);

            // Load relationships
            $payroll->load(['employee.role']);

            return response()->json([
                'success' => true,
                'message' => 'Nómina actualizada exitosamente',
                'data' => $payroll
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la nómina',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Request $request, $id)
    {
        try {
            $payroll = Payroll::findOrFail($id);

            $payroll->update([
                'statusPayroll' => false,
                'userUpdate' => $request->userUpdate ?? null,
                'dateUpdate' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Nómina desactivada exitosamente',
                'data' => $payroll
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al desactivar la nómina',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a deactivated payroll.
     */
    public function restore(Request $request, $id)
    {
        try {
            $payroll = Payroll::findOrFail($id);

            $payroll->update([
                'statusPayroll' => true,
                'userUpdate' => $request->userUpdate ?? null,
                'dateUpdate' => now(),
            ]);

            $payroll->load(['employee.role']);

            return response()->json([
                'success' => true,
                'message' => 'Nómina restaurada exitosamente',
                'data' => $payroll
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar la nómina',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
