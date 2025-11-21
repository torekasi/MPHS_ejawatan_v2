/**
 * IC Auto-Population Functions
 * This script handles auto-population of form fields based on Malaysian IC number
 */

// Malaysian state codes for IC number place of birth mapping
const stateCodes = {
    '01': 'Johor',
    '02': 'Kedah',
    '03': 'Kelantan',
    '04': 'Melaka',
    '05': 'Negeri Sembilan',
    '06': 'Pahang',
    '07': 'Perak',
    '08': 'Perlis',
    '09': 'Pulau Pinang',
    '10': 'Selangor',
    '11': 'Terengganu',
    '12': 'Sabah',
    '13': 'Sarawak',
    '14': 'Wilayah Persekutuan Kuala Lumpur',
    '15': 'Wilayah Persekutuan Labuan',
    '16': 'Wilayah Persekutuan Putrajaya'
};

// Format IC number with automatic hyphen insertion
function formatICNumber(value) {
    // Remove all non-numeric characters
    const numbers = value.replace(/\D/g, '');
    
    // Ensure we have exactly 12 digits
    const limitedNumbers = numbers.slice(0, 12);
    
    // Apply formatting based on length
    if (limitedNumbers.length <= 6) {
        return limitedNumbers;
    } else if (limitedNumbers.length <= 8) {
        return limitedNumbers.slice(0, 6) + '-' + limitedNumbers.slice(6);
    } else {
        return limitedNumbers.slice(0, 6) + '-' + limitedNumbers.slice(6, 8) + '-' + limitedNumbers.slice(8);
    }
}

// Extract date of birth from IC number
function extractDOBFromIC(icNumber) {
    const cleanIC = icNumber.replace(/\D/g, '');
    if (cleanIC.length < 6) return null;
    
    const year = cleanIC.slice(0, 2);
    const month = cleanIC.slice(2, 4);
    const day = cleanIC.slice(4, 6);
    
    // Determine century (assuming people born in 1900s for years 00-30, 2000s for 31-99)
    const fullYear = parseInt(year) <= 30 ? '20' + year : '19' + year;
    
    return fullYear + '-' + month + '-' + day;
}

// Extract gender from IC number
function extractGenderFromIC(icNumber) {
    const cleanIC = icNumber.replace(/\D/g, '');
    if (cleanIC.length < 12) return null;
    
    const lastDigit = parseInt(cleanIC.slice(-1));
    return lastDigit % 2 === 0 ? 'Perempuan' : 'Lelaki';
}

// Extract place of birth from IC number
function extractPlaceOfBirthFromIC(icNumber) {
    const cleanIC = icNumber.replace(/\D/g, '');
    if (cleanIC.length < 8) return null;
    
    const placeCode = cleanIC.slice(6, 8);
    return stateCodes[placeCode] || null;
}

// Auto-calculate age from birth date
function calculateAge(birthDateString) {
    if (!birthDateString) return '';
    
    const birthDate = new Date(birthDateString);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    
    return age >= 0 ? age : '';
}

// Auto-select religion and bangsa based on name
function autoSelectReligionAndBangsa(name) {
    console.log('Checking name for religion/bangsa:', name);
    
    // Get the select elements
    const agamaSelect = document.getElementById('agama');
    const bangsaSelect = document.getElementById('bangsa');
    
    if (!agamaSelect || !bangsaSelect) {
        console.error('Religion or race select elements not found');
        return;
    }
    
    // Convert name to uppercase for case-insensitive matching
    const upperName = name.toUpperCase();
    
    // Check for Muslim indicators in name
    const muslimIndicators = [
        ' BIN ', ' BINTI ', ' BT ', ' B. ', ' BT. ', ' BINTI. ', ' BIN. ',
        'BIN ', 'BINTI ', 'BT ', 'B. ', 'BT. ', 'BINTI. ', 'BIN. ',  // At start
        ' BIN', ' BINTI', ' BT', ' B.', ' BT.', ' BINTI.', ' BIN.'   // At end
    ];
    
    // Check if any indicator is found in the name
    let foundIndicator = null;
    for (const indicator of muslimIndicators) {
        if (upperName.includes(indicator)) {
            foundIndicator = indicator;
            console.log('Found Muslim indicator:', indicator);
            break;
        }
    }
    
    // If a Muslim indicator is found, set religion and bangsa
    if (foundIndicator) {
        console.log('Setting religion to Islam and bangsa to Melayu');
        
        // Set religion to Islam
        agamaSelect.value = 'Islam';
        
        // Set bangsa to Melayu
        bangsaSelect.value = 'Melayu';
        
        // Force the changes to take effect
        setTimeout(() => {
            // Manually trigger change events
            agamaSelect.dispatchEvent(new Event('change'));
            bangsaSelect.dispatchEvent(new Event('change'));
        }, 200);
    }
}

