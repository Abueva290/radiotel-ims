{{-- Shared by the New Repair Job modal and the /repairs/create page --}}
@php
    $input = 'w-full rounded-lg border-slate-300 text-[15px] px-3.5 py-2.5 focus:border-slate-500 focus:ring-slate-500';
    $canAssign = auth()->user()->hasRole(...\App\Models\RepairJob::SUPERVISOR_ROLES);
    $received = old('date_received', now()->toDateString());
@endphp

<div x-data="repairForm({
        units: {{ (int) old('units', 1) }},
        received: @js($received),
        target: @js(old('target_completion_date', '')),
        serials: @js(old('serials', '')),
        lookupUrl: @js(route('repairs.serial-lookup')),
     })" x-init="lookupAll()">

    {{-- Job information --}}
    <div class="border border-slate-200 rounded-xl p-4 sm:p-6">
        <h3 class="font-semibold text-[15px]">Job Information</h3>
        <p class="text-xs text-slate-400 mb-5">Parts used are added after the job is created.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium mb-1.5">Customer</label>
                <select name="customer_id" class="{{ $input }}" required>
                    <option value="">Select customer...</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
                @error('customer_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="min-w-0">
                <label class="block text-sm font-medium mb-1.5">Unit Model</label>
                <input type="text" name="unit_model" value="{{ old('unit_model') }}" required
                       placeholder="e.g. Motorola DP4801e" class="{{ $input }}">
                @error('unit_model') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="min-w-0">
                <label class="block text-sm font-medium mb-1.5">Number of Units</label>
                <input type="number" min="1" name="units" x-model.number="units" required class="{{ $input }}">
                @error('units') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="min-w-0">
                <label class="block text-sm font-medium mb-1.5">Job No.</label>
                <input type="text" value="{{ $jobNo }}" disabled class="{{ $input }} bg-slate-50 text-slate-500">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium mb-1.5">Assessment Notes</label>
                <textarea name="assessment_notes" rows="2" placeholder="Findings, required repair, parts needed..."
                          class="{{ $input }}">{{ old('assessment_notes') }}</textarea>
                @error('assessment_notes') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    {{-- Service timeframe --}}
    <div class="border border-slate-200 rounded-xl p-4 sm:p-6 mt-5">
        <h3 class="font-semibold text-[15px]">Service Timeframe</h3>
        <p class="text-xs text-slate-400 mb-5">Set the target completion date agreed with the customer. Jobs without a target date are never marked overdue.</p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="min-w-0">
                <label class="block text-sm font-medium mb-1.5">Date Received</label>
                <input type="date" name="date_received" x-model="received" max="{{ now()->toDateString() }}" required class="{{ $input }}">
                @error('date_received') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="min-w-0">
                <label class="block text-sm font-medium mb-1.5">Time Received</label>
                <input type="time" name="time_received" value="{{ old('time_received', now()->format('H:i')) }}" class="{{ $input }}">
                @error('time_received') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="min-w-0">
                <label class="block text-sm font-medium mb-1.5">Target Completion <span class="font-normal text-slate-400">(optional)</span></label>
                <input type="date" name="target_completion_date" x-model="target" :min="received" class="{{ $input }}">
                <p class="text-xs text-slate-400 mt-1" x-text="turnaroundText()"></p>
                @error('target_completion_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            @if ($canAssign)
                <div class="sm:col-span-3">
                    <label class="block text-sm font-medium mb-1.5">Assign to Technician <span class="font-normal text-slate-400">(optional — can be done later)</span></label>
                    <select name="assigned_to" class="{{ $input }}">
                        <option value="">Assign later</option>
                        @foreach ($activeTechs as $tech)
                            <option value="{{ $tech->id }}" @selected(old('assigned_to') == $tech->id)>
                                {{ $tech->name }} — {{ \App\Models\User::ROLES[$tech->role] }}
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_to') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            @endif
        </div>
    </div>

    {{-- Serial numbers and warranty --}}
    <div class="border border-slate-200 rounded-xl p-4 sm:p-6 mt-5">
        <h3 class="font-semibold text-[15px]">Serial Numbers & Warranty</h3>
        <p class="text-xs text-slate-400 mb-5">One serial number per line (up to the number of units). Units sold by Radiotel are recognised automatically, with their warranty.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium mb-1.5">Serial Number(s) <span class="font-normal text-slate-400">(optional)</span></label>
                <textarea name="serials" rows="2" x-model="serials" @input.debounce.600ms="lookupAll()"
                          placeholder="e.g. 481TRK2205" class="{{ $input }} font-mono uppercase"></textarea>
                <p class="text-xs mt-1" :class="serialList().length > units ? 'text-red-600' : 'text-slate-400'"
                   x-text="serialList().length + ' of ' + (units || 0) + ' unit(s) have a serial number'"></p>
                @error('serials') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

                {{-- Live warranty check for each serial --}}
                <div class="mt-3 space-y-2" x-show="results.length" aria-live="polite">
                    <template x-for="r in results" :key="r.serial">
                        <div class="rounded-lg border border-slate-200 px-3 py-2 text-sm flex flex-wrap items-center gap-x-3 gap-y-1">
                            <span class="font-mono font-medium" x-text="r.serial"></span>
                            <template x-if="r.loading"><span class="text-xs text-slate-400">Checking…</span></template>
                            <template x-if="!r.loading && r.found">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="text-slate-600" x-text="r.product + (r.sold_to ? ' · sold to ' + r.sold_to : '')"></span>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                          :class="toneClass(r.warranty_tone)" x-text="r.warranty_label"></span>
                                    <span class="text-xs text-slate-500" x-show="r.warranty_end" x-text="'until ' + r.warranty_end + (r.remaining ? ' (' + r.remaining + ')' : '')"></span>
                                </span>
                            </template>
                            <template x-if="!r.loading && !r.found">
                                <span class="text-xs text-slate-500">Not in Radiotel's sales records — enter the warranty card dates below if the customer has one.</span>
                            </template>
                            <template x-if="!r.loading && r.history && r.history.length">
                                <span class="w-full text-xs text-slate-500">
                                    Previous repairs:
                                    <template x-for="h in r.history" :key="h.job_no">
                                        <a :href="h.url" target="_blank" class="underline mr-2" x-text="h.job_no + ' (' + h.status + ')'"></a>
                                    </template>
                                </span>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <div class="min-w-0">
                <label class="block text-sm font-medium mb-1.5">Warranty Start <span class="font-normal text-slate-400">(from warranty card)</span></label>
                <input type="date" name="warranty_start" value="{{ old('warranty_start') }}" class="{{ $input }}">
                @error('warranty_start') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="min-w-0">
                <label class="block text-sm font-medium mb-1.5">Warranty End</label>
                <input type="date" name="warranty_end" value="{{ old('warranty_end') }}" class="{{ $input }}">
                @error('warranty_end') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <p class="sm:col-span-2 text-xs text-slate-400 -mt-2">Leave blank when there is no warranty information. These dates apply only to serials not sold by Radiotel. Being within the warranty period does not by itself mean the repair is covered — coverage depends on the warranty conditions.</p>
        </div>
    </div>

    <div class="flex justify-between items-baseline mt-5 px-1">
        <p class="text-sm text-slate-500">
            Service fee: <span x-text="units || 0"></span> unit(s) × ₱{{ number_format($fee, 2) }}
        </p>
        <p class="text-xl font-semibold"
           x-text="'₱' + ((units || 0) * {{ $fee }}).toLocaleString('en-PH', { minimumFractionDigits: 2 })"></p>
    </div>
</div>

@once
<script>
    function repairForm(o) {
        const tones = @js(\App\Support\Badge::TONES);
        return {
            units: o.units,
            received: o.received,
            target: o.target,
            serials: o.serials,
            results: [],
            cache: {},
            serialList() {
                return [...new Set(this.serials.split(/[\n,]+/).map(s => s.replace(/\s+/g, '').toUpperCase()).filter(Boolean))];
            },
            turnaroundText() {
                if (!this.received || !this.target) return '';
                const days = Math.round((new Date(this.target) - new Date(this.received)) / 86400000);
                return days < 0 ? 'Target is before the date received' : days + '-day turnaround';
            },
            toneClass(t) {
                return tones[t] || tones.gray;
            },
            async lookupAll() {
                const list = this.serialList().slice(0, 20);
                this.results = list.map(s => this.cache[s] || { serial: s, loading: true });
                for (const s of list) {
                    if (this.cache[s]) continue;
                    try {
                        const res = await fetch(o.lookupUrl + '?serial=' + encodeURIComponent(s), { headers: { Accept: 'application/json' } });
                        this.cache[s] = { ...(await res.json()), serial: s, loading: false };
                    } catch (e) {
                        this.cache[s] = { serial: s, loading: false, found: false };
                    }
                    this.results = this.serialList().slice(0, 20).map(x => this.cache[x] || { serial: x, loading: true });
                }
            },
        };
    }
</script>
@endonce