<?php

namespace App\Http\Controllers;

use App\User;
use Exception;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Create Account
    public function signup()
    {
        try {
            $data = request()->all();
            $user = new User();
            $newUser = $user->signup($data);           
            $response = [ 'error' => 0, 'data' => $newUser ];
            
        } catch(Exception $ex) {            
            $response = [ 'error' => 1, 'message' => $ex->getMessage() ];
        }

        return response()->json($response);
    }

    // User Login
    public function login()
    {
        try{
            $credentials = request()->only('email', 'password');
            $user = new User();
            $login = $user->login($credentials);
            $response = [ 'error' => 0, 'data' => $login ];

        } catch(Exception $ex) {
            $response = [ 'error' => 1, 'message' => $ex->getMessage() ];
        }

        return response()->json($response);
    }
    
}
