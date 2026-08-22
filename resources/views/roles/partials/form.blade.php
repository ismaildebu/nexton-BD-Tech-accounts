{{--
    Shared permission assignment form for Role create / edit.
    Requires: $groups (array), $permissions (collection),
    $selectedPermissions (array of permission names).
--}}
<div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    <h2 class="text-lg font-bold text-gray-800 mb-4">Assign Permissions</h2>

    <div class="mb-4 flex gap-2">
        <button type="button" id="check-all"
                class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200">
            Check All
        </button>
        <button type="button" id="uncheck-all"
                class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200">
            Uncheck All
        </button>
    </div>

    <div class="space-y-6">
        @foreach ($groups as $group)
            <div>
                <h3 class="mb-2 text-sm font-bold uppercase tracking-wide text-gray-500">
                    {{ ucfirst($group) }}
                </h3>
                <div class="grid grid-cols-2 gap-x-6 gap-y-2 md:grid-cols-3">
                    @foreach ($permissions->where('group', $group) as $permission)
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox"
                                   name="permissions[]"
                                   value="{{ $permission->name }}"
                                   {{ in_array($permission->name, $selectedPermissions, true) ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            {{ $permission->name }}
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('check-all')?.addEventListener('click', function () {
        document.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = true);
    });
    document.getElementById('uncheck-all')?.addEventListener('click', function () {
        document.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = false);
    });
</script>
@endpush
