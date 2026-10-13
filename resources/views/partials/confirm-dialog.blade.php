{{--
    One confirmation dialog for the whole system.

    Any form opts in with data attributes — nothing runs until the user presses Confirm:
        <form method="POST" action="..."
              data-confirm="Mark RS-2026-0041 as Completed?"
              data-confirm-title="Update job status"
              data-confirm-button="Yes, update"
              data-confirm-tone="primary">   (primary, danger or warning)

    Cancel (or Esc, or clicking outside) closes the dialog and the record is not touched.
--}}
<div x-data="confirmDialog()" x-cloak
     @confirm-action.window="ask($event.detail)"
     @keydown.escape.window="open && cancel()">
    <div x-show="open" x-transition.opacity style="display: none"
         class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/50 p-4"
         @click.self="cancel()">
        <div x-show="open" x-transition
             role="alertdialog" aria-modal="true" aria-labelledby="confirm-title" aria-describedby="confirm-message"
             class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <div class="flex items-start gap-4">
                <div class="w-11 h-11 shrink-0 rounded-full flex items-center justify-center"
                     :class="{ 'bg-red-50 text-red-600': tone === 'danger', 'bg-amber-50 text-amber-600': tone === 'warning', 'bg-slate-100 text-slate-700': tone === 'primary' }">
                    <i class="ti text-2xl" :class="tone === 'primary' ? 'ti-help-circle' : 'ti-alert-triangle'"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 id="confirm-title" class="text-base font-semibold" x-text="title"></h2>
                    <p id="confirm-message" class="mt-1 text-sm text-slate-600 whitespace-pre-line" x-text="message"></p>
                </div>
            </div>
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 mt-6">
                <button type="button" x-ref="cancelBtn" @click="cancel()"
                        class="px-5 py-2.5 rounded-lg bg-slate-100 text-sm font-medium hover:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-400">
                    Cancel
                </button>
                <button type="button" @click="confirm()"
                        class="px-5 py-2.5 rounded-lg text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-1"
                        :class="{ 'bg-red-600 hover:bg-red-700 focus:ring-red-500': tone === 'danger', 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500': tone === 'warning', 'bg-slate-800 hover:bg-slate-900 focus:ring-slate-500': tone === 'primary' }"
                        x-text="button"></button>
            </div>
        </div>
    </div>
</div>

<script>
    // Intercept every form that has data-confirm (capture phase, before anything else)
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) return;

        if (form.dataset.confirmed === '1') {
            // Confirmed: let it through and stop double submits while the page loads
            form.dataset.confirmed = '';
            setTimeout(() => form.querySelectorAll('button:not([type=button])').forEach(b => {
                b.disabled = true;
                b.classList.add('opacity-60', 'cursor-wait');
            }), 0);
            return;
        }

        e.preventDefault();
        window.dispatchEvent(new CustomEvent('confirm-action', { detail: { form, submitter: e.submitter } }));
    }, true);

    function confirmDialog() {
        return {
            open: false,
            form: null,
            submitter: null,
            title: '',
            message: '',
            button: 'Confirm',
            tone: 'primary',
            ask({ form, submitter }) {
                const d = form.dataset;
                this.form = form;
                this.submitter = submitter || null;
                this.title = d.confirmTitle || 'Please confirm';
                this.message = d.confirm;
                this.button = d.confirmButton || 'Confirm';
                this.tone = d.confirmTone || 'primary';
                this.open = true;
                this.$nextTick(() => this.$refs.cancelBtn.focus());
            },
            cancel() {
                this.open = false;
                this.form = null;
            },
            confirm() {
                const form = this.form;
                this.open = false;
                if (!form) return;
                form.dataset.confirmed = '1';
                this.submitter && form.contains(this.submitter) ? form.requestSubmit(this.submitter) : form.requestSubmit();
                // If browser validation blocked the submit, ask again next time
                form.dataset.confirmed = '';
                this.form = null;
            },
        };
    }
</script>