<?php

namespace App;

use Exception;
use Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

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

    public function addCategories($data)
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

        $this->name = $data['name'];
        $this->save();

        return $this;
    }
}
