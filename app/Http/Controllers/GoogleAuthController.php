<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use GuzzleHttp\Client;
use Tymon\JWTAuth\Facades\JWTAuth;

class GoogleAuthController extends Controller
{

    public function redirect()
    {
        $parameters = [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'redirect_uri' => 'https://uts-nizar.libranation.my.id/api/auth/google/call-back',
            'response_type' => 'code',
            'scope' => 'email profile',
            'access_type' => 'offline',
            'include_granted_scopes' => 'true',
            'state' => 'state_parameter_passthrough_value'
        ];

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($parameters);
        return response()->json(['redirect_url' => $authUrl]);
    }


    public function callbackGoogle(Request $request)
    {
        $code = $request->input('code');

        if (!$code) {
            return response()->json(['error' => 'Authorization code not found'], 400);
        }

        try {
            $client = new Client();

            $response = $client->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'code' => $code,
                    'client_id' => env('GOOGLE_CLIENT_ID'),
                    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                    'redirect_uri' => 'https://uts-nizar.libranation.my.id/api/auth/google/call-back',
                    'grant_type' => 'authorization_code',
                ],
            ]);

            $tokenData = json_decode($response->getBody(), true);
            $accessToken = $tokenData['access_token'];
            $refreshToken = $tokenData['refresh_token'];

            // Use the access token to get user information
            $google_user = Socialite::driver('google')->stateless()->userFromToken($accessToken);

            $user = User::where('google_id', $google_user->getId())->first();

            if (!$user) {
                $new_user = User::create([
                    'name' => $google_user->getName(),
                    'email' => $google_user->getEmail(),
                    'google_id' => $google_user->getId(),
                    'role' => 'user' // Set user role here
                ]);

                $user = Auth::user();
                
                $payload = [
                    'id' => $new_user->id,
                    'name' => $new_user->name,
                    'role' => $new_user->role,
                    'iat' => Carbon::now()->timestamp,
                    'exp' => Carbon::now()->timestamp + 3600,
                ];

                $token = JWT::encode($payload, env('JWT_SECRET_KEY'), 'HS256');

                Auth::login($new_user);
                return response()->json(['message' => 'User created and logged in successfully', 'user' => $new_user, 'bearer token' => $token],200);
            } else {
                // Generate JWT token for the existing user
                $payload = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                    'iat' => Carbon::now()->timestamp,
                    'exp' => Carbon::now()->timestamp + 3600,
                ];

                $token = JWT::encode($payload, env('JWT_SECRET_KEY'), 'HS256');

                Auth::login($user);
                return response()->json(['message' => 'User logged in successfully', 'user' => $user, 'bearer token' => $token],200);
            }
        } catch (\Throwable $th) {
            return response()->json(['error' => 'Something went wrong', 'details' => $th->getMessage()], 424);
        }
    }

}
