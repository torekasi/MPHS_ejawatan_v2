/**
 * Duplicate Application Validator - Client Side
 * 
 * JavaScript module for real-time duplicate checking
 * Works with server-side DuplicateValidator module
 * 
 * Usage:
 * <script src="/assets/js/duplicate-validator.js"></script>
 * <script>
 *   DuplicateValidator.init({
 *     nricFieldId: 'nombor_ic',
 *     jobIdFieldId: 'job_id',
 *     formId: 'application_form'
 *   });
 * </script>
 * 
 * @version 1.0
 * @date 2025-10-28
 */

const DuplicateValidator = (function() {
    'use strict';
    
    // Configuration
    let config = {
        nricFieldId: 'nombor_ic',
        jobIdFieldId: 'job_id',
        jobCodeFieldId: 'job_code',
        formId: null,
        checkUrl: 'ajax-check-duplicate.php',
        debounceDelay: 800,
        enableAutoCheck: true,
        onDuplicateFound: null,
        onCheckComplete: null
    };
    
    // State
    let checkTimeout = null;
    let lastCheckData = null;
    let isChecking = false;
    
    /**
     * Initialize the validator
     * @param {Object} options - Configuration options
     */
    function init(options) {
        // Merge options with defaults
        config = Object.assign({}, config, options);
        
        // Setup event listeners
        setupEventListeners();
        
        console.log('[DuplicateValidator] Initialized', config);
    }
    
    /**
     * Setup event listeners on NRIC and Job ID fields
     */
    function setupEventListeners() {
        const nricField = document.getElementById(config.nricFieldId);
        const jobIdField = document.getElementById(config.jobIdFieldId);
        const jobCodeField = document.getElementById(config.jobCodeFieldId);
        
        if (nricField && (jobIdField || jobCodeField)) {
            // NRIC field change
            nricField.addEventListener('input', function() {
                if (config.enableAutoCheck) {
                    scheduleDuplicateCheck();
                }
            });
            
            nricField.addEventListener('blur', function() {
                if (config.enableAutoCheck) {
                    checkDuplicate();
                }
            });
            
            // Job ID/Code field change
            if (jobIdField) {
                jobIdField.addEventListener('change', function() {
                    if (config.enableAutoCheck) {
                        scheduleDuplicateCheck();
                    }
                });
            }
            
            if (jobCodeField) {
                jobCodeField.addEventListener('change', function() {
                    if (config.enableAutoCheck) {
                        scheduleDuplicateCheck();
                    }
                });
            }
        }
        
        // Form submission validation
        if (config.formId) {
            const form = document.getElementById(config.formId);
            if (form) {
                form.addEventListener('submit', function(e) {
                    return handleFormSubmit(e);
                });
            }
        }
    }
    
    /**
     * Schedule duplicate check with debounce
     */
    function scheduleDuplicateCheck() {
        if (checkTimeout) {
            clearTimeout(checkTimeout);
        }
        
        checkTimeout = setTimeout(function() {
            checkDuplicate();
        }, config.debounceDelay);
    }
    
    /**
     * Perform duplicate check via AJAX
     * @param {Function} callback - Optional callback function
     */
    function checkDuplicate(callback) {
        const nric = getNRICValue();
        const jobId = getJobIdValue();
        const jobCode = getJobCodeValue();
        
        // Validate required fields
        if (!nric || (!jobId && !jobCode)) {
            console.log('[DuplicateValidator] Missing NRIC or Job ID/Code');
            return;
        }
        
        // Check if data changed since last check
        const checkData = JSON.stringify({ nric, jobId, jobCode });
        if (checkData === lastCheckData && !callback) {
            console.log('[DuplicateValidator] Data unchanged, skipping check');
            return;
        }
        
        lastCheckData = checkData;
        isChecking = true;
        
        // Show loading indicator
        showCheckingIndicator();
        
        // Prepare request data
        const formData = new FormData();
        formData.append('nric', nric);
        if (jobId) {
            formData.append('job_id', jobId);
        }
        if (jobCode) {
            formData.append('job_code', jobCode);
        }
        
        // Make AJAX request
        fetch(config.checkUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            isChecking = false;
            hideCheckingIndicator();
            
            console.log('[DuplicateValidator] Check result:', data);
            
            if (data.duplicate) {
                handleDuplicateFound(data);
            } else {
                handleNoDuplicate(data);
            }
            
            // Call callback if provided
            if (callback) {
                callback(data);
            }
            
            // Call custom handler if configured
            if (config.onCheckComplete) {
                config.onCheckComplete(data);
            }
        })
        .catch(error => {
            isChecking = false;
            hideCheckingIndicator();
            
            console.error('[DuplicateValidator] Check failed:', error);
            
            // Allow form submission on error (fail open)
            if (callback) {
                callback({ status: 'error', duplicate: false });
            }
        });
    }
    
    /**
     * Handle duplicate found
     * @param {Object} data - Response data
     */
    function handleDuplicateFound(data) {
        // Show error message
        showErrorMessage(
            data.message || 'Anda telah membuat permohonan untuk jawatan ini. Permohonan pendua tidak dibenarkan.',
            data.application_reference
        );
        
        // Disable submit button
        disableSubmitButton();
        
        // Highlight NRIC field
        highlightField(config.nricFieldId, 'error');
        
        // Call custom handler if configured
        if (config.onDuplicateFound) {
            config.onDuplicateFound(data);
        }
    }
    
    /**
     * Handle no duplicate found
     * @param {Object} data - Response data
     */
    function handleNoDuplicate(data) {
        // Hide error message
        hideErrorMessage();
        
        // Enable submit button
        enableSubmitButton();
        
        // Remove highlight from NRIC field
        highlightField(config.nricFieldId, 'success');
    }
    
    /**
     * Handle form submission
     * @param {Event} e - Submit event
     * @return {Boolean} - Allow/prevent submission
     */
    function handleFormSubmit(e) {
        // If currently checking, prevent submission
        if (isChecking) {
            e.preventDefault();
            
            showWarning('Sedang memeriksa permohonan pendua. Sila tunggu sebentar...');
            
            // Retry after check completes
            checkDuplicate(function(data) {
                if (!data.duplicate) {
                    // Re-submit form
                    const form = document.getElementById(config.formId);
                    if (form) {
                        form.submit();
                    }
                }
            });
            
            return false;
        }
        
        // Perform final check before submission
        if (config.enableAutoCheck) {
            e.preventDefault();
            
            checkDuplicate(function(data) {
                if (data.duplicate) {
                    // Block submission
                    handleDuplicateFound(data);
                } else {
                    // Allow submission
                    const form = document.getElementById(config.formId);
                    if (form) {
                        // Remove event listener to prevent loop
                        form.removeEventListener('submit', handleFormSubmit);
                        form.submit();
                    }
                }
            });
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Get NRIC value from field
     * @return {String}
     */
    function getNRICValue() {
        const field = document.getElementById(config.nricFieldId);
        return field ? field.value.trim() : '';
    }
    
    /**
     * Get Job ID value from field
     * @return {String}
     */
    function getJobIdValue() {
        const field = document.getElementById(config.jobIdFieldId);
        return field ? field.value.trim() : '';
    }
    
    /**
     * Get Job Code value from field
     * @return {String}
     */
    function getJobCodeValue() {
        const field = document.getElementById(config.jobCodeFieldId);
        return field ? field.value.trim() : '';
    }
    
    /**
     * Show checking indicator
     */
    function showCheckingIndicator() {
        const nricField = document.getElementById(config.nricFieldId);
        if (nricField) {
            // Add spinner/loading class
            nricField.classList.add('checking-duplicate');
            
            // Show inline message
            let indicator = document.getElementById('duplicate-checking-indicator');
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.id = 'duplicate-checking-indicator';
                indicator.className = 'duplicate-checking-message';
                indicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memeriksa permohonan pendua...';
                nricField.parentNode.insertBefore(indicator, nricField.nextSibling);
            }
            indicator.style.display = 'block';
        }
    }
    
    /**
     * Hide checking indicator
     */
    function hideCheckingIndicator() {
        const nricField = document.getElementById(config.nricFieldId);
        if (nricField) {
            nricField.classList.remove('checking-duplicate');
        }
        
        const indicator = document.getElementById('duplicate-checking-indicator');
        if (indicator) {
            indicator.style.display = 'none';
        }
    }
    
    /**
     * Show error message
     * @param {String} message
     * @param {String} reference - Application reference
     */
    function showErrorMessage(message, reference) {
        let errorDiv = document.getElementById('duplicate-error-message');
        
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.id = 'duplicate-error-message';
            errorDiv.className = 'alert alert-danger duplicate-error';
            
            // Insert after NRIC field or at top of form
            const nricField = document.getElementById(config.nricFieldId);
            if (nricField && nricField.parentNode) {
                nricField.parentNode.insertBefore(errorDiv, nricField.nextSibling);
            } else if (config.formId) {
                const form = document.getElementById(config.formId);
                if (form) {
                    form.insertBefore(errorDiv, form.firstChild);
                }
            }
        }
        
        let html = '<strong><i class="fas fa-exclamation-triangle"></i> Permohonan Pendua Dijumpai!</strong><br>' + message;
        
        if (reference) {
            html += '<br><small>Rujukan: <strong>' + reference + '</strong></small>';
            html += '<br><a href="application-status.php?ref=' + encodeURIComponent(reference) + '" class="btn btn-sm btn-primary mt-2">Lihat Status Permohonan</a>';
        }
        
        errorDiv.innerHTML = html;
        errorDiv.style.display = 'block';
        
        // Scroll to error message
        errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    /**
     * Hide error message
     */
    function hideErrorMessage() {
        const errorDiv = document.getElementById('duplicate-error-message');
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
    }
    
    /**
     * Show warning message
     * @param {String} message
     */
    function showWarning(message) {
        let warningDiv = document.getElementById('duplicate-warning-message');
        
        if (!warningDiv) {
            warningDiv = document.createElement('div');
            warningDiv.id = 'duplicate-warning-message';
            warningDiv.className = 'alert alert-warning';
            
            const form = document.getElementById(config.formId);
            if (form) {
                form.insertBefore(warningDiv, form.firstChild);
            }
        }
        
        warningDiv.innerHTML = '<i class="fas fa-info-circle"></i> ' + message;
        warningDiv.style.display = 'block';
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            warningDiv.style.display = 'none';
        }, 5000);
    }
    
    /**
     * Highlight field
     * @param {String} fieldId
     * @param {String} type - 'error' or 'success'
     */
    function highlightField(fieldId, type) {
        const field = document.getElementById(fieldId);
        if (field) {
            // Remove existing classes
            field.classList.remove('is-invalid', 'is-valid', 'border-danger', 'border-success');
            
            if (type === 'error') {
                field.classList.add('is-invalid', 'border-danger');
            } else if (type === 'success') {
                field.classList.add('is-valid', 'border-success');
            }
        }
    }
    
    /**
     * Disable submit button
     */
    function disableSubmitButton() {
        if (config.formId) {
            const form = document.getElementById(config.formId);
            if (form) {
                const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                submitButtons.forEach(function(button) {
                    button.disabled = true;
                    button.classList.add('disabled');
                    button.setAttribute('data-disabled-by-validator', 'true');
                });
            }
        }
    }
    
    /**
     * Enable submit button
     */
    function enableSubmitButton() {
        if (config.formId) {
            const form = document.getElementById(config.formId);
            if (form) {
                const submitButtons = form.querySelectorAll('button[data-disabled-by-validator="true"]');
                submitButtons.forEach(function(button) {
                    button.disabled = false;
                    button.classList.remove('disabled');
                    button.removeAttribute('data-disabled-by-validator');
                });
            }
        }
    }
    
    /**
     * Manual check trigger (for programmatic use)
     * @param {Object} data - { nric, job_id or job_code }
     * @param {Function} callback - Callback function
     */
    function manualCheck(data, callback) {
        const formData = new FormData();
        formData.append('nric', data.nric);
        
        if (data.job_id) {
            formData.append('job_id', data.job_id);
        }
        if (data.job_code) {
            formData.append('job_code', data.job_code);
        }
        
        fetch(config.checkUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (callback) {
                callback(result);
            }
        })
        .catch(error => {
            console.error('[DuplicateValidator] Manual check failed:', error);
            if (callback) {
                callback({ status: 'error', duplicate: false });
            }
        });
    }
    
    // Public API
    return {
        init: init,
        checkDuplicate: checkDuplicate,
        manualCheck: manualCheck,
        enableAutoCheck: function() {
            config.enableAutoCheck = true;
        },
        disableAutoCheck: function() {
            config.enableAutoCheck = false;
        }
    };
})();

// Auto-initialize if data attributes present
document.addEventListener('DOMContentLoaded', function() {
    const autoInit = document.querySelector('[data-duplicate-validator="true"]');
    if (autoInit) {
        const options = {
            nricFieldId: autoInit.getAttribute('data-nric-field') || 'nombor_ic',
            jobIdFieldId: autoInit.getAttribute('data-job-id-field') || 'job_id',
            jobCodeFieldId: autoInit.getAttribute('data-job-code-field') || 'job_code',
            formId: autoInit.getAttribute('data-form-id') || autoInit.id,
            checkUrl: autoInit.getAttribute('data-check-url') || 'ajax-check-duplicate.php'
        };
        
        DuplicateValidator.init(options);
    }
});


