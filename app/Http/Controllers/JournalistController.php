// app/Http/Controllers/JournalistController.php
public function bulkStore(BulkJournalistRequest $request): JsonResponse
{
    $companyId = auth()->user()->company_id;
    $userId    = auth()->id();
    $now       = now();

    $incoming = collect($request->input('journalists'));

    // DB-level duplicate code check
    $codes     = $incoming->pluck('code')->map(fn($c) => strtoupper(trim($c)));
    $existing  = Party::where('company_id', $companyId)
                    ->where('type', 'journalist')
                    ->whereIn('code', $codes)
                    ->pluck('code');

    if ($existing->isNotEmpty()) {
        return response()->json([
            'message'         => 'এই কোডগুলো ইতিমধ্যে বিদ্যমান।',
            'duplicate_codes' => $existing,
        ], 422);
    }

    $rows = $incoming->map(fn($j) => [
        'company_id'          => $companyId,
        'type'                => 'journalist',
        'name'                => trim($j['name']),
        'code'                => strtoupper(trim($j['code'])),
        'beat'                => $j['beat']               ?? null,
        'phone'               => $j['phone']              ?? null,
        'email'               => $j['email']              ?? null,
        'area'                => $j['district']           ?? null,
        'district'            => $j['district']           ?? null,
        'media_outlet'        => $j['media_outlet']       ?? null,
        'free_percent'        => $j['commission_percent'] ?? null,
        'commission_percent'  => $j['commission_percent'] ?? null,
        'opening_balance'     => $j['opening_balance']    ?? 0,
        'balance_type'        => $j['balance_type']       ?? 'receivable',
        'ar_account_id'       => $j['ar_account_id']      ?? null,
        'is_active'           => $j['is_active']          ?? true,
        'created_by'          => $userId,
        'created_at'          => $now,
        'updated_at'          => $now,
    ])->toArray();

    try {
        DB::transaction(function () use ($rows) {
            foreach (array_chunk($rows, 500) as $chunk) {
                Party::insert($chunk);
            }
        });
    } catch (Throwable $e) {
        report($e);
        return response()->json(['message' => 'Save করতে সমস্যা হয়েছে।'], 500);
    }

    return response()->json([
        'message' => count($rows) . ' জন সাংবাদিক সফলভাবে যোগ করা হয়েছে।',
        'count'   => count($rows),
    ], 201);
}