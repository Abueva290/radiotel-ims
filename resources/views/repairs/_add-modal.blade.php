<div x-data="{ open: {{ old('_form') === 'add-repair' && $errors->any() ? 'true' : 'false' }} }"
     @open-add-repair.window="open = true"
     @keydown.escape.window="open = false">

    <div x-show="open" style="display: none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-2 sm:p-4"
         @click.self="open = false">

        <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[92vh] overflow-y-auto"
             role="dialog" aria-modal="true" aria-labelledby="add-repair-title">

            {{-- Header --}}
            <div class="flex items-center justify-between px-5 sm:px-8 py-5 border-b border-slate-100 sticky top-0 bg-white z-10">
                <h2 id="add-repair-title" class="text-lg font-semibold">New Repair Job</h2>
                <button type="button" @click="open = false" aria-label="Close"
                        class="w-9 h-9 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50">✕</button>
            </div>

            <form method="POST" action="{{ route('repairs.store') }}" class="px-4 sm:px-8 py-6">
                @csrf
                <input type="hidden" name="_form" value="add-repair">

                @include('repairs._form-fields')

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