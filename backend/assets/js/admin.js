/**
 * Global Admin Interactive JavaScript
 * Toast notifications, Modals, AJAX helpers
 */

function showToast(message, type = 'success', duration = 4000) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `alert alert-${type}`;
    toast.style.cssText = `
        min-width: 280px;
        max-width: 420px;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2);
        animation: slideInRight 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        margin-bottom: 0;
        cursor: pointer;
    `;
    toast.innerHTML = `
        <div style="flex: 1; font-weight: 500;">${message}</div>
        <span style="opacity: 0.6; font-size: 16px; margin-left: 8px;">&times;</span>
    `;

    toast.onclick = () => toast.remove();
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(20px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// Modal open/close helpers
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modal when clicking outside
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
        document.body.style.overflow = '';
    }
});
