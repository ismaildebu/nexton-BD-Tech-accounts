@extends('layouts.app')

@section('content')

<style>
    /* =========================================================
       SETTINGS PAGE
    ========================================================== */

    .settings-page {
        max-width: 1400px;
        margin: 0 auto;
        padding: 28px;
        background:
            radial-gradient(circle at top left, rgba(99, 102, 241, .08), transparent 28%),
            radial-gradient(circle at top right, rgba(14, 165, 233, .07), transparent 25%);
    }

    /* =========================================================
       HEADER
    ========================================================== */

    .settings-header {
        position: relative;
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 28px;
        padding: 22px 24px;
        border-radius: 18px;
        background: linear-gradient(
            135deg,
            #ffffff 0%,
            #f8faff 55%,
            #f5f3ff 100%
        );
        border: 1px solid #e5e7eb;
        box-shadow: 0 8px 28px rgba(30, 41, 59, .06);
        overflow: hidden;
    }

    .settings-header::after {
        content: "";
        position: absolute;
        width: 180px;
        height: 180px;
        right: -70px;
        top: -90px;
        border-radius: 50%;
        background: linear-gradient(
            135deg,
            rgba(99, 102, 241, .13),
            rgba(14, 165, 233, .08)
        );
    }

    .settings-header-icon {
        position: relative;
        z-index: 1;
        width: 54px;
        height: 54px;
        border-radius: 15px;
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
        box-shadow: 0 8px 18px rgba(79, 70, 229, .24);
    }

    .settings-header h1 {
        position: relative;
        z-index: 1;
        margin: 0 0 4px;
        font-size: 27px;
        font-weight: 750;
        letter-spacing: -.4px;
        color: #172033;
    }

    .settings-header p {
        position: relative;
        z-index: 1;
        margin: 0;
        color: #7b8494;
        font-size: 14px;
    }


    /* =========================================================
       ALERTS
    ========================================================== */

    .settings-alert {
        padding: 14px 17px;
        border-radius: 12px;
        margin-bottom: 22px;
        font-size: 14px;
        box-shadow: 0 4px 12px rgba(20, 30, 50, .04);
    }

    .settings-alert.success {
        background: linear-gradient(135deg, #ecfdf5, #f0fdf4);
        border: 1px solid #bbf7d0;
        color: #166534;
    }

    .settings-alert.danger {
        background: linear-gradient(135deg, #fef2f2, #fff7f7);
        border: 1px solid #fecaca;
        color: #991b1b;
    }

    .settings-alert ul {
        margin: 7px 0 0 20px;
        padding: 0;
    }


    /* =========================================================
       GRID
    ========================================================== */

    .settings-grid {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr);
        gap: 22px;
        align-items: start;
    }


    /* =========================================================
       CARDS
    ========================================================== */

    .settings-card {
        position: relative;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        box-shadow: 0 7px 25px rgba(20, 30, 50, .055);
        overflow: hidden;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .settings-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 32px rgba(20, 30, 50, .09);
    }


    /* =========================================================
       COMPANY CARD
    ========================================================== */

    .settings-card:first-child {
        border-top: 3px solid #6366f1;
    }


    /* =========================================================
       ACCOUNTING CARD
    ========================================================== */

    .settings-card:last-child {
        border-top: 3px solid #10b981;
    }


    /* =========================================================
       CARD HEADER
    ========================================================== */

    .settings-card-header {
        padding: 21px 24px;
        border-bottom: 1px solid #edf0f4;
        display: flex;
        align-items: center;
        gap: 13px;
    }

    .settings-card:first-child .settings-card-header {
        background: linear-gradient(
            135deg,
            #f8faff,
            #f5f3ff
        );
    }

    .settings-card:last-child .settings-card-header {
        background: linear-gradient(
            135deg,
            #f0fdf9,
            #ecfdf5
        );
    }

    .settings-card-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 20px;
    }

    .settings-card-icon.company {
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        color: #ffffff;
        box-shadow: 0 6px 14px rgba(79, 70, 229, .20);
    }

    .settings-card-icon.accounting {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #ffffff;
        box-shadow: 0 6px 14px rgba(5, 150, 105, .20);
    }

    .settings-card-header h2 {
        margin: 0 0 3px;
        font-size: 17px;
        font-weight: 700;
        color: #1f2937;
    }

    .settings-card-header p {
        margin: 0;
        color: #8a93a2;
        font-size: 13px;
    }

    .settings-card-body {
        padding: 24px;
    }


    /* =========================================================
       FORM GRID
    ========================================================== */

    .settings-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .settings-field.full {
        grid-column: 1 / -1;
    }


    /* =========================================================
       LABEL
    ========================================================== */

    .settings-label {
        display: block;
        margin-bottom: 7px;
        color: #374151;
        font-size: 13px;
        font-weight: 650;
    }

    .required {
        color: #ef4444;
        margin-left: 2px;
    }


    /* =========================================================
       INPUT
    ========================================================== */

    .settings-input-wrap {
        position: relative;
    }

    .settings-input-icon {
        position: absolute;
        left: 13px;
        top: 50%;
        transform: translateY(-50%);
        color: #9aa3b2;
        font-size: 15px;
        pointer-events: none;
        line-height: 1;
        transition: color .18s ease;
    }

    .settings-input,
    .settings-textarea {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid #dce1e8;
        border-radius: 10px;
        background: #ffffff;
        color: #202938;
        font-size: 14px;
        outline: none;
        transition:
            border-color .18s ease,
            box-shadow .18s ease,
            background .18s ease;
    }

    .settings-input {
        height: 45px;
        padding: 0 13px 0 39px;
    }

    .settings-textarea {
        min-height: 105px;
        padding: 12px 13px 12px 39px;
        resize: vertical;
        font-family: inherit;
        line-height: 1.5;
    }

    .settings-input:hover,
    .settings-textarea:hover {
        border-color: #b9c2d0;
    }

    .settings-input::placeholder,
    .settings-textarea::placeholder {
        color: #a4acb8;
    }

    .settings-input:focus,
    .settings-textarea:focus {
        border-color: #6366f1;
        background: #ffffff;
        box-shadow:
            0 0 0 3px rgba(99, 102, 241, .11),
            0 4px 12px rgba(99, 102, 241, .05);
    }

    .settings-input-wrap:focus-within .settings-input-icon {
        color: #6366f1;
    }

    .settings-input.is-invalid,
    .settings-textarea.is-invalid {
        border-color: #ef4444;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, .07);
    }


    /* =========================================================
       ERROR
    ========================================================== */

    .settings-error {
        margin-top: 5px;
        color: #dc2626;
        font-size: 12px;
    }


    /* =========================================================
       HELP BOX
    ========================================================== */

    .settings-help {
        margin-top: 22px;
        padding: 15px;
        border-radius: 12px;
        background: linear-gradient(
            135deg,
            #fffaf0,
            #fffbeb
        );
        border: 1px solid #fde68a;
        display: flex;
        gap: 10px;
        color: #6b5b2a;
        font-size: 13px;
        line-height: 1.55;
    }

    .settings-help-icon {
        color: #d97706;
        font-weight: 700;
        flex-shrink: 0;
        font-size: 16px;
    }


    /* =========================================================
       ACCOUNTING FIELD HIGHLIGHT
    ========================================================== */

    .settings-card:last-child .settings-field {
        position: relative;
    }

    .settings-card:last-child .settings-input:focus {
        border-color: #10b981;
        box-shadow:
            0 0 0 3px rgba(16, 185, 129, .11),
            0 4px 12px rgba(16, 185, 129, .05);
    }

    .settings-card:last-child
    .settings-input-wrap:focus-within
    .settings-input-icon {
        color: #059669;
    }


    /* =========================================================
       ACTION BAR
    ========================================================== */

    .settings-actions {
        margin-top: 22px;
        background: linear-gradient(
            135deg,
            #ffffff,
            #f8faff
        );
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 6px 22px rgba(20, 30, 50, .05);
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
    }

    .settings-security {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #7b8494;
        font-size: 13px;
    }

    .settings-security-icon {
        width: 23px;
        height: 23px;
        border-radius: 50%;
        background: #dcfce7;
        color: #16a34a;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
    }


    /* =========================================================
       SAVE BUTTON
    ========================================================== */

    .settings-save-btn {
        border: 0;
        border-radius: 10px;
        background: linear-gradient(
            135deg,
            #6366f1,
            #4f46e5
        );
        color: #ffffff;
        min-height: 44px;
        padding: 0 23px;
        font-size: 14px;
        font-weight: 650;
        cursor: pointer;
        transition:
            transform .18s ease,
            box-shadow .18s ease,
            background .18s ease;
        box-shadow:
            0 5px 14px rgba(79, 70, 229, .24);
    }

    .settings-save-btn:hover {
        background: linear-gradient(
            135deg,
            #4f46e5,
            #4338ca
        );
        transform: translateY(-2px);
        box-shadow:
            0 8px 18px rgba(79, 70, 229, .30);
    }

    .settings-save-btn:active {
        transform: translateY(0);
        box-shadow:
            0 4px 10px rgba(79, 70, 229, .20);
    }


    /* =========================================================
       MOBILE
    ========================================================== */

    @media (max-width: 1100px) {

        .settings-grid {
            grid-template-columns: 1fr;
        }

    }


    @media (max-width: 700px) {

        .settings-page {
            padding: 18px 14px;
        }

        .settings-header {
            padding: 18px;
            border-radius: 14px;
        }

        .settings-header-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            font-size: 20px;
        }

        .settings-header h1 {
            font-size: 23px;
        }

        .settings-form-grid {
            grid-template-columns: 1fr;
        }

        .settings-field.full {
            grid-column: auto;
        }

        .settings-card-body {
            padding: 18px;
        }

        .settings-card-header {
            padding: 18px;
        }

        .settings-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .settings-security {
            justify-content: center;
            text-align: center;
        }

        .settings-save-btn {
            width: 100%;
        }

    }


    @media (max-width: 420px) {

        .settings-header {
            gap: 12px;
        }

        .settings-header p {
            font-size: 12px;
        }

        .settings-card-header h2 {
            font-size: 16px;
        }

    }

