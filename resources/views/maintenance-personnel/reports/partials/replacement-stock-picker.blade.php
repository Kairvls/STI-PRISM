@once
<div
    id="replacementStockModal"
    class="fixed inset-0 z-[90] hidden items-center justify-center bg-slate-900/50 p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="replacementStockTitle"
>
    <div class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <i data-lucide="package-check" class="h-5 w-5"></i>
                </span>
                <div>
                    <h2 id="replacementStockTitle" class="text-base font-semibold text-slate-900">Stock available in inventory</h2>
                    <p class="mt-0.5 text-sm text-slate-500">
                        Swap in a unit from storage now, or send the item to the Purchaser as a procurement request.
                    </p>
                </div>
            </div>
            <button
                type="button"
                onclick="ReplacementStockPicker.close()"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                aria-label="Close"
            >
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <div id="replacementStockBody" class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5"></div>

        <div class="flex shrink-0 flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/70 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <button
                type="button"
                onclick="ReplacementStockPicker.proceedToProcurement()"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                Proceed to procurement request anyway
            </button>
            <div class="flex items-center justify-end gap-3">
                <button
                    type="button"
                    onclick="ReplacementStockPicker.close()"
                    class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100"
                >
                    Back
                </button>
                <button
                    type="button"
                    id="replacementStockConfirm"
                    onclick="ReplacementStockPicker.confirm()"
                    disabled
                    class="rounded-xl bg-[#0025cc] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#001fad] disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.ReplacementStockPicker = (function () {
        const ACTION_PATTERN = /\/maintenance\/reports\/update-status\/(\d+)/;
        let activeForm = null;
        let activeData = null;

        const modal = () => document.getElementById("replacementStockModal");
        const body = () => document.getElementById("replacementStockBody");
        const confirmButton = () => document.getElementById("replacementStockConfirm");

        function esc(value) {
            return String(value ?? "").replace(/[&<>"']/g, (c) => ({
                "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;",
            })[c]);
        }

        function submitButton(form) {
            return form.querySelector('button[type="submit"]');
        }

        function setChecking(form, checking) {
            const button = submitButton(form);
            if (!button) return;
            if (checking) {
                button.dataset.rsLabel = button.innerHTML;
                button.disabled = true;
                button.textContent = "Checking stock…";
            } else if (button.dataset.rsLabel !== undefined) {
                button.disabled = false;
                button.innerHTML = button.dataset.rsLabel;
                delete button.dataset.rsLabel;
            }
        }

        function submitWith(form, choices) {
            form.querySelectorAll(".rs-hidden").forEach((input) => input.remove());
            Object.entries(choices).forEach(([itemId, value]) => {
                const input = document.createElement("input");
                input.type = "hidden";
                input.className = "rs-hidden";
                input.name = `replacement_choice[${itemId}]`;
                input.value = value;
                form.appendChild(input);
            });
            form.dataset.stockChecked = "1";
            if (typeof form.requestSubmit === "function") {
                form.requestSubmit();
            } else {
                form.submit();
            }
        }

        function stockItems() {
            return (activeData?.items || []).filter((item) => item.candidates.length > 0);
        }

        function selectedChoices() {
            const choices = {};
            stockItems().forEach((item) => {
                const picked = body().querySelector(`input[name="rs-choice-${item.report_item_id}"]:checked`);
                if (picked) choices[item.report_item_id] = picked.value;
            });
            return choices;
        }

        function refresh() {
            const choices = selectedChoices();
            const taken = new Set(Object.values(choices).filter((value) => value !== "procurement"));

            body().querySelectorAll("input[data-rs-unit]").forEach((input) => {
                const lockedElsewhere = taken.has(input.value) && !input.checked;
                input.disabled = lockedElsewhere;
                input.closest("label")?.classList.toggle("opacity-40", lockedElsewhere);
            });

            const items = stockItems();
            const complete = items.every((item) => choices[item.report_item_id]);
            const swaps = Object.values(choices).filter((value) => value !== "procurement").length;
            const button = confirmButton();
            button.disabled = !complete;
            button.textContent = swaps > 0
                ? `Swap in ${swaps} unit${swaps === 1 ? "" : "s"}`
                : "Send to procurement";
        }

        function candidateCard(item, unit) {
            const model = [unit.brand, unit.model].filter(Boolean).join(" ");
            const field = (label, value) => `
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">${esc(label)}</p>
                    <p class="mt-0.5 truncate text-sm text-slate-800">${esc(value || "—")}</p>
                </div>`;

            return `
                <label class="flex cursor-pointer gap-3 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-slate-300 has-[:checked]:border-[#0025cc] has-[:checked]:ring-2 has-[:checked]:ring-[#0025cc]/15">
                    <input type="radio" name="rs-choice-${item.report_item_id}" value="${unit.equipment_id}" data-rs-unit
                        class="mt-1 border-slate-300 text-[#0025cc] focus:ring-[#0025cc]" />
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-slate-900">${esc(unit.name)}</span>
                            ${model ? `<span class="text-sm text-slate-500">${esc(model)}</span>` : ""}
                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-200">${esc(unit.match)}</span>
                        </span>
                        <span class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 sm:grid-cols-3">
                            ${field("Asset tag", unit.asset_tag)}
                            ${field("Serial no.", unit.serial)}
                            ${field("Storage", unit.storage)}
                            ${field("Condition", unit.condition)}
                            <span class="col-span-2">
                                <span class="block text-[11px] font-semibold uppercase tracking-wider text-slate-400">Warranty</span>
                                <span class="mt-0.5 block text-sm ${unit.warranty_active ? "text-emerald-700" : "text-slate-500"}">${esc(unit.warranty)}</span>
                            </span>
                        </span>
                        <a href="${esc(unit.view_url)}" target="_blank" rel="noopener" onclick="event.stopPropagation()"
                            class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-[#0025cc] hover:underline">
                            View profile <i data-lucide="external-link" class="h-3 w-3"></i>
                        </a>
                    </span>
                </label>`;
        }

        function itemSection(item) {
            const heading = `
                <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">${esc(item.name)}</p>
                        <p class="text-xs text-slate-500">${esc([item.asset_tag, item.room].filter(Boolean).join(" · ") || "Reported item")}</p>
                    </div>
                    <span class="text-xs font-semibold ${item.candidates.length ? "text-emerald-700" : "text-slate-400"}">
                        ${item.candidates.length ? `${item.candidates.length} in stock` : "No matching stock"}
                    </span>
                </div>`;

            if (!item.candidates.length) {
                return `
                    <section>
                        ${heading}
                        <div class="flex items-start gap-2 rounded-xl bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-500">
                            <i data-lucide="file-text" class="mt-0.5 h-4 w-4 shrink-0"></i>
                            <p>Nothing matching is in storage, so this item goes to the Purchaser as a procurement request (RIS, ATP, RFC).</p>
                        </div>
                    </section>`;
            }

            return `
                <section>
                    ${heading}
                    <div class="space-y-2">
                        ${item.candidates.map((unit) => candidateCard(item, unit)).join("")}
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-600 transition hover:bg-slate-50 has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50/60">
                            <input type="radio" name="rs-choice-${item.report_item_id}" value="procurement"
                                class="border-slate-300 text-amber-600 focus:ring-amber-500" />
                            Don't use stock. Send this item to procurement instead.
                        </label>
                    </div>
                </section>`;
        }

        function open(form, data) {
            activeForm = form;
            activeData = data;
            body().innerHTML = data.items.map(itemSection).join("");
            body().querySelectorAll('input[type="radio"]').forEach((input) => input.addEventListener("change", refresh));
            refresh();
            modal().classList.remove("hidden");
            modal().classList.add("flex");
            if (window.lucide) window.lucide.createIcons();
        }

        function close() {
            modal().classList.add("hidden");
            modal().classList.remove("flex");
            activeForm = null;
            activeData = null;
        }

        function confirm() {
            if (!activeForm) return;
            const form = activeForm;
            const choices = selectedChoices();
            close();
            submitWith(form, choices);
        }

        function proceedToProcurement() {
            if (!activeForm) return;
            const form = activeForm;
            const choices = {};
            stockItems().forEach((item) => { choices[item.report_item_id] = "procurement"; });
            close();
            submitWith(form, choices);
        }

        async function check(form, reportId) {
            const params = new URLSearchParams();
            form.querySelectorAll('input[name="report_item_ids[]"]:checked').forEach((input) => {
                params.append("item_ids[]", input.value);
            });

            setChecking(form, true);
            try {
                const response = await fetch(`/maintenance/reports/${reportId}/replacement-stock?${params}`, {
                    headers: { Accept: "application/json" },
                    credentials: "same-origin",
                });
                if (!response.ok) throw new Error("Stock check failed");
                const data = await response.json();
                setChecking(form, false);

                if (!data.has_stock) {
                    submitWith(form, {});
                    return;
                }
                open(form, data);
            } catch (error) {
                setChecking(form, false);
                submitWith(form, {});
            }
        }

        document.addEventListener("submit", function (event) {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;

            const match = (form.getAttribute("action") || "").match(ACTION_PATTERN);
            const status = form.querySelector('[name="status"]');
            if (!match || !status || status.value !== "For Replacement") return;

            if (form.dataset.stockChecked === "1") {
                delete form.dataset.stockChecked;
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            check(form, match[1]);
        }, true);

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && !modal().classList.contains("hidden")) close();
        });

        return { close, confirm, proceedToProcurement };
    })();
</script>
@endonce
