<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email')->lower())->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        return response()->json([
            'token' => $user->createToken('web')->plainTextToken,
            'user' => new UserResource($user->load('team')),
        ]);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load('team'));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(status: 204);
    }

    /**
     * There is no mail server behind this portfolio project, so we only log
     * the request. The response is the same whether the account exists or
     * not, so it can't be used to find out which emails are registered.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Log::info('Password reset requested', ['email' => $request->input('email')]);

        return response()->json([
            'message' => 'If an account exists for this email, a reset link is on its way.',
        ]);
    }
}