</style>


<div class="settings-page">

    {{-- =========================================================
         Header
    ========================================================== --}}
    <div class="settings-header">

        <div class="settings-header-icon">
            ⚙
        </div>

        <div>
            <h1>Settings</h1>

            <p>
                Manage your company information and accounting preferences.
            </p>
        </div>

    </div>


    {{-- =========================================================
         Success Message
    ========================================================== --}}
    @if(session('success'))

        <div class="settings-alert success">
            ✓ &nbsp; {{ session('success') }}
        </div>

    @endif


    {{-- =========================================================
         Validation Errors
    ========================================================== --}}
    @if($errors->any())

        <div class="settings-alert danger">

            <strong>Please correct the following errors:</strong>

            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

        </div>

    @endif


    <form
        method="POST"
        action="{{ route('settings.update') }}"
    >

        @csrf
        @method('PUT')


        {{-- =====================================================
             Main Grid
        ====================================================== --}}
        <div class="settings-grid">


            {{-- =================================================
                 Company Information
            ================================================== --}}
            <div class="settings-card">

                <div class="settings-card-header">

                    <div class="settings-card-icon company">
                        🏢
                    </div>

                    <div>
                        <h2>Company Information</h2>

                        <p>
                            Basic information about your company.
                        </p>
                    </div>

                </div>


                <div class="settings-card-body">

                    <div class="settings-form-grid">


                        {{-- Company Name --}}
                        <div class="settings-field full">

                            <label
                                for="company_name"
                                class="settings-label"
                            >
                                Company Name
                                <span class="required">*</span>
                            </label>

                            <div class="settings-input-wrap">

                                <span class="settings-input-icon">
                                    🏢
                                </span>

                                <input
                                    type="text"
                                    id="company_name"
                                    name="company_name"
                                    class="settings-input @error('company_name') is-invalid @enderror"
                                    value="{{ old('company_name', $company->company_name) }}"
                                    maxlength="255"
                                    placeholder="Enter company name"
                                    required
                                >

                            </div>

                            @error('company_name')
                                <div class="settings-error">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- Owner --}}
                        <div class="settings-field">

                            <label
                                for="owner_name"
                                class="settings-label"
                            >
                                Owner Name
                            </label>

                            <div class="settings-input-wrap">

                                <span class="settings-input-icon">
                                    👤
                                </span>

                                <input
                                    type="text"
                                    id="owner_name"
                                    name="owner_name"
                                    class="settings-input @error('owner_name') is-invalid @enderror"
                                    value="{{ old('owner_name', $company->owner_name) }}"
                                    maxlength="255"
                                    placeholder="Enter owner name"
                                >

                            </div>

                            @error('owner_name')
                                <div class="settings-error">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- Email --}}
                        <div class="settings-field">

                            <label
                                for="email"
                                class="settings-label"
                            >
                                Email
                            </label>

                            <div class="settings-input-wrap">

                                <span class="settings-input-icon">
                                    ✉
                                </span>

                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    class="settings-input @error('email') is-invalid @enderror"
                                    value="{{ old('email', $company->email) }}"
                                    maxlength="255"
                                    placeholder="company@example.com"
                                >

                            </div>

                            @error('email')
                                <div class="settings-error">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- Phone --}}
                        <div class="settings-field">

                            <label
                                for="phone"
                                class="settings-label"
                            >
                                Phone
                            </label>

                            <div class="settings-input-wrap">

                                <span class="settings-input-icon">
                                    ☎
                                </span>

                                <input
                                    type="text"
                                    id="phone"
                                    name="phone"
                                    class="settings-input @error('phone') is-invalid @enderror"
                                    value="{{ old('phone', $company->phone) }}"
                                    maxlength="50"
                                    placeholder="Enter phone number"
                                >

                            </div>

                            @error('phone')
                                <div class="settings-error">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- City --}}
                        <div class="settings-field">

                            <label
                                for="city"
                                class="settings-label"
                            >
                                City
                            </label>

                            <div class="settings-input-wrap">

                                <span class="settings-input-icon">
                                    ⌖
                                </span>

                                <input
                                    type="text"
                                    id="city"
                                    name="city"
                                    class="settings-input @error('city') is-invalid @enderror"
                                    value="{{ old('city', $company->city) }}"
                                    maxlength="100"
                                    placeholder="Enter city"
                                >

                            </div>

                            @error('city')
                                <div class="settings-error">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- Country --}}
                        <div class="settings-field">

                            <label
                                for="country"
                                class="settings-label"
                            >
                                Country
                            </label>

                            <div class="settings-input-wrap">

                                <span class="settings-input-icon">
                                    ◎
                                </span>

                                <input
                                    type="text"
                                    id="country"
                                    name="country"
                                    class="settings-input @error('country') is-invalid @enderror"
                                    value="{{ old('country', $company->country) }}"
                                    maxlength="100"
                                    placeholder="Enter country"
                                >

                            </div>

                            @error('country')
                                <div class="settings-error">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- Address --}}
                        <div class="settings-field full">

                            <label
                                for="address"
                                class="settings-label"
                            >
                                Address
                            </label>

                            <div class="settings-input-wrap">

                                <span
                                    class="settings-input-icon"
                                    style="top: 20px;"
                                >
                                    ⌖
                                </span>

                                <textarea
                                    id="address"
                                    name="address"
                                    class="settings-textarea @error('address') is-invalid @enderror"
                                    maxlength="1000"
                                    placeholder="Enter company address"
                                >{{ old('address', $company->address) }}</textarea>

                            </div>

                            @error('address')
                                <div class="settings-error">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                    </div>

                </div>

            </div>


            {{-- =================================================
                 Accounting Preferences
            ================================================== --}}
            <div class="settings-card">

                <div class="settings-card-header">

                    <div class="settings-card-icon accounting">
                        💰
                    </div>

                    <div>
                        <h2>Accounting Preferences</h2>

                        <p>
                            Configure your accounting currency.
                        </p>
                    </div>

                </div>


                <div class="settings-card-body">


                    {{-- Currency --}}
                    <div class="settings-field">

                        <label
                            for="currency"
                            class="settings-label"
                        >
                            Currency
                            <span class="required">*</span>
                        </label>

                        <div class="settings-input-wrap">

                            <span class="settings-input-icon">
                                ¤
                            </span>

                            <input
                                type="text"
                                id="currency"
                                name="currency"
                                class="settings-input @error('currency') is-invalid @enderror"
                                value="{{ old('currency', $company->currency) }}"
                                maxlength="10"
                                placeholder="e.g. BDT"
                                required
                            >

                        </div>

                        @error('currency')
                            <div class="settings-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Currency Symbol --}}
                    <div
                        class="settings-field"
                        style="margin-top: 20px;"
                    >

                        <label
                            for="currency_symbol"
                            class="settings-label"
                        >
                            Currency Symbol
                            <span class="required">*</span>
                        </label>

                        <div class="settings-input-wrap">

                            <span class="settings-input-icon">
                                ৳
                            </span>

                            <input
                                type="text"
                                id="currency_symbol"
                                name="currency_symbol"
                                class="settings-input @error('currency_symbol') is-invalid @enderror"
                                value="{{ old('currency_symbol', $company->currency_symbol) }}"
                                maxlength="10"
                                placeholder="e.g. ৳"
                                required
                            >

                        </div>

                        @error('currency_symbol')
                            <div class="settings-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Information --}}
                    <div class="settings-help">

                        <div class="settings-help-icon">
                            ⓘ
                        </div>

                        <div>
                            Your currency settings are used throughout
                            invoices, vouchers, reports and other
                            accounting documents.
                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- =====================================================
             Actions
        ====================================================== --}}
        @can('settings.manage')

            <div class="settings-actions">

                <div class="settings-security">

                    <span class="settings-security-icon">
                        ✓
                    </span>

                    <span>
                        Only authorized users can modify company settings.
                    </span>

                </div>


                <button
                    type="submit"
                    class="settings-save-btn"
                >
                    ✓ &nbsp; Save Settings
                </button>

            </div>

        @endcan


    </form>

</div>

@endsection