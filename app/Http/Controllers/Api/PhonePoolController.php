<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhonePool;
use App\Models\PhonePoolReturn;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PhonePoolController extends Controller
{
    /**
     * POST /api/phone-pool/assign
     * body: { "caller_id": "8095551234" }
     * Returns a phone number (caller_id) from the pool that matches the same area code as caller_id,
     * or, if not available, the closest area code numerically. Falls back to any active number.
     */
    public function assignFromPool(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'caller_id' => ['required', 'string', 'max:20'],
        ]);

        // Normalize: keep digits, use the last 10 digits as national number
        $digits = preg_replace('/\D+/', '', (string)$data['caller_id']);
        if (strlen($digits) < 10) {
            return response()->json(['message' => 'caller_id must contain at least 10 digits'], 422);
        }
        $national = substr($digits, -10);
        $targetArea = substr($national, 0, 3);

        // First, try exact area code match among active numbers
        $exact = PhonePool::query()
            ->where('active', true)
            ->where('area_code', $targetArea)
            ->orderByRaw('CASE WHEN last_assigned_date IS NULL THEN 0 ELSE 1 END ASC')
            ->orderBy('last_assigned_date', 'asc')
            ->first();

        $chosen = $exact;

        if (! $chosen) {
            // No exact match: find closest area code numerically among active numbers
            $active = PhonePool::query()
                ->where('active', true)
                ->get(['id', 'caller_id', 'area_code', 'last_assigned_date', 'last_assigned_campaign', 'user_id']);

            $best = null;
            $bestDist = PHP_INT_MAX;

            $targetNum = (int)$targetArea;

            foreach ($active as $rec) {
                // Ensure numeric area code
                $ac = preg_replace('/\D+/', '', (string)$rec->area_code);
                if ($ac === '') {
                    continue;
                }
                $dist = abs(((int)$ac) - $targetNum);

                if ($dist < $bestDist) {
                    $best = $rec;
                    $bestDist = $dist;
                } elseif ($dist === $bestDist) {
                    // Tie-breaker: least recently assigned
                    $bestAssigned = $best?->last_assigned_date;
                    $recAssigned = $rec->last_assigned_date;
                    if (($bestAssigned && $recAssigned && $recAssigned->lt($bestAssigned)) || ($bestAssigned && ! $recAssigned)) {
                        $best = $rec;
                    }
                }
            }

            $chosen = $best;
        }

        if (! $chosen) {
            return response()->json(['message' => 'No active phone numbers available in the pool'], 404);
        }

        // Optionally bump last_assigned_date to now to distribute load
        $chosen->last_assigned_date = Carbon::now();
        $chosen->save();
        $data = [
            'id' => $chosen->id,
            'caller_id' => $chosen->caller_id,
            'area_code' => $chosen->area_code,
            'active' => $chosen->active,
            'last_assigned_date' => $chosen->last_assigned_date?->toIso8601String(),
            'last_assigned_campaign' => $chosen->last_assigned_campaign,
            'user_id' => $chosen->user_id,
        ];
        PhonePoolReturn::create([
            'data' => json_encode($data),
        ]);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * POST /api/phone-pool/return
     * body: { "caller_id": "8095551234", "campaign_id": "optional" }
     * Marks a phone number as returned to the pool and records a return transaction.
     */
    public function returnToPool(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'caller_id' => ['required', 'string', 'max:20'],
            'campaign_id' => ['nullable', 'string', 'max:50'],
        ]);

        $digits = preg_replace('/\D+/', '', (string)$data['caller_id']);
        if (strlen($digits) < 10) {
            return response()->json(['message' => 'caller_id must contain at least 10 digits'], 422);
        }
        $national = substr($digits, -10);

        $record = PhonePool::query()->where('caller_id', $national)->first();
        if (! $record) {
            return response()->json(['message' => 'Phone not found in pool'], 404);
        }

        // Mark as active (available)
        $record->active = true;
        $record->save();

        // Save return transaction
        PhonePoolReturn::query()->create([
            'phone_pool_id' => $record->id,
            'caller_id'     => $national,
            'campaign_id'   => $data['campaign_id'] ?? null,
            'returned_at'   => Carbon::now(),
            'user_id'       => $user?->id,
        ]);

        return response()->json([
            'message' => 'Phone returned to pool.',
            'data'    => [
                'id' => $record->id,
                'caller_id' => $record->caller_id,
                'area_code' => $record->area_code,
                'active' => $record->active,
            ],
        ]);
    }
}
