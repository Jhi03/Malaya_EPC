<?php
// Analytics view data preparation
// Calculate chart data from expense records

// Initialize arrays for time-based data
$days = [];
$daily_budget = [];
$daily_expense = [];

$weeks = [];
$weekly_budget = [];
$weekly_expense = [];

$months = [];
$monthly_budget = [];
$monthly_expense = [];

$quarters = [];
$quarterly_budget = [];
$quarterly_expense = [];

// Initialize arrays for category data
$categories = [];
$category_expenses = [];

// Initialize arrays for subcategory data
$subcategories = [];
$subcategory_names = [];
$subcategory_expenses = [];

// Calculate impact data
$total_over_budget = 0;
$total_company_loss = 0;
$total_billed_to_client = 0;
$total_rental = 0;
$count_over_budget = 0;
$count_company_loss = 0;
$count_billed_to_client = 0;
$count_rental = 0;

// Process records if available
if (!empty($expense_records)) {
    // Process each expense record
    foreach ($expense_records as $record) {
        // Get expense amount
        $expense_amount = isset($record['is_rental']) && $record['is_rental'] === 'Yes' 
            ? floatval($record['rental_rate'] ?? 0) 
            : floatval($record['expense'] ?? 0);
        $budget_amount = floatval($record['budget'] ?? 0);
        
        // Calculate rental total
        if (isset($record['is_rental']) && $record['is_rental'] === 'Yes') {
            $total_rental += floatval($record['rental_rate'] ?? 0);
            $count_rental++;
        }
        
        // Extract date data for different time periods
        if (isset($record['purchase_date'])) {
            $date = new DateTime($record['purchase_date']);
            
            // Daily data (format: "Jan 01")
            $day_key = $date->format('M d');
            if (!isset($daily_budget[$day_key])) {
                $days[$day_key] = $day_key;
                $daily_budget[$day_key] = 0;
                $daily_expense[$day_key] = 0;
            }
            $daily_budget[$day_key] += $budget_amount;
            $daily_expense[$day_key] += $expense_amount;
            
            // Weekly data (format: "Week 01, 2025")
            $week_number = $date->format('W');
            $year = $date->format('Y');
            $week_key = "Week {$week_number}, {$year}";
            if (!isset($weekly_budget[$week_key])) {
                $weeks[$week_key] = $week_key;
                $weekly_budget[$week_key] = 0;
                $weekly_expense[$week_key] = 0;
            }
            $weekly_budget[$week_key] += $budget_amount;
            $weekly_expense[$week_key] += $expense_amount;
            
            // Monthly data (format: "Jan 2025")
            $month_key = $date->format('M Y');
            if (!isset($monthly_budget[$month_key])) {
                $months[$month_key] = $month_key;
                $monthly_budget[$month_key] = 0;
                $monthly_expense[$month_key] = 0;
            }
            $monthly_budget[$month_key] += $budget_amount;
            $monthly_expense[$month_key] += $expense_amount;
            
            // Quarterly data (format: "Q1 2025")
            $quarter = ceil($date->format('n') / 3);
            $year = $date->format('Y');
            $quarter_key = "Q{$quarter} {$year}";
            if (!isset($quarterly_budget[$quarter_key])) {
                $quarters[$quarter_key] = $quarter_key;
                $quarterly_budget[$quarter_key] = 0;
                $quarterly_expense[$quarter_key] = 0;
            }
            $quarterly_budget[$quarter_key] += $budget_amount;
            $quarterly_expense[$quarter_key] += $expense_amount;
        }
        
        // Process category data
        $category = $record['category'] ?? 'Unknown';
        if (!isset($category_expenses[$category])) {
            $categories[] = $category;
            $category_expenses[$category] = 0;
        }
        $category_expenses[$category] += $expense_amount;
        
        // Process subcategory data
        $subcategory = $record['subcategory'] ?? 'None';
        $subcategory_key = (!empty($subcategory) && $subcategory !== 'None') 
            ? "{$category}: {$subcategory}" 
            : $category;
        
        if (!isset($subcategory_expenses[$subcategory_key])) {
            $subcategory_names[] = $subcategory_key;
            $subcategory_expenses[$subcategory_key] = 0;
        }
        $subcategory_expenses[$subcategory_key] += $expense_amount;
        
        // Process impact data
        if (isset($record['variance']) && $record['variance'] < 0) {
            $total_over_budget += abs($record['variance']);
            $count_over_budget++;
        }
        
        if (isset($record['is_company_loss']) && $record['is_company_loss'] === 'Yes') {
            $total_company_loss += $expense_amount;
            $count_company_loss++;
        }
        
        if (isset($record['bill_to_client']) && $record['bill_to_client'] === 'Yes') {
            $total_billed_to_client += $expense_amount;
            $count_billed_to_client++;
        }
    }
}

// Sort time-based data chronologically
ksort($days);
ksort($weeks);
ksort($months);
ksort($quarters);