// Auto-select warganegara based on negeri kelahiran
function autoSelectWarganegara() {
    const negeriSelect = document.getElementById('negeri_kelahiran');
    const warganegaraSelect = document.getElementById('warganegara');
    
    if (!negeriSelect || !warganegaraSelect) {
        console.error('Negeri or warganegara select elements not found');
        return;
    }
    
    if (negeriSelect.value && negeriSelect.value !== 'Bukan Malaysia') {
        // Set to Warganegara Malaysia for any Malaysian state
        warganegaraSelect.value = 'Warganegara Malaysia';
    } else if (negeriSelect.value === 'Bukan Malaysia') {
        warganegaraSelect.value = 'Bukan Warganegara';
    }
}

// Handle IC number input and auto-population
function handleICInput(event) {
    console.log('IC input handler triggered');
    
    // Get the IC input field
    const icInput = document.getElementById('nombor_ic');
    if (!icInput) {
        console.error('IC input element not found');
        return;
    }
    
    // Get and format the IC value
    let icValue = icInput.value;
    console.log('Current IC value:', icValue);
    
    // Format IC if this was triggered by user input
    if (event && event.type === 'input') {
        icValue = formatICNumber(icValue);
        icInput.value = icValue;
    }
    
    // Set custom validity message based on format
    if (!icInput.value.match(/^\d{6}-\d{2}-\d{4}$/)) {
        icInput.setCustomValidity('Format NRIC mesti 6-2-4 seperti 800101-14-1234');
    } else {
        icInput.setCustomValidity('');
    }

    // Clean IC for processing (remove all non-digits)
    const cleanIC = icValue.replace(/\D/g, '');
    
    // Only proceed if we have a complete IC number (12 digits)
    if (cleanIC.length !== 12) {
        console.log('IC number incomplete, skipping auto-population');
        return;
    }
    
    console.log('Processing complete IC:', cleanIC);
    
    // 1. Extract and set date of birth
    const dob = extractDOBFromIC(cleanIC);
    console.log('Extracted DOB:', dob);
    
    if (dob) {
        const dobInput = document.getElementById('tarikh_lahir');
        if (dobInput) {
            dobInput.value = dob;
            console.log('Set DOB input to:', dob);
            
            // Calculate and set age based on DOB
            const age = calculateAge(dob);
            const umurInput = document.getElementById('umur');
            if (umurInput) {
                umurInput.value = age;
                console.log('Set age to:', age);
            }
        }
    }
    
    // 2. Extract and set gender
    const gender = extractGenderFromIC(cleanIC);
    console.log('Extracted gender:', gender);
    
    if (gender) {
        // Prefer select if present
        const genderSelect = document.querySelector('select[name="jantina"], #jantina');
        if (genderSelect) {
            genderSelect.value = gender;
            console.log('Set gender select to:', gender);
            genderSelect.dispatchEvent(new Event('change'));
        } else {
            const genderRadios = document.querySelectorAll('input[name="jantina"]');
            genderRadios.forEach(radio => {
                if (radio.value === gender) {
                    radio.checked = true;
                    console.log('Set gender radio to:', gender);
                } else {
                    radio.checked = false;
                }
            });
        }
    }
    
    // 3. Extract and set place of birth
    const placeOfBirth = extractPlaceOfBirthFromIC(cleanIC);
    console.log('Extracted place of birth:', placeOfBirth);
    
    if (placeOfBirth) {
        const negeriSelect = document.getElementById('negeri_kelahiran');
        if (negeriSelect) {
            negeriSelect.value = placeOfBirth;
            console.log('Set birthplace to:', placeOfBirth);
            
            // Also set warganegara based on birthplace
            autoSelectWarganegara();
        }
    }
    
    // Force update all fields
    setTimeout(function() {
        // Trigger change events after a short delay to ensure values are set
        const dobInput = document.getElementById('tarikh_lahir');
        if (dobInput) dobInput.dispatchEvent(new Event('change'));
        
        const negeriSelect = document.getElementById('negeri_kelahiran');
        if (negeriSelect) negeriSelect.dispatchEvent(new Event('change'));
        
        const genderRadio = document.querySelector('input[name="jantina"]:checked');
        if (genderRadio) genderRadio.dispatchEvent(new Event('change'));
    }, 100);
}

