<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if( !Auth::check() ){
            return response()->json(['error' => 1, 'message' => "Invalid Token"]);
        }

        $user = Auth::user();
        $admin = true;
        if( strtolower($user->role) != 'admin' ){
            $admin = false;
        }

        $request->attributes->add(['admin' => $admin]); 
        return $next($request);
    }
}
