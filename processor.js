/**
 * Tree Species Environmental Filter - JavaScript
 * Real-time validation and form interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeFormValidation();
    initializeRangeInputs();
    initializeProcessingAnimation();
});

/**
 * Initialize form validation
 */
function initializeFormValidation() {
    const form = document.querySelector('.processing-form');
    if (!form) return;

    // Add real-time validation for select inputs
    const selectElements = form.querySelectorAll('select');
    selectElements.forEach(select => {
        select.addEventListener('change', function() {
            validateSelectInput(this);
        });
    });

    // Add form submission validation
    form.addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            showValidationErrors();
        }
    });
}

/**
 * Initialize range input validation and constraints
 */
function initializeRangeInputs() {
    const fromInput = document.getElementById('from_value');
    const toInput = document.getElementById('to_value');
    
    if (!fromInput || !toInput) return;

    const fromNote = document.getElementById('from-note');
    const toNote = document.getElementById('to-note');
    
    // Get min/max values from input attributes
    const minValue = parseFloat(fromInput.getAttribute('min'));
    const maxValue = parseFloat(fromInput.getAttribute('max'));

    // Real-time validation for 'from' input
    fromInput.addEventListener('input', function() {
        const value = parseFloat(this.value);
        const toValue = parseFloat(toInput.value);
        
        // Validate range
        if (value < minValue || value > maxValue) {
            this.classList.add('error');
            fromNote.classList.add('error');
            fromNote.textContent = `Value must be between ${minValue} and ${maxValue}`;
        } else {
            this.classList.remove('error');
            fromNote.classList.remove('error');
            fromNote.textContent = `Range: ${minValue} - ${maxValue} ${fromInput.dataset.unit || ''}`;
        }

        // Update 'to' input minimum
        if (!isNaN(value)) {
            toInput.setAttribute('min', value + 0.1);
            
            // Check if 'to' value is still valid
            if (!isNaN(toValue) && toValue <= value) {
                toInput.classList.add('error');
                toNote.classList.add('error');
                toNote.textContent = `Must be higher than ${value}`;
            }
        }
    });

    // Real-time validation for 'to' input
    toInput.addEventListener('input', function() {
        const value = parseFloat(this.value);
        const fromValue = parseFloat(fromInput.value);
        
        // Validate range
        if (value < minValue || value > maxValue) {
            this.classList.add('error');
            toNote.classList.add('error');
            toNote.textContent = `Value must be between ${minValue} and ${maxValue}`;
        } else if (!isNaN(fromValue) && value <= fromValue) {
            this.classList.add('error');
            toNote.classList.add('error');
            toNote.textContent = `Must be higher than ${fromValue}`;
        } else {
            this.classList.remove('error');
            toNote.classList.remove('error');
            toNote.textContent = "Must be higher than 'From' value";
        }
    });

    // Prevent typing invalid characters
    [fromInput, toInput].forEach(input => {
        input.addEventListener('keypress', function(e) {
            // Allow: backspace, delete, tab, escape, enter, decimal point
            if ([46, 8, 9, 27, 13, 110, 190].indexOf(e.keyCode) !== -1 ||
                // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                (e.keyCode === 65 && e.ctrlKey === true) ||
                (e.keyCode === 67 && e.ctrlKey === true) ||
                (e.keyCode === 86 && e.ctrlKey === true) ||
                (e.keyCode === 88 && e.ctrlKey === true)) {
                return;
            }
            // Ensure that it is a number and stop the keypress
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });
    });

    // Handle paste events
    [fromInput, toInput].forEach(input => {
        input.addEventListener('paste', function(e) {
            // Allow paste but validate the pasted content
            setTimeout(() => {
                const value = parseFloat(this.value);
                if (isNaN(value)) {
                    this.value = '';
                }
            }, 0);
        });
    });
}

/**
 * Validate select input
 */
function validateSelectInput(selectElement) {
    if (selectElement.value === '') {
        selectElement.classList.add('error');
        return false;
    } else {
        selectElement.classList.remove('error');
        return true;
    }
}

/**
 * Validate entire form
 */
function validateForm() {
    const form = document.querySelector('.processing-form');
    if (!form) return true;

    let isValid = true;

    // Validate select inputs
    const selectElements = form.querySelectorAll('select[required]');
    selectElements.forEach(select => {
        if (!validateSelectInput(select)) {
            isValid = false;
        }
    });

    // Validate range inputs
    const fromInput = document.getElementById('from_value');
    const toInput = document.getElementById('to_value');
    
    if (fromInput && toInput) {
        const fromValue = parseFloat(fromInput.value);
        const toValue = parseFloat(toInput.value);
        const minValue = parseFloat(fromInput.getAttribute('min'));
        const maxValue = parseFloat(fromInput.getAttribute('max'));

        // Validate 'from' input
        if (isNaN(fromValue) || fromValue < minValue || fromValue > maxValue) {
            fromInput.classList.add('error');
            isValid = false;
        }

        // Validate 'to' input
        if (isNaN(toValue) || toValue < minValue || toValue > maxValue || toValue <= fromValue) {
            toInput.classList.add('error');
            isValid = false;
        }
    }

    return isValid;
}

/**
 * Show validation errors
 */
function showValidationErrors() {
    // Create or update error message
    let errorDiv = document.querySelector('.validation-errors');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'validation-errors';
        errorDiv.style.cssText = `
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        `;
        
        const form = document.querySelector('.processing-form');
        form.insertBefore(errorDiv, form.firstChild);
    }

    errorDiv.innerHTML = `
        <strong>Please correct the following errors:</strong>
        <ul style="margin: 0.5rem 0 0 1.5rem; padding: 0;">
            ${getValidationErrorMessages().map(msg => `<li>${msg}</li>`).join('')}
        </ul>
    `;

    // Scroll to error message
    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });

    // Auto-hide after 5 seconds
    setTimeout(() => {
        if (errorDiv.parentNode) {
            errorDiv.remove();
        }
    }, 5000);
}

