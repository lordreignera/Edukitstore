import './bootstrap';

if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {
            // The app remains fully usable if service-worker support is unavailable.
        });
    });
}

const sessionHeartbeatUrl = document.querySelector('meta[name="session-heartbeat-url"]')?.content;
const sessionExpiryNotice = document.querySelector('[data-session-expired-notice]');

if (sessionHeartbeatUrl && sessionExpiryNotice) {
    const heartbeatEveryMs = 4 * 60 * 1000;
    const activeWindowMs = 10 * 60 * 1000;
    let lastUserActivityAt = Date.now();
    let lastHeartbeatAttemptAt = Date.now();
    let heartbeatInFlight = false;
    let heartbeatFailed = false;

    sessionExpiryNotice.querySelector('[data-session-expired-reload]')?.addEventListener('click', () => {
        window.location.reload();
    });

    const refreshSessionWhileActive = async () => {
        if (heartbeatFailed || heartbeatInFlight || document.hidden
            || Date.now() - lastUserActivityAt > activeWindowMs
            || Date.now() - lastHeartbeatAttemptAt < heartbeatEveryMs) {
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!csrfToken) return;

        heartbeatInFlight = true;
        lastHeartbeatAttemptAt = Date.now();

        try {
            const response = await fetch(sessionHeartbeatUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (response.status === 401 || response.status === 419) {
                heartbeatFailed = true;
                sessionExpiryNotice.hidden = false;
                sessionExpiryNotice.style.display = 'flex';
            }
        } catch (error) {
            // A brief network interruption should not disrupt the page; the next
            // active interval will retry while the existing session is still valid.
        } finally {
            heartbeatInFlight = false;
        }
    };

    const noteUserActivity = () => {
        lastUserActivityAt = Date.now();
        refreshSessionWhileActive();
    };

    ['pointerdown', 'keydown', 'input', 'scroll', 'touchstart'].forEach((eventName) => {
        document.addEventListener(eventName, noteUserActivity, { passive: true, capture: true });
    });

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) noteUserActivity();
    });

    window.setInterval(refreshSessionWhileActive, heartbeatEveryMs);
}

const pushButton = document.querySelector('[data-push-notifications]');
const pushLabel = pushButton?.querySelector('[data-push-notifications-label]');
const pushStatus = document.querySelector('[data-push-notifications-status]');

const decodeVapidKey = (encodedKey) => {
    const padding = '='.repeat((4 - encodedKey.length % 4) % 4);
    const base64 = (encodedKey + padding).replace(/-/g, '+').replace(/_/g, '/');
    return Uint8Array.from(window.atob(base64), (character) => character.charCodeAt(0));
};

const sendPushSubscription = async (url, method, subscription) => {
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: JSON.stringify(subscription),
    });

    if (!response.ok) {
        const result = await response.json().catch(() => ({}));
        throw new Error(result.message || 'Could not update phone alert settings.');
    }
};

pushButton?.addEventListener('click', async () => {
    const vapidPublicKey = pushButton.dataset.vapidPublicKey;
    if (!window.isSecureContext) {
        if (pushStatus) pushStatus.textContent = 'Phone alerts require a secure HTTPS connection.';
        return;
    }
    if (!vapidPublicKey || !('PushManager' in window) || !('Notification' in window) || !('serviceWorker' in navigator)) {
        if (pushStatus) pushStatus.textContent = 'Phone alerts need VAPID setup and a browser that supports push notifications.';
        return;
    }

    pushButton.disabled = true;
    try {
        const registration = await navigator.serviceWorker.register('/sw.js', { scope: '/' });
        let subscription = await registration.pushManager.getSubscription();

        if (pushButton.dataset.subscribed === 'true' && subscription) {
            await sendPushSubscription(pushButton.dataset.unsubscribeUrl, 'DELETE', { endpoint: subscription.endpoint });
            await subscription.unsubscribe();
            pushButton.dataset.subscribed = 'false';
            if (pushLabel) pushLabel.textContent = 'Enable notifications';
            if (pushStatus) pushStatus.textContent = 'Notifications disabled on this device.';
            return;
        }

        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            throw new Error('Allow notifications in your browser settings to receive order alerts.');
        }

        subscription ??= await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: decodeVapidKey(vapidPublicKey),
        });

        await sendPushSubscription(pushButton.dataset.subscribeUrl, 'POST', subscription.toJSON());
        pushButton.dataset.subscribed = 'true';
        if (pushLabel) pushLabel.textContent = 'Notifications on';
        if (pushStatus) pushStatus.textContent = 'Notifications enabled on this device.';
    } catch (error) {
        if (pushStatus) pushStatus.textContent = error.message || 'Could not enable notifications.';
    } finally {
        pushButton.disabled = false;
    }
});

