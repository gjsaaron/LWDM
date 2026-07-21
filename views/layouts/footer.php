</main> <!-- /main -->
</div> <!-- /content -->
</div> <!-- /wrapper -->

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Sidebar Toggle
document.getElementById('sidebarCollapse')?.addEventListener('click', function () {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('d-none');
    }
});

// Auto dismiss flash alert messages after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
        if (bsAlert) {
            bsAlert.close();
        }
    });
}, 5000);

// Add loading state spinner to submit buttons on form submit
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn && !submitBtn.classList.contains('no-spin')) {
            submitBtn.disabled = true;
            const origHtml = submitBtn.innerHTML;
            submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...`;
            // Re-enable after timeout as backup
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = origHtml;
            }, 8000);
        }
    });
});
</script>
</body>
</html>
