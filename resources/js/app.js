import './bootstrap';
import '../css/realtime.css';
if (window.Echo) {

    window.Echo.channel("reports")
        .subscribed(() => {
            console.log("Subscribed to reports");
        })
        .error((err) => {
            console.log("Subscription error", err);
        })
        .listen(".ReportSubmitted", async (e) => {

            console.log("Received ReportSubmitted", e);

            showReportToast(e.report);

            playNotificationSound();

            updateCounters(e.report);

            await addReportCard(e.report);

        })
        .listenToAll((event, data) => {
            console.log("ALL EVENTS:", event, data);
        });

}


// =====================================================
// TOAST NOTIFICATION
// =====================================================

function showReportToast(report) {

    let toast = document.getElementById("report-toast");

    if (!toast) {

        toast = document.createElement("div");

        toast.id = "report-toast";

        document.body.appendChild(toast);

    }

    toast.innerHTML = `
        <div class="toast-icon">
            <i data-lucide="bell-ring"></i>
        </div>

        <div class="toast-content">

            <div class="toast-title">
                New Maintenance Report
            </div>

            <div class="toast-message">
                Report #${report.report_id}
            </div>

            <div class="toast-subtitle">
                ${report.report_urgency_level} • ${report.report_current_status}
            </div>

        </div>
    `;

    if (window.lucide) {
        lucide.createIcons();
    }

    toast.classList.add("show");

    clearTimeout(toast.timeout);

    toast.timeout = setTimeout(() => {

        toast.classList.remove("show");

    }, 4500);

}


// =====================================================
// NOTIFICATION SOUND
// =====================================================

function playNotificationSound() {

    const audio = new Audio("/sounds/notification.mp3");

    audio.play().catch(() => {});

}


// =====================================================
// INSERT NEW REPORT CARD
// =====================================================

async function addReportCard(report) {

    const response = await fetch(
        `/maintenance/report-card/${report.report_id}`
    );

    if (!response.ok) {
        return;
    }

    const html = await response.text();

    const container = document.getElementById("card-view");

    if (!container) {
        return;
    }

    container.insertAdjacentHTML("afterbegin", html);

    const card = container.firstElementChild;

    if (card) {
        card.classList.add("report-highlight");
    }

    if (window.lucide) {
        lucide.createIcons();
    }

}


// =====================================================
// UPDATE DASHBOARD COUNTS
// =====================================================

function updateCounters(report) {

    const total = document.getElementById("totalReportsCount");

    if (total) {

        total.textContent =
            Number(total.textContent.replace(/,/g, "")) + 1;

    }

    if (report.report_current_status === "Pending") {

        const pending =
            document.getElementById("pendingReportsCount");

        if (pending) {

            pending.textContent =
                Number(pending.textContent.replace(/,/g, "")) + 1;

        }

    }

    if (report.report_current_status === "Processing") {

        const processing =
            document.getElementById("processingReportsCount");

        if (processing) {

            processing.textContent =
                Number(processing.textContent.replace(/,/g, "")) + 1;

        }

    }

    if (report.report_current_status === "Resolved") {

        const resolved =
            document.getElementById("resolvedReportsCount");

        if (resolved) {

            resolved.textContent =
                Number(resolved.textContent.replace(/,/g, "")) + 1;

        }

    }

}




import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();