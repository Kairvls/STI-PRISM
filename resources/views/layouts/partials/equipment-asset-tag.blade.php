<script>
    window.equipmentAssetTags = (function () {
        const used = new Set(
            (@json(($usedAssetTags ?? collect())->values()->all()))
                .map((tag) => String(tag || '').trim().toLowerCase())
                .filter(Boolean)
        );

        const reserved = new Set();

        function part(value, fallback) {
            return String(value || '')
                .toUpperCase()
                .replace(/[^A-Z0-9]+/g, '')
                || fallback;
        }

        function prefix(roomName, equipmentName) {
            const room = part(roomName, 'ROOM');
            const equipment = part(equipmentName, 'EQ');
            return `${room} - ${equipment} - `;
        }

        function formatTag(tagPrefix, sequence) {
            return `${tagPrefix}${String(sequence).padStart(3, '0')}`;
        }

        function isTaken(tag) {
            const key = String(tag || '').trim().toLowerCase();
            return !key || used.has(key) || reserved.has(key);
        }

        function maxSequenceForPrefix(tagPrefix) {
            const normalizedPrefix = String(tagPrefix || '').toLowerCase();
            let max = 0;

            const scan = (tag) => {
                const value = String(tag || '').trim();
                if (!value.toLowerCase().startsWith(normalizedPrefix)) {
                    return;
                }
                const suffix = value.slice(tagPrefix.length);
                const match = suffix.match(/^(\d+)/);
                if (match) {
                    max = Math.max(max, Number(match[1]) || 0);
                }
            };

            used.forEach(scan);
            reserved.forEach(scan);

            return max;
        }

        function nextSequence(tagPrefix) {
            let sequence = maxSequenceForPrefix(tagPrefix) + 1;
            while (isTaken(formatTag(tagPrefix, sequence))) {
                sequence += 1;
            }
            return sequence;
        }

        function generate(roomName, equipmentName, count = 1) {
            const qty = Math.min(200, Math.max(1, Number(count) || 1));
            const tagPrefix = prefix(roomName, equipmentName);
            const tags = [];
            let sequence = nextSequence(tagPrefix);

            for (let i = 0; i < qty; i += 1) {
                while (isTaken(formatTag(tagPrefix, sequence))) {
                    sequence += 1;
                }
                const tag = formatTag(tagPrefix, sequence);
                tags.push(tag);
                reserved.add(tag.toLowerCase());
                sequence += 1;
            }

            return tags;
        }

        function release(tags) {
            (tags || []).forEach((tag) => {
                const key = String(tag || '').trim().toLowerCase();
                if (key) {
                    reserved.delete(key);
                }
            });
        }

        function register(tags) {
            (tags || []).forEach((tag) => {
                const key = String(tag || '').trim().toLowerCase();
                if (key) {
                    used.add(key);
                }
            });
        }

        function resetReserved() {
            reserved.clear();
        }

        return {
            part,
            prefix,
            formatTag,
            generate,
            register,
            release,
            resetReserved,
            isTaken,
        };
    })();
</script>
