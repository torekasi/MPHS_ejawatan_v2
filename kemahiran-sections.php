<?php
// This file contains the dynamic sections for Kemahiran Bahasa and Kemahiran Komputer
// Include this in your job-application-1.php where appropriate
?>

<!-- Language Skills Section (Kemahiran Bahasa) -->
<div class="bg-white rounded-lg shadow-md overflow-hidden mt-6">
    <div class="section-title">
        KEMAHIRAN BAHASA
    </div>
    <div class="p-6">
        <div id="language-skills-container">
            <!-- Initial language skill entry -->
            <div class="language-skill-entry bg-gray-50 p-4 rounded-lg mb-4">
                <div class="grid grid-cols-3 gap-4">
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Bahasa <span class="required">*</span>
                        </label>
                        <input type="text" name="kemahiran_bahasa[0][bahasa]" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase-input" 
                               placeholder="MASUKKAN BAHASA" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Pertuturan
                        </label>
                        <select name="kemahiran_bahasa[0][pertuturan]" 
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
                        <select name="kemahiran_bahasa[0][penulisan]" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">PILIH TAHAP</option>
                            <option value="Baik">Baik</option>
                            <option value="Sederhana">Sederhana</option>
                            <option value="Lemah">Lemah</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="button" class="remove-language-btn text-red-500 text-sm" style="display: none;">Buang</button>
                </div>
            </div>
        </div>
        
        <!-- Add Language Button -->
        <div class="flex justify-end">
            <button type="button" id="addLanguageSkill" class="add-row-btn">Tambah Bahasa</button>
        </div>
    </div>
</div>

<!-- Computer Skills Section (Kemahiran Komputer) -->
<div class="bg-white rounded-lg shadow-md overflow-hidden mt-6">
    <div class="section-title">
        KEMAHIRAN KOMPUTER
    </div>
    <div class="p-6">
        <div id="computer-skills-container">
            <!-- Initial computer skill entry -->
            <div class="computer-skill-entry bg-gray-50 p-4 rounded-lg mb-4">
                <div class="grid grid-cols-1 gap-4">
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Nama Perisian <span class="required">*</span>
                        </label>
                        <input type="text" name="kemahiran_komputer[0][nama_perisian]" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase-input" 
                               placeholder="MASUKKAN NAMA PERISIAN" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Tahap Kemahiran
                    </label>
                    <select name="kemahiran_komputer[0][tahap_kemahiran]" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">PILIH TAHAP</option>
                        <option value="Mahir">Mahir</option>
                        <option value="Sederhana">Sederhana</option>
                        <option value="Asas">Asas</option>
                    </select>
                </div>
                <div class="flex justify-end">
                    <button type="button" class="remove-computer-btn text-red-500 text-sm" style="display: none;">Buang</button>
                </div>
            </div>
        </div>
        
        <!-- Add Computer Skill Button -->
        <div class="flex justify-end">
            <button type="button" id="addComputerSkill" class="add-row-btn">Tambah Kemahiran Komputer</button>
        </div>
    </div>
</div>

<!-- JavaScript for Dynamic Skills Sections -->
<script>
// Language Skills Functions
document.addEventListener('DOMContentLoaded', function() {
    // Initialize language skills section
    const addLanguageBtn = document.getElementById('addLanguageSkill');
    if (addLanguageBtn) {
        addLanguageBtn.addEventListener('click', addLanguageSkill);
        
        // Show remove buttons if there are multiple entries
        updateLanguageRemoveButtons();
    }
    
    // Initialize computer skills section
    const addComputerBtn = document.getElementById('addComputerSkill');
    if (addComputerBtn) {
        addComputerBtn.addEventListener('click', addComputerSkill);
        
        // Show remove buttons if there are multiple entries
        updateComputerRemoveButtons();
    }
});

// Language Skills Functions
function addLanguageSkill() {
    try {
        const container = document.getElementById('language-skills-container');
        const entries = container.querySelectorAll('.language-skill-entry');
        const newIndex = entries.length;
        
        // Create new entry
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
        
        // Add event listener to remove button
        const removeBtn = newEntry.querySelector('.remove-language-btn');
        removeBtn.addEventListener('click', function() {
            container.removeChild(newEntry);
            updateLanguageRemoveButtons();
        });
        
        // Add new entry to container
        container.appendChild(newEntry);
        
        // Update remove buttons visibility
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
        
        // Show remove buttons only if there are multiple entries
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

// Computer Skills Functions
function addComputerSkill() {
    try {
        const container = document.getElementById('computer-skills-container');
        const entries = container.querySelectorAll('.computer-skill-entry');
        const newIndex = entries.length;
        
        // Create new entry
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
        
        // Add event listener to remove button
        const removeBtn = newEntry.querySelector('.remove-computer-btn');
        removeBtn.addEventListener('click', function() {
            container.removeChild(newEntry);
            updateComputerRemoveButtons();
        });
        
        // Add new entry to container
        container.appendChild(newEntry);
        
        // Update remove buttons visibility
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
        
        // Show remove buttons only if there are multiple entries
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
</script>

