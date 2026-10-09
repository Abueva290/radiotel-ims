@php $input = 'w-full rounded-lg border-slate-300 text-[15px] px-3.5 py-2.5 focus:border-slate-500 focus:ring-slate-500'; @endphp

<div x-data="{ open: {{ old('_form') === 'add-repair' && $errors->any() ? 'true' : 'false' }} }"
     @open-add-repair.window="open = true"
     @keydown.escape.window="open = false">

    <div x-show="open" style="display: none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-2 sm:p-4"
         @click.self="open = false">

        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

            {{-- Header --}}
            <div class="flex items-center justify-between px-5 sm:px-8 py-5 border-b border-slate-100">
                <h2 class="text-lg font-semibold">New Repair Job</h2>
                <button type="button" @click="open = false"
                        class="w-9 h-9 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50">✕</button>
            </div>

            <form method="POST" action="{{ route('repairs.store') }}" class="px-4 sm:px-8 py-6"
                  x-data="{ units: {{ (int) old('units', 1) }} }">
                @csrf
                <input type="hidden" name="_form" value="add-repair">

                <div class="border border-slate-200 rounded-xl p-4 sm:p-6">
                    <h3 class="font-semibold text-[15px]">Job Information</h3>
                    <p class="text-xs text-slate-400 mb-5">Parts used are added after the job is created.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium mb-1.5">Customer</label>
                            <select name="customer_id" class="{{ $input }}">
                                <option value="">Select customer...</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}</option>
                                @endforeach
                            </select>
                            @error('customer_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="min-w-0">
                            <label class="block text-sm font-medium mb-1.5">Unit Model</label>
                            <input type="text" name="unit_model" value="{{ old('unit_model') }}"
                                   placeholder="e.g. Motorola DP4801e" class="{{ $input }}">
                            @error('unit_model') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="min-w-0">
                            <label class="block text-sm font-medium mb-1.5">Number of Units</label>
                            <input type="number" min="1" name="units" x-model.number="units" class="{{ $input }}">
                            @error('units') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="min-w-0">
                            <label class="block text-sm font-medium mb-1.5">Date Received</label>
                            <input type="date" name="date_received" value="{{ old('date_received', now()->toDateString()) }}" class="{{ $input }}">
                            @error('date_received') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="min-w-0">
                            <label class="block text-sm font-medium mb-1.5">Job No.</label>
                            <input type="text" value="{{ $jobNo }}" disabled class="{{ $input }} bg-slate-50 text-slate-500">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium mb-1.5">Assessment Notes</label>
                            <textarea name="assessment_notes" rows="3" placeholder="Findings, required repair, parts needed..."
                                      class="{{ $input }}">{{ old('assessment_notes') }}</textarea>
                            @error('assessment_notes') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex justify-between items-baseline mt-6 pt-4 border-t border-slate-100">
                        <p class="text-sm text-slate-500">
                            Service fee: <span x-text="units || 0"></span> unit(s) × ₱{{ number_format($fee, 2) }}
                        </p>
                        <p class="text-xl font-semibold"
                           x-text="'₱' + ((units || 0) * {{ $fee }}).toLocaleString('en-PH', { minimumFractionDigits: 2 })"></p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="open = false"
                            class="px-6 py-2.5 rounded-lg bg-slate-100 text-[15px] hover:bg-slate-200">Cancel</button>
                    <button class="px-6 py-2.5 rounded-lg bg-slate-900 text-white text-[15px] hover:bg-slate-700">Create Job</button>
                </div>
            </form>
        </div>
    </div>
</div>