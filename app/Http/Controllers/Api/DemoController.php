<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StartDemoRequest;
use App\Http\Resources\UserResource;
use App\Services\DemoTeamBuilder;
use Illuminate\Http\JsonResponse;

class DemoController extends Controller
{
    /**
     * Every visitor gets their own throwaway team, so people trying the
     * demo at the same time can't mess up each other's rota.
     */
    public function store(StartDemoRequest $request, DemoTeamBuilder $builder): JsonResponse
    {
        $demo = $builder->build(['demo' => true]);
        $user = $request->input('role') === 'manager' ? $demo['manager'] : $demo['employee'];

        return response()->json([
            'token' => $user->createToken('demo')->plainTextToken,
            'user' => new UserResource($user->load('team')),
        ], 201);
    }
}
