document.addEventListener('DOMContentLoaded', function () {
    console.log('Job Application JS loaded');

    const form = document.getElementById('applicationForm');
    const fileInputs = document.querySelectorAll('input[type="file"]');
    const modal = document.getElementById('validationModal');
    const modalErrorsList = document.getElementById('validationModalErrors');
    const modalCloseBtn = document.getElementById('validationModalClose');
    const modalAcknowledgeBtn = document.getElementById('validationModalAcknowledge');

    console.log('Found file inputs:', fileInputs.length);
    console.log('Modal found:', !!modal);

    function showModal(errors) {
        console.log('showModal called with errors:', errors);

        if (!modal || !modalErrorsList) {
            console.log('Modal or modalErrorsList not found, using alert');
            alert(errors.join('\n'));
            return;
        }

        console.log('Modal found, showing modal');
        modalErrorsList.innerHTML = '';
        errors.forEach(error => {
            const li = document.createElement('li');
            li.textContent = error;
            modalErrorsList.appendChild(li);
        });

        // Remove hidden to show modal (flex is already in HTML)
        modal.classList.remove('hidden');
        console.log('Modal classes after show:', modal.className);
    }

    function hideModal() {
        console.log('hideModal called');
        if (!modal) return;

        modal.classList.add('hidden');
        console.log('Modal classes after hide:', modal.className);
    }

    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', hideModal);
    }

    if (modalAcknowledgeBtn) {
        modalAcknowledgeBtn.addEventListener('click', hideModal);
    }

    function getAllowedTypes(input) {
        const attribute = input.getAttribute('data-allowed-types');
        if (attribute) {
            return attribute.split(',').map(type => type.trim()).filter(Boolean);
        }
        return ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
    }

    function getMaxSizeMb(input) {
        const maxSizeAttr = input.getAttribute('data-max-size');
        const parsed = parseFloat(maxSizeAttr);
        if (!isNaN(parsed) && parsed > 0) {
            return parsed;
        }
        return 2;
    }

    function getFieldLabel(input) {
        return input.getAttribute('data-field-label') || input.name || 'Dokumen';
    }

    // Client-side validation function (kept as fallback)
    function validateSingleFileInput(input, { silent = false, showToasts = true } = {}) {
        if (!input || input.files.length === 0) {
            clearFileStatus(input);
            return { valid: true };
        }

        const file = input.files[0];
        const maxSize = getMaxSizeMb(input);
        const allowedTypes = getAllowedTypes(input);
        const fieldLabel = getFieldLabel(input);
        const fileSize = file.size / 1024 / 1024;

        // Show loading state while validating
        if (!silent) {
            input.classList.remove('border-green-500', 'border-red-500');
            input.classList.add('border-yellow-400');
            showFileStatus(input.id, 'loading', 'Memeriksa fail...');
        }

        // Check file size first
        if (fileSize > maxSize) {
            const message = `${fieldLabel}: Saiz fail melebihi ${maxSize}MB (saiz semasa ${fileSize.toFixed(2)}MB)`;
            if (!silent) {
                showFileStatus(input.id, 'error', message);
                if (showToasts) {
                    showToast('error', `Fail ${fieldLabel} terlalu besar! Maksimum ${maxSize}MB dibenarkan.`);
                }
            }
            input.classList.remove('border-green-500', 'border-yellow-400');
            input.classList.add('border-red-500');
            input.setAttribute('aria-invalid', 'true');
            return { valid: false, message };
        }

        // Check file type
        if (!allowedTypes.includes(file.type)) {
            const message = `${fieldLabel}: Jenis fail tidak dibenarkan (${file.type || 'tidak diketahui'})`;
            if (!silent) {
                showFileStatus(input.id, 'error', message);
                if (showToasts) {
                    showToast('error', `Jenis fail ${fieldLabel} tidak dibenarkan! Sila pilih jenis fail yang betul.`);
                }
            }
            input.classList.remove('border-green-500', 'border-yellow-400');
            input.classList.add('border-red-500');
            input.setAttribute('aria-invalid', 'true');
            return { valid: false, message };
        }

        // File is valid
        const successMessage = `✓ ${fieldLabel} sah (${fileSize.toFixed(2)}MB) - ${file.name}`;
        if (!silent) {
            showFileStatus(input.id, 'success', successMessage);
            if (showToasts) {
                showToast('success', `${fieldLabel} berjaya dimuat naik!`);
            }
        }
        input.classList.remove('border-red-500', 'border-yellow-400');
        input.classList.add('border-green-500');
        input.removeAttribute('aria-invalid');
        return { valid: true };
    }

    // Legacy function for backward compatibility (not used in AJAX validation)
    function validateAllFileInputs(options = {}) {
        const errors = [];
        fileInputs.forEach(input => {
            const result = validateSingleFileInput(input, options);
            if (!result.valid && result.message) {
                errors.push(result.message);
            }
        });
        return errors;
    }

    if (fileInputs.length > 0) {
        console.log('Setting up file validation for', fileInputs.length, 'file inputs');

        fileInputs.forEach((input, index) => {
            console.log(`Setting up validation for input ${index}:`, input.id || input.name, 'max-size:', input.getAttribute('data-max-size'));

            // Test if the input is working
            input.addEventListener('click', function() {
                console.log('File input clicked:', this.id || this.name);
            });

            input.addEventListener('change', function () {
                console.log('File input changed for:', this.id || this.name);

                if (this.files.length === 0) {
                    console.log('No file selected, clearing status');
                    clearFileStatus(this);
                    this.removeAttribute('aria-invalid');
                    // Show placeholder text for empty file input
                    const fieldLabel = getFieldLabel(this);
                    showFileStatus(this.id, 'info', `${fieldLabel}: Tiada fail dipilih`);
                    this.classList.add('border-blue-400');
                    return;
                }

                const file = this.files[0];
                console.log('File selected:', file.name, 'size:', (file.size / 1024 / 1024).toFixed(2), 'MB', 'type:', file.type);

                // Show file size immediately
                const fileSizeMB = (file.size / 1024 / 1024).toFixed(2);
                const fieldLabel = getFieldLabel(this);
                showFileStatus(this.id, 'info', `${fieldLabel}: ${fileSizeMB}MB - ${file.name}`);

                // Immediate client-side validation
                const maxSize = getMaxSizeMb(this);
                const allowedTypes = getAllowedTypes(this);
                const fileSize = file.size / 1024 / 1024;

                console.log('Validation params:', { maxSize, allowedTypes, fieldLabel, fileSize });

                // Show loading state
                this.classList.remove('border-green-500', 'border-red-500');
                this.classList.add('border-yellow-400');
                showFileStatus(this.id, 'loading', 'Memeriksa fail...');

                // Validate file size
                if (fileSize > maxSize) {
                    const message = `${fieldLabel}: Saiz fail melebihi ${maxSize}MB (saiz semasa ${fileSize.toFixed(2)}MB)`;
                    console.log('File size validation failed:', message);

                    this.value = '';
                    this.classList.remove('border-green-500', 'border-yellow-400');
                    this.classList.add('border-red-500');
                    this.setAttribute('aria-invalid', 'true');
                    showFileStatus(this.id, 'error', message);

                    // Show modal with validation error
                    showModal([message]);
                    showToast('error', `Fail ${fieldLabel} terlalu besar! Maksimum ${maxSize}MB dibenarkan.`);
                    return;
                }

                // Validate file type
                if (!allowedTypes.includes(file.type)) {
                    const message = `${fieldLabel}: Jenis fail tidak dibenarkan (${file.type})`;
                    console.log('File type validation failed:', message);

                    this.value = '';
                    this.classList.remove('border-green-500', 'border-yellow-400');
                    this.classList.add('border-red-500');
                    this.setAttribute('aria-invalid', 'true');
                    showFileStatus(this.id, 'error', message);

                    // Show modal with validation error
                    showModal([message]);
                    showToast('error', `Jenis fail ${fieldLabel} tidak dibenarkan! Sila pilih jenis fail yang betul.`);
                    return;
                }

                // File is valid
                console.log('File validation passed');
                this.classList.remove('border-red-500', 'border-yellow-400');
                this.classList.add('border-green-500');
                this.removeAttribute('aria-invalid');
                showFileStatus(this.id, 'success', `✓ ${fieldLabel} sah dan sedia untuk dimuat naik`);
                showToast('success', `${fieldLabel} berjaya dimuat naik!`);
            });
        });
    } else {
        console.log('No file inputs found!');
    }

    function showFileStatus(inputId, type, message) {
        if (!inputId) return;
        const statusDiv = document.getElementById(`${inputId}_status`);
        if (statusDiv) {
            statusDiv.textContent = message;
            statusDiv.className = `file-status ${type}`.trim();
        }
    }

    function clearFileStatus(input) {
        if (!input) return;
        input.classList.remove('border-red-500', 'border-green-500', 'border-yellow-400', 'border-blue-400');
        input.removeAttribute('aria-invalid');
        if (input.id) {
            showFileStatus(input.id, '', '');
        }
    }

    // Toast notification system
    function showToast(type, message, duration = 3000) {
        // Remove existing toasts
        const existingToasts = document.querySelectorAll('.toast-notification');
        existingToasts.forEach(toast => toast.remove());

        const toast = document.createElement('div');
        toast.className = `toast-notification fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg transition-all transform translate-x-full animate-slide-in`;

        if (type === 'success') {
            toast.classList.add('bg-green-500', 'text-white');
        } else if (type === 'error') {
            toast.classList.add('bg-red-500', 'text-white');
        } else {
            toast.classList.add('bg-blue-500', 'text-white');
        }

        toast.innerHTML = `
            <div class="flex items-center space-x-2">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-2 hover:opacity-75">&times;</button>
            </div>
        `;

        document.body.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
        }, 10);

        // Auto remove after duration
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 300);
        }, duration);
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            console.log('Form submission started');

            // Collect all files for validation
            const validationErrors = [];
            fileInputs.forEach(input => {
                if (input.files.length > 0) {
                    const result = validateSingleFileInput(input, { silent: true, showToasts: false });
                    if (!result.valid && result.message) {
                        // Clear invalid file
                        input.value = '';
                        validationErrors.push(result.message);
                    }
                }
            });

            if (validationErrors.length > 0) {
                event.preventDefault();
                showModal(validationErrors);

                // Re-enable submit button
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
                    submitBtn.innerHTML = 'Simpan & Seterusnya';
                }
                return false;
            }

            // All files valid, proceed with submission
            console.log('All files valid, submitting form');
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = 'Menghantar...';
            }
            return true;
        });
    }

    const lesenContainer = document.getElementById('lesen-memandu-container');
    /*
    const addLesenBtn = document.getElementById('add-lesen-btn');
    
    if (addLesenBtn && lesenContainer) {
        addLesenBtn.addEventListener('click', function() {
            const newRow = document.createElement('div');
            newRow.className = 'lesen-memandu-row grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 relative';
            
            newRow.innerHTML = `
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Lesen Memandu (Kelas) <span class="required">*</span>
                    </label>
                    <select name="lesen_memandu[]" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                           required>
                        <option value="">Sila Pilih</option>
                        <option value="A">A - Motosikal (Tanpa Gear)</option>
                        <option value="B">B - Motosikal (Dengan Gear)</option>
                        <option value="B1">B1 - Motosikal (125cc dan ke bawah)</option>
                        <option value="B2">B2 - Motosikal (Melebihi 125cc dan ke bawah 500cc)</option>
                        <option value="C">C - Kereta Persendirian</option>
                        <option value="D">D - Motosikal & Kereta Persendirian</option>
                        <option value="E">E - Kenderaan Perdagangan (Ringan)</option>
                        <option value="E1">E1 - Traktor Pertanian</option>
                        <option value="E2">E2 - Jentera Bergerak</option>
                        <option value="F">F - Kenderaan Perdagangan (Berat)</option>
                        <option value="G">G - Traktor Berat</option>
                        <option value="H">H - Bas Berat</option>
                        <option value="I">I - Bas Ringan</option>
                        <option value="Tiada">Tiada Lesen</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Tarikh Tamat Lesen Memandu <span class="required">*</span>
                    </label>
                    <input type="date" name="tarikh_tamat_lesen[]" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                           required>
                </div>
                <button type="button" class="remove-row-btn absolute top-0 right-0">
                    &times; Buang
                </button>
            `;
            
            lesenContainer.appendChild(newRow);
            
            const removeBtn = newRow.querySelector('.remove-row-btn');
            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    lesenContainer.removeChild(newRow);
                });
            }
        });
    }
    */

    /*
    const addLanguageBtn = document.getElementById('addLanguageSkill');
    if (addLanguageBtn) {
        addLanguageBtn.addEventListener('click', addLanguageSkill);
        updateLanguageRemoveButtons();
    }
    
    const addComputerBtn = document.getElementById('addComputerSkill');
    if (addComputerBtn) {
        addComputerBtn.addEventListener('click', addComputerSkill);
        updateComputerRemoveButtons();
    }

    function addLanguageSkill() {
        try {
            const container = document.getElementById('language-skills-container');
            const entries = container.querySelectorAll('.language-skill-entry');
            const newIndex = entries.length;
            
            const newEntry = document.createElement('div');
            newEntry.className = 'language-skill-entry bg-gray-50 p-4 rounded-lg mb-4';
            
            newEntry.innerHTML = `
                <div class="grid grid-cols-3 gap-4">
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Bahasa <span class="required">*</span>
                        </label>
                        <input type="text" name="kemahiran_bahasa[${newIndex}][bahasa]" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase-input" 
                               placeholder="MASUKKAN BAHASA" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Pertuturan
                        </label>
                        <select name="kemahiran_bahasa[${newIndex}][pertuturan]" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">PILIH TAHAP</option>
                            <option value="Baik">Baik</option>
                            <option value="Sederhana">Sederhana</option>
                            <option value="Lemah">Lemah</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Penulisan
                        </label>
                        <select name="kemahiran_bahasa[${newIndex}][penulisan]" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">PILIH TAHAP</option>
                            <option value="Baik">Baik</option>
                            <option value="Sederhana">Sederhana</option>
                            <option value="Lemah">Lemah</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="button" class="remove-language-btn text-red-500 text-sm">Buang</button>
                </div>
            `;
            
            const removeBtn = newEntry.querySelector('.remove-language-btn');
            removeBtn.addEventListener('click', function() {
                container.removeChild(newEntry);
                updateLanguageRemoveButtons();
            });
            
            container.appendChild(newEntry);
            
            updateLanguageRemoveButtons();
            
            console.log('Added new language skill entry');
        } catch (error) {
            console.error('Error adding language skill:', error);
        }
    }

    function updateLanguageRemoveButtons() {
        try {
            const container = document.getElementById('language-skills-container');
            const entries = container.querySelectorAll('.language-skill-entry');
            const removeButtons = container.querySelectorAll('.remove-language-btn');
            
            if (entries.length > 1) {
                removeButtons.forEach(btn => {
                    btn.style.display = 'block';
                });
            } else {
                removeButtons.forEach(btn => {
                    btn.style.display = 'none';
                });
            }
        } catch (error) {
            console.error('Error updating language remove buttons:', error);
        }
    }

    function addComputerSkill() {
        try {
            const container = document.getElementById('computer-skills-container');
            const entries = container.querySelectorAll('.computer-skill-entry');
            const newIndex = entries.length;
            
            const newEntry = document.createElement('div');
            newEntry.className = 'computer-skill-entry bg-gray-50 p-4 rounded-lg mb-4';
            
            newEntry.innerHTML = `
                <div class="grid grid-cols-1 gap-4">
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Nama Perisian <span class="required">*</span>
                        </label>
                        <input type="text" name="kemahiran_komputer[${newIndex}][nama_perisian]" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase-input" 
                               placeholder="MASUKKAN NAMA PERISIAN" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Tahap Kemahiran
                    </label>
                    <select name="kemahiran_komputer[${newIndex}][tahap_kemahiran]" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">PILIH TAHAP</option>
                        <option value="Mahir">Mahir</option>
                        <option value="Sederhana">Sederhana</option>
                        <option value="Asas">Asas</option>
                    </select>
                </div>
                <div class="flex justify-end">
                    <button type="button" class="remove-computer-btn text-red-500 text-sm">Buang</button>
                </div>
            `;
            
            const removeBtn = newEntry.querySelector('.remove-computer-btn');
            removeBtn.addEventListener('click', function() {
                container.removeChild(newEntry);
                updateComputerRemoveButtons();
            });
            
            container.appendChild(newEntry);
            
            updateComputerRemoveButtons();
            
            console.log('Added new computer skill entry');
        } catch (error) {
            console.error('Error adding computer skill:', error);
        }
    }

    function updateComputerRemoveButtons() {
        try {
            const container = document.getElementById('computer-skills-container');
            const entries = container.querySelectorAll('.computer-skill-entry');
            const removeButtons = container.querySelectorAll('.remove-computer-btn');
            
            if (entries.length > 1) {
                removeButtons.forEach(btn => {
                    btn.style.display = 'block';
                });
            } else {
                removeButtons.forEach(btn => {
                    btn.style.display = 'none';
                });
            }
        } catch (error) {
            console.error('Error updating computer remove buttons:', error);
        }
    }
    */
});