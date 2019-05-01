<?php

namespace App;

use DB;
use Validator;
use Exception;
use JWTAuth;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'balance', 'apiToken'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'created_at', 'updated_at'
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // Create Account
    public function signup($data)
    {
        $validator = Validator::make($data, [
            'name' => 'required|min:3|max:50',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
            'balance' => 'required|numeric'
        ]);

        if( $validator->fails() ){
            return response()->json(['error' => $validator->errors()]);
        }

        $isUserExists = DB::table('users')->select('email')->where('email', $data['email'])->count();

        if( $isUserExists > 0 ){
            throw new Exception('User already exists.');
        }

        $this->name = $data['name'];
        $this->email = $data['email'];
        $this->password = bcrypt($data['password']);
        $this->balance = $data['balance'];
        $this->role = 'user';
        $this->apiToken = str_random(60);
        $this->save();
        
        $token = auth()->login($this);

        return $this->respondWithToken($token);
    }

    // User Login
    public function login($credentials)
    {
        if ( !$token = JWTAuth::attempt($credentials) ) {
            throw new Exception('Unauthorized Token.');
        }
        return $this->respondWithToken($token);
    }

    public function getUser()
    {
        if( !JWTAuth::parseToken()->authenticate() ){
            throw new Exception('Invalid Token');
        }
        
        $userid = Auth::id();
        $userInfo = $this->find($userid);

        return $userInfo;
    }

    public function updateUser($data)
    {
        $validator = Validator::make($data, [
            'name' => 'required|min:3|max:50',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'balance' => 'required|numeric'
        ]);

        if( $validator->fails() ){
            return response()->json(['error' => $validator->errors()]);
        }

        if( !Auth::check() ){
            throw new Exception('Invalid Token');
        }

        $user = $this->find(Auth::id());
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = bcrypt($data['password']);
        $user->balance = $data['balance'];
        $user->save();

        return $user;
    }

    public function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth()->factory()->getTTL() * 60
        ]);
    }

}
