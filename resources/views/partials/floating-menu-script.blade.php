<script>
    // Places a body-teleported dropdown next to its trigger so table overflow can't clip it.
    window.purPlaceMenu = window.purPlaceMenu || function (trigger, menu) {
        if (!trigger || !menu) return;
        const rect = trigger.getBoundingClientRect();
        const gap = 6;
        const width = menu.offsetWidth;
        const height = menu.offsetHeight;
        const left = Math.max(8, Math.min(rect.right - width, window.innerWidth - width - 8));
        const fitsBelow = rect.bottom + gap + height <= window.innerHeight - 8;
        const top = fitsBelow || rect.top - gap - height < 8 ? rect.bottom + gap : rect.top - gap - height;
        menu.style.left = left + 'px';
        menu.style.top = top + 'px';
    };
</script>
