// Complete Assets Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    
    // Global variables
    let currentAsset = null;
    let selectMode = false;
    let allAssets = [];
    
    // DOM elements
    const addAssetBtn = document.getElementById('addAssetBtn');
    const selectBtn = document.getElementById('selectBtn');
    const deleteBtn = document.getElementById('deleteBtn');
    const searchInput = document.getElementById('searchInput');
    
    // Initialize Bootstrap modals
    let assetModal, deleteModal;
    
    setTimeout(() => {
        const assetModalElement = document.getElementById('assetModal');
        const deleteModalElement = document.getElementById('deleteModal');
        
        if (assetModalElement) {
            assetModal = new bootstrap.Modal(assetModalElement, {
                backdrop: 'static',
                keyboard: false
            });
        }
        
        if (deleteModalElement) {
            deleteModal = new bootstrap.Modal(deleteModalElement);
        }
    }, 100);
    
    const assetForm = document.getElementById('assetForm');
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');
    
    // Initialize
    initializeAssets();
    
    function initializeAssets() {
        // Store all assets data
        const assetRows = document.querySelectorAll('.asset-row');
        allAssets = Array.from(assetRows).map(row => JSON.parse(row.dataset.json));
        
        // Add event listeners
        setupEventListeners();
        
        // Setup row click handlers
        setupRowClickHandlers();
    }
    
    function setupEventListeners() {
        // Add asset button
        if (addAssetBtn) addAssetBtn.addEventListener('click', openAddModal);
        
        // Select mode toggle
        if (selectBtn) selectBtn.addEventListener('click', toggleSelectMode);
        
        // Delete button
        if (deleteBtn) deleteBtn.addEventListener('click', showDeleteConfirmation);
        
        // Search functionality
        if (searchInput) searchInput.addEventListener('input', handleSearch);
        
        // Edit asset button
        const editBtn = document.getElementById('editAssetBtn');
        if (editBtn) editBtn.addEventListener('click', openEditModal);
        
        // Delete confirmation
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        if (confirmDeleteBtn) confirmDeleteBtn.addEventListener('click', confirmDelete);
        
        // Form submission
        if (assetForm) assetForm.addEventListener('submit', handleFormSubmit);
        
        // ESC key handler for modals and dropdowns
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                // Close Bootstrap modals
                const openBootstrapModal = document.querySelector('.modal.show');
                if (openBootstrapModal) {
                    const modalInstance = bootstrap.Modal.getInstance(openBootstrapModal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
                
                // Close custom dropdowns
                closeAllDropdowns();
                
                // Exit select mode
                if (selectMode) {
                    toggleSelectMode();
                }
            }
        });
        
        // Setup dropdown functionality
        setupDropdowns();
    }
    
    function setupDropdowns() {
        const sortBtn = document.getElementById('sortBtn');
        const filterBtn = document.getElementById('filterBtn');
        const sortDropdown = document.getElementById('sortDropdown');
        const filterDropdown = document.getElementById('filterDropdown');
        
        // Sort dropdown
        if (sortBtn && sortDropdown) {
            sortBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                closeAllDropdowns();
                sortDropdown.classList.toggle('show');
                sortBtn.classList.toggle('active');
            });
            
            // Sort options
            sortDropdown.addEventListener('click', function(e) {
                if (e.target.classList.contains('dropdown-item-custom')) {
                    const sortType = e.target.dataset.sort;
                    handleSort(sortType);
                    
                    // Update active state
                    sortDropdown.querySelectorAll('.dropdown-item-custom').forEach(item => {
                        item.classList.remove('active');
                    });
                    e.target.classList.add('active');
                    
                    closeAllDropdowns();
                }
            });
        }
        
        // Filter dropdown
        if (filterBtn && filterDropdown) {
            filterBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                closeAllDropdowns();
                filterDropdown.classList.toggle('show');
                filterBtn.classList.toggle('active');
            });
            
            // Filter options
            filterDropdown.addEventListener('click', function(e) {
                if (e.target.classList.contains('dropdown-item-custom')) {
                    const filterType = e.target.dataset.filter;
                    handleFilter(filterType);
                    
                    // Update active state
                    filterDropdown.querySelectorAll('.dropdown-item-custom').forEach(item => {
                        item.classList.remove('active');
                    });
                    e.target.classList.add('active');
                    
                    closeAllDropdowns();
                }
            });
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function() {
            closeAllDropdowns();
        });
    }
    
    function closeAllDropdowns() {
        const dropdowns = document.querySelectorAll('.dropdown-menu-custom');
        const buttons = document.querySelectorAll('.sort-btn, .filter-btn');
        
        dropdowns.forEach(dropdown => dropdown.classList.remove('show'));
        buttons.forEach(button => button.classList.remove('active'));
    }
    
    function handleSort(sortType) {
        const rows = Array.from(document.querySelectorAll('.asset-row'));
        
        rows.sort((a, b) => {
            const assetA = JSON.parse(a.dataset.json);
            const assetB = JSON.parse(b.dataset.json);
            
            switch (sortType) {
                case 'description-asc':
                    return (assetA.asset_description || '').localeCompare(assetB.asset_description || '');
                case 'description-desc':
                    return (assetB.asset_description || '').localeCompare(assetA.asset_description || '');
                case 'date-newest':
                    return new Date(assetB.creation_date || 0) - new Date(assetA.creation_date || 0);
                case 'date-oldest':
                    return new Date(assetA.creation_date || 0) - new Date(assetB.creation_date || 0);
                case 'value-high':
                    return (parseFloat(assetB.asset_value) || 0) - (parseFloat(assetA.asset_value) || 0);
                case 'value-low':
                    return (parseFloat(assetA.asset_value) || 0) - (parseFloat(assetB.asset_value) || 0);
                default:
                    return 0;
            }
        });
        
        // Re-append sorted rows
        const tbody = document.querySelector('.asset-list-table tbody');
        if (tbody) {
            rows.forEach((row, index) => {
                // Update row numbers
                const rowNumber = row.querySelector('.row-number');
                if (rowNumber) {
                    rowNumber.textContent = index + 1;
                }
                tbody.appendChild(row);
            });
        }
        
        // Re-setup click handlers
        setupRowClickHandlers();
    }
    
    function handleFilter(filterType) {
        const rows = document.querySelectorAll('.asset-row');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const asset = JSON.parse(row.dataset.json);
            const isTracked = !!asset.record_id;
            let isVisible = true;
            
            switch (filterType) {
                case 'all':
                    isVisible = true;
                    break;
                case 'tracked':
                    isVisible = isTracked;
                    break;
                case 'untracked':
                    isVisible = !isTracked;
                    break;
                default:
                    isVisible = true;
            }
            
            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });
        
        updateAssetCount(visibleCount);
        showNoResultsMessage(visibleCount === 0);
    }
    
    function setupRowClickHandlers() {
        document.querySelectorAll('.asset-row').forEach(row => {
            row.addEventListener('click', function(e) {
                if (selectMode) {
                    if (e.target.type === 'checkbox') return;
                    
                    const checkbox = this.querySelector('.row-checkbox');
                    if (checkbox) {
                        checkbox.checked = !checkbox.checked;
                        updateDeleteButtonVisibility();
                    }
                } else {
                    if (e.target.type === 'checkbox') return;
                    
                    // Remove previous selection
                    document.querySelectorAll('.asset-row').forEach(r => r.classList.remove('selected'));
                    
                    // Add selection to current row
                    this.classList.add('selected');
                    
                    // Show asset details
                    const assetData = JSON.parse(this.dataset.json);
                    showAssetDetails(assetData);
                }
            });
        });
    }
    
    function showAssetDetails(asset) {
        currentAsset = asset;
        
        // Hide placeholder, show details
        const placeholder = document.getElementById('assetPlaceholder');
        const info = document.getElementById('assetInfo');
        
        if (placeholder) placeholder.style.display = 'none';
        if (info) info.style.display = 'block';
        
        // Update asset details
        const titleEl = document.getElementById('assetTitle');
        if (titleEl) titleEl.textContent = asset.asset_description || 'Untitled Asset';
        
        const serialEl = document.getElementById('detailSerial');
        if (serialEl) serialEl.textContent = asset.serial_number || 'N/A';
        
        const locationEl = document.getElementById('detailLocation');
        if (locationEl) locationEl.textContent = asset.location || 'N/A';
        
        const assignedEl = document.getElementById('detailAssigned');
        if (assignedEl) assignedEl.textContent = asset.assigned_to || 'N/A';
        
        const projectEl = document.getElementById('detailProject');
        if (projectEl) projectEl.textContent = asset.project_name || 'N/A';
        
        // Update value
        const value = asset.asset_value ? `₱${parseFloat(asset.asset_value).toLocaleString('en-PH', {minimumFractionDigits: 2})}` : 'N/A';
        const valueEl = document.getElementById('detailValue');
        if (valueEl) valueEl.textContent = value;
        
        // Update warranty
        const warranty = asset.warranty_expiry ? new Date(asset.warranty_expiry).toLocaleDateString() : 'N/A';
        const warrantyEl = document.getElementById('detailWarranty');
        if (warrantyEl) warrantyEl.textContent = warranty;
        
        // Handle image
        const imageContainer = document.getElementById('assetImageContainer');
        if (imageContainer) {
            if (asset.asset_img && asset.asset_img.trim() !== '') {
                imageContainer.innerHTML = `<img src="${asset.asset_img}" alt="Asset Image" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="no-image" style="display: none;">
                    <img src="icons/image.svg" alt="No Image" width="48">
                    <p>Image not found</p>
                </div>`;
            } else {
                imageContainer.innerHTML = `<div class="no-image">
                    <img src="icons/image.svg" alt="No Image" width="48">
                    <p>No Image Available</p>
                </div>`;
            }
        }
        
        // Show/hide untracked notice
        const isUntracked = !asset.record_id;
        const untrackedNotice = document.getElementById('untrackedNotice');
        if (untrackedNotice) {
            untrackedNotice.style.display = isUntracked ? 'block' : 'none';
        }
    }
    
    function openAddModal() {
        if (!assetModal) {
            console.error('Asset modal not initialized');
            return;
        }
        
        const modalTitleElement = document.getElementById('modalTitle');
        const submitBtnElement = document.getElementById('submitBtn');
        
        if (modalTitleElement) modalTitleElement.textContent = 'Add Asset';
        if (submitBtnElement) submitBtnElement.textContent = 'Add Asset';
        
        if (assetForm) assetForm.reset();
        
        const assetIdInput = document.getElementById('asset_id');
        if (assetIdInput) assetIdInput.value = '';
        
        assetModal.show();
    }
    
    function openEditModal() {
        if (!currentAsset || !assetModal) {
            console.error('Cannot open edit modal - missing asset or modal');
            return;
        }
        
        const modalTitleElement = document.getElementById('modalTitle');
        const submitBtnElement = document.getElementById('submitBtn');
        
        if (modalTitleElement) modalTitleElement.textContent = 'Edit Asset';
        if (submitBtnElement) submitBtnElement.textContent = 'Update Asset';
        
        // Populate form
        const fields = [
            'asset_id', 'asset_description', 'serial_number', 
            'location', 'assigned_to', 'warranty_expiry'
        ];
        
        fields.forEach(field => {
            const element = document.getElementById(field);
            if (element) {
                element.value = currentAsset[field] || '';
            }
        });
        
        assetModal.show();
    }
    
    function toggleSelectMode() {
        selectMode = !selectMode;
        
        if (selectBtn) {
            selectBtn.textContent = selectMode ? 'CANCEL' : 'SELECT';
            selectBtn.classList.toggle('active', selectMode);
        }
        
        const rows = document.querySelectorAll('.asset-row');
        
        rows.forEach(row => {
            const checkbox = row.querySelector('.row-checkbox');
            const number = row.querySelector('.row-number');
            
            if (selectMode) {
                if (checkbox) {
                    checkbox.style.display = 'inline-block';
                    checkbox.checked = false;
                    checkbox.addEventListener('change', updateDeleteButtonVisibility);
                }
                if (number) number.style.display = 'none';
            } else {
                if (checkbox) {
                    checkbox.style.display = 'none';
                    checkbox.checked = false;
                    checkbox.removeEventListener('change', updateDeleteButtonVisibility);
                }
                if (number) number.style.display = 'inline-block';
            }
        });
        
        updateDeleteButtonVisibility();
        
        if (!selectMode) {
            // Re-setup click handlers for normal mode
            setupRowClickHandlers();
        }
    }
    
    function updateDeleteButtonVisibility() {
        const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
        if (deleteBtn) {
            deleteBtn.style.display = checkedBoxes.length > 0 ? 'block' : 'none';
        }
    }
    
    function showDeleteConfirmation() {
        const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
        if (checkedBoxes.length === 0) return;
        
        if (!deleteModal) {
            console.error('Delete modal not initialized');
            return;
        }
        
        const count = checkedBoxes.length;
        const message = count === 1 
            ? 'Are you sure you want to delete this asset?' 
            : `Are you sure you want to delete these ${count} assets?`;
        
        const deleteMessageElement = document.getElementById('deleteMessage');
        if (deleteMessageElement) {
            deleteMessageElement.textContent = message;
        }
        
        deleteModal.show();
    }
    
    function confirmDelete() {
        const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
        const assetIds = Array.from(checkedBoxes).map(cb => 
            cb.closest('.asset-row').dataset.id
        );
        
        if (assetIds.length === 0) return;
        
        const deleteAssetIdsInput = document.getElementById('deleteAssetIds');
        const deleteForm = document.getElementById('deleteForm');
        
        if (deleteAssetIdsInput && deleteForm) {
            deleteAssetIdsInput.value = assetIds.join(',');
            deleteForm.submit();
        }
        
        if (deleteModal) {
            deleteModal.hide();
        }
    }
    
    function handleSearch() {
        if (!searchInput) return;
        
        const query = searchInput.value.toLowerCase().trim();
        const rows = document.querySelectorAll('.asset-row');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const asset = JSON.parse(row.dataset.json);
            const searchText = [
                asset.asset_description,
                asset.serial_number,
                asset.location,
                asset.assigned_to,
                asset.project_name
            ].join(' ').toLowerCase();
            
            const isVisible = query === '' || searchText.includes(query);
            row.style.display = isVisible ? '' : 'none';
            
            if (isVisible) visibleCount++;
        });
        
        updateAssetCount(visibleCount);
        
        // Show no results message if needed
        showNoResultsMessage(visibleCount === 0 && query !== '');
    }
    
    function updateAssetCount(count) {
        const assetCount = document.getElementById('assetCount');
        if (assetCount) {
            assetCount.textContent = `${count} item${count !== 1 ? 's' : ''}`;
        }
    }
    
    function showNoResultsMessage(show) {
        const rows = document.querySelectorAll('.asset-row');
        const tableWrapper = document.querySelector('.asset-list-table-wrapper');
        
        if (show) {
            // Hide all rows but keep table structure
            rows.forEach(row => row.style.visibility = 'hidden');
            
            // Show message in an existing empty row or create one
            let noResultsRow = document.getElementById('noResultsRow');
            if (!noResultsRow) {
                const tbody = document.querySelector('.asset-list-table tbody');
                noResultsRow = document.createElement('tr');
                noResultsRow.id = 'noResultsRow';
                noResultsRow.innerHTML = `
                    <td colspan="6" class="text-center py-5">
                        <div class="no-assets">
                            <img src="icons/search.svg" alt="No Results" width="48">
                            <p>No assets found matching your search</p>
                            <button class="add-first-asset-btn" onclick="document.getElementById('searchInput').value=''; document.getElementById('searchInput').dispatchEvent(new Event('input'));">
                                Clear Search
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(noResultsRow);
            }
            noResultsRow.style.display = 'table-row';
        } else {
            // Show all rows and hide no results
            rows.forEach(row => row.style.visibility = 'visible');
            const noResultsRow = document.getElementById('noResultsRow');
            if (noResultsRow) {
                noResultsRow.style.display = 'none';
            }
        }
    }
    
    function handleFormSubmit(e) {
        e.preventDefault();
        
        const submitBtnElement = document.getElementById('submitBtn');
        
        // Add loading state
        if (submitBtnElement) {
            submitBtnElement.disabled = true;
            submitBtnElement.textContent = 'Saving...';
        }
        
        // Submit form after a brief delay to show loading state
        setTimeout(() => {
            if (assetForm) {
                assetForm.submit();
            }
        }, 500);
    }
    
    // Utility functions
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP'
        }).format(amount);
    }
    
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
    
    // Export functions for global access
    window.assetFunctions = {
        showAssetDetails,
        openAddModal,
        openEditModal,
        toggleSelectMode,
        handleSearch
    };
    
    // Auto-save form data to localStorage
    function setupAutoSave() {
        if (!assetForm) return;
        
        const formElements = assetForm.querySelectorAll('input, select, textarea');
        
        formElements.forEach(element => {
            element.addEventListener('input', () => {
                const formData = new FormData(assetForm);
                const data = Object.fromEntries(formData.entries());
                localStorage.setItem('assetFormData', JSON.stringify(data));
            });
        });
        
        // Restore form data when modal opens
        const assetModalElement = document.getElementById('assetModal');
        if (assetModalElement) {
            assetModalElement.addEventListener('shown.bs.modal', () => {
                const assetIdInput = document.getElementById('asset_id');
                if (!assetIdInput || !assetIdInput.value) { // Only for new assets
                    const savedData = localStorage.getItem('assetFormData');
                    if (savedData) {
                        try {
                            const data = JSON.parse(savedData);
                            Object.keys(data).forEach(key => {
                                const element = document.getElementById(key);
                                if (element && key !== 'asset_id') {
                                    element.value = data[key];
                                }
                            });
                        } catch (error) {
                            console.log('Error parsing saved form data:', error);
                        }
                    }
                }
            });
        }
        
        // Clear saved data on successful submission
        if (assetForm) {
            assetForm.addEventListener('submit', () => {
                localStorage.removeItem('assetFormData');
            });
        }
    }
    
    setupAutoSave();
    
    // Image preview functionality
    const assetImgInput = document.getElementById('asset_img');
    if (assetImgInput) {
        assetImgInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    let preview = document.getElementById('imagePreview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.id = 'imagePreview';
                        preview.className = 'mt-2';
                        e.target.parentNode.appendChild(preview);
                    }
                    preview.innerHTML = `
                        <img src="${event.target.result}" alt="Preview" style="max-width: 200px; max-height: 150px; border-radius: 6px; border: 1px solid #ddd;">
                        <div class="mt-1 text-muted small">Preview</div>
                    `;
                };
                reader.readAsDataURL(file);
            } else {
                // Remove preview if no file selected
                const preview = document.getElementById('imagePreview');
                if (preview) {
                    preview.remove();
                }
            }
        });
    }
    
    // Performance optimization: Virtual scrolling for large lists (if needed)
    function setupVirtualScrolling() {
        const container = document.querySelector('.asset-list-table-body-container');
        const rows = document.querySelectorAll('.asset-row');
        
        if (rows.length > 100) { // Only enable for large lists
            console.log('Virtual scrolling enabled for', rows.length, 'items');
            // Implementation would go here for virtual scrolling
        }
    }
    
    setupVirtualScrolling();
    
    // Global pagination function
    window.changePage = function(page) {
        window.location.href = 'ms_assets.php?page=' + page;
    };
    
    // Initialize everything
    console.log('Assets page initialized with', allAssets.length, 'assets');
    
});