<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Permission;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    public function __invoke(Request $request, string $resource): JsonResponse
    {
        $tenant = $request->user()->tenant_id;
        $data = match ($resource) {
            'companies' => Company::where('tenant_id', $tenant)->where('status', 'active')->orderBy('code')->get(['id', 'code', 'name']),
            'branches' => Branch::whereHas('company', fn ($query) => $query->where('tenant_id', $tenant))->when($request->filled('company_id'), fn ($query) => $query->where('company_id', $request->input('company_id')))->where('status', 'active')->orderBy('code')->get(['id', 'code', 'name', 'company_id']),
            'warehouses' => Warehouse::whereHas('branch.company', fn ($query) => $query->where('tenant_id', $tenant))->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $request->input('branch_id')))->where('status', 'active')->orderBy('code')->get(['id', 'code', 'name', 'branch_id']),
            'users' => User::where('tenant_id', $tenant)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'email']),
            'permissions' => Permission::where(fn ($query) => $query->whereNull('tenant_id')->orWhere('tenant_id', $tenant))->orderBy('key')->get(['id', 'key', 'description']),
            default => abort(404, 'Lookup resource tidak ditemukan.'),
        };

        return response()->json(['data' => $data]);
    }
}
