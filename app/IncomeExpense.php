<?php

namespace App;

use DB;
use App\User;
use Validator;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class IncomeExpense extends Model
{
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

        $this->user_id = $userid;
        $this->expense = $data['expense'];
        $this->balance = $user->balance - $data['expense'];
        $this->save();

        $user->balance = $user->balance - $data['expense'];
        $user->save();

        $user = array_merge($user->getOriginal(), $this->getOriginal());
        
        return $user;
    }
}
