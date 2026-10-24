// Shows server error messages for failed HTMX requests instead of failing silently.
document.addEventListener('htmx:responseError', (event) => {
    const status = event.detail.xhr.status;
    const target = document.getElementById('notifications');
    if (!target) return;

    const messages = {
        419: 'Your session has expired. Please refresh the page.',
        429: 'Too many attempts. Please wait a moment and try again.',
    };

    const alert = document.createElement('div');
    alert.className = 'alert alert-error';
    alert.setAttribute('role', 'alert');
    alert.textContent = messages[status] ?? `Request failed (${status}). Please try again.`;
    target.replaceChildren(alert);
});

document.addEventListener('htmx:sendError', () => {
    const target = document.getElementById('notifications');
    if (!target) return;

    const alert = document.createElement('div');
    alert.className = 'alert alert-error';
    alert.setAttribute('role', 'alert');
    alert.textContent = 'Could not reach the server. Check your connection.';
    target.replaceChildren(alert);
});
