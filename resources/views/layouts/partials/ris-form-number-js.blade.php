{{-- Shared RIS No. label helper: RIS-YYYYMM-0000000 --}}
<script>
    window.risFormNumberLabel = window.risFormNumberLabel || function (risId, formNumber) {
        if (formNumber) return String(formNumber);
        var id = Number(risId) || 0;
        var d = new Date();
        var ym = String(d.getFullYear()) + String(d.getMonth() + 1).padStart(2, '0');
        var seq = String(Math.min(Math.max(id, 0), 9999999)).padStart(7, '0');
        return 'RIS-' + ym + '-' + seq;
    };
</script>