// Handle date of birth change for age calculation
function handleDOBChange() {
    const dobInput = document.getElementById('tarikh_lahir');
    if (!dobInput) return;
    
    const age = calculateAge(dobInput.value);
    const umurInput = document.getElementById('umur');
    if (umurInput) {
        umurInput.value = age;
    }
}

// Handle name input for religion and bangsa auto-selection
function handleNameInput() {
    const nameInput = document.getElementById('nama_penuh');
    if (!nameInput || !nameInput.value) return;
    
    // Convert to uppercase if needed
    if (nameInput.value !== nameInput.value.toUpperCase()) {
        nameInput.value = nameInput.value.toUpperCase();
    }
    
    // Process for religion and bangsa
    autoSelectReligionAndBangsa(nameInput.value);
}

// Handle negeri kelahiran change for 'Bukan Malaysia' case
function handleNegeriKelahiranChange() {
    autoSelectWarganegara();
}

// Toggle correspondence address fields based on checkbox
function toggleCorrespondenceAddress() {
    const checkbox = document.getElementById('alamat_surat_sama');
    const correspondenceFields = document.getElementById('correspondence-fields');
    
    if (!checkbox || !correspondenceFields) {
        console.error('Correspondence address elements not found');
        return;
    }
    
    if (checkbox.checked) {
        // Keep fields visible but copy values from permanent address
        // Copy values from permanent address to correspondence address
        const alamatTetap = document.getElementById('alamat_tetap');
        const poskodTetap = document.getElementById('poskod_tetap');
        const bandarTetap = document.getElementById('bandar_tetap');
        const negeriTetap = document.getElementById('negeri_tetap');
        
        const alamatSurat = document.getElementById('alamat_surat');
        const poskodSurat = document.getElementById('poskod_surat');
        const bandarSurat = document.getElementById('bandar_surat');
        const negeriSurat = document.getElementById('negeri_surat');
        
        if (alamatTetap && alamatSurat) alamatSurat.value = alamatTetap.value;
        if (poskodTetap && poskodSurat) poskodSurat.value = poskodTetap.value;
        if (bandarTetap && bandarSurat) bandarSurat.value = bandarTetap.value;
        if (negeriTetap && negeriSurat) negeriSurat.value = negeriTetap.value;
        
        // Make correspondence fields read-only
        const allFields = correspondenceFields.querySelectorAll('input, select');
        allFields.forEach(field => {
            field.readOnly = true;
            if (field.tagName === 'SELECT') {
                field.classList.add('bg-gray-100');
                field.style.pointerEvents = 'none';
            } else {
                field.classList.add('bg-gray-100');
            }
            field.required = false;
        });
    } else {
        // Make correspondence fields editable again
        const allFields = correspondenceFields.querySelectorAll('input, select');
        allFields.forEach(field => {
            field.readOnly = false;
            if (field.tagName === 'SELECT') {
                field.classList.remove('bg-gray-100');
                field.style.pointerEvents = 'auto';
            } else {
                field.classList.remove('bg-gray-100');
            }
            
            // Check if the field had the required attribute originally
            if (field.classList.contains('required-field')) {
                field.required = true;
            }
        });
    }
}

