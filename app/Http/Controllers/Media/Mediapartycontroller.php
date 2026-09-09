<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\BulkStoreJournalistRequest;
use App\Http\Requests\Media\BulkStoreMediaPartyRequest;
use App\Http\Requests\Media\StoreMediaPartyRequest;
use App\Http\Requests\Media\UpdateMediaPartyRequest;
use App\Models\Account;
use App\Models\MediaParty;
use App\Models\Publication;
use App\Services\Media\FreePercentageResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Common interface for Agent, Hawker, and Journalist — distinguished only
 * by `type`. There is NO relationship between types anywhere in this controller.
 */
class MediaPartyController extends Controller
{
    public function __construct(private readonly FreePercentageResolver $freePercentageResolver)
    {
    }

    // ---------------------------------------------------------------
    // Standard CRUD (unchanged)
    // ---------------------------------------------------------------

    public function index(Request $request)
    {
        $parties = MediaParty::query()
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->query('type')))
            ->orderBy('name')
            ->get();

        return view('media.parties.index', compact('parties'));
    }

    public function create()
    {
        $accounts = $this->arAccounts();

        return view('media.parties.create', compact('accounts'));
    }

    public function store(StoreMediaPartyRequest $request)
    {
        $party = MediaParty::create($request->validated());

        return redirect()->route('media.parties.show', $party)
            ->with('success', 'Party created!');
    }

    public function show(MediaParty $mediaParty)
    {
        $this->authorize('view', $mediaParty);

        $effectiveFreePercentages = Publication::active()->get()->map(function (Publication $publication) use ($mediaParty) {
            return [
                'publication' => $publication,
                'percentage'  => $this->freePercentageResolver->resolve($mediaParty, $publication),
                'source'      => $this->freePercentageResolver->source($mediaParty, $publication),
            ];
        });

        return view('media.parties.show', [
            'party'                    => $mediaParty,
            'effectiveFreePercentages' => $effectiveFreePercentages,
        ]);
    }

    public function edit(MediaParty $mediaParty)
    {
        $this->authorize('update', $mediaParty);
        $accounts = $this->arAccounts();

        return view('media.parties.edit', ['party' => $mediaParty, 'accounts' => $accounts]);
    }

    public function update(UpdateMediaPartyRequest $request, MediaParty $mediaParty)
    {
        $this->authorize('update', $mediaParty);
        $mediaParty->update($request->validated());

        return redirect()->route('media.parties.show', $mediaParty)
            ->with('success', 'Party updated!');
    }

    public function destroy(MediaParty $mediaParty)
    {
        $this->authorize('delete', $mediaParty);
        $mediaParty->delete();

        return redirect()->route('media.parties.index')
            ->with('success', 'Party deleted!');
    }

    // ---------------------------------------------------------------
    // Bulk — Agent + Hawker
    // ---------------------------------------------------------------

    public function bulkCreate()
    {
        $accounts = $this->arAccounts();

        return view('media.parties.bulk-create', compact('accounts'));
    }

    public function bulkStore(BulkStoreMediaPartyRequest $request): JsonResponse
    {
        $companyId = session('company_id');
        $userId    = auth()->id();
        $now       = now();

        $agentRows  = $this->prepareRows($request->input('agents',  []), 'agent',  $companyId, $userId, $now);
        $hawkerRows = $this->prepareRows($request->input('hawkers', []), 'hawker', $companyId, $userId, $now);
        $allRows    = array_merge($agentRows, $hawkerRows);

        if (empty($allRows)) {
            return response()->json(['message' => 'কোনো party দেওয়া হয়নি।'], 422);
        }

        // DB-level duplicate code check (cross-request, company-scoped)
        $codes    = array_column($allRows, 'code');
        $existing = MediaParty::where('company_id', $companyId)
            ->whereIn('code', $codes)
            ->whereNull('deleted_at')
            ->pluck('code')
            ->toArray();

        if (!empty($existing)) {
            return response()->json([
                'message'         => 'কিছু কোড ইতিমধ্যে বিদ্যমান।',
                'duplicate_codes' => $existing,
            ], 422);
        }

        try {
            DB::transaction(function () use ($allRows) {
                foreach (array_chunk($allRows, 500) as $chunk) {
                    MediaParty::insert($chunk);
                }
            });
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Save করতে সমস্যা হয়েছে। আবার চেষ্টা করুন।'], 500);
        }

        return response()->json([
            'message' => count($allRows) . 'টি party সফলভাবে যোগ করা হয়েছে।',
            'counts'  => [
                'agents'  => count($agentRows),
                'hawkers' => count($hawkerRows),
            ],
        ], 201);
    }

    // ---------------------------------------------------------------
    // Bulk — Journalist
    // ---------------------------------------------------------------

    public function journalistBulkCreate()
    {
        $accounts = $this->arAccounts();

        return view('media.parties.journalist-bulk-create', compact('accounts'));
    }

    public function journalistBulkStore(BulkStoreJournalistRequest $request): JsonResponse
    {
        $companyId = session('company_id');
        $userId    = auth()->id();
        $now       = now();

        $rows = collect($request->input('journalists'))->map(fn($j) => [
            'company_id'          => $companyId,
            'type'                => 'journalist',
            'name'                => trim($j['name']),
            'code'                => strtoupper(trim($j['code'])),
            'beat'                => $j['beat']               ?? null,
            'phone'               => $j['phone']              ?? null,
            'email'               => $j['email']              ?? null,
            'alternate_phone'     => $j['alternate_phone']    ?? null,
            'area'                => $j['area']               ?? null,
            'district'            => $j['district']           ?? null,
            'media_outlet'        => $j['media_outlet']       ?? null,
            'free_percentage'     => $j['free_percentage']    ?? 0,
            'commission_percent'  => $j['commission_percent'] ?? null,
            'opening_balance'     => $j['opening_balance']    ?? 0,
            'balance_type'        => $j['balance_type']       ?? 'Receivable',
            'account_id'          => $j['account_id']         ?? null,
            'is_active'           => $j['is_active']          ?? true,
            'created_by'          => $userId,
            'created_at'          => $now,
            'updated_at'          => $now,
        ])->toArray();

        // DB-level duplicate code check
        $codes    = array_column($rows, 'code');
        $existing = MediaParty::where('company_id', $companyId)
            ->whereIn('code', $codes)
            ->whereNull('deleted_at')
            ->pluck('code')
            ->toArray();

        if (!empty($existing)) {
            return response()->json([
                'message'         => 'কিছু কোড ইতিমধ্যে বিদ্যমান।',
                'duplicate_codes' => $existing,
            ], 422);
        }

        try {
            DB::transaction(function () use ($rows) {
                foreach (array_chunk($rows, 500) as $chunk) {
                    MediaParty::insert($chunk);
                }
            });
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Save করতে সমস্যা হয়েছে। আবার চেষ্টা করুন।'], 500);
        }

        return response()->json([
            'message' => count($rows) . ' জন সাংবাদিক সফলভাবে যোগ করা হয়েছে।',
            'count'   => count($rows),
        ], 201);
    }

    // ---------------------------------------------------------------
    // Private helpers
    // ---------------------------------------------------------------

    private function arAccounts()
    {
        return Account::query()
            ->where('company_id', session('company_id'))
            ->where('is_active', true)
            ->where('account_type', 'Asset')
            ->where('nature', 'Customer')
            ->orderBy('account_name')
            ->get();
    }

    private function prepareRows(array $rows, string $type, int $companyId, int $userId, $now): array
    {
        return array_map(fn($row) => [
            'company_id'      => $companyId,
            'type'            => $type,
            'name'            => trim($row['name']),
            'code'            => strtoupper(trim($row['code'])),
            'phone'           => $row['phone']           ?? null,
            'alternate_phone' => $row['alternate_phone'] ?? null,
            'address'         => $row['address']         ?? null,
            'area'            => $row['area']            ?? null,
            'free_percentage' => $row['free_percentage'] ?? 0,
            'opening_balance' => $row['opening_balance'] ?? 0,
            'balance_type'    => $row['balance_type']    ?? 'Receivable',
            'account_id'      => $row['account_id']      ?? null,
            'is_active'       => $row['is_active']       ?? true,
            'created_by'      => $userId,
            'created_at'      => $now,
            'updated_at'      => $now,
        ], $rows);
    }
}
