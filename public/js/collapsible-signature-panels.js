/**
 * System-wide Collapsible Signature Panel Enhancer
 * Transforms procurement signature cards (School Admin, President, Accounting,
 * Purchaser, Receiving Officer) into the compact 1-row bar with live mini preview
 * and "Change / Draw signature" toggle, matching Canvass Review.
 */
(function () {
    'use strict';

    function isDataImage(val) {
        return typeof val === 'string' && val.trim().indexOf('data:image/') === 0;
    }

    function findPanelSignatureUrl(panel) {
        // 1. Check nearby form hidden signature inputs
        var form = panel.closest('form') || panel.parentElement || document;
        var hiddenCandidates = [
            '#ris_issued_by_signature_image',
            '#ris_checked_by_signature_image',
            '#pa_approved_by_signature_image',
            '#accSigImageInput',
            '#roSigImageInput',
            'input[name="ris_requested_by_signature"]',
            'input[name="signature_image"]',
            'input[name="approved_by_signature"]',
            'input[name$="_signature"]'
        ];
        for (var i = 0; i < hiddenCandidates.length; i++) {
            var inp = form.querySelector(hiddenCandidates[i]);
            if (inp && isDataImage(inp.value)) {
                return inp.value.trim();
            }
        }

        // 2. Check visible signature overlay images in the same form/modal
        var overlayCandidates = [
            '#daIssuedSigOverlay',
            '#daCheckedSigOverlay',
            '#paApprovedSigOverlay',
            '#accSigPreview',
            '#accPaperSigOverlay',
            'img[id^="purSigOverlay-"]',
            'img[id^="roSigOverlay-"]'
        ];
        for (var j = 0; j < overlayCandidates.length; j++) {
            var img = form.querySelector(overlayCandidates[j]);
            if (img && img.style.display !== 'none' && isDataImage(img.getAttribute('src'))) {
                return img.getAttribute('src').trim();
            }
        }

        // 3. Check if an active saved card is highlighted (Alpine Purchaser RIS)
        var activeSaved = panel.querySelector('.border-\\[\\#0025cc\\] img');
        if (activeSaved && activeSaved.getAttribute('src')) {
            return activeSaved.getAttribute('src').trim();
        }

        return '';
    }

    function enhancePanel(panel) {
        if (!panel || panel.dataset.sigCollapsible === '1') return;
        // Skip Canvass Review's own native collapsible bar
        if (panel.closest('[x-data*="canvassReviewCard"]')) return;

        var children = Array.from(panel.children);
        if (children.length < 2) return;

        var headerRow = children[0];
        var titleEl = headerRow.querySelector('h4, h3');
        if (!titleEl) return;

        panel.dataset.sigCollapsible = '1';

        // Wrap everything after the header row into a collapsible drawer
        var bodyWrap = document.createElement('div');
        bodyWrap.setAttribute('data-sig-collapsible-body', '1');
        bodyWrap.className = 'mt-3 border-t border-slate-100 pt-3';

        for (var i = 1; i < children.length; i++) {
            // Keep <style> tags outside or inside harmlessly; moving them inside is fine
            bodyWrap.appendChild(children[i]);
        }
        panel.appendChild(bodyWrap);

        // Hide the old standalone "Signature added" badge so we don't duplicate status
        var oldBadge = headerRow.querySelector('[id$="SignBadge"], [x-show*="createSignaturePreview"]');
        if (oldBadge) {
            oldBadge.style.display = 'none';
            oldBadge.setAttribute('data-sig-suppressed-badge', '1');
        }

        // Restyle header row to match the sleek 1-row Canvass Review bar
        headerRow.className = 'flex flex-wrap items-center justify-between gap-3';

        var leftGroup = document.createElement('div');
        leftGroup.className = 'flex flex-wrap items-center gap-3';

        // Clean title with colon
        var rawTitle = titleEl.textContent.replace(/\*/g, '').replace(/:$/, '').trim();
        var titleLabel = document.createElement('span');
        titleLabel.className = 'text-xs font-bold text-slate-800';
        titleLabel.textContent = rawTitle + ':';
        // Preserve original heading element hidden (in case scripts update #purDocSignTitle)
        var titleSpanInside = titleEl.querySelector('#purDocSignTitle');
        if (titleSpanInside) {
            titleLabel.innerHTML = '';
            titleLabel.appendChild(titleSpanInside);
            titleLabel.appendChild(document.createTextNode(':'));
        }
        titleEl.style.display = 'none';
        leftGroup.appendChild(titleLabel);

        // Mini preview pill (matches Image 3: [ sig img ] [ ✓ Ready ])
        var readyPill = document.createElement('div');
        readyPill.className = 'hidden inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1';
        readyPill.setAttribute('data-sig-ready-pill', '1');
        readyPill.innerHTML =
            '<img data-sig-mini-img alt="Signature" class="h-6 max-w-[90px] object-contain" style="display:none;">' +
            '<span class="rounded px-1.5 py-0.5 text-[11px] font-semibold" style="background:#ecfdf5;color:#059669;">✓ Ready</span>';
        leftGroup.appendChild(readyPill);

        var requiredHint = document.createElement('span');
        requiredHint.className = 'text-xs font-semibold';
        requiredHint.style.color = '#dc2626';
        requiredHint.textContent = 'Required';
        leftGroup.appendChild(requiredHint);

        var toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50';
        toggleBtn.setAttribute('data-sig-toggle-btn', '1');

        headerRow.insertBefore(leftGroup, headerRow.firstChild);
        headerRow.appendChild(toggleBtn);

        var miniImg = readyPill.querySelector('[data-sig-mini-img]');
        var isOpen = false;
        var lastUrl = '';

        function updateUi(sigUrl, forceOpenState) {
            if (typeof forceOpenState === 'boolean') {
                isOpen = forceOpenState;
            }
            var hasSig = Boolean(sigUrl);
            readyPill.classList.toggle('hidden', !hasSig);
            requiredHint.classList.toggle('hidden', hasSig);
            if (miniImg) {
                if (hasSig) {
                    miniImg.src = sigUrl;
                    miniImg.style.display = '';
                } else {
                    miniImg.removeAttribute('src');
                    miniImg.style.display = 'none';
                }
            }
            bodyWrap.classList.toggle('hidden', !isOpen);
            toggleBtn.textContent = isOpen
                ? 'Hide signature pad'
                : (hasSig ? 'Change / Draw signature' : 'Draw or attach signature');
        }

        toggleBtn.addEventListener('click', function () {
            isOpen = !isOpen;
            updateUi(lastUrl);
        });

        // Collapse automatically when user selects a saved signature card
        bodyWrap.addEventListener('click', function (ev) {
            var useBtn = ev.target.closest(
                '.admin-da-use-saved, .pa-use-saved, .acc-use-saved, .pur-doc-use-saved, .ro-use-saved, button[title="Use this signature"]'
            );
            if (useBtn) {
                var cardImg = useBtn.querySelector('img');
                var pickedSrc = cardImg ? (cardImg.getAttribute('src') || '') : '';
                setTimeout(function () {
                    var resolved = findPanelSignatureUrl(panel) || pickedSrc;
                    lastUrl = resolved;
                    updateUi(lastUrl, !resolved);
                }, 40);
                return;
            }

            var clearBtn = ev.target.closest('[id$="ClearSign"]');
            if (clearBtn) {
                setTimeout(function () {
                    lastUrl = '';
                    updateUi('', true);
                }, 30);
            }
        });

        // Keep mini preview in sync whenever signature changes via draw pad or upload
        function syncFromDom(collapseOnNewSig) {
            var current = findPanelSignatureUrl(panel);
            if (current !== lastUrl) {
                var newlyAdded = !lastUrl && Boolean(current);
                lastUrl = current;
                if (collapseOnNewSig && newlyAdded) {
                    updateUi(lastUrl, false);
                } else {
                    updateUi(lastUrl);
                }
            }
        }

        // Initial setup: if no signature applied yet but a saved signature exists, auto-apply first saved signature
        setTimeout(function () {
            var initial = findPanelSignatureUrl(panel);
            if (!initial) {
                var firstSavedBtn = bodyWrap.querySelector(
                    '.admin-da-use-saved, .pa-use-saved, .acc-use-saved, .pur-doc-use-saved, .ro-use-saved, button[title="Use this signature"]'
                );
                if (firstSavedBtn) {
                    // Snapshot ancestor scroll positions so auto-selecting the saved signature
                    // never scrolls the modal away from the top Reason field.
                    var scrollSnapshots = [];
                    var scroller = panel.parentElement;
                    while (scroller && scroller !== document.body) {
                        scrollSnapshots.push([scroller, scroller.scrollTop]);
                        scroller = scroller.parentElement;
                    }
                    firstSavedBtn.click();
                    if (document.activeElement === firstSavedBtn && firstSavedBtn.blur) {
                        firstSavedBtn.blur();
                    }
                    scrollSnapshots.forEach(function (pair) {
                        pair[0].scrollTop = pair[1];
                    });
                    var firstImg = firstSavedBtn.querySelector('img');
                    setTimeout(function () {
                        lastUrl = findPanelSignatureUrl(panel) || (firstImg ? firstImg.getAttribute('src') || '' : '');
                        updateUi(lastUrl, !lastUrl);
                        scrollSnapshots.forEach(function (pair) {
                            pair[0].scrollTop = pair[1];
                        });
                    }, 50);
                    return;
                }
            }
            lastUrl = initial;
            updateUi(lastUrl, !lastUrl);
        }, 60);

        // Light observer on the parent form for draw-pad / file-upload signature updates
        var observeRoot = panel.closest('form') || panel.parentElement;
        if (observeRoot && window.MutationObserver) {
            var formObserver = new MutationObserver(function () {
                syncFromDom(true);
            });
            formObserver.observe(observeRoot, {
                attributes: true,
                attributeFilter: ['src', 'style', 'value', 'class'],
                subtree: true
            });
        }
    }

    function scanPanels(root) {
        var scope = root && root.querySelectorAll ? root : document;
        // Identify all signature panels by their "My saved signatures" heading
        var paragraphs = scope.querySelectorAll('p');
        paragraphs.forEach(function (p) {
            if (p.textContent && p.textContent.trim() === 'My saved signatures') {
                var card = p.closest('.rounded-xl.border.border-slate-200.bg-white');
                if (card) {
                    enhancePanel(card);
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { scanPanels(document); });
    } else {
        scanPanels(document);
    }

    // Watch for dynamically injected AJAX modals (e.g. Approve Directly, President Approve, etc.)
    if (window.MutationObserver) {
        var bodyObserver = new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
                var added = mutations[i].addedNodes;
                for (var j = 0; j < added.length; j++) {
                    var node = added[j];
                    if (node && node.nodeType === 1) {
                        scanPanels(node);
                    }
                }
            }
        });
        bodyObserver.observe(document.documentElement, { childList: true, subtree: true });
    }
})();
