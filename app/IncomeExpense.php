<?php

namespace App;

use DB;
use App\User;
use Validator;
use Exception;
use Carbon\Carbon;
use App\Categories;
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
            'categories' => 'array'
        ]);

        if( $validator->fails() ){
            return response()->json(['error' => $validator->errors()]);
        }

        if( !Auth::check() ){
            throw new Exception("Invalid Token");
        }

        $userid = Auth::id();
        $user = User::find($userid);

        $amount = [];
        foreach( $data['categories'] as $categories ) {
            foreach( $categories as $category ){
                $amount[] = $category['amount'];
            }
        }
        
        $total_amount = array_sum($amount);
        if( $total_amount != $data['expense'] ){
            throw new Exception('Expense is not matching the total amout spending on different categories.');
        }
        
        $this->user_id = $userid;
        $this->expense = $data['expense'];
        $this->balance = $user->balance - $data['expense'];
        $this->categories = json_encode($data['categories']);
        $this->save();

        $user->balance = $user->balance - $data['expense'];
        $user->save();

        $month = date('m', strtotime($this->created_at));
        $year = date('Y', strtotime($this->created_at));

        $savings = (array) DB::table('savings')->where([
                        ['user_id', '=', $userid],
                        ['month', '=', $month],
                        ['year', '=', $year],
                    ])->get()->toArray()[0];

        if( sizeof($savings) > 0 ){
            $income = $this::whereMonth('created_at', $month)->whereYear('created_at', $year)->where('user_id', $userid)->sum('income');
            $expense = $this::whereMonth('created_at', $month)->whereYear('created_at', $year)->where('user_id', $userid)->sum('expense');
            $savings_amount = ($savings['savings_percentage'] / 100) * $income;
            $savings = $income - $expense; 
            if( $savings < 0 ){
                $savings = 0;
            }
            if( $savings < $savings_amount ){
                $user['alert_message'] = "The expense has been lead by savings this month";
            }
            $user['savings'] = $savings;
        }

        $user = array_merge($user->toArray(), $this->toArray());
        $user['categories'] = json_decode($user['categories'], true);        

        return $user;
    }

    public function addSavings($data)
    {
        $validator = Validator::make($data, [
            'savings_percentage' => 'required|numeric|min:1|max:100',
            'month' => 'required|numeric|min:1|max:12',
            'year' => 'required|numeric|digits:4'
        ]);

        if( $validator->fails() ){
            return response()->json(['error' => $validator->errors()]);
        }

        if( !Auth::check() ){
            throw new Exception("Invalid Token");
        }

        $userid = Auth::id();
        $check = [ 'user_id' => $userid, 'month' => $data['month'], 'year' => $data['year'] ];
        $insert = [ 'savings_percentage' => $data['savings_percentage'], 'created_at' => Carbon::now(), 'updated_at' => Carbon::now() ];
        $savings = DB::table('savings')->updateOrInsert($check, $insert);

        return $savings;
    }

    // Get Income/Expense Report
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
                $report = $this::find($userid)->whereMonth('created_at', $month)->whereYear('created_at', $year)->get()->toArray();

            } else {
                $report = $this::find($userid)->whereYear('created_at', $year)->get()->toArray();
            }

        } else {
            if( $month ){
                $validator = Validator::make($data, [ 'year' => 'required' ]);                
                if( $validator->fails() ){ return response()->json(['error' => $validator->errors()]); }

            } else {
                $report = $this::find($userid)->get()->toArray();
            }
        }

        foreach($report as $key => $report_value) {
            $report[$key]['categories'] = json_decode($report_value['categories'], true);
        }

        return $report;
    }

    // Get Average Income/Expense Report
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
        $expense = $income = $categories = [];
        
        if( $year ){
            if( $month ){
                $expense = $this::find($userid)->whereMonth('created_at', $month)->whereYear('created_at', $year)->whereNotNull('expense')->avg('expense');
                $income = $this::find($userid)->whereMonth('created_at', $month)->whereYear('created_at', $year)->whereNotNull('income')->avg('income');
                $user_expense_categories = $this::find($userid)->select('categories')->whereMonth('created_at', $month)->whereYear('created_at', $year)->whereNotNull('expense')->get()->toArray();
                
                $user_expense_categories_array = [];
                foreach( $user_expense_categories as $user_expense_category ){
                    $user_expense_categories_array[] = json_decode($user_expense_category['categories'], true);
                }
                
                $categories = $this->group_categories( $user_expense_categories_array );
                $average_categories_report = $this->average_report( $categories );
                
            } else {
                $expense = $this::find($userid)->whereYear('created_at', $year)->whereNotNull('expense')->avg('expense');
                $income = $this::find($userid)->whereYear('created_at', $year)->whereNotNull('income')->avg('income');
                $user_expense_categories = $this::find($userid)->select('categories')->whereYear('created_at', $year)->whereNotNull('expense')->get()->toArray();

                $user_expense_categories_array = [];
                foreach( $user_expense_categories as $user_expense_category ){
                    $user_expense_categories_array[] = json_decode($user_expense_category['categories'], true);
                }

                $categories = $this->group_categories( $user_expense_categories_array );
                $average_categories_report = $this->average_report( $categories );
            }

        } else {
            if( $month ){
                $validator = Validator::make($data, [ 'year' => 'required' ]);                
                if( $validator->fails() ){ return response()->json(['error' => $validator->errors()]); }

            } else {
                $expense = $this::find($userid)->whereNotNull('expense')->avg('expense');
                $income = $this::find($userid)->whereNotNull('income')->avg('income');
                $user_expense_categories = $this::find($userid)->select('categories')->whereNotNull('expense')->get()->toArray();

                $user_expense_categories_array = [];
                foreach( $user_expense_categories as $user_expense_category ){
                    $user_expense_categories_array[] = json_decode($user_expense_category['categories'], true);
                }

                $categories = $this->group_categories( $user_expense_categories_array );
                $average_categories_report = $this->average_report( $categories );
            }
        }

        $average_report = [
            'month' => $month,
            'year' => $year,
            'user_id' => $userid,
            'average_income' => $income,
            'average_expesne' => $expense,
            'average_expense_categories' => $average_categories_report
        ];         
        
        return $average_report;
    }

    public function group_categories( $categories )
    {
        $categories_count_array = [];
        foreach( $categories as $categories_iters ){
            foreach( $categories_iters as $category => $sub_categories ) {
                if( !array_key_exists( $category, $categories_count_array ) ){
                    $categories_count_array[$category]['count'] = 1;
                } else {
                    $categories_count_array[$category]['count'] += 1;
                }
                foreach( $sub_categories as $sub_category ){                            
                    if( !array_key_exists( 'sub_categories', $categories_count_array[$category] ) ){                                
                        $categories_count_array[$category]['amount'] = $sub_category['amount'];
                        $categories_count_array[$category]['sub_categories'][$sub_category['sub_category_name']]['count'] = 1;
                        $categories_count_array[$category]['sub_categories'][$sub_category['sub_category_name']]['amount'] = $sub_category['amount'];

                    } else {
                        if( array_key_exists( $sub_category['sub_category_name'], $categories_count_array[$category]['sub_categories'] ) ){
                            $categories_count_array[$category]['amount'] += $sub_category['amount'];
                            $categories_count_array[$category]['sub_categories'][$sub_category['sub_category_name']]['count'] += 1;
                            $categories_count_array[$category]['sub_categories'][$sub_category['sub_category_name']]['amount'] += $sub_category['amount'];

                        } else {
                            $categories_count_array[$category]['amount'] += $sub_category['amount'];
                            $categories_count_array[$category]['sub_categories'][$sub_category['sub_category_name']]['count'] = 1;
                            $categories_count_array[$category]['sub_categories'][$sub_category['sub_category_name']]['amount'] = $sub_category['amount'];
                        }                               
                    }
                }
            }
        }

        return $categories_count_array;
    }

    public function average_report( $categories )
    {
        $average_report = [];
        foreach( $categories as $category => $category_iters ){
            $average_report[$category]['average'] = $category_iters['amount'] / $category_iters['count'];
            foreach( $category_iters['sub_categories'] as $sub_category => $sub_category_value ){
                $average_report[$category]['sub_categories'][$sub_category] = $sub_category_value['amount'] / $sub_category_value['count'];
            }
        }

        return $average_report;
    }
}
