<div id="ussd-session-details-modal" class="hidden fixed inset-0 z-[80] flex items-center justify-center bg-black/55 p-4">
    <div class="box w-full max-w-3xl max-h-[90vh] flex flex-col border border-n30 bg-n0 p-0 overflow-hidden shadow-2xl">
        <div class="flex items-center justify-between border-b border-n30 px-6 py-4">
            <h4 class="h4">USSD Session Details</h4>
            <button type="button" id="ussd-details-close-btn" class="text-n100 hover:text-error" aria-label="Close session details">
                <i class="las la-times text-2xl"></i>
            </button>
        </div>

        <div id="ussd-details-body" class="overflow-y-auto px-6 py-5 space-y-6 text-xs sm:text-sm">
            <div class="py-8 text-center text-n100">Loading session details...</div>
        </div>

        <div class="border-t border-n30 px-6 py-3 flex justify-end">
            <button type="button" id="ussd-details-footer-close" class="btn-outline py-1.5 px-4 text-xs">Close</button>
        </div>
    </div>
</div>

@push('page-js')
<script>
(function () {
    if (window.__q4UssdDetailsBound) return;
    window.__q4UssdDetailsBound = true;

    const modal = document.getElementById('ussd-session-details-modal');
    const body = document.getElementById('ussd-details-body');
    const closeBtns = [
        document.getElementById('ussd-details-close-btn'),
        document.getElementById('ussd-details-footer-close'),
    ];

    function closeModal() {
        modal && modal.classList.add('hidden');
    }

    closeBtns.forEach((btn) => btn && btn.addEventListener('click', closeModal));
    modal && modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) closeModal();
    });

    function esc(val) {
        const div = document.createElement('div');
        div.textContent = val == null ? '' : String(val);
        return div.innerHTML;
    }

    function statusBadge(status) {
        const s = String(status || 'Success');
        const lower = s.toLowerCase();
        const cls = lower === 'success'
            ? 'bg-[#10B981]/15 text-[#10B981]'
            : (lower === 'incomplete' || lower.includes('progress')
                ? 'bg-[#F59E0B]/15 text-[#F59E0B]'
                : 'bg-error/15 text-error');
        return `<span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold ${cls}">${esc(s)}</span>`;
    }

    function renderSummaryRow(label, valueHtml) {
        return `
            <tr class="even:bg-secondary/5 border-b border-n30/50">
                <td class="px-4 py-2.5 font-medium text-n200 w-44">${esc(label)}</td>
                <td class="px-4 py-2.5">${valueHtml}</td>
            </tr>
        `;
    }

    function renderDetails(data) {
        const summaryRows = [
            renderSummaryRow('Date', esc(data.date)),
            data.is_admin ? renderSummaryRow('Client', `<strong>${esc(data.client_name)}</strong>`) : '',
            renderSummaryRow('Service Code', `<span class="font-mono font-semibold text-primary">${esc(data.service_code)}</span>`),
            renderSummaryRow('Phone Number', `<span class="font-medium">${esc(data.phone_number)}</span>`),
            renderSummaryRow('Carrier', esc(data.carrier)),
            renderSummaryRow('Hops', esc(data.hops)),
            renderSummaryRow('Duration', esc(data.duration)),
            renderSummaryRow('Billed Cost', `<strong class="text-primary">${esc(data.billed)}</strong>`),
            data.is_admin ? renderSummaryRow('Upstream Cost', `<span>${esc(data.upstream)}</span> ${data.reconciled ? '<span class="ml-2 rounded-full bg-[#10B981]/15 px-2 py-0.5 text-[10px] font-semibold text-[#10B981]">Verified</span>' : '<span class="ml-2 rounded-full bg-[#F59E0B]/15 px-2 py-0.5 text-[10px] font-semibold text-[#F59E0B]">Pending</span>'}`) : '',
            data.is_admin ? renderSummaryRow('Net Margin', `<strong class="text-[#10B981]">${esc(data.margin)}</strong>`) : '',
            renderSummaryRow('Status', statusBadge(data.status)),
            renderSummaryRow('Session Id', `<span class="font-mono text-xs break-all">${esc(data.session_id)}</span>`),
            data.gateway_error && data.gateway_error !== 'None'
                ? renderSummaryRow('Error Message', `<span class="text-error font-medium">${esc(data.gateway_error)}</span>`)
                : renderSummaryRow('Error Message', '<span class="text-n100">None</span>'),
        ].join('');

        const hops = Array.isArray(data.hops_trace) ? data.hops_trace : [];
        const hopsHtml = hops.length > 0
            ? hops.map((hop, idx) => {
                const hopNum = hop.hop || (idx + 1);
                const err = hop.error_message ? `<span class="text-error font-medium">${esc(hop.error_message)}</span>` : '<span class="text-n100">None</span>';
                return `
                    <div class="rounded-xl border border-n30 overflow-hidden">
                        <div class="bg-[#0B3A75] text-white px-4 py-2 text-xs font-semibold flex items-center justify-between">
                            <span>${esc(hopNum)}.</span>
                            <span>${esc(hop.display_time || data.date)}</span>
                        </div>
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="bg-primary text-white text-[11px] uppercase">
                                    <th class="px-4 py-1.5 text-start w-44">Item</th>
                                    <th class="px-4 py-1.5 text-start">Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="even:bg-secondary/5 border-b border-n30/50">
                                    <td class="px-4 py-2 font-medium text-n200">Start Time</td>
                                    <td class="px-4 py-2">${esc(hop.display_time || data.date)}</td>
                                </tr>
                                <tr class="even:bg-secondary/5 border-b border-n30/50">
                                    <td class="px-4 py-2 font-medium text-n200">User Input</td>
                                    <td class="px-4 py-2 font-mono">${esc(hop.input || '—')}</td>
                                </tr>
                                <tr class="even:bg-secondary/5 border-b border-n30/50">
                                    <td class="px-4 py-2 font-medium text-n200 align-top">App Response</td>
                                    <td class="px-4 py-2"><pre class="whitespace-pre-wrap font-sans text-xs leading-relaxed">${esc(hop.app_response || '—')}</pre></td>
                                </tr>
                                <tr class="even:bg-secondary/5 border-b border-n30/50">
                                    <td class="px-4 py-2 font-medium text-n200">Error Message</td>
                                    <td class="px-4 py-2">${err}</td>
                                </tr>
                                <tr class="even:bg-secondary/5">
                                    <td class="px-4 py-2 font-medium text-n200">Status</td>
                                    <td class="px-4 py-2">${statusBadge(hop.status || data.status)}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                `;
            }).join('')
            : '<div class="rounded-xl border border-n30 p-4 text-center text-xs text-n100">No per-hop trace available for this session.</div>';

        body.innerHTML = `
            <div class="rounded-xl border border-n30 overflow-hidden">
                <table class="w-full text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-[#10B981] text-white text-xs uppercase">
                            <th class="px-4 py-2 text-start w-44">Item</th>
                            <th class="px-4 py-2 text-start">Value</th>
                        </tr>
                    </thead>
                    <tbody>${summaryRows}</tbody>
                </table>
            </div>

            <div class="space-y-3">
                <h5 class="h5">USSD Session Hops</h5>
                ${hopsHtml}
            </div>
        `;
    }

    document.addEventListener('click', async (e) => {
        const trigger = e.target.closest('.js-view-ussd-details');
        if (!trigger || !modal || !body) return;

        const url = trigger.dataset.detailsUrl;
        if (!url) return;

        body.innerHTML = '<div class="py-10 text-center text-n100">Loading session details...</div>';
        modal.classList.remove('hidden');

        try {
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            renderDetails(data);
        } catch (err) {
            body.innerHTML = '<div class="rounded-xl bg-error/10 p-4 text-center text-xs text-error">Unable to load session details. Please try again.</div>';
        }
    });
})();
</script>
@endpush