/**
 * Get validation error messages
 */
function getValidationErrorMessages() {
    const errors = [];

    // Check select inputs
    const selectElements = document.querySelectorAll('select[required].error');
    selectElements.forEach(select => {
        errors.push(`Please select a ${select.name || 'option'}`);
    });

    // Check range inputs
    const fromInput = document.getElementById('from_value');
    const toInput = document.getElementById('to_value');
    
    if (fromInput && fromInput.classList.contains('error')) {
        errors.push('From value is invalid or out of range');
    }
    
    if (toInput && toInput.classList.contains('error')) {
        errors.push('To value is invalid or must be higher than From value');
    }

    return errors;
}

/**
 * Initialize processing animation
 */
function initializeProcessingAnimation() {
    const form = document.querySelector('.processing-form');
    if (!form) return;

    form.addEventListener('submit', function() {
        // Add processing state to form
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = `
                <span class="loading-spinner"></span>
                Processing...
            `;
            
            // Add processing class to form
            form.classList.add('processing');
        }
    });
}

/**
 * Go to previous step
 */
function goToPreviousStep() {
    if (confirm('Are you sure you want to go back? Your current progress will be saved.')) {
        // You can implement AJAX call here to update session
        window.location.href = '?step=previous';
    }
}

/**
 * Proceed to next page
 */
function proceedToNextPage() {
    if (confirm('Proceed to the next page with the filtered tree list?')) {
        window.location.href = 'tree-details.php';
    }
}

/**
 * Reset the entire process
 */
function resetProcess() {
    if (confirm('Are you sure you want to start a new selection? All current progress will be lost.')) {
        // Clear session and restart
        fetch('reset-session.php', { method: 'POST' })
            .then(() => {
                window.location.href = 'processor.php';
            })
            .catch(error => {
                console.error('Error resetting session:', error);
                window.location.href = 'processor.php';
            });
    }
}

/**
 * Show success message
 */
function showSuccessMessage(message) {
    const successDiv = document.createElement('div');
    successDiv.className = 'success-message';
    successDiv.style.cssText = `
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #166534;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        font-size: 0.875rem;
        animation: slideIn 0.3s ease-out;
    `;
    successDiv.textContent = message;

    const form = document.querySelector('.processing-form');
    if (form) {
        form.insertBefore(successDiv, form.firstChild);
        
        // Auto-hide after 3 seconds
        setTimeout(() => {
            if (successDiv.parentNode) {
                successDiv.remove();
            }
        }, 3000);
    }
}

/**
 * Handle dynamic form updates (for future AJAX implementations)
 */
function updateFormStep(stepData) {
    // This function can be used for AJAX-based step updates
    console.log('Updating form step:', stepData);
}

/**
 * Utility function to format numbers with proper decimal places
 */
function formatNumber(value, decimals = 1) {
    return parseFloat(value).toFixed(decimals);
}

/**
 * Utility function to debounce input events
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Enhanced input validation with debouncing
 */
const debouncedValidation = debounce((input) => {
    // Perform validation
    input.dispatchEvent(new Event('input'));
}, 300);

/**
 * Initialize tooltips for help text
 */
function initializeTooltips() {
    const helpElements = document.querySelectorAll('.form-help, .step-desc');
    helpElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            // Add hover effects
            this.style.opacity = '1';
        });
    });
}

/**
 * Handle keyboard navigation
 */
function initializeKeyboardNavigation() {
    document.addEventListener('keydown', function(e) {
        // Handle Enter key on form elements
        if (e.key === 'Enter' && e.target.tagName === 'SELECT') {
            e.preventDefault();
            const form = e.target.closest('form');
            if (form && validateForm()) {
                form.submit();
            }
        }
        
        // Handle Escape key to clear errors
        if (e.key === 'Escape') {
            const errorDiv = document.querySelector('.validation-errors');
            if (errorDiv) {
                errorDiv.remove();
            }
        }
    });
}

// Initialize additional features
document.addEventListener('DOMContentLoaded', function() {
    initializeTooltips();
    initializeKeyboardNavigation();
});

/**
 * Auto-save form progress (for future implementation)
 */
function autoSaveProgress() {
    const form = document.querySelector('.processing-form');
    if (!form) return;

    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    // Save to localStorage as backup
    localStorage.setItem('tree_filter_progress', JSON.stringify({
        step: getCurrentStep(),
        data: data,
        timestamp: Date.now()
    }));
}

/**
 * Get current step number
 */
function getCurrentStep() {
    const stepElement = document.querySelector('.step-counter');
    if (stepElement) {
        const match = stepElement.textContent.match(/Step (\d+)/);
        return match ? parseInt(match[1]) : 1;
    }
    return 1;
}

/**
 * Load saved progress (for future implementation)
 */
function loadSavedProgress() {
    const saved = localStorage.getItem('tree_filter_progress');
    if (saved) {
        try {
            const progress = JSON.parse(saved);
            // Check if saved progress is recent (within last hour)
            if (Date.now() - progress.timestamp < 3600000) {
                return progress;
            }
        } catch (e) {
            console.error('Error loading saved progress:', e);
        }
    }
    return null;
}

// Export functions for potential external use
window.TreeFilter = {
    validateForm,
    goToPreviousStep,
    proceedToNextPage,
    resetProcess,
    showSuccessMessage,
    autoSaveProgress,
    loadSavedProgress
};