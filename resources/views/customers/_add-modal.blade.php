@php $input = 'w-full rounded-lg border-slate-300 text-[15px] px-3.5 py-2.5 focus:border-slate-500 focus:ring-slate-500'; @endphp

<div x-data="{ open: {{ old('_form') === 'add-customer' && $errors->any() ? 'true' : 'false' }} }"
     @open-add-customer.window="open = true"
     @keydown.escape.window="open = false">

    <div x-show="open" style="display: none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-2 sm:p-4"
         @click.self="open = false">

        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

            {{-- Header --}}
            <div class="flex items-center justify-between px-5 sm:px-8 py-5 border-b border-slate-100">
                <h2 class="text-lg font-semibold">Add Customer</h2>
                <button type="button" @click="open = false"
                        class="w-9 h-9 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50">✕</button>
            </div>

            <form method="POST" action="{{ route('customers.store') }}" class="px-4 sm:px-8 py-6">
                @csrf
                <input type="hidden" name="_form" value="add-customer">

                <div class="border border-slate-200 rounded-xl p-4 sm:p-6">
                    <h3 class="font-semibold text-[15px]">Customer Information</h3>
                    <p class="text-xs text-slate-400 mb-5">Used in sales and repair transactions.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium mb-1.5">Customer Name</label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                   placeholder="e.g. Davao City Police Office" class="{{ $input }}">
                            @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="min-w-0">
                            <label class="block text-sm font-medium mb-1.5">Contact Person</label>
                            <input type="text" name="contact_person" value="{{ old('contact_person') }}" class="{{ $input }}">
                            @error('contact_person') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="min-w-0">
                            <label class="block text-sm font-medium mb-1.5">Phone</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" class="{{ $input }}">
                            @error('phone') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium mb-1.5">Address</label>
                            <input type="text" name="address" value="{{ old('address') }}" class="{{ $input }}">
                            @error('address') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="open = false"
                            class="px-6 py-2.5 rounded-lg bg-slate-100 text-[15px] hover:bg-slate-200">Cancel</button>
                    <button class="px-6 py-2.5 rounded-lg bg-slate-900 text-white text-[15px] hover:bg-slate-700">Save Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>