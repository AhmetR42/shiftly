<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $activities = $request->user()->team->activities()
            ->with('user:id,name')
            ->latest()
            ->latest('id')
            ->paginate(20);

        return ActivityResource::collection($activities);
    }
}
