<script>
    window.equipmentCategoryDetect = @json(\App\Support\SuggestedIssues::categoryDetectPayload($categories ?? collect()));

    window.detectEquipmentCategoryId = function (name) {
        const payload = window.equipmentCategoryDetect || { ids: {}, rules: [] };
        const lower = String(name || '').toLowerCase().trim();

        if (!lower) {
            return '';
        }

        for (const rule of payload.rules || []) {
            const except = rule.except || [];
            if (except.some((item) => lower.includes(item))) {
                continue;
            }

            if ((rule.needles || []).some((needle) => lower.includes(needle))) {
                return String(payload.ids[rule.category] || '');
            }
        }

        return '';
    };

    window.bindEquipmentCategoryAutodetect = function (nameInput, categorySelect) {
        if (!nameInput || !categorySelect || categorySelect.dataset.categoryDetectBound === '1') {
            return;
        }

        categorySelect.dataset.categoryDetectBound = '1';
        let manual = false;

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
                if (categorySelect.value !== '') {
                    categorySelect.dataset.autoCategory = '1';
                    categorySelect.value = '';
                }
                return;
            }

            if (manual) {
                return;
            }

            const id = window.detectEquipmentCategoryId(nameInput.value);
            if (categorySelect.value === id) {
                return;
            }

            categorySelect.dataset.autoCategory = '1';
            categorySelect.value = id;
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
