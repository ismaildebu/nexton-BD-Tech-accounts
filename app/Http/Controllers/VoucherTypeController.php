<?php

namespace App\Http\Controllers;

use App\Models\VoucherType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class VoucherTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   
    public function index()
{
    $voucherTypes = VoucherType::with('company')
        ->where('company_id', session('company_id'))
        ->latest()
        ->get();

    return view('voucher-types.index', compact('voucherTypes'));
}

    /**
     * Show the form for creating a new resource.
     */
   
    public function create()
{
    return view('voucher-types.create');
}

    /**
     * Store a newly created resource in storage.
     */
  
    public function store(Request $request)
{
    $companyId = session('company_id');

    $request->validate([
        'name'   => 'required|string|max:255',
        'code'   => [
            'required',
            'string',
            'max:20',
            Rule::unique('voucher_types', 'code')->where('company_id', $companyId),
        ],
        'nature' => 'required|in:journal,payment,receipt,contra,opening',
    ]);

    VoucherType::create([
        'company_id' => $companyId,
        'name'       => $request->name,
        'code'       => strtoupper($request->code),
        'nature'     => $request->nature,
        'is_active'  => $request->has('is_active'),
    ]);

    return redirect()
        ->route('voucher-types.index')
        ->with('success', 'Voucher Type created successfully.');
}

    /**
     * Display the specified resource.
     */
   
    public function show(VoucherType $voucherType)
{
    $voucherType->load('company');

    return view('voucher-types.show', compact('voucherType'));
}

    /**
     * Show the form for editing the specified resource.
     */
   
    public function edit(VoucherType $voucherType)
{
    return view('voucher-types.edit', compact('voucherType'));
}

    /**
     * Update the specified resource in storage.
     */
   public function update(Request $request, VoucherType $voucherType)
{
    $companyId = session('company_id');

    $request->validate([
        'name'   => 'required|string|max:255',
        'code'   => [
            'required',
            'string',
            'max:20',
            Rule::unique('voucher_types', 'code')->where('company_id', $companyId)->ignore($voucherType->id),
        ],
        'nature' => 'required|in:journal,payment,receipt,contra,opening',
    ]);

    $voucherType->update([
        'name'       => $request->name,
        'code'       => strtoupper($request->code),
        'nature'     => $request->nature,
        'is_active'  => $request->has('is_active'),
    ]);

    return redirect()
        ->route('voucher-types.index')
        ->with('success', 'Voucher Type updated successfully.');
}

    /**
     * Remove the specified resource from storage.
     */
   
    public function destroy(VoucherType $voucherType)
{
    $voucherType->delete();

    return redirect()
        ->route('voucher-types.index')
        ->with('success', 'Voucher Type deleted successfully.');
}
}