// Convert associative arrays to indexed arrays for Chart.js
$chart_days = array_values($days);
$chart_daily_budget = array_values($daily_budget);
$chart_daily_expense = array_values($daily_expense);

$chart_weeks = array_values($weeks);
$chart_weekly_budget = array_values($weekly_budget);
$chart_weekly_expense = array_values($weekly_expense);

$chart_months = array_values($months);
$chart_monthly_budget = array_values($monthly_budget);
$chart_monthly_expense = array_values($monthly_expense);

$chart_quarters = array_values($quarters);
$chart_quarterly_budget = array_values($quarterly_budget);
$chart_quarterly_expense = array_values($quarterly_expense);

// Create the chart data array
$chart_data = [
    // Time data - daily
    'days' => $chart_days,
    'daily_budget' => $chart_daily_budget,
    'daily_expense' => $chart_daily_expense,
    
    // Time data - weekly
    'weeks' => $chart_weeks,
    'weekly_budget' => $chart_weekly_budget,
    'weekly_expense' => $chart_weekly_expense,
    
    // Time data - monthly
    'months' => $chart_months,
    'monthly_budget' => $chart_monthly_budget,
    'monthly_expense' => $chart_monthly_expense,
    
    // Time data - quarterly
    'quarters' => $chart_quarters,
    'quarterly_budget' => $chart_quarterly_budget,
    'quarterly_expense' => $chart_quarterly_expense,
    
    // Category data
    'categories' => array_keys($category_expenses),
    'category_expenses' => array_values($category_expenses),
    
    // Subcategory data
    'subcategories' => $subcategory_names,
    'subcategory_expenses' => array_values($subcategory_expenses),
    
    // Impact data
    'impact_labels' => ['Over Budget', 'Company Loss', 'Billed to Client'],
    'impact_data' => [$total_over_budget, $total_company_loss, $total_billed_to_client],
    'impact_counts' => [$count_over_budget, $count_company_loss, $count_billed_to_client],
    
    // Totals data
    'total_labels' => ['Budget', 'Expense', 'Variance', 'Tax', 'Rentals'],
    'total_values' => [
        $total_budget, 
        $total_expense, 
        $total_variance, 
        $total_tax, 
        $total_rental
    ]
];

