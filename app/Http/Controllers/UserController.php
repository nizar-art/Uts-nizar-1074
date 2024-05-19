<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Firebase\JWT\JWT;

class UserController extends Controller
{
    public function login ( Request $request){
        $validator = Validator::make($request->all(), [
            'email' =>'required|email',
            'password' => 'required|string'
        ]);
        if ($validator->fails()){
            return response()->json( $validator->messages())->setStatusCode(422);
        }

        $validated = $validator->validate();
        if ( Auth::attempt($validated)){
            $user = Auth::user();

            $payload = [
                'name' => $user->name,
                'role' => $user->role,
                'iat' => Carbon::now()->timestamp,
                'exp' => Carbon::now()->timestamp + 3600,
            ];

            $bearer = JWT::encode($payload, env('JWT_SECRET_KEY'), 'HS256');
            return response()->json([
               'nama' => $user->name,
               'role' => $user->role,
               'bearer' => $bearer
            ], 200);
        }
        return response()->json(["Akun tidak tersedia"], 422);
    }
}