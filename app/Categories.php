<?php

namespace App;

use Validator;
use Exception;
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

    public function addCategory($data)
    {
        $validator = Validator::make($data, [
            'name' => 'required',
        ]);

        if( $validator->fails() ){
            return response()->json(['error' => $validator->errors()]);
        }

        if( !Auth::check() ){
            throw new Exception('Invalid Token');
        }

        if( $this->where('name', $data['name'])->count() > 0 ){
            throw new Exception('Category Already Exist');
        }
        
        $this->name = strtolower($data['name']);
        $this->save();
        
        return $this;
    }
}
