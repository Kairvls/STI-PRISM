@once
<script>
    function inventoryTransferDestinationChanged(prefix) {
        const wrapper = document.querySelector(`[data-transfer-destination="${prefix}"]`);
        if (!wrapper) return;

        const type = wrapper.querySelector('input[type="radio"]:checked')?.value || 'room';
        const fields = {
            person: [document.getElementById(prefix + 'PersonField'), document.getElementById(prefix + 'CustodianId')],
            department: [document.getElementById(prefix + 'DepartmentField'), document.getElementById(prefix + 'DepartmentId')],
        };

        Object.entries(fields).forEach(([key, [field, select]]) => {
            const active = key === type;
            field?.classList.toggle('hidden', !active);
            if (select) {
                select.disabled = !active;
                select.required = active;
            }
        });

        const roomLabel = document.getElementById(prefix + 'RoomLabel');
        if (roomLabel) {
            roomLabel.textContent = type === 'room' ? 'Destination room' : 'Place it in (room)';
        }
    }

    function inventoryTransferPersonChanged(prefix) {
        const option = document.getElementById(prefix + 'CustodianId')?.selectedOptions[0];
        const roomSelect = document.getElementById(prefix + 'RoomId');
        const homeRoomId = option?.dataset.roomId || '';

        if (roomSelect && homeRoomId) {
            roomSelect.value = homeRoomId;
        }
    }

    function resetInventoryTransferDestination(prefix) {
        const wrapper = document.querySelector(`[data-transfer-destination="${prefix}"]`);
        if (!wrapper) return;

        const roomRadio = wrapper.querySelector('input[type="radio"][value="room"]');
        if (roomRadio) roomRadio.checked = true;
        ['RoomId', 'CustodianId', 'DepartmentId'].forEach((suffix) => {
            const select = document.getElementById(prefix + suffix);
            if (select) select.value = '';
        });
        setInventoryTransferQuantity(prefix, null, null);
        inventoryTransferDestinationChanged(prefix);
    }

    function setInventoryTransferQuantity(prefix, trackingMode, stockQty) {
        const field = document.getElementById(prefix + 'QuantityField');
        const input = document.getElementById(prefix + 'Quantity');
        const available = document.getElementById(prefix + 'QuantityAvailable');
        const stock = Math.max(1, Number(stockQty) || 1);
        const isBulk = String(trackingMode || '') === 'Bulk' && stock > 1;

        if (!field || !input) return;

        field.classList.toggle('hidden', !isBulk);
        input.disabled = !isBulk;
        input.required = isBulk;
        input.min = '1';
        input.max = String(stock);
        input.value = isBulk ? String(stock) : '';
        if (available) available.textContent = String(stock);
    }

    function buildInventoryBatchTransferRows(container, template, items) {
        if (!container || !template) return;
        const prefixBase = template.dataset.prefixBase || 'batchRow';
        container.innerHTML = '';

        items.forEach((item, index) => {
            const prefix = prefixBase + index;
            const html = template.innerHTML
                .replaceAll('__INDEX__', String(index))
                .replaceAll('__PREFIX__', prefix);
            const holder = document.createElement('div');
            holder.innerHTML = html.trim();
            const row = holder.firstElementChild;
            if (!row) return;

            const nameEl = row.querySelector('[data-item-name]');
            if (nameEl) nameEl.textContent = item.name || 'Equipment';
            const idInput = row.querySelector('[data-equipment-id]');
            if (idInput) idInput.value = String(item.id);

            container.appendChild(row);
            inventoryTransferDestinationChanged(prefix);
        });

        if (window.lucide) window.lucide.createIcons();
    }

    function applyFirstBatchDestinationToAll(container) {
        if (!container) return;
        const rows = Array.from(container.querySelectorAll('[data-batch-row]'));
        if (rows.length < 2) return;

        const first = rows[0];
        const type = first.querySelector('input[type="radio"]:checked')?.value || 'room';
        const room = first.querySelector('select[name$="[room_id]"]')?.value || '';
        const person = first.querySelector('select[name$="[custodian_id]"]')?.value || '';
        const department = first.querySelector('select[name$="[department_id]"]')?.value || '';

        rows.slice(1).forEach((row) => {
            const radio = row.querySelector(`input[type="radio"][value="${type}"]`);
            if (radio) radio.checked = true;
            const prefix = row.getAttribute('data-transfer-destination');
            if (prefix) inventoryTransferDestinationChanged(prefix);

            const roomSelect = row.querySelector('select[name$="[room_id]"]');
            if (roomSelect) roomSelect.value = room;
            const personSelect = row.querySelector('select[name$="[custodian_id]"]');
            if (personSelect && !personSelect.disabled) personSelect.value = person;
            const departmentSelect = row.querySelector('select[name$="[department_id]"]');
            if (departmentSelect && !departmentSelect.disabled) departmentSelect.value = department;
        });
    }
</script>
@endonce
