<style>
    .ro-preview-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px; padding: 0; background: #fff;
        border: 1px solid #e5e7eb; border-radius: 8px; color: #4b5563; cursor: pointer;
    }
    .ro-preview-btn:hover:not(:disabled) { background: #f9fafb; color: #111827; }
    .ro-preview-btn:disabled { opacity: .4; cursor: not-allowed; }
    .ro-preview-btn i, .ro-preview-btn svg { width: 16px; height: 16px; }
    .ris-preview-modal-overlay {
        position: fixed; inset: 0; z-index: 11000; background: rgba(15,23,42,.6);
        display: none; align-items: center; justify-content: center; padding: 20px;
    }
    .ris-preview-modal-container {
        background: #fff; border-radius: 12px; width: 100%; max-width: 1100px;
        max-height: 90vh; display: flex; flex-direction: column; overflow: hidden;
    }
    .ris-preview-modal-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 20px; border-bottom: 1px solid #e2e8f0;
    }
    .ris-preview-modal-title { font-size: 15px; font-weight: 700; }
    .ris-preview-modal-close {
        width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0;
        background: #f8fafc; cursor: pointer;
    }
    .ris-preview-modal-body iframe { width: 100%; height: calc(90vh - 110px); min-height: 400px; border: none; }
    .ris-preview-modal-footer {
        padding: 12px 20px; border-top: 1px solid #e2e8f0;
        display: flex; justify-content: flex-end; gap: 8px;
    }
    .ris-preview-modal-btn-close { padding: 8px 16px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; font-size: 12px; font-weight: 600; cursor: pointer; }
    .ris-preview-modal-btn-print { padding: 8px 16px; border-radius: 8px; background: #0f172a; color: #fff; font-size: 12px; font-weight: 600; text-decoration: none; }
</style>
<div id="risPreviewModal" class="ris-preview-modal-overlay">
    <div class="ris-preview-modal-container">
        <div class="ris-preview-modal-header">
            <h3 class="ris-preview-modal-title">Report Preview</h3>
            <button type="button" class="ris-preview-modal-close" onclick="closeReceivingRisPreview()"><i data-lucide="x"></i></button>
        </div>
        <div class="ris-preview-modal-body" id="risPreviewModalBody"></div>
        <div class="ris-preview-modal-footer">
            <button type="button" class="ris-preview-modal-btn-close" onclick="closeReceivingRisPreview()">Close</button>
            <a href="#" id="risPreviewPrintLink" target="_blank" class="ris-preview-modal-btn-print">Open in Print View</a>
        </div>
    </div>
</div>
<script>
window.openReceivingRisPreview = function (risId) {
    if (!risId) return;
    var modal = document.getElementById('risPreviewModal');
    var body = document.getElementById('risPreviewModalBody');
    var printLink = document.getElementById('risPreviewPrintLink');
    var url = '/receiving/ris/' + risId + '/print?ts=' + Date.now();
    if (printLink) printLink.href = url;
    modal.style.display = 'flex';
    body.innerHTML = '';
    var iframe = document.createElement('iframe');
    iframe.src = url;
    body.appendChild(iframe);
};
window.closeReceivingRisPreview = function () {
    var modal = document.getElementById('risPreviewModal');
    var body = document.getElementById('risPreviewModalBody');
    if (body) body.innerHTML = '';
    if (modal) modal.style.display = 'none';
};
document.addEventListener('click', function (e) {
    var modal = document.getElementById('risPreviewModal');
    if (modal && e.target === modal) closeReceivingRisPreview();
});
</script>
