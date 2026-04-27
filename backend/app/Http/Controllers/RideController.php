<?php

namespace App\Http\Controllers;

use App\Models\Ride;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RideController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Ride::whereRaw("CONCAT(ride_date, ' ', ride_time) >= NOW()")
                ->orderBy('ride_date')
                ->orderBy('ride_time')
                ->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from_place' => 'required|string|max:100',
            'to_place'   => 'required|string|max:100',
            'ride_date'  => 'required|date|after_or_equal:today',
            'ride_time'  => ['required', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'seats'      => 'required|integer|min:0|max:20',
            'name'       => 'required|string|max:100',
            'contact'    => 'required|string|max:200',
            'comment'    => 'nullable|string|max:500',
        ]);

        // Normalize time to HH:MM (strip seconds if browser sent HH:MM:SS)
        $data['ride_time'] = substr($data['ride_time'], 0, 5);

        $ride = Ride::create($data);

        return response()->json($ride, 201);
    }
}
