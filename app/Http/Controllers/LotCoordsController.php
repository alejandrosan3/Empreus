<?php

namespace App\Http\Controllers;

use App\Models\Lot;
use Illuminate\Http\Request;

class LotCoordsController extends Controller
{
    public function store(Request $request, Lot $lot)
    {
        // Optional: add $this->authorize('update', $lot); if you use policies.
        $data = $request->validate([
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        // Skip if already set (avoid extra writes)
        if (!is_null($lot->latitude) && !is_null($lot->longitude)) {
            return response()->json(['status' => 'skipped'], 200);
        }

        $lot->latitude  = $data['latitude'];
        $lot->longitude = $data['longitude'];
        $lot->save();

        return response()->json(['status' => 'ok'], 200);
    }
}
