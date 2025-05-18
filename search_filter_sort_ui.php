<!-- search_filter_sort_ui.php -->
<!-- Include this file where you want to display the UI components -->
<style>
    /* Dropdown styling */
    .sort-dropdown, .filter-dropdown {
        display: inline-block;
        position: relative;
        margin-right: 10px;
    }

    .sort-btn, .filter-btn, .reset-btn {
        padding: 6px 12px;
        border: 1px solid #ddd;
        background-color: #fff;
        border-radius: 14px;
        font-size: 14px;
        font-family: 'Atkinson Hyperlegible', sans-serif;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .sort-btn:hover, .filter-btn:hover, .reset-btn:hover {
        background-color: #f8f9fa;
    }

    .reset-btn {
        border: none;
    }

    .dropdown-menu {
        min-width: 200px;
        font-size: 12px;
        border-radius: 4px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border: 1px solid #ddd;
        position: absolute;
        z-index: 1000;
        display: none;
        background-color: #fff;
    }

    .sort-menu .dropdown-item {
        padding: 8px 12px;
        cursor: pointer;
        font-size: 12px;
        display: block;
        text-decoration: none;
        color: #212529;
    }

    .sort-menu .dropdown-item:hover {
        background-color: #f8f9fa;
    }

    .filter-menu {
        padding: 15px;
        width: 250px;
    }

    .filter-menu .form-label {
        font-size: 12px;
        font-weight: 500;
    }

    .filter-menu .form-select {
        font-size: 12px;
    }

    /* Show class for dropdowns */
    .dropdown-menu.show {
        display: block;
    }

    /* Style for active dropdowns */
    .sort-dropdown.active .sort-btn,
    .filter-dropdown.active .filter-btn {
        background-color: #e9ecef;
        border-color: #ced4da;
    }
    
    /* Search input styling */
    .search-container {
        position: relative;
        margin-right: 15px;
    }
    
    .search-input {
        padding: 6px 12px;
        padding-left: 20px;
        border: 1px solid #ddd;
        border-radius: 24px;
        font-size: 14px;
        width: 200px;
        font-family: 'Atkinson Hyperlegible', sans-serif;
    }
    
    .search-icon {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
    }
</style>
<div class="search-filter-bar">
    <!-- Left group: Add, Search, Filter -->
    <div class="left-controls">
        <button onclick="openExpenseModal('add')" class="add-record-btn">ADD RECORD</button>

        <div class="search-container">
            <input type="text" id="search-input" class="search-input" placeholder="SEARCH">
        </div>

        <div class="filter-options">
            <!-- Sort By Dropdown -->
            <div class="dropdown sort-dropdown">
                <button class="sort-btn" type="button" id="sortDropdown">
                    <img src="icons/arrow-down-up.svg" alt="SortIcon" width="16"> Sort By
                </button>
                <ul class="dropdown-menu sort-menu" aria-labelledby="sortDropdown">
                    <li><a class="dropdown-item sort-option" data-sort="a-z" href="#">A to Z (Description)</a></li>
                    <li><a class="dropdown-item sort-option" data-sort="z-a" href="#">Z to A (Description)</a></li>
                    <li><a class="dropdown-item sort-option" data-sort="oldest-newest" href="#">Oldest to Newest</a></li>
                    <li><a class="dropdown-item sort-option" data-sort="newest-oldest" href="#">Newest to Oldest</a></li>
                    <li><a class="dropdown-item sort-option" data-sort="highest-lowest" href="#">Highest to Lowest Cost</a></li>
                    <li><a class="dropdown-item sort-option" data-sort="lowest-highest" href="#">Lowest to Highest Cost</a></li>
                </ul>
            </div>
            
            <!-- Filter Dropdown -->
            <div class="dropdown filter-dropdown">
                <button class="filter-btn" type="button" id="filterDropdown">
                    <img src="icons/filter.svg" alt="FilterIcon" width="16"> Filter
                </button>
                <div class="dropdown-menu filter-menu" aria-labelledby="filterDropdown">
                    <form id="filter-form" action="" method="get">
                        <input type="hidden" name="projectId" value="<?= $project_id ?>">
                        <?php if (isset($_GET['sort_by'])): ?>
                        <input type="hidden" name="sort_by" value="<?= htmlspecialchars($_GET['sort_by']) ?>">
                        <?php endif; ?>
                        <?php if (isset($_GET['view'])): ?>
                        <input type="hidden" name="view" value="<?= htmlspecialchars($_GET['view']) ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="filter-category" class="form-label">Category</label>
                            <select id="filter-category" name="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['category_name']) ?>" <?= isset($_GET['category']) && $_GET['category'] === $cat['category_name'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="filter-subcategory" class="form-label">Subcategory</label>
                            <select id="filter-subcategory" name="subcategory" class="form-select" <?= empty($_GET['category']) ? 'disabled' : '' ?>>
                                <option value="">All Subcategories</option>
                                <?php 
                                if (!empty($_GET['category'])) {
                                    foreach ($subcategories as $subcat) {
                                        if ($subcat['category_name'] === $_GET['category']) {
                                            echo '<option value="' . htmlspecialchars($subcat['subcategory_name']) . '"';
                                            if (isset($_GET['subcategory']) && $_GET['subcategory'] === $subcat['subcategory_name']) echo ' selected';
                                            echo '>' . htmlspecialchars($subcat['subcategory_name']) . '</option>';
                                        }
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Reset Button -->
            <a href="?projectId=<?= $project_id ?><?= isset($_GET['view']) ? '&view=' . htmlspecialchars($_GET['view']) : '' ?>" class="btn reset-btn">
                <img src="icons/refresh.svg" alt="Reset" width="16"> Reset
            </a>
        </div>
    </div>

    <!-- Right group: View toggle -->
    <div class="view-toggle">
        <button class="toggle-btn <?= !isset($_GET['view']) || $_GET['view'] === 'records' ? 'active' : '' ?>" id="view-records-btn">RECORD</button>
        <button class="toggle-btn <?= isset($_GET['view']) && $_GET['view'] === 'analytics' ? 'active' : '' ?>" id="view-analytics-btn">ANALYTICS</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- JavaScript for filter and sort interactions -->
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Real-time search
    const searchInput = document.getElementById('search-input');
    const expenseTable = document.getElementById('expense-table');

    if (searchInput && expenseTable) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const tableRows = expenseTable.querySelectorAll('tbody tr');
            
            tableRows.forEach(row => {
                // Search in description (column index 2)
                const description = row.cells[2].textContent.toLowerCase();
                
                // Search in payee (column index 3)
                const payee = row.cells[3].textContent.toLowerCase();
                
                // Search in remarks (column index 8)
                const remarks = row.cells[8].textContent.toLowerCase();
                
                // Show row if any field matches
                if (searchTerm === '' || 
                    description.includes(searchTerm) || 
                    payee.includes(searchTerm) || 
                    remarks.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Dropdown toggle functionality
    const filterBtn = document.querySelector('.filter-btn');
    const sortBtn = document.querySelector('.sort-btn');
    const filterMenu = document.querySelector('.filter-menu');
    const sortMenu = document.querySelector('.sort-menu');
    
    // Function to close all dropdowns
    function closeAllDropdowns() {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    }
    
    // Filter button click handler
    if (filterBtn && filterMenu) {
        filterBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle this dropdown
            filterMenu.classList.toggle('show');
            
            // Close other dropdowns
            sortMenu?.classList.remove('show');
        });
    }
    
    // Sort button click handler
    if (sortBtn && sortMenu) {
        sortBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle this dropdown
            sortMenu.classList.toggle('show');
            
            // Close other dropdowns
            filterMenu?.classList.remove('show');
        });
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        // Don't close if clicking inside a dropdown
        if (e.target.closest('.dropdown-menu') || e.target.closest('.filter-btn') || e.target.closest('.sort-btn')) {
            return;
        }
        
        closeAllDropdowns();
    });
    
    // Keep filter dropdown open when interacting with form elements
    document.querySelectorAll('.filter-menu select, .filter-menu button').forEach(element => {
        element.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent click from bubbling up and closing dropdown
        });
    });
    
    // Sort options click handlers
    document.querySelectorAll('.sort-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            
            const sortValue = this.getAttribute('data-sort');
            
            // Create the URL with the sort parameter
            let url = new URL(window.location.href);
            let params = new URLSearchParams(url.search);
            
            // Update the sort_by parameter
            params.set('sort_by', sortValue);
            
            // Redirect to the new URL with the sort parameter
            window.location.href = `?${params.toString()}`;
        });
    });
    
    // Category change handler for filter
    const categorySelect = document.getElementById('filter-category');
    const subcategorySelect = document.getElementById('filter-subcategory');
    
    if (categorySelect && subcategorySelect) {
        categorySelect.addEventListener('change', function() {
            // Clear subcategory options
            subcategorySelect.innerHTML = '<option value="">All Subcategories</option>';
            
            if (this.value) {
                // Enable subcategory dropdown
                subcategorySelect.disabled = false;
                
                // Filter subcategories based on selected category
                const subcategories = <?= json_encode($subcategories ?? []); ?>;
                if (subcategories) {
                    const filteredSubcategories = subcategories.filter(subcat => 
                        subcat.category_name === this.value
                    );
                    
                    // Add options
                    filteredSubcategories.forEach(subcat => {
                        const option = document.createElement('option');
                        option.value = subcat.subcategory_name;
                        option.textContent = subcat.subcategory_name;
                        subcategorySelect.appendChild(option);
                    });
                }
            } else {
                // Disable subcategory dropdown if no category selected
                subcategorySelect.disabled = true;
            }
        });
    }
    
    // Highlight active filter and sort buttons if they are in use
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.has('category') || urlParams.has('subcategory')) {
        document.querySelector('.filter-dropdown')?.classList.add('active');
    }
    
    if (urlParams.has('sort_by')) {
        document.querySelector('.sort-dropdown')?.classList.add('active');
    }
});
</script>

