<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use App\Models\Company;
use Symfony\Component\HttpFoundation\Response;
class SetActiveCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            if (! session()->has('company_id')) {
                if ($user->company_id !== null) {
                    // A company-scoped user (i.e. not Super Admin) must
                    // always default to their OWN company — never an
                    // arbitrary one. Picking Company::first() here would
                    // let a fresh session briefly carry a company_id that
                    // isn't this user's own, which is exactly what
                    // EnsureCompanySelected exists to prevent.
                    $company = $user->company;
                } else {
                    // Super Admin has no company_id of their own and is
                    // allowed to view any company, so a default pick is
                    // fine here — this only ever applies to Super Admin.
                    $company = Company::first();
                }

                if ($company) {
                    session([
                        'company_id'   => $company->id,
                        'company_name' => $company->company_name,
                    ]);
                }
            }
        }
        return $next($request);
    }
}