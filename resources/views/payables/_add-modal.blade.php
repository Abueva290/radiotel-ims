@php
    $input = 'w-full rounded-lg border-slate-300 text-[15px] px-3.5 py-2.5 focus:border-slate-500 focus:ring-slate-500';
    $terms = $suppliers->pluck('credit_terms_days', 'id');
@endphp

<div x-data="{ open: {{ old('_form') === 'add-invoice' && $errors->any() ? 'true' : 'false' }} }"
     @open-add-invoice.window="open = true"
     @keydown.escape.window="open = false">

    <div x-show="open" style="display: none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
         @click.self="open = false">

        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

            {{-- Header --}}
            <div class="flex items-center justify-between px-8 py-5 border-b border-slate-100">
                <h2 class="text-lg font-semibold">Record Supplier Invoice</h2>
                <button type="button" @click="open = false"
                        class="w-9 h-9 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50">✕</button>
            </div>

            <form method="POST" action="{{ route('payables.store') }}" class="px-8 py-6"
                  x-data="{
                      terms: @js($terms),
                      supplier: '{{ old('supplier_id') }}',
                      date: '{{ old('invoice_date', now()->toDateString()) }}',
                      dueDate() {
                          if (!this.supplier || !this.date) return '—';
                          const [y, m, day] = this.date.split('-').map(Number);
                          const d = new Date(y, m - 1, day);
                          d.setDate(d.getDate() + Number(this.terms[this.supplier] ?? 0));
                          return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
                      },
                      termDays() {
                          return this.supplier ? (this.terms[this.supplier] ?? 0) + ' days' : '—';
                      }
                  }">
                @csrf
                <input type="hidden" name="_form" value="add-invoice">

                <div class="border border-slate-200 rounded-xl p-6">
                    <h3 class="font-semibold text-[15px]">Invoice Information</h3>
                    <p class="text-xs text-slate-400 mb-5">The due date is computed from the supplier's credit terms.</p>

                    <div class="grid grid-cols-2 gap-5">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium mb-1.5">Supplier</label>
                            <select name="supplier_id" x-model="supplier" class="{{ $input }}">
                                <option value="">Select supplier...</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                            @error('supplier_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-sm font-medium mb-1.5">Supplier Invoice No.</label>
                            <input type="text" name="invoice_no" value="{{ old('invoice_no') }}"
                                   placeholder="e.g. MSP-2026-1183" class="{{ $input }}">
                            @error('invoice_no') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-sm font-medium mb-1.5">Amount (₱)</label>
                            <input type="number" step="0.01" name="amount" value="{{ old('amount') }}" class="{{ $input }}">
                            @error('amount') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-sm font-medium mb-1.5">Invoice Date</label>
                            <input type="date" name="invoice_date" x-model="date" class="{{ $input }}">
                            @error('invoice_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex justify-between mt-6 pt-4 border-t border-slate-100 text-sm">
                        <p class="text-slate-500">Credit terms: <span class="font-medium text-slate-800" x-text="termDays()"></span></p>
                        <p class="text-slate-500">Due date: <span class="font-medium text-slate-800" x-text="dueDate()"></span></p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="open = false"
                            class="px-6 py-2.5 rounded-lg bg-slate-100 text-[15px] hover:bg-slate-200">Cancel</button>
                    <button class="px-6 py-2.5 rounded-lg bg-slate-900 text-white text-[15px] hover:bg-slate-700">Save Invoice</button>
                </div>
            </form>
        </div>
    </div>
</div>