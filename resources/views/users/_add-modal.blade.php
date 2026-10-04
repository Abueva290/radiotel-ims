@php $input = 'w-full rounded-lg border-slate-300 text-[15px] px-3.5 py-2.5 focus:border-slate-500 focus:ring-slate-500'; @endphp

<div x-data="{ open: {{ old('_form') === 'add-user' && $errors->any() ? 'true' : 'false' }} }"
     @open-add-user.window="open = true"
     @keydown.escape.window="open = false">

    <div x-show="open" style="display: none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
         @click.self="open = false">

        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

            {{-- Header --}}
            <div class="flex items-center justify-between px-8 py-5 border-b border-slate-100">
                <h2 class="text-lg font-semibold">Add User</h2>
                <button type="button" @click="open = false"
                        class="w-9 h-9 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50">✕</button>
            </div>

            <form method="POST" action="{{ route('users.store') }}" class="px-8 py-6">
                @csrf
                <input type="hidden" name="_form" value="add-user">

                <div class="border border-slate-200 rounded-xl p-6">
                    <h3 class="font-semibold text-[15px]">User Information</h3>
                    <p class="text-xs text-slate-400 mb-5">The employee will be asked to change the password on first login.</p>

                    <div class="grid grid-cols-2 gap-5">
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-sm font-medium mb-1.5">Full Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="{{ $input }}">
                            @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-sm font-medium mb-1.5">Email Address</label>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="name@radiotel.ph" class="{{ $input }}">
                            @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-medium mb-1.5">Role</label>
                            <select name="role" class="{{ $input }}">
                                @foreach (\App\Models\User::ROLES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('role', 'staff') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('role') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-medium mb-1.5">Temporary Password</label>
                            <div class="flex gap-3" x-data="{ pw: @js(old('password', '')) }">
                                <input type="text" name="password" x-model="pw" class="{{ $input }}">
                                <button type="button" class="btn-action px-5 py-2.5 text-[15px] whitespace-nowrap"
                                        @click="pw = 'Rt-' + Math.random().toString(36).slice(2, 8) + Math.floor(Math.random() * 90 + 10)">
                                    Generate
                                </button>
                            </div>
                            @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="open = false"
                            class="px-6 py-2.5 rounded-lg bg-slate-100 text-[15px] hover:bg-slate-200">Cancel</button>
                    <button class="px-6 py-2.5 rounded-lg bg-slate-900 text-white text-[15px] hover:bg-slate-700">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>