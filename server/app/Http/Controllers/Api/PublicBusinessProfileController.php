<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BusinessResource;
use App\Models\Business;

class PublicBusinessProfileController extends Controller
{
    public function show(string $slug): BusinessResource
    {
        $business = Business::query()
            ->published()
            ->where('slug', $slug)
            ->with([
                'primaryCategory',
                'categories',
                'listings' => fn ($query) => $query->published()->with('category')->latest(),
            ])
            ->select('*')
            ->selectRaw('ST_Y(location::geometry) AS latitude, ST_X(location::geometry) AS longitude')
            ->firstOrFail();

        return new BusinessResource($business);
    }
}
