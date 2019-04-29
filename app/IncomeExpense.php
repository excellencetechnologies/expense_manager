<?php

namespace App;

use DB;
use App\User;
use Validator;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class IncomeExpense extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'income', 'expense', 'balance'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at'
    ];

    // Add User Income
    public function addIncome($data)
    {
        $validator = Validator::make($data, [
            'income' => 'required|numeric',
        ]);

        if( $validator->fails() ){
            return response()->json(['error' => $validator->errors()]);
        }

        if( !Auth::check() ){
            throw new Exception("Invalid Token");
        }

        $userid = Auth::id();
        $user = User::find($userid);

        // Data save in income_expense table
        $this->income = $data['income'];
        $this->user_id = $userid;
        $this->balance = $user->balance + $data['income'];
        $this->save();

        // Authenticated User Balance Updated
        $user->balance = $user->balance + $data['income'];
        $user->save();

        $user  = array_merge($user->getOriginal(), $this->getOriginal());

        return $user;
    }

    // Add User Expense
    public function addExpense($data)
    {
        $validator = Validator::make($data, [
            'expense' => 'required|numeric',
        ]);

        if( $validator->fails() ){
            return response()->json(['error' => $validator->errors()]);
        }

        if( !Auth::check() ){
            throw new Exception("Invalid Token");
        }

        $userid = Auth::id();
        $user = User::find($userid);

        $expense = $data['expense'];
        $categories = json_encode($data['categories']);
        
        $this->user_id = $userid;
        $this->expense = $expense;
        $this->balance = $user->balance - $expense;
        $this->categories = $categories;
        $this->save();

        $user->balance = $user->balance - $expense;
        $user->save();

        $user = array_merge($user->toArray(), $this->toArray());
        
        return $user;
    }

    public function getReport($data)
    {
        $validator = Validator::make($data, [
            'month' => 'numeric',
            'year' => 'numeric',
        ]);

        if( $validator->fails() ){
            return response()->json(['error' => $validator->errors()]);
        }

        if( !Auth::check() ){
            throw new Exception("Invalid Token");
        }

        $userid = Auth::id();
        $month = isset($data['month']) ? $data['month'] : null;
        $year = isset($data['year']) ? $data['year'] : null;

        if( $year ){
            if( $month ){
                $report = $this::find($userid)->whereMonth('created_at', $month)->whereYear('created_at', $year)->get();

            } else {
                $report = $this::find($userid)->whereYear('created_at', $year)->get();
            }

        } else {
            if( $month ){
                $validator = Validator::make($data, [ 'year' => 'required' ]);                
                if( $validator->fails() ){ return response()->json(['error' => $validator->errors()]); }

            } else {
                $report = $this::find($userid)->get();
            }
        }

        return $report;
    }

    public function getAverageIncomeExpenseReport($data)
    {
        $validator = Validator::make($data, [
            'month' => 'numeric',
            'year' => 'numeric',
        ]);

        if( $validator->fails() ){
            return response()->json(['error' => $validator->errors()]);
        }

        if( !Auth::check() ){
            throw new Exception('Invalid Token'); 
        }

        $userid = Auth::id();
        $month = isset($data['month']) ? $data['month'] : null;
        $year = isset($data['year']) ? $data['year'] : null;
        $expenseArray = $incomeArray = $expense = $income = [];

        if( $year ){
            if( $month ){
                $expesne = $this::find($userid)->whereMonth('created_at', $month)->whereYear('created_at', $year)->whereNotNull('expense')->avg('expense');
                $income = $this::find($userid)->whereMonth('created_at', $month)->whereYear('created_at', $year)->whereNotNull('income')->avg('income');
                
            } else {
                $expesne = $this::find($userid)->whereYear('created_at', $year)->whereNotNull('expense')->avg('expense');
                $income = $this::find($userid)->whereYear('created_at', $year)->whereNotNull('income')->avg('income');
            }

        } else {
            if( $month ){
                $validator = Validator::make($data, [ 'year' => 'required' ]);                
                if( $validator->fails() ){ return response()->json(['error' => $validator->errors()]); }

            } else {
                $expesne = $this::find($userid)->whereNotNull('expense')->avg('expense');
                $income = $this::find($userid)->whereNotNull('income')->avg('income');
            }
        }

        $average_report = [
            'month' => $month,
            'year' => $year,
            'average_income' => $income,
            'average_expesne' => $expesne
        ];         
        
        return $average_report;
    }
}
