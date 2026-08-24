{{-- Shared equipment layout icon helpers (loaded into Alpine component methods) --}}
<script>
window.PrismEquipmentIcons = {
    category(name) {
        const n = String(name || '').toLowerCase();

        if (/monitor|display|screen/.test(n) && !/tv|television/.test(n)) return 'monitor';
        if (/keyboard/.test(n)) return 'keyboard';
        if (/mouse/.test(n)) return 'mouse';
        if (/system\s*unit|cpu|desktop|tower|\bpc\b/.test(n)) return 'system_unit';
        if (/\bups\b|\bavr\b/.test(n)) return 'power';
        if (/ethernet|internet\s*cable|lan\s*cable|network\s*cable/.test(n)) return 'network';
        if (/printer|inkjet|laser|multifunction/.test(n)) return 'printer';
        if (/projector/.test(n)) return 'projector';
        if (/\btv\b|television|flat\s*screen/.test(n)) return 'tv';
        if (/air\s*con|airconditioner|split\s*type|window\s*air|floor\s*standing\s*air|inverter/.test(n)) return 'aircon';
        if (/ceiling\s*fan|wall\s*fan|electric\s*fan|\bfan\b/.test(n)) return 'fan';
        if (/fluorescent|led\s*light|light\s*bulb|\bbulb\b|cfl/.test(n)) return 'bulb';
        if (/whiteboard|white\s*board/.test(n)) return 'whiteboard';
        if (/curtain/.test(n)) return 'curtain';
        if (/arm\s*desk|office\s*chair|monoblock|stool|stall\s*chair|\bchair\b/.test(n)) return 'chair';
        if (/laboratory\s*table|long\s*table|classroom\s*table|office\s*table|\btable\b/.test(n)) return 'table';

        return 'default';
    },

    group(name) {
        const cat = this.category(name);
        const map = {
            chair: 'Furniture and Fixtures',
            table: 'Furniture and Fixtures',
            whiteboard: 'Furniture and Fixtures',
            curtain: 'Furniture and Fixtures',
            fan: 'Ventilation Equipment',
            aircon: 'Air Conditioning Equipment',
            tv: 'Display Equipment',
            projector: 'Display Equipment',
            monitor: 'Computer Equipment',
            mouse: 'Computer Equipment',
            keyboard: 'Computer Equipment',
            system_unit: 'Computer Equipment',
            power: 'Computer Equipment',
            network: 'Network Equipment',
            bulb: 'Lighting Equipment',
            printer: 'Printing Equipment',
            default: 'Other Equipment',
        };
        return map[cat] || map.default;
    },

    svg(name, size = null) {
        const cat = this.category(name);
        const sizeAttr = size
            ? `width="${size}" height="${size}"`
            : `width="100%" height="100%"`;
        const common = `xmlns="http://www.w3.org/2000/svg" ${sizeAttr} viewBox="0 0 24 24" fill="none" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"`;

        const icons = {
            chair: `<svg ${common} stroke="#0f766e"><path d="M6 19V9a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v10"/><path d="M6 13h12"/><path d="M8 19v2"/><path d="M16 19v2"/><path d="M6 9V7a2 2 0 0 1 2-2h8"/></svg>`,
            table: `<svg ${common} stroke="#92400e"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M6 12v7"/><path d="M18 12v7"/><path d="M4 19h4"/><path d="M16 19h4"/></svg>`,
            whiteboard: `<svg ${common} stroke="#1d4ed8"><rect x="3" y="4" width="18" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 18v3"/><path d="M7 9h6"/><path d="M7 12h10"/></svg>`,
            curtain: `<svg ${common} stroke="#7c3aed"><path d="M4 4h16"/><path d="M6 4v16"/><path d="M18 4v16"/><path d="M6 8c2 2 2 4 0 6"/><path d="M18 8c-2 2-2 4 0 6"/><path d="M10 4v4c0 2-1 3-2 4"/><path d="M14 4v4c0 2 1 3 2 4"/></svg>`,
            fan: `<svg ${common} stroke="#0369a1"><circle cx="12" cy="12" r="2"/><path d="M12 4c2.5 2 3.5 4 2.5 6.5"/><path d="M20 12c-2 2.5-4 3.5-6.5 2.5"/><path d="M12 20c-2.5-2-3.5-4-2.5-6.5"/><path d="M4 12c2-2.5 4-3.5 6.5-2.5"/></svg>`,
            aircon: `<svg ${common} stroke="#0284c7"><rect x="3" y="5" width="18" height="8" rx="2"/><path d="M7 13v2"/><path d="M12 13v3"/><path d="M17 13v2"/><path d="M5 18h2"/><path d="M11 19h2"/><path d="M17 18h2"/></svg>`,
            tv: `<svg ${common} stroke="#334155"><rect x="3" y="5" width="18" height="12" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>`,
            projector: `<svg ${common} stroke="#b45309"><rect x="2" y="8" width="14" height="8" rx="2"/><circle cx="9" cy="12" r="2"/><path d="M16 10h3l3 2v0l-3 2h-3"/><path d="M6 16v2"/><path d="M12 16v2"/></svg>`,
            monitor: `<svg ${common} stroke="#005EA6"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8"/><path d="M12 16v4"/></svg>`,
            mouse: `<svg ${common} stroke="#005EA6"><rect x="8" y="3" width="8" height="18" rx="4"/><path d="M12 3v5"/></svg>`,
            keyboard: `<svg ${common} stroke="#005EA6"><rect x="2" y="7" width="20" height="10" rx="2"/><path d="M6 11h.01"/><path d="M10 11h.01"/><path d="M14 11h.01"/><path d="M18 11h.01"/><path d="M7 14h10"/></svg>`,
            system_unit: `<svg ${common} stroke="#005EA6"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 6h2"/><path d="M11 10h2"/><circle cx="12" cy="16" r="1.25" fill="#005EA6" stroke="none"/></svg>`,
            power: `<svg ${common} stroke="#ca8a04"><rect x="6" y="4" width="12" height="16" rx="2"/><path d="M10 8h4"/><path d="M10 12h4"/><path d="M10 16h2"/></svg>`,
            network: `<svg ${common} stroke="#0f766e"><path d="M4 9v6"/><path d="M8 7v10"/><path d="M12 5v14"/><path d="M16 8v8"/><path d="M20 10v4"/><path d="M3 12h18"/></svg>`,
            bulb: `<svg ${common} stroke="#ca8a04"><path d="M9 18h6"/><path d="M10 21h4"/><path d="M12 3a6 6 0 0 0-3 11c.5.6 1 1.3 1 2h4c0-.7.5-1.4 1-2a6 6 0 0 0-3-11z"/></svg>`,
            printer: `<svg ${common} stroke="#475569"><path d="M6 9V3h12v6"/><path d="M6 17H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><rect x="6" y="13" width="12" height="8" rx="1"/></svg>`,
            default: `<svg ${common} stroke="#64748b"><path d="M9 3h6l3 5v11a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V8l3-5z"/><path d="M9 3v5h6"/></svg>`,
        };

        return icons[cat] || icons.default;
    },
};
</script>