// Handle lesen memandu checkbox changes
function handleLesenMemanduChange() {
    const lesenCheckboxes = document.querySelectorAll('input[name="lesen_memandu[]"]');
    const tiadaLesenCheckbox = Array.from(lesenCheckboxes).find(cb => cb.value === 'Tiada');
    const tarikhTamatLesenField = document.querySelector('input[name="tarikh_tamat_lesen"]');
    const salinanLesenField = document.querySelector('input[name="salinan_lesen_memandu"]');
    
    if (!tiadaLesenCheckbox || !tarikhTamatLesenField || !salinanLesenField) {
        // Elements not present on this form; safely skip without errors
        return;
    }
    
    // Function to check if any lesen is selected except "Tiada"
    const hasActiveLesen = () => {
        let hasLesen = false;
        lesenCheckboxes.forEach(cb => {
            if (cb.checked && cb.value !== 'Tiada') {
                hasLesen = true;
            }
        });
        return hasLesen;
    };
    
    // Function to update fields visibility
    const updateLesenFields = () => {
        const showFields = hasActiveLesen();
        const hideFields = tiadaLesenCheckbox.checked;
        
        // Handle the tarikh tamat lesen and salinan lesen fields
        if (hideFields || !showFields) {
            // Hide and disable the fields
            tarikhTamatLesenField.parentElement.style.display = 'none';
            salinanLesenField.parentElement.style.display = 'none';
            tarikhTamatLesenField.required = false;
        } else {
            // Show and enable the fields
            tarikhTamatLesenField.parentElement.style.display = 'block';
            salinanLesenField.parentElement.style.display = 'block';
            tarikhTamatLesenField.required = true;
        }
    };
    
    // Add event listeners to all checkboxes
    lesenCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            // If "Tiada" is checked, uncheck all others
            if (this.value === 'Tiada' && this.checked) {
                lesenCheckboxes.forEach(otherCb => {
                    if (otherCb !== this) {
                        otherCb.checked = false;
                    }
                });
            }
            // If any other is checked, uncheck "Tiada"
            else if (this.value !== 'Tiada' && this.checked && tiadaLesenCheckbox.checked) {
                tiadaLesenCheckbox.checked = false;
            }
            
            updateLesenFields();
        });
    });
    
    // Initialize fields on page load
    updateLesenFields();
}

// Initialize all event listeners when the document is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Document loaded, initializing auto-population');
    
    // Set up IC number formatting and auto-population
    const icInput = document.getElementById('nombor_ic');
    if (icInput) {
        console.log('Setting up IC number input handler');
        
        // Add the event listener for user input
        icInput.addEventListener('input', handleICInput);
        
        // Format and trigger handlers for pre-filled value
        if (icInput.value) {
            // If the value doesn't have proper formatting, format it
            if (!icInput.value.match(/^\d{6}-\d{2}-\d{4}$/)) {
                icInput.value = formatICNumber(icInput.value);
            }
            // Trigger auto-population
            handleICInput();
        }
    }
    
    // Set up date of birth change handler
    const dobInput = document.getElementById('tarikh_lahir');
    const umurInput = document.getElementById('umur');
    
    if (dobInput) {
        dobInput.addEventListener('change', handleDOBChange);
        
        // Calculate age on page load if date of birth is already filled
        if (dobInput.value) {
            handleDOBChange();
        }
    }
    
    // Ensure age is calculated on page load (for back button navigation)
    if (dobInput && umurInput && dobInput.value && !umurInput.value) {
        const age = calculateAge(dobInput.value);
        if (age) {
            umurInput.value = age;
            console.log('Age calculated on page load:', age);
        }
    }
    
    // Handle browser back button - recalculate age when page becomes visible
    window.addEventListener('pageshow', function(event) {
        const dobInput = document.getElementById('tarikh_lahir');
        const umurInput = document.getElementById('umur');
        
        if (dobInput && umurInput && dobInput.value) {
            const age = calculateAge(dobInput.value);
            if (age) {
                umurInput.value = age;
                console.log('Age recalculated on pageshow:', age);
            }
        }
    });
    
    // Set up name input handler for religion and bangsa auto-selection
    const nameInput = document.getElementById('nama_penuh');
    if (nameInput) {
        nameInput.addEventListener('input', handleNameInput);
        
        // If name is already filled, trigger the handler
        if (nameInput.value) {
            handleNameInput();
        }
    }
    
    // Set up negeri kelahiran change handler for warganegara auto-selection
    const negeriKelahiranSelect = document.getElementById('negeri_kelahiran');
    if (negeriKelahiranSelect) {
        negeriKelahiranSelect.addEventListener('change', handleNegeriKelahiranChange);
        
        // If negeri is already selected, trigger the handler
        if (negeriKelahiranSelect.value) {
            handleNegeriKelahiranChange();
        }
    }
    
    // Set up correspondence address toggle
    const addressCheckbox = document.getElementById('alamat_surat_sama');
    if (addressCheckbox) {
        addressCheckbox.addEventListener('change', toggleCorrespondenceAddress);
        
        // If checkbox is already checked, trigger the handler
        if (addressCheckbox.checked) {
            toggleCorrespondenceAddress();
        }
    }
    
    // Set up lesen memandu handler
    handleLesenMemanduChange();
    
    // Mark correspondence fields as required for later reference
    const correspondenceFields = document.getElementById('correspondence-fields');
    if (correspondenceFields) {
        const requiredFields = correspondenceFields.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            field.classList.add('required-field');
        });
    }
    
    console.log('Auto-population initialization complete');
});
