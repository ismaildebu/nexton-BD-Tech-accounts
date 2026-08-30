<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\LegalDocument;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

it('blocks cross-company legal document route-model binding on the first request', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $companyA = Company::create([
        'company_name' => 'Company A',
        'business_type' => 'General',
        'status' => true,
    ]);

    $companyB = Company::create([
        'company_name' => 'Company B',
        'business_type' => 'General',
        'status' => true,
    ]);

    session(['company_id' => $companyA->id]);

    $document = LegalDocument::create([
        'company_id' => $companyA->id,
        'title' => 'Company A Trade License',
        'category' => 'Trade License',
        'document_number' => 'TL-A-001',
        'file_path' => 'documents/test/license.pdf',
        'file_name' => 'license.pdf',
        'file_type' => 'pdf',
        'file_size' => 1024,
        'uploaded_by' => null,
    ]);

    $userB = User::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $userB->assignRole('admin');

    $this->flushSession();

    $this->actingAs($userB)
        ->get(route('legal-documents.show', $document))
        ->assertNotFound();
});

it('allows a user to access a legal document belonging to their own company', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $company = Company::create([
        'company_name' => 'Company A',
        'business_type' => 'General',
        'status' => true,
    ]);

    $user = User::factory()->create([
        'company_id' => $company->id,
    ]);

    $user->assignRole('admin');

    session(['company_id' => $company->id]);

    $document = LegalDocument::create([
        'company_id' => $company->id,
        'title' => 'Company A Trade License',
        'category' => 'Trade License',
        'document_number' => 'TL-A-002',
        'file_path' => 'documents/test/license.pdf',
        'file_name' => 'license.pdf',
        'file_type' => 'pdf',
        'file_size' => 1024,
        'uploaded_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('legal-documents.show', $document))
        ->assertOk();
});