// Convert to JSON for JavaScript
$chart_data_json = json_encode($chart_data);
?>
<!-- Add this before the script tag -->
<script>
// Pass PHP data to JavaScript
window.analyticsData = <?= $chart_data_json ?>;
console.log("Analytics data loaded:", window.analyticsData);
</script>
<style>
    /* Enhanced Analytics Dashboard Styling */
    .analytics-dashboard {
        font-family: 'Atkinson Hyperlegible', sans-serif;
    }

    /* Card styles */
    .card {
        border: none;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
    }

    /* Progress bar styling */
    .progress {
        background-color: rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    /* Button styling */
    .btn-outline-primary, .btn-outline-secondary {
        border-radius: 50px;
        font-size: 0.85rem;
        padding: 0.375rem 0.85rem;
    }

    .btn-group .btn {
        border-radius: 0;
    }

    .btn-group .btn:first-child {
        border-top-left-radius: 4px;
        border-bottom-left-radius: 4px;
    }

    .btn-group .btn:last-child {
        border-top-right-radius: 4px;
        border-bottom-right-radius: 4px;
    }

    /* Date range selector */
    #date-range-btn {
        border-radius: 50px;
        display: flex;
        align-items: center;
        padding: 0.375rem 1rem;
    }

    #date-range-display {
        padding: 0.375rem 0.75rem;
        background: #f8f9fa;
        border-radius: 4px;
        font-size: 0.85rem;
    }

    /* Chart containers */
    .chart-container {
        position: relative;
        height: 350px;
        width: 100%;
    }

    /* Modal styling */
    .modal-content {
        border: none;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .modal-header {
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .modal-footer {
        border-top: 1px solid rgba(0, 0, 0, 0.05);
    }

    /* Time period selector */
    .time-selector .btn-group {
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        border-radius: 4px;
        overflow: hidden;
    }

    /* Media queries for responsiveness */
    @media (max-width: 768px) {
        .card {
            margin-bottom: 1rem;
        }
        
        .date-range-selector {
            margin-bottom: 1rem;
        }
        
        .time-selector {
            justify-content: flex-start !important;
        }
        
        .breakdown-selector, .chart-type-toggle {
            margin-bottom: 0.5rem;
        }
        
        .chart-container {
            height: 300px;
        }
    }

    /* Accessibility enhancements */
    .progress {
        height: 8px;
    }

    .text-muted {
        color: #6c757d !important;
        font-weight: 400;
    }

    .small {
        font-size: 0.875rem;
    }

    /* Animation for better user experience */
    .card .progress-bar {
        transition: width 1s ease-in-out;
    }

    /* Font Awesome fallback for icons */
    .fa-calendar-alt::before {
        content: "📅";
    }
</style>
<!-- Enhanced Analytics Dashboard -->
<div class="analytics-view container-fluid py-3">
    
    <h3>Analytics Dashboard</h3>
    <!-- Add KPI Cards before charts -->
    <div class="row mb-4">
        <!-- Budget Card -->
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card shadow-sm rounded">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Budget</h6>
                    <p class="fs-4 fw-bold text-primary mb-1">₱<?= number_format($total_budget, 2) ?></p>
                    <div class="progress mt-2" style="height: 5px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <p class="text-muted small mt-1">100% of allocated funds</p>
                </div>
            </div>
        </div>
        
        <!-- Expense Card -->
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card shadow-sm rounded">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Expense</h6>
                    <p class="fs-4 fw-bold text-danger mb-1">₱<?= number_format($total_expense, 2) ?></p>
                    <?php $expense_percentage = $total_budget > 0 ? ($total_expense / $total_budget) * 100 : 0; ?>
                    <div class="progress mt-2" style="height: 5px;">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: <?= min(100, $expense_percentage) ?>%;" aria-valuenow="<?= $expense_percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <p class="text-muted small mt-1"><?= number_format($expense_percentage, 1) ?>% of budget</p>
                </div>
            </div>
        </div>
        
        <!-- Variance Card -->
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card shadow-sm rounded">
                <div class="card-body">
                    <h6 class="card-title text-muted">Variance</h6>
                    <p class="fs-4 fw-bold <?= $total_variance >= 0 ? 'text-success' : 'text-danger' ?> mb-1">
                        ₱<?= number_format($total_variance, 2) ?>
                    </p>
                    <?php $variance_percentage = $total_budget > 0 ? (abs($total_variance) / $total_budget) * 100 : 0; ?>
                    <div class="progress mt-2" style="height: 5px;">
                        <div class="progress-bar <?= $total_variance >= 0 ? 'bg-success' : 'bg-danger' ?>" role="progressbar" 
                            style="width: <?= min(100, $variance_percentage) ?>%;" 
                            aria-valuenow="<?= $variance_percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <p class="text-muted small mt-1"><?= number_format($variance_percentage, 1) ?>% <?= $total_variance >= 0 ? 'under' : 'over' ?> budget</p>
                </div>
            </div>
        </div>
        
        <!-- Rental Card -->
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card shadow-sm rounded">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Rentals</h6>
                    <?php 
                    $total_rental = 0;
                    $count_rental = 0;
                    foreach ($expense_records as $record) {
                        if (isset($record['is_rental']) && $record['is_rental'] === 'Yes') {
                            $total_rental += floatval($record['rental_rate'] ?? 0);
                            $count_rental++;
                        }
                    }
                    $rental_percentage = $total_expense > 0 ? ($total_rental / $total_expense) * 100 : 0;
                    ?>
                    <p class="fs-4 fw-bold text-warning mb-1">₱<?= number_format($total_rental, 2) ?></p>
                    <div class="progress mt-2" style="height: 5px;">
                        <div class="progress-bar bg-warning" role="progressbar" 
                            style="width: <?= min(100, $rental_percentage) ?>%;" 
                            aria-valuenow="<?= $rental_percentage ?>" 
                            aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <p class="text-muted small mt-1"><?= number_format($rental_percentage, 1) ?>% of expense (<?= $count_rental ?> items)</p>
                </div>
            </div>
        </div>
    </div>
    <!-- Add these controls before the chart rows -->
    <!-- Date Range Dropdown -->
    <div class="date-range-dropdown dropdown">
        <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" id="date-range-btn" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-calendar-alt me-1"></i> Date Range
        </button>
        <div class="dropdown-menu p-3" style="width: 300px;">
            <h6 class="dropdown-header">Select Date Range</h6>
            
            <div class="mb-3">
                <label for="dropdown-start-date" class="form-label small">Start Date</label>
                <input type="date" class="form-control form-control-sm" id="dropdown-start-date">
            </div>
            
            <div class="mb-3">
                <label for="dropdown-end-date" class="form-label small">End Date</label>
                <input type="date" class="form-control form-control-sm" id="dropdown-end-date">
            </div>
            
            <div class="mb-3">
                <label class="form-label small">Quick Selections</label>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm quick-range-btn" data-days="7">Last 7 Days</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm quick-range-btn" data-days="30">Last 30 Days</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm quick-range-btn" data-days="90">Last 3 Months</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm quick-range-btn" data-days="all">All Time</button>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <span id="date-range-display" class="small text-muted align-self-center">All Time</span>
                <button type="button" class="btn btn-primary btn-sm" id="apply-date-range">Apply</button>
            </div>
        </div>
    </div>

    <!-- Chart Rows -->
    <div class="row mb-4">
        <!-- Time Chart -->
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm rounded-4">
                <div class="card-body">
                    <!-- For Time Chart -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="card-title">Budget vs Expense Over Time</h5>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-secondary active" data-chart="line">Line</button>
                            <button type="button" class="btn btn-outline-secondary" data-chart="bar">Bar</button>
                        </div>
                    </div>
                    <div style="height: 300px; position: relative;">
                        <canvas id="timeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Category Chart -->
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm rounded-4">
                <div class="card-body">
                    <!-- For Category Chart -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="card-title">Expense by Category</h5>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-secondary active" data-breakdown="category">Category</button>
                            <button type="button" class="btn btn-outline-secondary" data-breakdown="subcategory">Subcategory</button>
                        </div>
                    </div>
                    <div style="height: 300px; position: relative;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <!-- Impact Chart -->
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm rounded-4">
                <div class="card-body">
                    <h5 class="card-title">Budget Impact Analysis</h5>
                    <div style="height: 300px; position: relative;">
                        <canvas id="impactChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Totals Chart -->
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm rounded-4">
                <div class="card-body">
                    <h5 class="card-title">Financial Metrics Comparison</h5>
                    <div style="height: 300px; position: relative;">
                        <canvas id="totalsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Add this where you want the export buttons to appear -->
    <div class="export-buttons d-flex justify-content-end mb-3">
        <button id="export-csv" class="btn btn-sm btn-outline-secondary me-2">
            <i class="fas fa-file-csv me-1"></i> Export CSV
        </button>
        <button id="export-png" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-file-image me-1"></i> Export Charts
        </button>
    </div>
</div>

<!-- Chart.js initialization script -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script>
// Chart.js initialization script
// Make it self-contained to avoid conflicts
(function() {
    console.log("Analytics module loaded");
    
    // Configuration
    const config = {
        chartInitialized: false,
        currentPeriod: 'weekly',
        currentChartType: 'line',
        currentBreakdown: 'category',
        dateRangeStart: null,
        dateRangeEnd: null
    };
    
    // Chart references
    let timeChart, categoryChart, impactChart, totalsChart;
    
    // Get data from PHP or use fallback
    const chartData = window.analyticsData || {
        // Fallback dummy data if PHP data is missing
        months: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
        monthly_budget: [1000, 1200, 900, 1500, 1100],
        monthly_expense: [800, 1300, 950, 1400, 1050],
        categories: ['OPEX', 'CAPEX', 'ASSET'],
        category_expenses: [5000, 3000, 2000],
        subcategories: ['OPEX: Food', 'OPEX: Gas', 'CAPEX: Materials', 'ASSET'],
        subcategory_expenses: [3000, 2000, 3000, 2000],
        impact_labels: ['Over Budget', 'Company Loss', 'Billed to Client'],
        impact_data: [1500, 2000, 3000],
        total_labels: ['Budget', 'Expense', 'Variance', 'Tax', 'Rentals'],
        total_values: [10000, 8500, 1500, 1020, 2000]
    };
    
    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log("DOM loaded for analytics");
        
        // Setup view toggle
        setupViewToggle();
        
        // Check if analytics view is visible on page load
        const analyticsView = document.getElementById('analytics-view');
        if (analyticsView && analyticsView.style.display !== 'none') {
            initializeAnalyticsDashboard();
        }
        
        // Setup analytics button click
        const analyticsBtn = document.getElementById('view-analytics-btn');
        if (analyticsBtn) {
            analyticsBtn.addEventListener('click', function() {
                initializeAnalyticsDashboard();
            });
        }
    });
    
    // View toggle functionality
    function setupViewToggle() {
        const recordsBtn = document.getElementById('view-records-btn');
        const analyticsBtn = document.getElementById('view-analytics-btn');
        const recordsView = document.getElementById('records-view');
        const analyticsView = document.getElementById('analytics-view');
        
        if (recordsBtn && analyticsBtn && recordsView && analyticsView) {
            recordsBtn.addEventListener('click', function() {
                recordsView.style.display = 'block';
                analyticsView.style.display = 'none';
                recordsBtn.classList.add('active');
                analyticsBtn.classList.remove('active');
                
                // Update URL
                const url = new URL(window.location.href);
                url.searchParams.delete('view');
                history.replaceState({}, '', url);
            });
            
            analyticsBtn.addEventListener('click', function() {
                recordsView.style.display = 'none';
                analyticsView.style.display = 'block';
                analyticsBtn.classList.add('active');
                recordsBtn.classList.remove('active');
                
                // Update URL
                const url = new URL(window.location.href);
                url.searchParams.set('view', 'analytics');
                history.replaceState({}, '', url);
            });
            
            // Set initial view based on URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('view') === 'analytics') {
                analyticsBtn.click();
            }
        }
    }
    
    // Initialize analytics dashboard
    function initializeAnalyticsDashboard() {
        console.log("Initializing analytics dashboard");
        
        if (!config.chartInitialized) {
            // Initialize charts
            initializeCharts();
            
            // Setup UI controls
            setupControls();
            
            // Mark as initialized
            config.chartInitialized = true;
        }
    }
    
    // Setup all controls
    function setupControls() {
        setupChartControls();
        setupDateRangePicker();
        setupExportButtons();
    }
    
    // Setup chart controls (period, type, breakdown)
    function setupChartControls() {
        // Time period buttons
        const periodButtons = document.querySelectorAll('[data-period]');
        periodButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all period buttons
                periodButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Update config
                config.currentPeriod = this.getAttribute('data-period');
                console.log("Period selected:", config.currentPeriod);
                
                // Update charts
                updateCharts();
            });
        });
        
        // Chart type buttons (line/bar)
        const chartTypeButtons = document.querySelectorAll('[data-chart]');
        chartTypeButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all chart type buttons
                chartTypeButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Update config
                config.currentChartType = this.getAttribute('data-chart');
                console.log("Chart type selected:", config.currentChartType);
                
                // Update charts
                updateCharts();
            });
        });
        
        // Breakdown buttons (category/subcategory)
        const breakdownButtons = document.querySelectorAll('[data-breakdown]');
        breakdownButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all breakdown buttons
                breakdownButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Update config
                config.currentBreakdown = this.getAttribute('data-breakdown');
                console.log("Breakdown selected:", config.currentBreakdown);
                
                // Update charts
                updateCategoryChart();
            });
        });
    }
    
    // Setup date range dropdown
    function setupDateRangePicker() {
        console.log("Setting up date range dropdown");
        
        // Get elements
        const dateRangeBtn = document.getElementById('date-range-btn');
        const startDateInput = document.getElementById('dropdown-start-date');
        const endDateInput = document.getElementById('dropdown-end-date');
        const applyBtn = document.getElementById('apply-date-range');
        const dateRangeDisplay = document.getElementById('date-range-display');
        const quickRangeButtons = document.querySelectorAll('.quick-range-btn');
        
        // Check if elements exist
        if (!dateRangeBtn || !startDateInput || !endDateInput || !applyBtn) {
            console.error("Date range dropdown elements not found");
            return;
        }
        
        // Initialize Bootstrap dropdown if possible
        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
            new bootstrap.Dropdown(dateRangeBtn);
        }
        
        // Setup quick selection buttons
        quickRangeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const days = this.getAttribute('data-days');
                console.log("Quick range selected:", days);
                
                const endDate = new Date();
                let startDate = new Date();
                
                if (days === 'all') {
                    startDateInput.value = '';
                    endDateInput.value = '';
                } else {
                    startDate.setDate(startDate.getDate() - parseInt(days));
                    
                    // Format dates for input fields (YYYY-MM-DD)
                    const formatDate = (date) => {
                        const year = date.getFullYear();
                        const month = String(date.getMonth() + 1).padStart(2, '0');
                        const day = String(date.getDate()).padStart(2, '0');
                        return `${year}-${month}-${day}`;
                    };
                    
                    startDateInput.value = formatDate(startDate);
                    endDateInput.value = formatDate(endDate);
                }
            });
        });
        
        // Setup apply button
        applyBtn.addEventListener('click', function() {
            console.log("Applying date range");
            
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            
            // Update config
            config.dateRangeStart = startDate ? new Date(startDate) : null;
            config.dateRangeEnd = endDate ? new Date(endDate) : null;
            
            // Update dropdown button text
            if (startDate && endDate) {
                const formatDisplayDate = (dateStr) => {
                    const date = new Date(dateStr);
                    return date.toLocaleDateString();
                };
                
                dateRangeDisplay.textContent = `${formatDisplayDate(startDate)} - ${formatDisplayDate(endDate)}`;
                dateRangeBtn.innerHTML = `<i class="fas fa-calendar-alt me-1"></i> ${formatDisplayDate(startDate)} - ${formatDisplayDate(endDate)}`;
            } else {
                dateRangeDisplay.textContent = 'All Time';
                dateRangeBtn.innerHTML = `<i class="fas fa-calendar-alt me-1"></i> All Time`;
            }
            
            // Close dropdown if using Bootstrap
            if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
                const dropdownEl = document.querySelector('.date-range-dropdown .dropdown-menu');
                const dropdown = bootstrap.Dropdown.getInstance(dateRangeBtn);
                if (dropdown) dropdown.hide();
            }
            
            // Update charts
            updateCharts();
        });
        
        console.log("Date range dropdown setup complete");
    }
    
    // Fix the export buttons setup function
    function setupExportButtons() {
        // Export to CSV
        const exportCsvBtn = document.getElementById('export-csv');
        if (exportCsvBtn) {
            console.log("Export CSV button found");
            exportCsvBtn.addEventListener('click', function() {
                console.log("Export CSV button clicked");
                exportToCSV();
            });
        } else {
            console.error("Export CSV button not found");
        }
        
        // Export charts as images
        // Check for both possible IDs
        const exportPngBtn = document.getElementById('export-png') || document.getElementById('export-image');
        if (exportPngBtn) {
            console.log("Export PNG button found");
            exportPngBtn.addEventListener('click', function() {
                console.log("Export PNG button clicked");
                exportChartsAsImages();
            });
        } else {
            console.error("Export PNG button not found");
        }
    }
    
    // Initialize charts
    function initializeCharts() {
        console.log("Initializing charts");
        
        try {
            // 1. Time Chart
            const timeCanvas = document.getElementById('timeChart');
            if (timeCanvas) {
                timeChart = new Chart(timeCanvas, {
                    type: config.currentChartType,
                    data: {
                        labels: getTimeLabels(),
                        datasets: [
                            {
                                label: 'Budget',
                                data: getBudgetData(),
                                borderColor: 'rgba(54, 162, 235, 1)',
                                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                borderWidth: 2,
                                fill: true
                            },
                            {
                                label: 'Expense',
                                data: getExpenseData(),
                                borderColor: 'rgba(255, 99, 132, 1)',
                                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                                borderWidth: 2,
                                fill: true
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const value = context.raw;
                                        return context.dataset.label + ': ₱' + value.toLocaleString();
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '₱' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
                console.log("Time chart initialized");
            }
            
            // 2. Category Chart (Donut)
            const categoryCanvas = document.getElementById('categoryChart');
            if (categoryCanvas) {
                categoryChart = new Chart(categoryCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: getCategoryLabels(),
                        datasets: [{
                            data: getCategoryData(),
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.7)',
                                'rgba(54, 162, 235, 0.7)',
                                'rgba(255, 206, 86, 0.7)',
                                'rgba(75, 192, 192, 0.7)',
                                'rgba(153, 102, 255, 0.7)',
                                'rgba(255, 159, 64, 0.7)',
                                'rgba(199, 199, 199, 0.7)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '60%',
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    boxWidth: 12,
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const value = context.raw;
                                        const total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return context.label + ': ₱' + value.toLocaleString() + ' (' + percentage + '%)';
                                    }
                                }
                            }
                        }
                    }
                });
                console.log("Category chart initialized");
            }
            
            // 3. Impact Chart
            const impactCanvas = document.getElementById('impactChart');
            if (impactCanvas) {
                impactChart = new Chart(impactCanvas, {
                    type: 'bar',
                    data: {
                        labels: chartData.impact_labels || [],
                        datasets: [{
                            label: 'Amount',
                            data: chartData.impact_data || [],
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.7)',
                                'rgba(255, 159, 64, 0.7)',
                                'rgba(54, 162, 235, 0.7)'
                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(255, 159, 64, 1)',
                                'rgba(54, 162, 235, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const value = context.raw;
                                        return '₱' + value.toLocaleString();
                                    },
                                    afterLabel: function(context) {
                                        const counts = chartData.impact_counts || [0, 0, 0];
                                        return counts[context.dataIndex] + ' items';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '₱' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
                console.log("Impact chart initialized");
            }
            
            // 4. Totals Chart
            const totalsCanvas = document.getElementById('totalsChart');
            if (totalsCanvas) {
                totalsChart = new Chart(totalsCanvas, {
                    type: 'bar',
                    data: {
                        labels: chartData.total_labels || [],
                        datasets: [{
                            label: 'Amount',
                            data: chartData.total_values || [],
                            backgroundColor: [
                                'rgba(54, 162, 235, 0.7)',
                                'rgba(255, 99, 132, 0.7)',
                                'rgba(75, 192, 192, 0.7)',
                                'rgba(153, 102, 255, 0.7)',
                                'rgba(255, 206, 86, 0.7)'
                            ],
                            borderColor: [
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 99, 132, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)',
                                'rgba(255, 206, 86, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const value = context.raw;
                                        return '₱' + value.toLocaleString();
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '₱' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
                console.log("Totals chart initialized");
            }
            
            console.log("All charts initialized successfully");
        } catch (error) {
            console.error("Error initializing charts:", error);
        }
    }
    
    // Helper functions to get labels and data based on current settings
    function getTimeLabels() {
        switch (config.currentPeriod) {
            case 'daily':
                return chartData.days || [];
            case 'weekly':
                return chartData.weeks || [];
            case 'monthly':
                return chartData.months || [];
            case 'quarterly':
                return chartData.quarters || [];
            default:
                return chartData.months || [];
        }
    }
    
    function getBudgetData() {
        switch (config.currentPeriod) {
            case 'daily':
                return chartData.daily_budget || [];
            case 'weekly':
                return chartData.weekly_budget || [];
            case 'monthly':
                return chartData.monthly_budget || [];
            case 'quarterly':
                return chartData.quarterly_budget || [];
            default:
                return chartData.monthly_budget || [];
        }
    }
    
    function getExpenseData() {
        switch (config.currentPeriod) {
            case 'daily':
                return chartData.daily_expense || [];
            case 'weekly':
                return chartData.weekly_expense || [];
            case 'monthly':
                return chartData.monthly_expense || [];
            case 'quarterly':
                return chartData.quarterly_expense || [];
            default:
                return chartData.monthly_expense || [];
        }
    }
    
    function getCategoryLabels() {
        return config.currentBreakdown === 'category' 
            ? (chartData.categories || []) 
            : (chartData.subcategories || []);
    }
    
    function getCategoryData() {
        return config.currentBreakdown === 'category'
            ? (chartData.category_expenses || [])
            : (chartData.subcategory_expenses || []);
    }
    
    // Update all charts
    function updateCharts() {
        updateTimeChart();
        updateCategoryChart();
    }
    
    // Update time chart
    function updateTimeChart() {
        if (!timeChart) return;
        
        console.log("Updating time chart to", config.currentPeriod, config.currentChartType);
        
        // Update chart type if needed
        if (timeChart.config.type !== config.currentChartType) {
            timeChart.config.type = config.currentChartType;
            
            // Update dataset styling based on chart type
            if (config.currentChartType === 'bar') {
                timeChart.data.datasets[0].backgroundColor = 'rgba(54, 162, 235, 0.7)';
                timeChart.data.datasets[1].backgroundColor = 'rgba(255, 99, 132, 0.7)';
                timeChart.data.datasets[0].fill = false;
                timeChart.data.datasets[1].fill = false;
            } else {
                timeChart.data.datasets[0].backgroundColor = 'rgba(54, 162, 235, 0.2)';
                timeChart.data.datasets[1].backgroundColor = 'rgba(255, 99, 132, 0.2)';
                timeChart.data.datasets[0].fill = true;
                timeChart.data.datasets[1].fill = true;
            }
        }
        
        // Get data based on current period
        let labels = getTimeLabels();
        let budgetData = getBudgetData();
        let expenseData = getExpenseData();
        
        // Apply date range filter if set
        if (config.dateRangeStart || config.dateRangeEnd) {
            const filteredData = filterDataByDateRange(labels, budgetData, expenseData);
            labels = filteredData.labels;
            budgetData = filteredData.budgetData;
            expenseData = filteredData.expenseData;
        }
        
        // Update chart data
        timeChart.data.labels = labels;
        timeChart.data.datasets[0].data = budgetData;
        timeChart.data.datasets[1].data = expenseData;
        
        // Update chart
        timeChart.update();
    }
    
    // Update category chart
    function updateCategoryChart() {
        if (!categoryChart) return;
        
        console.log("Updating category chart to", config.currentBreakdown);
        
        const labels = getCategoryLabels();
        const data = getCategoryData();
        
        // Generate colors if needed
        const backgroundColor = generateColors(data.length);
        
        // Update chart data
        categoryChart.data.labels = labels;
        categoryChart.data.datasets[0].data = data;
        categoryChart.data.datasets[0].backgroundColor = backgroundColor;
        
        // Update chart
        categoryChart.update();
    }
    
    // Filter data by date range
    function filterDataByDateRange(labels, budgetData, expenseData) {
        if (!config.dateRangeStart && !config.dateRangeEnd) {
            return { labels, budgetData, expenseData };
        }
        
        const filteredLabels = [];
        const filteredBudget = [];
        const filteredExpense = [];
        
        // Function to parse dates from different formats
        function parseDate(label) {
            try {
                // Handle different date formats
                if (/\d{4}-\d{2}-\d{2}/.test(label)) {
                    // YYYY-MM-DD format
                    return new Date(label);
                } else if (/\w{3} \d{4}/.test(label)) {
                    // "MMM YYYY" format (e.g., "Jan 2025")
                    return new Date(label);
                } else if (/\w{3} \d{1,2}/.test(label)) {
                    // "MMM DD" format (e.g., "Jan 15")
                    return new Date(label + ", " + new Date().getFullYear());
                } else if (/Week \d{1,2}, \d{4}/.test(label)) {
                    // "Week XX, YYYY" format
                    const [_, weekStr, yearStr] = label.match(/Week (\d{1,2}), (\d{4})/);
                    const year = parseInt(yearStr);
                    const week = parseInt(weekStr);
                    
                    // Create a date for Jan 1 of that year
                    const date = new Date(year, 0, 1);
                    
                    // Add (week-1) * 7 days to get to the start of the week
                    date.setDate(date.getDate() + (week - 1) * 7);
                    
                    return date;
                } else if (/Q\d \d{4}/.test(label)) {
                    // "Q1 2025" format
                    const [_, quarterStr, yearStr] = label.match(/Q(\d) (\d{4})/);
                    const year = parseInt(yearStr);
                    const quarter = parseInt(quarterStr);
                    
                    // Map quarter to month (Q1=Jan, Q2=Apr, Q3=Jul, Q4=Oct)
                    const month = (quarter - 1) * 3;
                    
                    return new Date(year, month, 1);
                } else {
                    // Try to parse as is
                    return new Date(label);
                }
            } catch (e) {
                console.warn("Could not parse date:", label);
                return null;
            }
        }
        
        // Filter data based on date range
        for (let i = 0; i < labels.length; i++) {
            const date = parseDate(labels[i]);
            
            // Skip invalid dates
            if (!date) continue;
            
            const isAfterStart = !config.dateRangeStart || date >= config.dateRangeStart;
            const isBeforeEnd = !config.dateRangeEnd || date <= config.dateRangeEnd;
            
            if (isAfterStart && isBeforeEnd) {
                filteredLabels.push(labels[i]);
                filteredBudget.push(budgetData[i]);
                filteredExpense.push(expenseData[i]);
            }
        }
        
        return {
            labels: filteredLabels,
            budgetData: filteredBudget,
            expenseData: filteredExpense
        };
    }
    
    // Generate colors for chart segments
    function generateColors(count) {
        const baseColors = [
            'rgba(255, 99, 132, 0.7)',    // red
            'rgba(54, 162, 235, 0.7)',    // blue
            'rgba(255, 206, 86, 0.7)',    // yellow
            'rgba(75, 192, 192, 0.7)',    // green
            'rgba(153, 102, 255, 0.7)',   // purple
            'rgba(255, 159, 64, 0.7)',    // orange
            'rgba(199, 199, 199, 0.7)',   // gray
            'rgba(83, 102, 255, 0.7)',    // indigo
            'rgba(40, 159, 64, 0.7)',     // forest green
            'rgba(210, 99, 132, 0.7)'     // pink
        ];
        
        if (count <= baseColors.length) {
            return baseColors.slice(0, count);
        }
        
        // Generate additional colors if needed
        const colors = [...baseColors];
        for (let i = baseColors.length; i < count; i++) {
            const r = Math.floor(Math.random() * 255);
            const g = Math.floor(Math.random() * 255);
            const b = Math.floor(Math.random() * 255);
            colors.push(`rgba(${r}, ${g}, ${b}, 0.7)`);
        }
        
        return colors;
    }
    
    // Export data to CSV
    function exportToCSV() {
        console.log("Exporting to CSV");
        
        // Create CSV content
        let csvContent = "data:text/csv;charset=utf-8,";
        
        // Add headers
        csvContent += "Date,Budget,Expense,Variance\n";
        
        // Get data based on current period
        let labels = getTimeLabels();
        let budgetData = getBudgetData();
        let expenseData = getExpenseData();
        
        // Apply date range filter if set
        if (config.dateRangeStart || config.dateRangeEnd) {
            const filteredData = filterDataByDateRange(labels, budgetData, expenseData);
            labels = filteredData.labels;
            budgetData = filteredData.budgetData;
            expenseData = filteredData.expenseData;
        }
        
        // Add data rows
        for (let i = 0; i < labels.length; i++) {
            const budget = budgetData[i] || 0;
            const expense = expenseData[i] || 0;
            const variance = budget - expense;
            
            csvContent += `${labels[i]},${budget},${expense},${variance}\n`;
        }
        
        // Create download link
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `expense_data_${config.currentPeriod}.csv`);
        document.body.appendChild(link);
        
        // Trigger download
        link.click();
        
        // Clean up
        document.body.removeChild(link);
    }
    
    // Very simple chart export (minimum chance of failure)
    function exportChartsAsImages() {
        // Get all chart canvases
        const timeChart = document.getElementById('timeChart');
        const categoryChart = document.getElementById('categoryChart');
        const impactChart = document.getElementById('impactChart');
        const totalsChart = document.getElementById('totalsChart');
        
        // Download each chart (if it exists)
        try {
            if (timeChart) {
                const link = document.createElement('a');
                link.download = 'time_chart.png';
                link.href = timeChart.toDataURL();
                link.click();
            }
        } catch (e) { console.error("Error exporting time chart:", e); }
        
        try {
            if (categoryChart) {
                const link = document.createElement('a');
                link.download = 'category_chart.png';
                link.href = categoryChart.toDataURL();
                link.click();
            }
        } catch (e) { console.error("Error exporting category chart:", e); }
        
        try {
            if (impactChart) {
                const link = document.createElement('a');
                link.download = 'impact_chart.png';
                link.href = impactChart.toDataURL();
                link.click();
            }
        } catch (e) { console.error("Error exporting impact chart:", e); }
        
        try {
            if (totalsChart) {
                const link = document.createElement('a');
                link.download = 'totals_chart.png';
                link.href = totalsChart.toDataURL();
                link.click();
            }
        } catch (e) { console.error("Error exporting totals chart:", e); }
        
        alert("Charts exported as images. Check your downloads folder.");
    }
})();
</script>