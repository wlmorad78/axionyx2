<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index()
    {
        return response()->json(['data' => []]);
    }

    public function store(Request $request)
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function show($id)
    {
        return response()->json(['message' => 'Not found'], 404);
    }

    public function update(Request $request, $id)
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function destroy($id)
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function restore($id)
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function forceDelete($id)
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
