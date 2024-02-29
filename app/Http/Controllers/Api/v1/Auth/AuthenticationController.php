<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Requests\Api\v1\CreateUserRequest;
use App\Http\Requests\Api\v1\LoginRequest;
use App\Http\Resources\v1\UserResource;
use App\Http\Responses\v1\ApiResponse;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationController
{
    // Login (issue API token)
    public function login(LoginRequest $request)
    {

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {

            $user = Auth::user();

            $token = $user->createToken('API Token of ' . $user->name)->plainTextToken; // Optionally allow device identification

            // Customize token details and response based on your needs
            $tokenData = [
                'user' => new UserResource($user),
                'token' => $token,
            ];

            return ApiResponse::success($tokenData, 'Login successfull.')->respond();
        }


        return ApiResponse::error('Invalid credentials', Response::HTTP_UNAUTHORIZED)->respond();
    }

    // Register (create user and issue API token)
    public function register(CreateUserRequest $request)
    {
        $avatar = [
            'image' => null,
            'thumbnail' => null
        ];

        if($request->hasFile('avatar')){
            $imageService = new ImageService();
            $avatar = $imageService->storeImage($request->file('avatar'), 'users/avatars');
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'avatar' => $avatar['image'],
            'avatar_thumbnail' => $avatar['thumbnail'],
        ]);

        $token = $user->createToken('API Token of ' . $user->name)->plainTextToken; // Optionally allow device identification

        // Customize token details and response based on your needs
        $tokenData = [
            'user' => new UserResource($user),
            'token' => $token,
        ];

        return ApiResponse::success($tokenData, 'Registration successfull.')->respond();
    }

    // Logout (invalidate API token)
    public function logout()
    {
        Auth::user()->tokens()->delete();

        return response()->json(['status' => 'success']);
    }

    // Refresh API token
    public function refreshToken(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['status' => 'error', 'message' => 'Authorization token is missing'], 401);
        }

        // Validate and refresh token based on your implementation (e.g., Sanctum)
        // ...

        // Customize token details and response based on your needs
        $tokenData = [
            'token' => $newToken->plainTextToken,
            'expires_at' => $newToken->expiresAt,
        ];

        return response()->json([
            'status' => 'success',
            'data' => $tokenData,
        ]);
    }

}
