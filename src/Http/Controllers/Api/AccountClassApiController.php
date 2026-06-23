<?php

namespace Numerar\Contable\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Numerar\Contable\Http\Resources\AccountClassResource;
use Numerar\Contable\Models\AccountClass;

class AccountClassApiController extends Controller
{
    public function index(): JsonResponse
    {
        $classes = AccountClass::orderBy('code')->get();
        return AccountClassResource::collection($classes)->response();
    }
}
