<script>
    window.equipmentCategoryDetect = @json(\App\Support\SuggestedIssues::categoryDetectPayload($categories ?? collect()));

    window.detectEquipmentCategoryId = function (name) {
        const payload = window.equipmentCategoryDetect || { ids: {}, rules: [] };
        const lower = String(name || '').toLowerCase().trim();

        if (!lower) {
            return '';
        }

        let bestId = '';
        let bestLen = 0;

        for (const rule of payload.rules || []) {
            const except = rule.except || [];
            if (except.some((item) => lower.includes(item))) {
                continue;
            }

            for (const needle of (rule.needles || [])) {
                const n = String(needle || '').toLowerCase().trim();
                if (!n || !lower.includes(n) || n.length <= bestLen) {
                    continue;
                }
                bestLen = n.length;
                bestId = String(payload.ids[rule.category] || '');
            }
        }

        return bestId;
    };

    window.bindEquipmentCategoryAutodetect = function (nameInput, categorySelect) {
        if (!nameInput || !categorySelect || categorySelect.dataset.categoryDetectBound === '1') {
            return;
        }

        categorySelect.dataset.categoryDetectBound = '1';
        let manual = false;

        const applyCategoryValue = function (id) {
            const next = String(id || '');
            if (String(categorySelect.value || '') === next) {
                return;
            }
            categorySelect.dataset.autoCategory = '1';
            categorySelect.value = next;
            categorySelect.dispatchEvent(new Event('change', { bubbles: true }));
            categorySelect.dispatchEvent(new Event('input', { bubbles: true }));
        };

        categorySelect.addEventListener('change', function () {
            if (categorySelect.dataset.autoCategory === '1') {
                delete categorySelect.dataset.autoCategory;
                return;
            }
            manual = true;
        });

        nameInput.addEventListener('input', function () {
            if (!String(nameInput.value || '').trim()) {
                manual = false;
                applyCategoryValue('');
                return;
            }

            if (manual) {
                return;
            }

            applyCategoryValue(window.detectEquipmentCategoryId(nameInput.value));
        });

        nameInput.addEventListener('equipment-category-reset', function () {
            manual = false;
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        window.bindEquipmentCategoryAutodetect(
            document.getElementById('add_equipment_name'),
            document.getElementById('add_equipment_category')
        );
    });
</script>
