Route::get('/account-templates', function (Illuminate\Http\Request $request) {
    $businessType = $request->query('business_type');

    if (!$businessType) {
        return response()->json(['templates' => []]);
    }

    $templates = \App\Models\AccountTemplate::where('is_active', true)
        ->where('business_type', $businessType)
        ->orderBy('account_code')
        ->select('id', 'account_code', 'account_name', 'account_type')
        ->get();

    return response()->json(['templates' => $templates]);
})->name('account-templates.index');