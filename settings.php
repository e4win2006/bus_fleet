<?php
require_once __DIR__ . '/includes/auth_middleware.php';
requireRole('admin');

$page = 'settings';
$page_title = 'System Settings - FleetVision';
$page_css = 'dashboard.css';
$show_sidebar = true;

include __DIR__ . '/includes/header.php';
?>

<style>
    /* ── Settings Page Extra Styles ─────────────────────────────────────────── */
    .settings-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
        max-width: 660px;
    }

    /* Danger Zone Card */
    .danger-zone-card {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.07) 0%, rgba(220, 38, 38, 0.04) 100%);
        border: 1.5px solid rgba(239, 68, 68, 0.35);
        border-radius: 14px;
        padding: 28px 32px;
        position: relative;
        overflow: hidden;
    }

    .danger-zone-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #ef4444, #dc2626);
        border-radius: 14px 14px 0 0;
    }

    .danger-zone-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 6px;
    }

    .danger-zone-header h3 {
        color: #ef4444;
        font-size: 1.05rem;
        font-weight: 700;
        margin: 0;
    }

    .danger-zone-icon {
        width: 32px;
        height: 32px;
        background: rgba(239, 68, 68, 0.15);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }

    .danger-zone-desc {
        font-size: 0.875rem;
        color: var(--text-secondary, #94a3b8);
        margin-bottom: 20px;
        line-height: 1.6;
    }

    .reset-scope-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 22px;
    }

    .scope-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 600;
    }

    .scope-chip.erased {
        background: rgba(239, 68, 68, 0.12);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .scope-chip.kept {
        background: rgba(34, 197, 94, 0.1);
        color: #22c55e;
        border: 1px solid rgba(34, 197, 94, 0.3);
    }

    /* Reset Button */
    .btn-reset-db {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 22px;
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35);
        letter-spacing: 0.01em;
    }

    .btn-reset-db:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5);
        background: linear-gradient(135deg, #f87171, #ef4444);
    }

    .btn-reset-db:active {
        transform: translateY(0);
    }

    /* ── Confirmation Modal ──────────────────────────────────────────────────── */
    .reset-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
        z-index: 9000;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.25s ease;
    }

    .reset-modal-overlay.active {
        opacity: 1;
        pointer-events: all;
    }

    .reset-modal {
        background: var(--card-bg, #1e293b);
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: 18px;
        padding: 36px 40px;
        width: 480px;
        max-width: 92vw;
        transform: scale(0.92) translateY(20px);
        transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
        position: relative;
        text-align: center;
    }

    .reset-modal-overlay.active .reset-modal {
        transform: scale(1) translateY(0);
    }

    .modal-icon-ring {
        width: 68px;
        height: 68px;
        background: rgba(239, 68, 68, 0.12);
        border: 2px solid rgba(239, 68, 68, 0.35);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 28px;
        animation: pulseRing 2s ease infinite;
    }

    @keyframes pulseRing {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.25);
        }

        50% {
            box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
        }
    }

    .reset-modal h2 {
        font-size: 1.25rem;
        font-weight: 700;
        color: #ef4444;
        margin-bottom: 10px;
    }

    .reset-modal p {
        font-size: 0.875rem;
        color: var(--text-secondary, #94a3b8);
        line-height: 1.65;
        margin-bottom: 24px;
    }

    .modal-confirm-group {
        margin-bottom: 24px;
        text-align: left;
    }

    .modal-confirm-group label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-secondary, #94a3b8);
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .modal-confirm-group input {
        width: 100%;
        box-sizing: border-box;
        padding: 10px 14px;
        border-radius: 8px;
        border: 1.5px solid rgba(239, 68, 68, 0.3);
        background: rgba(239, 68, 68, 0.05);
        color: inherit;
        font-size: 0.9rem;
        outline: none;
        transition: border-color 0.2s;
    }

    .modal-confirm-group input:focus {
        border-color: #ef4444;
        background: rgba(239, 68, 68, 0.08);
    }

    .modal-confirm-group input.invalid {
        border-color: #f87171;
        animation: shake 0.35s ease;
    }

    @keyframes shake {

        0%,
        100% {
            transform: translateX(0);
        }

        25% {
            transform: translateX(-5px);
        }

        75% {
            transform: translateX(5px);
        }
    }

    .modal-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }

    .btn-modal-cancel {
        padding: 10px 20px;
        background: transparent;
        border: 1.5px solid rgba(148, 163, 184, 0.3);
        border-radius: 8px;
        color: var(--text-secondary, #94a3b8);
        font-size: 0.88rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-modal-cancel:hover {
        border-color: rgba(148, 163, 184, 0.6);
        color: #fff;
    }

    .btn-modal-confirm {
        padding: 10px 22px;
        background: linear-gradient(135deg, #ef4444, #dc2626);
        border: none;
        border-radius: 8px;
        color: #fff;
        font-size: 0.88rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    .btn-modal-confirm:hover {
        opacity: 0.9;
    }

    .btn-modal-confirm:disabled {
        opacity: 0.45;
        cursor: not-allowed;
        box-shadow: none;
    }

    /* Toast */
    .reset-toast {
        position: fixed;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%) translateY(80px);
        padding: 14px 24px;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 600;
        color: #fff;
        z-index: 9999;
        transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s;
        opacity: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        max-width: 90vw;
        text-align: center;
        pointer-events: none;
    }

    .reset-toast.show {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
    }

    .reset-toast.success {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        box-shadow: 0 8px 24px rgba(34, 197, 94, 0.4);
    }

    .reset-toast.error {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        box-shadow: 0 8px 24px rgba(239, 68, 68, 0.4);
    }

    /* Spinner */
    .spinner {
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255, 255, 255, 0.4);
        border-top-color: #fff;
        border-radius: 50%;
        display: inline-block;
        animation: spin 0.7s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>

<header class="top-header">
    <div class="header-left">
        <h1 class="page-title">System Settings</h1>
        <p class="page-subtitle">Manage global application configurations.</p>
    </div>
</header>

<section class="content-section">
    <div class="settings-grid">

        <!-- General Preferences -->
        <div class="user-form-card">
            <h3>General Preferences</h3>
            <form style="margin-top: 20px;">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 500; margin-bottom: 5px;">Company Name</label>
                    <input type="text" value="FleetVision Transit"
                        style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" disabled>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 500; margin-bottom: 5px;">Default Timezone</label>
                    <select style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" disabled>
                        <option>UTC-5 (Eastern Time)</option>
                        <option>UTC+0 (GMT)</option>
                    </select>
                </div>
                <button type="button" disabled
                    style="padding: 10px 15px; background: #94a3b8; color: white; border: none; border-radius: 4px; cursor: not-allowed;">Save
                    Settings (Demo)</button>
            </form>
        </div>

        <!-- Danger Zone -->
        <div class="danger-zone-card">
            <div class="danger-zone-header">
                <span class="danger-zone-icon">⚠️</span>
                <h3>Danger Zone</h3>
            </div>

            <p class="danger-zone-desc">
                <strong style="color: #ef4444;">Reset Database</strong> permanently wipes all operational records.
                This action <strong>cannot be undone.</strong> User accounts and login credentials will be preserved,
                but all fleet and scheduling data will be erased.
            </p>

            <div class="reset-scope-list">
                <span class="scope-chip erased">🚌 Buses</span>
                <span class="scope-chip erased">🗺️ Routes</span>
                <span class="scope-chip erased">🚦 Trips</span>
                <span class="scope-chip erased">🔧 Services</span>
                <span class="scope-chip erased">💬 Messages</span>
            </div>

            <button class="btn-reset-db" id="openResetModal">
                🗑️ Reset Database
            </button>
        </div>

    </div><!-- /.settings-grid -->
</section>

<!-- ── Confirmation Modal ──────────────────────────────────────────────────── -->
<div class="reset-modal-overlay" id="resetModalOverlay" role="dialog" aria-modal="true"
    aria-labelledby="resetModalTitle">
    <div class="reset-modal">
        <div class="modal-icon-ring">🗑️</div>
        <h2 id="resetModalTitle">Confirm Database Reset</h2>
        <p>
            This will permanently delete all <strong>buses, routes, trips, maintenance records,</strong>
            and <strong>messages</strong>. User accounts will be kept.<br><br>
            To confirm, type <strong style="color:#ef4444; letter-spacing:0.05em;">RESET</strong> in the box below.
        </p>

        <div class="modal-confirm-group">
            <label for="resetConfirmInput">Type RESET to confirm</label>
            <input type="text" id="resetConfirmInput" placeholder="RESET" autocomplete="off" spellcheck="false">
        </div>

        <div class="modal-actions">
            <button class="btn-modal-cancel" id="cancelResetBtn">Cancel</button>
            <button class="btn-modal-confirm" id="confirmResetBtn" disabled>
                🗑️ Yes, Reset Everything
            </button>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="reset-toast" id="resetToast"></div>

<script>
    (function () {
        const overlay = document.getElementById('resetModalOverlay');
        const openBtn = document.getElementById('openResetModal');
        const cancelBtn = document.getElementById('cancelResetBtn');
        const confirmBtn = document.getElementById('confirmResetBtn');
        const confirmInput = document.getElementById('resetConfirmInput');
        const toast = document.getElementById('resetToast');

        // Open / close helpers
        function openModal() { overlay.classList.add('active'); confirmInput.value = ''; confirmBtn.disabled = true; setTimeout(() => confirmInput.focus(), 280); }
        function closeModal() { overlay.classList.remove('active'); }

        openBtn.addEventListener('click', openModal);
        cancelBtn.addEventListener('click', closeModal);
        overlay.addEventListener('click', function (e) { if (e.target === overlay) closeModal(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });

        // Enable confirm button only when "RESET" is typed
        confirmInput.addEventListener('input', function () {
            confirmBtn.disabled = (this.value.trim() !== 'RESET');
            confirmInput.classList.remove('invalid');
        });

        // Show toast helper
        let toastTimer;
        function showToast(msg, type) {
            clearTimeout(toastTimer);
            toast.textContent = msg;
            toast.className = 'reset-toast ' + type + ' show';
            toastTimer = setTimeout(() => { toast.classList.remove('show'); }, 5000);
        }

        // Confirm & submit
        confirmBtn.addEventListener('click', function () {
            if (confirmInput.value.trim() !== 'RESET') {
                confirmInput.classList.add('invalid');
                return;
            }

            // Show spinner
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<span class="spinner"></span> Resetting…';
            cancelBtn.disabled = true;

            fetch('ajax_handlers.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=ajax_reset_database&confirm_token=RESET_CONFIRMED'
            })
                .then(r => r.json())
                .then(data => {
                    closeModal();
                    if (data.success) {
                        showToast('✅ ' + data.message, 'success');
                    } else {
                        showToast('❌ ' + (data.message || 'Reset failed'), 'error');
                    }
                })
                .catch(() => {
                    closeModal();
                    showToast('❌ Network error. Please try again.', 'error');
                })
                .finally(() => {
                    confirmBtn.innerHTML = '🗑️ Yes, Reset Everything';
                    confirmBtn.disabled = false;
                    cancelBtn.disabled = false;
                });
        });
    })();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>