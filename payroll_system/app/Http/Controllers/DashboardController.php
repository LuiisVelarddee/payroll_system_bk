<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function getStats(Request $request)
    {
        $query = Payroll::where('statusPayroll', true);

        if ($request->has('month')) {
            $query->where('month', $request->month);
        }

        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        $stats = $query->select(
            DB::raw('SUM(netSalary) as totalPayroll'),
            DB::raw('SUM(deliveries) as totalDeliveries'),
            DB::raw('SUM(hourBonus + deliveryBonus) as totalBonuses'),
            DB::raw('SUM(isr) as totalDeductions')
        )->first();

        return response()->json([
            'success' => true,
            'data' => [
                'totalPayroll' => $stats->totalPayroll ?? 0,
                'totalDeliveries' => $stats->totalDeliveries ?? 0,
                'totalBonuses' => $stats->totalBonuses ?? 0,
                'totalDeductions' => $stats->totalDeductions ?? 0,
            ]
        ], 200);
    }

    /**
     * Get monthly trend for the year
     */
    public function getMonthlyTrend(Request $request)
    {
        $year = $request->year ?? date('Y');
        
        $months = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];

        $trend = [];
        
        foreach ($months as $month) {
            $monthData = Payroll::where('statusPayroll', true)
                ->where('month', $month)
                ->where('year', $year)
                ->select(
                    DB::raw('SUM(baseSalary) as baseSalary'),
                    DB::raw('SUM(deliveryBonus) as deliveryBonus'),
                    DB::raw('SUM(hourBonus) as hourBonus')
                )->first();

            $trend[] = [
                'month' => $month,
                'baseSalary' => $monthData->baseSalary ?? 0,
                'deliveryBonus' => $monthData->deliveryBonus ?? 0,
                'hourBonus' => $monthData->hourBonus ?? 0,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $trend
        ], 200);
    }

    /**
     * Get expense distribution
     */
    public function getExpenseDistribution(Request $request)
    {
        $query = Payroll::where('statusPayroll', true);

        if ($request->has('month')) {
            $query->where('month', $request->month);
        }

        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        $distribution = $query->select(
            DB::raw('SUM(netSalary) as netSalary'),
            DB::raw('SUM(isr) as deductions')
        )->first();

        return response()->json([
            'success' => true,
            'data' => [
                'netSalary' => $distribution->netSalary ?? 0,
                'deductions' => $distribution->deductions ?? 0,
            ]
        ], 200);
    }

    /**
     * Get employee details for a specific month
     */
    public function getEmployeeDetails(Request $request)
    {
        $month = $request->month;
        $year = $request->year ?? date('Y');

        $query = Payroll::with(['employee'])
            ->where('statusPayroll', true)
            ->where('year', $year);

        // If month is specified, filter by month
        if ($month) {
            $query->where('month', $month);
            
            $payrolls = $query->get();

            $details = $payrolls->map(function ($payroll) {
                return [
                    'employeeNumber' => $payroll->employee->employeeNumber ?? 'N/A',
                    'name' => $payroll->employee->nameEmployee ?? 'N/A',
                    'hoursWorked' => 192, // 8 hours * 6 days * 4 weeks
                    'deliveryPayment' => $payroll->deliveryBonus ?? 0,
                    'deductions' => $payroll->isr ?? 0,
                    'foodVouchers' => $payroll->foodVouchers ?? 0,
                    'totalNet' => $payroll->netSalary ?? 0,
                ];
            });
        } else {
            // If no month specified, aggregate by employee for the entire year
            $payrolls = $query->get();
            
            // Group by employee and sum all values
            $employeeData = [];
            
            foreach ($payrolls as $payroll) {
                $employeeId = $payroll->employeeID;
                
                if (!isset($employeeData[$employeeId])) {
                    $employeeData[$employeeId] = [
                        'employeeNumber' => $payroll->employee->employeeNumber ?? 'N/A',
                        'name' => $payroll->employee->nameEmployee ?? 'N/A',
                        'hoursWorked' => 0,
                        'deliveryPayment' => 0,
                        'deductions' => 0,
                        'foodVouchers' => 0,
                        'totalNet' => 0,
                    ];
                }
                
                // Sum values for the year
                $employeeData[$employeeId]['hoursWorked'] += 192; // 192 hours per month
                $employeeData[$employeeId]['deliveryPayment'] += $payroll->deliveryBonus ?? 0;
                $employeeData[$employeeId]['deductions'] += $payroll->isr ?? 0;
                $employeeData[$employeeId]['foodVouchers'] += $payroll->foodVouchers ?? 0;
                $employeeData[$employeeId]['totalNet'] += $payroll->netSalary ?? 0;
            }
            
            $details = array_values($employeeData);
        }

        return response()->json([
            'success' => true,
            'data' => $details
        ], 200);
    }

    /**
     * Get available years with payroll data
     */
    public function getAvailableYears()
    {
        $years = Payroll::where('statusPayroll', true)
            ->select('year')
            ->distinct()
            ->orderBy('year', 'asc')
            ->pluck('year')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => $years
        ], 200);
    }
}
