<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkPartyRequest;
use App\Models\Party;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class PartyController extends Controller
{
    public function bulkStore(BulkPartyRequest $request): JsonResponse
    {
        $now        = now();
        $companyId  = auth()->user()->company_id; // multi-tenant
        $userId     = auth()->id();

        $agentsData  = $this->prepareRows($request->input('agents', []),  'agent',  $companyId, $userId, $now);
        $hawkersData = $this->prepareRows($request->input('hawkers', []), 'hawker', $companyId, $userId, $now);

        $allRows = array_merge($agentsData, $hawkersData);

        if (empty($allRows)) {
            return response()->json(['message' => 'কোনো party দেওয়া হয়নি।'], 422);
        }

        // Code uniqueness check against DB (cross-company safe)
        $incomingCodes = array_column($allRows, 'code');
        $existingCodes = Party::where('company_id', $companyId)
            ->whereIn('code', $incomingCodes)
            ->pluck('code')
            ->toArray();

        if (!empty($existingCodes)) {
            return response()->json([
                'message'         => 'কিছু কোড ইতিমধ্যে বিদ্যমান।',
                'duplicate_codes' => $existingCodes,
            ], 422);
        }

        try {
            DB::transaction(function () use ($allRows) {
                // Chunk করে insert — large datasets safe
                foreach (array_chunk($allRows, 500) as $chunk) {
                    Party::insert($chunk);
                }
            });
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Save করতে সমস্যা হয়েছে। আবার চেষ্টা করুন।'], 500);
        }

        return response()->json([
            'message' => count($allRows) . 'টি party সফলভাবে যোগ করা হয়েছে।',
            'counts'  => [
                'agents'  => count($agentsData),
                'hawkers' => count($hawkersData),
            ],
        ], 201);
    }

    private function prepareRows(array $rows, string $type, int $companyId, int $userId, $now): array
    {
        return array_map(fn($row) => [
            'company_id'       => $companyId,
            'type'             => $type,
            'name'             => trim($row['name']),
            'code'             => strtoupper(trim($row['code'])),
            'phone'            => $row['phone']       ?? null,
            'alt_phone'        => $row['alt_phone']    ?? null,
            'area'             => $row['area']         ?? null,
            'free_percent'     => $row['free_percent'] ?? null,
            'opening_balance'  => $row['opening_balance'] ?? 0,
            'balance_type'     => $row['balance_type'] ?? 'receivable',
            'ar_account_id'    => $row['ar_account_id'] ?? null,
            'is_active'        => true,
            'created_by'       => $userId,
            'created_at'       => $now,
            'updated_at'       => $now,
        ], $rows);
    }
}