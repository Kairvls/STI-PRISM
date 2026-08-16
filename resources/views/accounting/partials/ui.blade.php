<style>
.fade-in { animation: accFade .35s ease both; }
.slide-up { animation: accUp .4s ease both; }
.card-hover { transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
.card-hover:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(15,23,42,.06); }
@keyframes accFade { from { opacity: 0 } to { opacity: 1 } }
@keyframes accUp { from { opacity: 0; transform: translateY(8px) } to { opacity: 1; transform: none } }
@keyframes accModal { from { opacity: 0; transform: translateY(10px) scale(.98) } to { opacity: 1; transform: none } }
.acc-table tbody tr { transition: background .15s ease; }
.acc-table tbody tr:hover { background: #f8fafc; }
.acc-viewer { max-height: calc(100vh - 220px); overflow: auto; background: #f3f4f6; }
.acc-viewer .rfc-print-sheet,
.acc-viewer .liq-print-sheet { transform-origin: top center; }
.acc-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
.acc-btn { height: 2.5rem; border-radius: .75rem; padding: 0 1rem; font-size: .875rem; font-weight: 600; transition: all .15s ease; }
.acc-btn-approve { background: #059669; color: #fff; }
.acc-btn-approve:hover { background: #047857; }
.acc-btn-revise { background: #fffbeb; color: #92400e; border: 1px solid #fcd34d; }
.acc-btn-revise:hover { background: #fef3c7; }
.acc-btn-funds { background: #111827; color: #fff; }
.acc-btn-funds:hover { background: #030712; }
.acc-btn-ghost { background: #fff; color: #374151; border: 1px solid #e5e7eb; }
.acc-modal { animation: accModal .22s ease both; }
.acc-backdrop { background: rgba(15,23,42,.45); }
.acc-empty { border: 1px dashed #e5e7eb; background: #f9fafb; }
</style>