const installPanel = document.querySelector('[data-pwa-install]');
const installAction = installPanel?.querySelector('[data-pwa-install-action]');
const installMessage = installPanel?.querySelector('[data-pwa-install-message]');
const installDismissButtons = installPanel?.querySelectorAll('[data-pwa-install-dismiss]');
const installDismissKey = 'edukit-pwa-install-dismissed';
let deferredInstallPrompt = null;

const isInstalled = () => window.matchMedia('(display-mode: standalone)').matches
    || window.navigator.standalone === true;

const isIos = () => /iphone|ipad|ipod/i.test(window.navigator.userAgent);

const showInstallPanel = () => {
    if (installPanel && !isInstalled() && !window.sessionStorage.getItem(installDismissKey)) {
        installPanel.hidden = false;
    }
};

const hideInstallPanel = () => {
    if (installPanel) {
        installPanel.hidden = true;
    }
    window.sessionStorage.setItem(installDismissKey, '1');
};

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstallPrompt = event;
    showInstallPanel();
});

window.addEventListener('appinstalled', () => {
    deferredInstallPrompt = null;
    hideInstallPanel();
});

installDismissButtons?.forEach((button) => button.addEventListener('click', hideInstallPanel));

installAction?.addEventListener('click', async () => {
    if (deferredInstallPrompt) {
        deferredInstallPrompt.prompt();
        await deferredInstallPrompt.userChoice;
        deferredInstallPrompt = null;
        hideInstallPanel();
        return;
    }

    if (isIos() && installMessage) {
        installMessage.textContent = 'In Safari, tap Share, then choose Add to Home Screen.';
    }
});

if (isIos() && !isInstalled() && !window.sessionStorage.getItem(installDismissKey)) {
    window.setTimeout(showInstallPanel, 2500);
}

if (document.body?.hasAttribute('data-private-page')) {
    // A browser may restore a dashboard from its back/forward cache without a request.
    // Revalidate that snapshot when it is restored. Do not hide the document on every
    // ordinary navigation because that causes a visible flash before the next page loads.
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            window.location.reload();
        }
    });
}

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordTarget);
        const showing = input.type === 'text';

        input.type = showing ? 'password' : 'text';
        button.setAttribute('aria-pressed', showing ? 'false' : 'true');
        button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');

        const icon = button.querySelector('svg');
        if (icon) {
            icon.innerHTML = showing
                ? '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.5"></circle>'
                : '<path d="m3 3 18 18"></path><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path><path d="M9.8 5.2A10.4 10.4 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-2 2.8"></path><path d="M6.6 6.6C3.6 8.5 2 12 2 12s3.5 7 10 7a9.8 9.8 0 0 0 4.2-.9"></path>';
        }
    });
});

document.querySelectorAll('[data-district-select]').forEach((select) => {
    const input = select.querySelector('[data-district-input]');
    const toggle = select.querySelector('[data-district-toggle]');
    const menu = select.querySelector('[data-district-menu]');
    const empty = select.querySelector('[data-district-empty]');
    const options = Array.from(select.querySelectorAll('[data-district-option]'));

    const setOpen = (open) => {
        menu.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
    };

    const filterOptions = () => {
        const term = input.value.trim().toLowerCase();
        let matches = 0;

        options.forEach((option) => {
            const matchesSearch = option.dataset.districtName.toLowerCase().includes(term);
            option.style.display = matchesSearch ? 'flex' : 'none';
            matches += matchesSearch ? 1 : 0;
        });

        empty.style.display = matches > 0 ? 'none' : 'block';
    };

    input.addEventListener('focus', () => {
        filterOptions();
        setOpen(true);
    });
    input.addEventListener('input', () => {
        filterOptions();
        setOpen(true);
    });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setOpen(false);
        }
    });
    toggle.addEventListener('click', () => {
        filterOptions();
        setOpen(menu.hidden);
        input.focus();
    });
    options.forEach((option) => {
        option.addEventListener('click', () => {
            input.value = option.dataset.districtName;
            input.dispatchEvent(new Event('change', { bubbles: true }));
            setOpen(false);
            input.focus();
        });
    });
    document.addEventListener('click', (event) => {
        if (!select.contains(event.target)) {
            setOpen(false);
        }
    });
});
