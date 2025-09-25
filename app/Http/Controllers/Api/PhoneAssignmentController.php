<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignPhoneRequest;
use App\Models\PhonePool;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PhoneAssignmentController extends Controller
{
    public function assign(AssignPhoneRequest $request)
    {
        $user     = $request->user(); // Sanctum user
        $campaign = (string)$request->input('campaign_id');

        // Ability check (optional but recommended)
        if (! $user->tokenCan('assign:phone')) {
            return response()->json(['message' => 'Token lacks ability: assign:phone'], 403);
        }

        $callerId = (string)($request->input('caller_id') ?? $request->input('caller_id_guess') ?? '');
        $areaCode = (string)($request->input('area_code') ?? $request->input('area_code_guess') ?? '');

        $record = PhonePool::query()->updateOrCreate(
            ['caller_id' => substr($callerId, -10)],
            [
                'area_code'              => $areaCode ?: null,
                'active'                 => true,
                'last_assigned_date'     => Carbon::now(),
                'last_assigned_campaign' => $campaign,
                'user_id'                => $user->id,
            ]
        );

        return response()->json([
            'message' => 'Phone recorded & assigned.',
            'data'    => [
                'id'                     => $record->id,
                'caller_id'              => $record->caller_id,
                'area_code'              => $record->area_code,
                'active'                 => $record->active,
                'last_assigned_date'     => $record->last_assigned_date?->toIso8601String(),
                'last_assigned_campaign' => $record->last_assigned_campaign,
                'user_id'                => $record->user_id,
            ],
        ]);
    }

    public function show(Request $request, string $callerId)
    {
        $record = PhonePool::query()->where('caller_id', $callerId)->first();

        if (! $record) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        // Optional: authorization check if you want to restrict per owner (user_id)
        return response()->json(['data' => $record]);
    }
}
