<?php

namespace App\Http\Controllers\Api\NewHandheld;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewHandheldController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        return $this->successResponse([], 'New Handheld module - coming soon');
    }
}