<script>
    // View toggle
    document.addEventListener('DOMContentLoaded', function() {
        // Get references to UI elements
        const recordsBtn = document.getElementById('view-records-btn');
        const analyticsBtn = document.getElementById('view-analytics-btn');
        const recordsView = document.getElementById('records-view');
        const analyticsView = document.getElementById('analytics-view');
        
        // Toggle between views
        if (recordsBtn && analyticsBtn && recordsView && analyticsView) {
            recordsBtn.addEventListener('click', function() {
                recordsView.style.display = 'block';
                analyticsView.style.display = 'none';
                recordsBtn.classList.add('active');
                analyticsBtn.classList.remove('active');
                
                // Update URL without reloading page
                const url = new URL(window.location.href);
                url.searchParams.delete('view');
                window.history.pushState({}, '', url);
            });
            
            analyticsBtn.addEventListener('click', function() {
                recordsView.style.display = 'none';
                analyticsView.style.display = 'block';
                analyticsBtn.classList.add('active');
                recordsBtn.classList.remove('active');
                
                // Update URL without reloading page
                const url = new URL(window.location.href);
                url.searchParams.set('view', 'analytics');
                window.history.pushState({}, '', url);
                
                // Initialize charts when analytics view becomes visible
                if (typeof initializeCharts === 'function') {
                    setTimeout(initializeCharts, 100);
                }
            });
            
            // Set initial view based on URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('view') === 'analytics') {
                analyticsBtn.click(); // This triggers the click event handler above
            }
        }
    });
</script>