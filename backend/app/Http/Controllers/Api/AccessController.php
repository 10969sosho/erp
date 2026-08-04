<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccessController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function roles(Request $request): JsonResponse
    {
        return response()->json(['data' => Role::where('tenant_id', $request->user()->tenant_id)->with('permissions')->paginate(50)]);
    }

    public function createRole(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:80'], 'name' => ['required', 'string'], 'permission_keys' => ['sometimes', 'array'], 'permission_keys.*' => ['string']]);
        $role = DB::transaction(function () use ($data, $request): Role {
            $role = Role::create(['tenant_id' => $request->user()->tenant_id, 'code' => $data['code'], 'name' => $data['name'], 'status' => 'active']);
            $permissions = Permission::whereIn('key', $data['permission_keys'] ?? [])->get();
            abort_unless($permissions->count() === count(array_unique($data['permission_keys'] ?? [])), 422, 'Permission tidak valid.');
            foreach ($permissions as $permission) {
                DB::table('role_permissions')->updateOrInsert(['role_id' => $role->id, 'permission_id' => $permission->id, 'scope_type' => 'tenant'], ['id' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now()]);
            }
            $this->audit->record($request, 'created', $role);

            return $role;
        });

        return response()->json(['data' => $role->load('permissions')], 201);
    }

    public function assignRole(Request $request, string $userId, string $roleId): JsonResponse
    {
        $user = User::where('tenant_id', $request->user()->tenant_id)->findOrFail($userId);
        $role = Role::where('tenant_id', $request->user()->tenant_id)->findOrFail($roleId);
        DB::table('user_roles')->updateOrInsert(['user_id' => $user->id, 'role_id' => $role->id], ['id' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now()]);
        $this->audit->record($request, 'role_assigned', $user, ['role_id' => $role->id]);

        return response()->json(['data' => $user->load('roles')]);
    }
}
