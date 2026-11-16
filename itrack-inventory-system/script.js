// Form validation (basic client-side)
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = 'red';
                } else {
                    field.style.borderColor = '#ddd';
                }
            });
            if (!isValid) {
                e.preventDefault();
                alert('Please fill all required fields.');
            }
        });
    });

    // Specific to request.php: Sync dropdown selection to hidden field
    const deviceSelect = document.getElementById('deviceSelect');
    const deviceTypeText = document.getElementById('deviceTypeText');
    if (deviceSelect && deviceTypeText) {
        deviceSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            deviceTypeText.value = selectedOption.getAttribute('data-type') || '';
        });
    }

    // Toggle between signup/login forms on index.php (optional enhancement)
    const signupForm = document.getElementById('signupForm');
    const loginForm = document.getElementById('loginForm');
    if (signupForm && loginForm) {
        // You can add buttons to toggle visibility if desired, e.g.:
        // document.getElementById('toggleSignup').addEventListener('click', () => { ... });
    }
});

// Password strength hint (optional, for signup)
const passwordField = document.querySelector('input[type="password"]');
if (passwordField) {
    passwordField.addEventListener('input', function() {
        if (this.value.length < 6) {
            this.setCustomValidity('Password must be at least 6 characters');
        } else {
            this.setCustomValidity('');
        }
    });
}