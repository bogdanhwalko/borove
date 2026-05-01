<?php

namespace App\Http\Controllers;

use App\Models\Ride;
use App\Services\UserRatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RideController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = optional($request->user())->id;

        $query = Ride::whereRaw("CONCAT(ride_date, ' ', ride_time) >= NOW()")
            ->where(function ($q) use ($userId) {
                $q->where('status', 'published');
                if ($userId) $q->orWhere('user_id', $userId);
            })
            ->orderBy('ride_date')
            ->orderBy('ride_time');

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from_place' => 'required|string|max:100',
            'to_place'   => 'required|string|max:100',
            'ride_date'  => 'required|date|after_or_equal:today',
            'ride_time'  => ['required', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'seats'      => 'required|integer|min:0|max:20',
            'comment'    => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        // Derive name and contact from authenticated user's profile (not user-editable)
        // Format: "Прізвище Ім'я (нікнейм)"
        $fullName = trim(($user->last_name ?? '') . ' ' . ($user->first_name ?? ''));
        if ($fullName !== '') {
            $name = $user->nickname ? $fullName . ' (' . $user->nickname . ')' : $fullName;
        } else {
            $name = $user->nickname ?: 'Користувач';
        }
        $contact  = $user->phone ? '+380' . substr($user->phone, 1) : '';

        if (!$contact) {
            return response()->json(['message' => 'У вашому профілі не вказано телефон. Заповніть профіль перед створенням поїздки.'], 422);
        }

        // Normalize time to HH:MM (strip seconds if browser sent HH:MM:SS)
        $data['ride_time'] = substr($data['ride_time'], 0, 5);
        $data['name']      = mb_substr($name, 0, 100);
        $data['contact']   = $contact;
        $data['user_id']   = $user->id;
        $data['status']    = app(UserRatingService::class)->statusForRide($user);

        $ride = Ride::create($data);

        return response()->json($ride, 201);
    }

    public function updateSeats(Request $request, int $id): JsonResponse
    {
        $ride = Ride::findOrFail($id);
        if ($ride->user_id !== $request->user()->id && !$request->user()->is_admin) {
            abort(403);
        }
        $data = $request->validate([
            'seats' => 'required|integer|min:0|max:20',
        ]);
        $ride->update($data);
        return response()->json($ride);
    }
}
