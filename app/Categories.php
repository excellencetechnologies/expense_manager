<?php

namespace App;

use DB;
use Validator;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Schema\Blueprint;

class Categories extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at'
    ];

    // Add Category
    public function addCategory($data)
    {
        $validator = Validator::make($data, [
            'name' => 'required',
            'sub_categories' => 'required|array'
        ]);

        if( $validator->fails() ){
            return response()->json(['error' => $validator->errors()]);
        }

        if( !Auth::check() ){
            throw new Exception('Invalid Token');
        }

        if( $data['admin'] ){

            if( $this->where('name', $data['name'])->count() > 0 ){
                throw new Exception('Category Already Exist');
            }

            $this->name = strtolower($data['name']);
            $this->save();

            foreach( $data['sub_categories'] as $sub_category ){
                DB::table('sub_categories')->insert([
                    'name' => $sub_category,
                    'category_id' => $this->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
            }

            $sub_categories = DB::table('sub_categories')->select('id', 'category_id', 'name')->where('category_id', $this->id)->get()->toArray();
            $response = [
                'id' => $this->id,
                'name' => $this->name,
                'sub_categories' => $sub_categories
            ]; 

        } else {

            $users_categories = DB::table('users_categories')->where('name', $data['name'])->count();
            if( $users_categories > 0 || $this->where('name', $data['name'])->count() > 0 ){
                throw new Exception('Category Already Exist');
            }

            DB::table('users_categories')->insert([
                'user_id' => auth()->id(),
                'name' => strtolower($data['name']),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            $users_category_id = DB::getPdo()->lastInsertId();
            foreach( $data['sub_categories'] as $sub_category ){
                DB::table('users_sub_categories')->insert([
                    'name' => strtolower($sub_category),
                    'users_category_id' => $users_category_id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
            }

            $users_categories = DB::table('users_categories')->where('id', $users_category_id)->get()->toArray();
            $users_sub_categories = DB::table('users_sub_categories')->select('id', 'users_category_id', 'name')->where('users_category_id', $users_category_id)->get()->toArray();
            $response = [
                'id' => $users_categories[0]->id,
                'user_id' => $users_categories[0]->user_id,
                'name' => $users_categories[0]->name,
                'sub_categories' => $users_sub_categories
            ];
        }        
        
        return $response;
    }

    // Add Sub Categories
    public function addSubCategory($data)
    {
        $validator = Validator::make($data, [
            'category_id' => 'required|numeric|min:1',
            'sub_categories' => 'required|array'
        ]);

        if( $validator->fails() ){
            return response()->json(['error' => $validator->errors()]);
        }

        if( !Auth::check() ){
            throw new Exception('Invalid Token');
        }

        $data['sub_categories'] = array_map('strtolower', array_filter($data['sub_categories']));

        if( $data['admin'] ){

            if( $this->where('id', $data['category_id'])->count() < 1 ){
                throw new Exception('Category not found');
            }

            $sub_categories = DB::table('sub_categories')->where('category_id', $data['category_id'])->get()->toArray();
            $sub_category_exist = array_values(
                array_intersect(
                    array_map( 
                        function($iter){ 
                            return strtolower($iter->name); 
                        }, 
                        $sub_categories
                    ),
                    $data['sub_categories']
                )
            );

            if( sizeof($sub_category_exist) > 0 ){
                throw new Exception(ucfirst($sub_category_exist[0]) . " already exists");
            }

            foreach( $data['sub_categories'] as $sub_category ){
                DB::table('sub_categories')->insert([
                    'name' => strtolower($sub_category),
                    'category_id' => $data['category_id']
                ]);
            }

            $sub_categories = DB::table('sub_categories')->select('id', 'name', 'category_id')->where('category_id', $data['category_id'])->get()->toArray();
            $response = [
                'message' => 'Sub Categories Added Successfully',
                'sub_categories' => $sub_categories
            ];

        } else {

            $userid = Auth::id();
            $users_categories = DB::table('users_categories')->where('id', $data['category_id'])->where('user_id', $userid)->count();
            
            if( $users_categories < 1 ){
                throw new Exception('Category not found');                
            }

            $users_sub_categories = DB::table('users_sub_categories')->where('users_category_id', $data['category_id'])->get()->toArray();
            $users_sub_category_exist = array_values(
                array_intersect(
                    array_map( 
                        function($iter){ 
                            return strtolower($iter->name); 
                        }, 
                        $users_sub_categories
                    ),
                    $data['sub_categories']
                )
            );
            
            if( sizeof($users_sub_category_exist) > 0 ){
                throw new Exception(ucfirst($users_sub_category_exist[0]) . " already exists");
            }

            foreach( $data['sub_categories'] as $users_sub_category ){
                DB::table('users_sub_categories')->insert([
                    'name' => strtolower($users_sub_category),
                    'users_category_id' => $data['category_id']
                ]);
            }

            $users_sub_categories = DB::table('users_sub_categories')->select('id', 'name', 'users_category_id')->where('users_category_id', $data['category_id'])->get()->toArray();
            $response = [
                'message' => 'Sub Categories Added Successfully',
                'users_sub_categories' => $users_sub_categories
            ];
        }

        return $response;
    }

    // Delete Category
    public function deleteCategory($data)
    {
        $validator = Validator::make($data, [
            'id' => 'required|min:1',
        ]);

        if( $validator->fails() ){
            return response()->json(['error' => $validator->errors()]);
        }

        if( !Auth::check() ){
            throw new Exception('Invalid Token');
        }

        if( $data['admin'] ){
            $category = $this::where('id', $data['id']);
            if( $category->count() < 1 ){
                throw new Exception('Category does not exist.');
            }
    
            $category->delete();
            DB::table('sub_categories')->where('category_id', $data['id'])->delete();

        } else {
            $userid = Auth::id();
            $category = DB::table('users_categories')->where('id', $data['id'])->where('user_id', $userid);            
            if( $category->count() < 1 ){
                throw new Exception('Category does not exist.');
            }

            $category->delete();
            DB::table('users_sub_categories')->where('users_category_id', $data['id'])->delete();
        }

        return 'Category deleted successfully';
    }
}
