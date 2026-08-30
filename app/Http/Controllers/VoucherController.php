<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\LedgerPostingException;
use App\Exceptions\VoucherValidationException;
use App\Http\Controllers\Concerns\EnforcesPlanLimits;
use App\Http\Requests\StoreVoucherRequest;
use App\Http\Requests\UpdateVoucherRequest;
use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\Transaction;
use App\Models\VoucherType;
use App\Services\PlanLimitService;
use App\Services\VoucherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VoucherController extends Controller
{
    use EnforcesPlanLimits;

    public function __construct(
        private readonly VoucherService $voucherService,
        private readonly PlanLimitService $planLimitService,
    ) {}

    // ---------------------------------------------------------------
    // Index
    // ---------------------------------------------------------------

    public function index(Request $request): View
    {
        abort_unless(
            auth()->user()?->can('vouchers.view'),
            403
        );

        $companyId = (int) session('company_id');

        $query = Transaction::query()
            ->with([
                'voucherType',
                'financialYear',
                'creator',
                'approver',
                'poster',
            ])
            ->where('company_id', $companyId)
            ->orderByDesc('id');

        if ($request->filled('voucher_number')) {
            $query->where(
                'voucher_number',
                'like',
                '%' . $request->input('voucher_number') . '%'
            );
        }

        if ($request->filled('voucher_type_id')) {
            $query->where(
                'voucher_type_id',
                (int) $request->input('voucher_type_id')
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->input('status')
            );
        }

        if ($request->filled('financial_year_id')) {
            $query->where(
                'financial_year_id',
                (int) $request->input('financial_year_id')
            );
        }

        if ($request->filled('date_from')) {
            $query->whereDate(
                'voucher_date',
                '>=',
                $request->input('date_from')
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'voucher_date',
                '<=',
                $request->input('date_to')
            );
        }

        if ($request->filled('reference_number')) {
            $query->where(
                'reference_number',
                'like',
                '%' . $request->input('reference_number') . '%'
            );
        }

        $vouchers = $query
            ->paginate(15)
            ->withQueryString();

        $voucherTypes = VoucherType::query()
            ->forCompany($companyId)
            ->active()
            ->orderBy('name')
            ->get();

        $financialYears = FinancialYear::query()
            ->where('company_id', $companyId)
            ->orderByDesc('start_date')
            ->get();

        return view(
            'vouchers.index',
            compact(
                'vouchers',
                'voucherTypes',
                'financialYears'
            )
        );
    }

    // ---------------------------------------------------------------
    // Create
    // ---------------------------------------------------------------

    public function create(): View
    {
        abort_unless(
            auth()->user()?->can('vouchers.create'),
            403
        );

        $companyId = (int) session('company_id');

        $voucherTypes = VoucherType::query()
            ->forCompany($companyId)
            ->active()
            ->orderBy('name')
            ->get();

        $financialYears = FinancialYear::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderByDesc('start_date')
            ->get();

        $accounts = Account::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('account_name')
            ->get();

        return view(
            'vouchers.create',
            compact(
                'voucherTypes',
                'financialYears',
                'accounts'
            )
        );
    }

    // ---------------------------------------------------------------
    // Store
    // ---------------------------------------------------------------

    public function store(
        StoreVoucherRequest $request
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->can('vouchers.create'),
            403
        );

        try {
            $data = $request->validated();

            $data['company_id'] = (int) session('company_id');

            // "journal_vouchers_monthly" covers every voucher created
            // through this controller (Payment/Receipt/Journal/Contra,
            // etc.) - VoucherType has no separate "is_journal" flag, and
            // this is the app's one generic transactional-entry mechanism,
            // consistent with how invoices/expenses/orders are each
            // capped per month elsewhere.
            $this->enforcePlanLimit(
                $this->planLimitService,
                $data['company_id'],
                'journal_vouchers_monthly',
                Transaction::where('company_id', $data['company_id'])
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
            );

            /*
             * New voucher can only be created as Draft
             * or submitted for approval.
             *
             * Direct posting is disabled.
             */
            $saveMode = $request->input(
                'save_mode',
                'draft'
            );

            if ($saveMode === 'submit') {
                abort_unless(
                    auth()->user()?->can('vouchers.submit'),
                    403
                );

                $transaction = $this->voucherService
                    ->createDraft($data);

                $transaction = $this->voucherService
                    ->submitForApproval($transaction);

                return redirect()
                    ->route(
                        'vouchers.show',
                        $transaction
                    )
                    ->with(
                        'success',
                        'Voucher submitted for approval successfully.'
                    );
            }

            /*
             * Legacy "post" value is intentionally treated
             * as submit-for-approval.
             *
             * This prevents direct Draft -> Posted.
             */
            if ($saveMode === 'post') {
                abort_unless(
                    auth()->user()?->can('vouchers.submit'),
                    403
                );

                $transaction = $this->voucherService
                    ->saveAndPost($data);

                return redirect()
                    ->route(
                        'vouchers.show',
                        $transaction
                    )
                    ->with(
                        'success',
                        'Voucher submitted for approval successfully.'
                    );
            }

            $transaction = $this->voucherService
                ->createDraft($data);

            return redirect()
                ->route(
                    'vouchers.show',
                    $transaction
                )
                ->with(
                    'success',
                    'Voucher saved as draft.'
                );

        } catch (VoucherValidationException $e) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );

        } catch (LedgerPostingException $e) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    // ---------------------------------------------------------------
    // Show
    // ---------------------------------------------------------------

    public function show(
        Transaction $transaction
    ): View {
        abort_unless(
            auth()->user()?->can('vouchers.view'),
            403
        );

        $this->authorizeCompanyAccess($transaction);

        $transaction->loadMissing([
            'details.account',
            'voucherType',
            'financialYear',
            'company',
            'creator',
            'approver',
            'poster',
        ]);

        return view(
            'vouchers.show',
            compact('transaction')
        );
    }

    // ---------------------------------------------------------------
    // Edit
    // ---------------------------------------------------------------

    public function edit(
        Transaction $transaction
    ): View {
        abort_unless(
            auth()->user()?->can('vouchers.edit'),
            403
        );

        $this->authorizeCompanyAccess($transaction);

        if (! $transaction->isDraft()) {
            abort(
                403,
                'Only draft vouchers can be edited.'
            );
        }

        $companyId = (int) session('company_id');

        $transaction->loadMissing([
            'details.account',
            'voucherType',
            'financialYear',
        ]);

        $voucherTypes = VoucherType::query()
            ->forCompany($companyId)
            ->active()
            ->orderBy('name')
            ->get();

        $financialYears = FinancialYear::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderByDesc('start_date')
            ->get();

        $accounts = Account::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('account_name')
            ->get();

        return view(
            'vouchers.edit',
            compact(
                'transaction',
                'voucherTypes',
                'financialYears',
                'accounts'
            )
        );
    }

    // ---------------------------------------------------------------
    // Update
    // ---------------------------------------------------------------

    public function update(
        UpdateVoucherRequest $request,
        Transaction $transaction
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->can('vouchers.edit'),
            403
        );

        $this->authorizeCompanyAccess($transaction);

        if (! $transaction->isDraft()) {
            return back()->with(
                'error',
                'Only draft vouchers can be updated.'
            );
        }

        try {
            $data = $request->validated();

            $data['company_id'] = (int) session('company_id');

            $transaction = $this->voucherService
                ->updateDraft(
                    $transaction,
                    $data
                );

            $saveMode = $request->input(
                'save_mode',
                'draft'
            );

            if ($saveMode === 'submit' || $saveMode === 'post') {
                abort_unless(
                    auth()->user()?->can('vouchers.submit'),
                    403
                );

                $transaction = $this->voucherService
                    ->submitForApproval($transaction);

                return redirect()
                    ->route(
                        'vouchers.show',
                        $transaction
                    )
                    ->with(
                        'success',
                        'Voucher submitted for approval successfully.'
                    );
            }

            return redirect()
                ->route(
                    'vouchers.show',
                    $transaction
                )
                ->with(
                    'success',
                    'Voucher updated successfully.'
                );

        } catch (VoucherValidationException $e) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );

        } catch (LedgerPostingException $e) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    // ---------------------------------------------------------------
    // Submit for Approval
    // ---------------------------------------------------------------

    public function submit(
    Transaction $transaction
): RedirectResponse {
    $this->authorizeCompanyAccess($transaction);

    abort_unless(
        auth()->user()?->can('vouchers.submit'),
        403
    );

    if (! $transaction->isDraft()) {
    
            return back()->with(
                'error',
                'Only draft vouchers can be submitted for approval.'
            );
        }

        try {
            $transaction = $this->voucherService
                ->submitForApproval($transaction);

            return redirect()
                ->route(
                    'vouchers.show',
                    $transaction
                )
                ->with(
                    'success',
                    'Voucher submitted for approval successfully.'
                );

        } catch (VoucherValidationException $e) {
            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }

    // ---------------------------------------------------------------
    // Approve
    // ---------------------------------------------------------------

    
    public function approve(
    Request $request,
    Transaction $transaction
): RedirectResponse {
    $this->authorizeCompanyAccess($transaction);

    abort_unless(
        auth()->user()?->can('vouchers.approve'),
        403
    );

    if (! $transaction->isSubmitted()) {
            return back()->with(
                'error',
                'Only submitted vouchers can be approved.'
            );
        }

        // ✅ Fix: Segregation of duties — যে user ভাউচার তৈরি/submit করেছে
        // (created_by), সে নিজেই সেটা approve করতে পারবে না, এমনকি তার
        // 'vouchers.approve' permission থাকলেও। এটা fraud/error prevention-এর
        // জন্য accounting-এ একটা স্ট্যান্ডার্ড control। Super Admin ছাড় পায়,
        // কারণ ছোট setup-এ কখনো কখনো একজনই সব করতে বাধ্য হয়।
        if ($transaction->created_by === auth()->id() && ! auth()->user()->isSuperAdmin()) {
            return back()->with(
                'error',
                'যিনি ভাউচার submit করেছেন তিনি নিজে সেটা approve করতে পারবেন না। অন্য কোনো authorized ব্যক্তির approval প্রয়োজন।'
            );
        }

        $request->validate([
            'approval_note' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        try {
            $transaction = $this->voucherService->approve(
                $transaction,
                $request->input('approval_note')
            );

            return redirect()
                ->route(
                    'vouchers.show',
                    $transaction
                )
                ->with(
                    'success',
                    'Voucher approved successfully.'
                );

        } catch (VoucherValidationException $e) {
            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }

    // ---------------------------------------------------------------
    // Post Approved Voucher
    // ---------------------------------------------------------------

    public function post(
    Transaction $transaction
): RedirectResponse {
    $this->authorizeCompanyAccess($transaction);

    abort_unless(
        auth()->user()?->can('vouchers.post'),
        403
    );

    if (! $transaction->isApproved()) {
    
            return back()->with(
                'error',
                'Only approved vouchers can be posted.'
            );
        }

        try {
            $transaction = $this->voucherService
                ->postApproved($transaction);

            return redirect()
                ->route(
                    'vouchers.show',
                    $transaction
                )
                ->with(
                    'success',
                    'Voucher posted to Ledger successfully.'
                );

        } catch (VoucherValidationException $e) {
            return back()->with(
                'error',
                $e->getMessage()
            );

        } catch (LedgerPostingException $e) {
            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }

    // ---------------------------------------------------------------
    // Cancel
    // ---------------------------------------------------------------

    public function cancel(
    Request $request,
    Transaction $transaction
): RedirectResponse {
    $this->authorizeCompanyAccess($transaction);

    abort_unless(
        auth()->user()?->can('vouchers.cancel'),
        403
    );

    $request->validate([
    
            'cancellation_reason' => [
                'required',
                'string',
                'min:5',
                'max:500',
            ],
        ]);

        if ($transaction->isCancelled()) {
            return back()->with(
                'error',
                'This voucher is already cancelled.'
            );
        }

        try {
            $this->voucherService->cancel(
                $transaction,
                (string) $request->input(
                    'cancellation_reason'
                )
            );

            return redirect()
                ->route(
                    'vouchers.show',
                    $transaction
                )
                ->with(
                    'success',
                    'Voucher cancelled successfully.'
                );

        } catch (VoucherValidationException $e) {
            return back()->with(
                'error',
                $e->getMessage()
            );

        } catch (LedgerPostingException $e) {
            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }

    // ---------------------------------------------------------------
    // Destroy
    // ---------------------------------------------------------------

   public function destroy(
    Transaction $transaction
): RedirectResponse {
    $this->authorizeCompanyAccess($transaction);

    abort_unless(
        auth()->user()?->can('vouchers.delete'),
        403
    );

    try {
    
            $this->voucherService
                ->deleteDraft($transaction);

            return redirect()
                ->route('vouchers.index')
                ->with(
                    'success',
                    'Draft voucher deleted successfully.'
                );

        } catch (VoucherValidationException $e) {
            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }

    // ---------------------------------------------------------------
    // Print
    // ---------------------------------------------------------------

    public function print(
        Transaction $transaction
    ): View {
        $this->authorizeCompanyAccess($transaction);

        abort_unless(
            auth()->user()?->can('vouchers.print'),
            403
        );

        $transaction->loadMissing([
            'details.account',
            'voucherType',
            'financialYear',
            'company',
            'creator',
            'approver',
            'poster',
        ]);

        return view(
            'vouchers.print',
            compact('transaction')
        );
    }

    // ---------------------------------------------------------------
    // PDF
    // ---------------------------------------------------------------

    public function downloadPdf(
        Transaction $transaction
    ) {
        $this->authorizeCompanyAccess($transaction);

        abort_unless(
            auth()->user()?->can('vouchers.print'),
            403
        );

        $transaction->loadMissing([
            'details.account',
            'voucherType',
            'financialYear',
            'company',
            'creator',
            'approver',
            'poster',
        ]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'vouchers.pdf',
            compact('transaction')
        )->setPaper('a4');

        return $pdf->download(
            $transaction->voucher_number . '.pdf'
        );
    }

    // ---------------------------------------------------------------
    // Company Isolation Guard
    // ---------------------------------------------------------------

    
    /**
     * Ensure the authenticated user can access the transaction's company.
     */
    private function authorizeCompanyAccess(Transaction $transaction): void
    {
        $user = auth()->user();

        abort_unless(
            $user !== null,
            403,
            'Unauthenticated.'
        );

        abort_unless(
            $user->canAccessCompany((int) $transaction->company_id),
            403,
            'You are not authorized to access this voucher.'
        );

        if (
            ! $user->isSuperAdmin()
            && (int) session('company_id') !== (int) $transaction->company_id
        ) {
            abort(
                403,
                'You are not authorized to access this voucher.'
            );
        }
    }

}