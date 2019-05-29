<?php

namespace App\Http\Controllers;

use Exception;
use App\IncomeExpense;
use Illuminate\Http\Request;

class IncomeExpenseController extends Controller
{
    public function addIncome()
    {
        try {
            $data = request()->all();
            $incomeExpense = new IncomeExpense();
            $income = $incomeExpense->addIncome($data);
            $response = [ 'error' => 0, 'data' => $income ];
            
        } catch( Exception $ex ) {
            $response = [ 'error' => 1, 'message' => $ex->getMessage() ];
        }

        return response()->json($response);
    }

    public function addExpense()
    {
        try {
            $data = request()->all();
            $incomeExpense = new IncomeExpense();
            $expense = $incomeExpense->addExpense($data);
            $response = [ 'error' => 0, 'data' => $expense ];

        } catch( Exception $ex ) {
            $response = [ 'error' => 1, 'message' => $ex->getMessage() ];
        }

        return response()->json($response);
    }

    public function addSavings()
    {
        try {
            $data = request()->all();
            $incomeExpense = new IncomeExpense();
            $savings = $incomeExpense->addSavings($data);
            $response = [ 'error' => 0, 'data' => $savings ];

        } catch( Exception $ex ) {
            $response = [ 'error' => 1, 'message' => $ex->getMessage() ];
        }

        return response()->json($response);
    }

    public function getReport()
    {
        try {
            $data = request()->all();
            $incomeExpense = new IncomeExpense();
            $report = $incomeExpense->getReport($data);
            $response = [ 'error' => 0, 'data' => $report ];

        } catch( Exception $ex ) {
            $response = [ 'error' => 1, 'message' => $ex->getMessage() ];
        }

        return response()->json($response);
    }

    public function getAverageIncomeExpenseReport()
    {
        try {
            $data = request()->all();
            $incomeExpense = new IncomeExpense();
            $report = $incomeExpense->getAverageIncomeExpenseReport($data);
            $response = [ 'error' => 0, 'data' => $report ];

        } catch( Exception $ex ) {
            $response = [ 'error' => 1, 'message' => $ex->getMessage() ];
        }

        return response()->json($response);
    }
}
