{{-- Footer for the dashboard RIS preview; buttons are configured per row by openDashboardRisPreview(). --}}
<div class="flex flex-wrap items-center justify-end gap-2">
    <button
        type="button"
        onclick="window.{{ $closeFn }}()"
        class="px-3 py-2 text-sm font-medium text-gray-500 transition hover:text-gray-950"
    >
        Close
    </button>

    <a
        href="#"
        data-dashboard-ris-module
        class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
    >
        Open in Procurement Requests
    </a>

    <form
        method="POST"
        action=""
        data-dashboard-ris-accept-form
        class="hidden"
        onsubmit="var btn = this.querySelector('button[type=submit]'); if (btn.disabled) return false; btn.disabled = true; btn.textContent = 'Accepting…';"
    >
        @csrf
        <button
            type="submit"
            class="rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:cursor-wait disabled:opacity-60"
        >
            Accept &amp; continue
        </button>
    </form>
</div>
