/**
 * Attendly AMS - Universal Accessible Password Visibility Toggle
 * Supports single and multiple password fields on any page independently.
 */
(function() {
    'use strict';

    function initPasswordToggles() {
        var passwordInputs = document.querySelectorAll('input[type="password"]');

        passwordInputs.forEach(function(input) {
            if (input.dataset.toggleInitialized === 'true') {
                return;
            }

            var parent = input.parentElement;
            var toggleBtn = parent ? parent.querySelector('.password-toggle-btn') : null;

            if (!toggleBtn) {
                var wrapper = document.createElement('div');
                wrapper.className = 'password-field-group';

                input.parentNode.insertBefore(wrapper, input);
                wrapper.appendChild(input);

                toggleBtn = document.createElement('button');
                toggleBtn.type = 'button';
                toggleBtn.className = 'password-toggle-btn';
                toggleBtn.setAttribute('aria-label', 'Show password');
                toggleBtn.setAttribute('title', 'Show password');
                toggleBtn.tabIndex = 0;
                toggleBtn.innerHTML = '<i class="fas fa-eye" aria-hidden="true"></i>';

                wrapper.appendChild(toggleBtn);
            }

            input.dataset.toggleInitialized = 'true';

            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var currentType = input.getAttribute('type');
                if (currentType === 'password') {
                    input.setAttribute('type', 'text');
                    toggleBtn.innerHTML = '<i class="fas fa-eye-slash" aria-hidden="true"></i>';
                    toggleBtn.setAttribute('aria-label', 'Hide password');
                    toggleBtn.setAttribute('title', 'Hide password');
                } else {
                    input.setAttribute('type', 'password');
                    toggleBtn.innerHTML = '<i class="fas fa-eye" aria-hidden="true"></i>';
                    toggleBtn.setAttribute('aria-label', 'Show password');
                    toggleBtn.setAttribute('title', 'Show password');
                }
            });

            toggleBtn.addEventListener('keydown', function(e) {
                if (e.key === ' ' || e.key === 'Enter') {
                    e.preventDefault();
                    toggleBtn.click();
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPasswordToggles);
    } else {
        initPasswordToggles();
    }

    window.initPasswordToggles = initPasswordToggles;
})();

