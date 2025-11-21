// main.js - Handles loading indicators and notifications for form actions and page loads

document.addEventListener('DOMContentLoaded', function () {
    // Inject global loading overlay
    const loader = document.createElement('div');
    loader.id = 'global-loader';
    loader.style = 'display:none;position:fixed;z-index:9999;top:0;left:0;width:100vw;height:100vh;background:rgba(255,255,255,0.7);justify-content:center;align-items:center;';
    loader.innerHTML = '<div style="font-size:2rem;color:#2563eb;"><svg class="animate-spin h-10 w-10 mr-3 inline" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="#2563eb" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="#2563eb" d="M4 12a8 8 0 018-8v8z"></path></svg>Memuatkan...</div>';
    document.body.appendChild(loader);

    // Show loader on form submit
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            document.getElementById('global-loader').style.display = 'flex';
        });
    });

    // Show loader on page navigation (optional, for SPA or AJAX navigation)
    // window.addEventListener('beforeunload', function () {
    //     document.getElementById('global-loader').style.display = 'flex';
    // });
});

// Notification utility
function showNotification(message, type = 'info') {
    let notif = document.createElement('div');
    notif.className = 'fixed top-6 right-6 z-[10000] px-6 py-3 rounded shadow text-white';
    notif.style.background = type === 'success' ? '#16a34a' : (type === 'error' ? '#dc2626' : '#2563eb');
    notif.innerText = message;
    document.body.appendChild(notif);
    setTimeout(() => notif.remove(), 3000);
}

// Usage in PHP: echo "<script>showNotification('Berjaya!', 'success');</script>";
