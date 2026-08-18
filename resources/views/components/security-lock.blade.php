{{--
    App-wide idle lock.

    Included once from layouts.master, so every page that extends the layout gets
    the same behaviour: after a period of inactivity the content blurs and the
    password prompt appears in place. Entering the password unlocks the page the
    user is already on, rather than sending them back to the dashboard.

    This replaces the dashboard-only version, which lived in dashboard.blade.php
    and only ran its idle timer while the dashboard was on screen.

    The server side is unchanged: POST dashboard.verify.password sets the session
    flag, POST dashboard.lock clears it, and TrackUserActivity expires it after
    the same interval so a refresh cannot walk past an idle lock.
--}}

<div class="modal fade" id="securityLockModal" tabindex="-1" aria-labelledby="securityLockModalLabel"
     aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="securityLockModalLabel">
                    <i class="ri-lock-line me-2"></i>Session Locked
                </h5>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="ri-shield-check-line text-primary" style="font-size: 48px;"></i>
                </div>
                <p class="text-center text-muted mb-4">
                    Your session has been idle. Please enter your password to continue.
                </p>
                <form id="securityLockForm">
                    @csrf
                    <div class="mb-3">
                        <label for="securityLockPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="securityLockPassword"
                               name="password" required autocomplete="current-password">
                        <div class="invalid-feedback" id="securityLockError"></div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary" id="securityLockBtn">
                            <i class="ri-check-line me-1"></i> Unlock
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    /* Blur the page content only. The sidebar stays legible, and since every page
       carries this lock there is nothing to gain by navigating away. */
    .main-content {
        transition: filter 0.3s ease;
    }

    .main-content.security-blurred {
        filter: blur(5px);
        pointer-events: none;
        user-select: none;
    }

    /* Modals render at body level, but be explicit: the lock itself must never
       inherit the blur or lose clicks. */
    #securityLockModal,
    #securityLockModal * {
        filter: none !important;
        pointer-events: auto !important;
        user-select: auto !important;
    }

    #securityLockModal {
        z-index: 9999 !important;
    }

    #securityLockModal .modal-content {
        border: none;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    }

    #securityLockModal .modal-header {
        border-bottom: 2px solid rgba(255, 255, 255, 0.1);
    }

    #securityLockModal .form-control:focus {
        border-color: #0ab39c;
        box-shadow: 0 0 0 0.2rem rgba(10, 179, 156, 0.25);
    }

    #securityLockModal .btn-primary {
        background-color: #0ab39c;
        border-color: #0ab39c;
    }

    #securityLockModal .btn-primary:hover {
        background-color: #089981;
        border-color: #089981;
    }
</style>

<script>
    (function () {
        // Keep in step with IDLE_MINUTES in App\Http\Middleware\TrackUserActivity.
        const IDLE_TIMEOUT = 15 * 60 * 1000;

        const VERIFY_URL = '{{ route("dashboard.verify.password") }}';
        const LOCK_URL = '{{ route("dashboard.lock") }}';

        // Written by verifyPassword(), expired by TrackUserActivity. If the server
        // already considers this session idle, the page loads locked.
        let isUnlocked = @json((bool) session('dashboard_unlocked', false));

        let idleTimer = null;
        let lockModal = null;

        function content() {
            return document.querySelector('.main-content');
        }

        function csrf() {
            const tag = document.querySelector('meta[name="csrf-token"]');
            return tag ? tag.getAttribute('content') : '';
        }

        function lock() {
            isUnlocked = false;
            clearTimeout(idleTimer);

            const el = content();
            if (el) {
                el.classList.add('security-blurred');
            }

            if (lockModal) {
                lockModal.show();
                setTimeout(function () {
                    const input = document.getElementById('securityLockPassword');
                    if (input) {
                        input.focus();
                    }
                }, 400);
            }
        }

        function unlock() {
            isUnlocked = true;

            const el = content();
            if (el) {
                el.classList.remove('security-blurred');
            }

            if (lockModal) {
                lockModal.hide();
            }

            const input = document.getElementById('securityLockPassword');
            if (input) {
                input.value = '';
                input.classList.remove('is-invalid');
            }

            resetIdleTimer();
        }

        // Drop the server flag too, so reloading cannot skip an idle lock.
        function clearServerUnlock() {
            try {
                fetch(LOCK_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf()
                    },
                    keepalive: true
                });
            } catch (e) {}
        }

        function resetIdleTimer() {
            clearTimeout(idleTimer);

            if (!isUnlocked) {
                return;
            }

            idleTimer = setTimeout(function () {
                clearServerUnlock();
                lock();
            }, IDLE_TIMEOUT);
        }

        async function submitPassword(e) {
            if (e) {
                e.preventDefault();
            }

            const input = document.getElementById('securityLockPassword');
            const errorDiv = document.getElementById('securityLockError');
            const btn = document.getElementById('securityLockBtn');

            if (!input || !input.value) {
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Checking...';

            try {
                const response = await fetch(VERIFY_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf()
                    },
                    body: JSON.stringify({ password: input.value })
                });

                const data = await response.json();

                if (data.success) {
                    unlock();
                } else {
                    input.classList.add('is-invalid');
                    errorDiv.textContent = data.message || 'Invalid password. Please try again.';
                    input.value = '';
                    input.focus();
                }
            } catch (error) {
                input.classList.add('is-invalid');
                errorDiv.textContent = 'Could not reach the server. Please try again.';
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="ri-check-line me-1"></i> Unlock';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const modalEl = document.getElementById('securityLockModal');
            if (!modalEl || typeof bootstrap === 'undefined') {
                return;
            }

            lockModal = new bootstrap.Modal(modalEl, {
                backdrop: 'static',
                keyboard: false
            });

            const form = document.getElementById('securityLockForm');
            if (form) {
                form.addEventListener('submit', submitPassword);
            }

            if (isUnlocked) {
                resetIdleTimer();
            } else {
                lock();
            }

            ['mousemove', 'mousedown', 'keypress', 'scroll', 'touchstart', 'click']
                .forEach(function (evt) {
                    document.addEventListener(evt, resetIdleTimer, { passive: true });
                });
        });
    })();
</script>
