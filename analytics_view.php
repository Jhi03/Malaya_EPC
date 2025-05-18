<?php
// At the top of analytics_view.php, add this data preparation
// Basic calculation of chart data from $expense_records
$months = [];
$monthly_budget = [];
$monthly_expense = [];

$categories = [];
$category_expenses = [];

// Calculate impact data
$total_over_budget = 0;
$total_company_loss = 0;
$total_billed_to_client = 0;
$count_over_budget = 0;
$count_company_loss = 0;
$count_billed_to_client = 0;

// Process records if available
if (!empty($expense_records)) {
    // Process each expense record
    foreach ($expense_records as $record) {
        // Extract date data
        $date = isset($record['purchase_date']) ? date('M Y', strtotime($record['purchase_date'])) : 'Unknown';
        if (!isset($monthly_budget[$date])) {
            $months[] = $date;
            $monthly_budget[$date] = 0;
            $monthly_expense[$date] = 0;
        }
        
        // Add to monthly totals
        $monthly_budget[$date] += floatval($record['budget'] ?? 0);
        $expense_amount = isset($record['is_rental']) && $record['is_rental'] === 'Yes' 
            ? floatval($record['rental_rate'] ?? 0) 
            : floatval($record['expense'] ?? 0);
        $monthly_expense[$date] += $expense_amount;
        
        // Process category data
        $category = $record['category'] ?? 'Unknown';
        if (!in_array($category, $categories)) {
            $categories[] = $category;
            $category_expenses[$category] = 0;
        }
        $category_expenses[$category] += $expense_amount;
        
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

// Convert associative arrays to indexed arrays for Chart.js
$chart_months = array_keys($monthly_budget);
$chart_budget = array_values($monthly_budget);
$chart_expense = array_values($monthly_expense);

// Create the chart data array
$chart_data = [
    // Time data
    'months' => $chart_months,
    'monthly_budget' => $chart_budget,
    'monthly_expense' => $chart_expense,
    
    // Category data
    'categories' => array_keys($category_expenses),
    'category_expenses' => array_values($category_expenses),
    
    // Impact data
    'impact_labels' => ['Over Budget', 'Company Loss', 'Billed to Client'],
    'impact_data' => [$total_over_budget, $total_company_loss, $total_billed_to_client],
    'impact_counts' => [$count_over_budget, $count_company_loss, $count_billed_to_client],
    
    // Totals data
    'total_labels' => ['Budget', 'Expense', 'Variance', 'Tax', 'Rentals'],
    'total_values' => [$total_budget, $total_expense, $total_variance, $total_tax, 0] // Replace 0 with actual rental total
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
    <!-- Date Range Picker Modal -->
    <div class="modal fade" id="dateRangeModal" tabindex="-1" aria-labelledby="dateRangeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dateRangeModalLabel">Select Date Range</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start-date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start-date">
                        </div>
                        <div class="col-md-6">
                            <label for="end-date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end-date">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <label class="form-label">Quick Selections</label>
                            <div class="btn-group w-100">
                                <button type="button" class="btn btn-outline-secondary" data-quick-range="7">Last 7 Days</button>
                                <button type="button" class="btn btn-outline-secondary" data-quick-range="30">Last 30 Days</button>
                                <button type="button" class="btn btn-outline-secondary" data-quick-range="90">Last 3 Months</button>
                                <button type="button" class="btn btn-outline-secondary" data-quick-range="all">All Time</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="apply-date-range">Apply</button>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="d-flex">
                <div class="me-2">
                    <button id="date-range-btn" class="btn btn-outline-primary btn-sm">
                        📅 Select Date Range
                    </button>
                </div>
                <div id="date-range-display" class="text-muted small pt-1">
                    All Time
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="d-flex justify-content-end">
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-primary" data-period="daily">Daily</button>
                    <button type="button" class="btn btn-outline-primary active" data-period="weekly">Weekly</button>
                    <button type="button" class="btn btn-outline-primary" data-period="monthly">Monthly</button>
                    <button type="button" class="btn btn-outline-primary" data-period="quarterly">Quarterly</button>
                </div>
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
</div>

<!-- Chart.js initialization script -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function() {
    // Configuration object to store current state
    const config = {
        chartInitialized: false,
        currentPeriod: 'weekly',
        currentChartType: 'line',
        currentBreakdown: 'category',
        dateRangeStart: null,
        dateRangeEnd: null
    };
    
    // Charts references
    let timeChart, categoryChart, impactChart, totalsChart;
    
    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log("DOM loaded for analytics");
        
        // Data from PHP or fallback to dummy data
        const chartData = window.analyticsData || {
            // Fallback dummy data if PHP data is missing
            months: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
            monthly_budget: [1000, 1200, 900, 1500, 1100],
            monthly_expense: [800, 1300, 950, 1400, 1050],
            categories: ['OPEX', 'CAPEX', 'ASSET'],
            category_expenses: [5000, 3000, 2000],
            impact_labels: ['Over Budget', 'Company Loss', 'Billed to Client'],
            impact_data: [1500, 2000, 3000],
            total_labels: ['Budget', 'Expense', 'Variance', 'Tax', 'Rentals'],
            total_values: [10000, 8500, 1500, 1020, 2000]
        };
        
        // Setup view toggle functionality
        setupViewToggle();
        
        // Initialize if analytics view is already visible
        const analyticsView = document.getElementById('analytics-view');
        if (analyticsView && analyticsView.style.display !== 'none') {
            initializeAnalyticsDashboard();
        }
        
        // Initialize charts when analytics button is clicked
        const analyticsBtn = document.getElementById('view-analytics-btn');
        if (analyticsBtn) {
            analyticsBtn.addEventListener('click', function() {
                initializeAnalyticsDashboard();
            });
        }
        
        // Main initialization function for analytics dashboard
        function initializeAnalyticsDashboard() {
            if (!config.chartInitialized) {
                console.log("Initializing analytics dashboard");
                initializeCharts();
                setupControls();
                config.chartInitialized = true;
            }
        }
        
        // Setup view toggle between records and analytics
        function setupViewToggle() {
            const recordsBtn = document.getElementById('view-records-btn');
            const analyticsBtn = document.getElementById('view-analytics-btn');
            const recordsView = document.getElementById('records-view');
            const analyticsView = document.getElementById('analytics-view');
            
            // Log the elements to verify they exist
            console.log("View elements found?", {
                recordsBtn: !!recordsBtn,
                analyticsBtn: !!analyticsBtn,
                recordsView: !!recordsView,
                analyticsView: !!analyticsView
            });
            
            if (recordsBtn && analyticsBtn && recordsView && analyticsView) {
                recordsBtn.addEventListener('click', function() {
                    recordsView.style.display = 'block';
                    analyticsView.style.display = 'none';
                    recordsBtn.classList.add('active');
                    analyticsBtn.classList.remove('active');
                    
                    // Update URL parameter
                    const url = new URL(window.location.href);
                    url.searchParams.delete('view');
                    history.replaceState({}, '', url);
                });
                
                analyticsBtn.addEventListener('click', function() {
                    console.log("Analytics button clicked");
                    recordsView.style.display = 'none';
                    analyticsView.style.display = 'block';
                    analyticsBtn.classList.add('active');
                    recordsBtn.classList.remove('active');
                    
                    // Update URL parameter
                    const url = new URL(window.location.href);
                    url.searchParams.set('view', 'analytics');
                    history.replaceState({}, '', url);
                });
                
                // Set initial view based on URL parameter
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('view') === 'analytics') {
                    console.log("Initial view set to analytics");
                    analyticsBtn.click();
                }
            }
        }
        
        // Setup all controls
        function setupControls() {
            setupChartControls();
            setupDateRangePicker();
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
                    
                    // Get selected period
                    const period = this.getAttribute('data-period');
                    console.log("Period selected:", period);
                    config.currentPeriod = period;
                    
                    // Update charts based on period
                    updateCharts();
                    
                    // Alert for now (to be replaced with actual implementation)
                    alert("Period selected: " + period);
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
                    
                    // Get selected chart type
                    const chartType = this.getAttribute('data-chart');
                    console.log("Chart type selected:", chartType);
                    config.currentChartType = chartType;
                    
                    // Update chart type
                    updateCharts();
                    
                    // Alert for now (to be replaced with actual implementation)
                    alert("Chart type selected: " + chartType);
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
                    
                    // Get selected breakdown
                    const breakdown = this.getAttribute('data-breakdown');
                    console.log("Breakdown selected:", breakdown);
                    config.currentBreakdown = breakdown;
                    
                    // Update chart breakdown
                    updateCharts();
                    
                    // Alert for now (to be replaced with actual implementation)
                    alert("Breakdown selected: " + breakdown);
                });
            });
        }
        
        // Initialize all charts
        function initializeCharts() {
            console.log("Initializing analytics charts");
            
            try {
                // 1. Time Chart
                const timeCanvas = document.getElementById('timeChart');
                if (timeCanvas) {
                    console.log("Found timeChart canvas");
                    timeChart = new Chart(timeCanvas, {
                        type: 'line',
                        data: {
                            labels: chartData.months,
                            datasets: [
                                {
                                    label: 'Budget',
                                    data: chartData.monthly_budget,
                                    borderColor: 'rgba(54, 162, 235, 1)',
                                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                    fill: true
                                },
                                {
                                    label: 'Expense',
                                    data: chartData.monthly_expense,
                                    borderColor: 'rgba(255, 99, 132, 1)',
                                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                                    fill: true
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
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
                    console.log("Found categoryChart canvas");
                    categoryChart = new Chart(categoryCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: chartData.categories,
                            datasets: [{
                                data: chartData.category_expenses,
                                backgroundColor: [
                                    'rgba(255, 99, 132, 0.7)',
                                    'rgba(54, 162, 235, 0.7)',
                                    'rgba(255, 206, 86, 0.7)',
                                    'rgba(75, 192, 192, 0.7)',
                                    'rgba(153, 102, 255, 0.7)',
                                    'rgba(255, 159, 64, 0.7)'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right'
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label;
                                            const value = context.raw;
                                            const total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                                            const percentage = ((value / total) * 100).toFixed(1);
                                            return label + ': ₱' + value.toLocaleString() + ' (' + percentage + '%)';
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
                    console.log("Found impactChart canvas");
                    impactChart = new Chart(impactCanvas, {
                        type: 'bar',
                        data: {
                            labels: chartData.impact_labels,
                            datasets: [{
                                label: 'Amount',
                                data: chartData.impact_data,
                                backgroundColor: [
                                    'rgba(255, 99, 132, 0.7)',
                                    'rgba(255, 159, 64, 0.7)',
                                    'rgba(54, 162, 235, 0.7)'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            indexAxis: 'y',  // Horizontal bar chart
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
                    console.log("Found totalsChart canvas");
                    totalsChart = new Chart(totalsCanvas, {
                        type: 'bar',
                        data: {
                            labels: chartData.total_labels,
                            datasets: [{
                                label: 'Amount',
                                data: chartData.total_values,
                                backgroundColor: [
                                    'rgba(54, 162, 235, 0.7)',
                                    'rgba(255, 99, 132, 0.7)',
                                    'rgba(75, 192, 192, 0.7)',
                                    'rgba(153, 102, 255, 0.7)',
                                    'rgba(255, 206, 86, 0.7)'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            indexAxis: 'y',  // Horizontal bar chart
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
        
        // Function to update charts based on current settings
        function updateCharts() {
            // This function will be expanded to update charts based on 
            // period, chart type, breakdown, and date range
            console.log("Updating charts with settings:", config);
            
            // For now, we just display the current configuration
            // Later, this would update the actual chart data and display
        }
        
        // Setup date range picker
        function setupDateRangePicker() {
            console.log("Setting up date range picker");
            
            const dateRangeBtn = document.getElementById('date-range-btn');
            if (!dateRangeBtn) {
                console.error("Date range button not found");
                return;
            }
            
            // Create modal if it doesn't exist
            let modalElement = document.getElementById('simpleDateRangeModal');
            if (!modalElement) {
                console.log("Creating simple modal element");
                modalElement = document.createElement('div');
                modalElement.id = 'simpleDateRangeModal';
                modalElement.style.cssText = 'display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1050;';
                modalElement.innerHTML = `
                    <div style="position: relative; width: 500px; max-width: 90%; margin: 100px auto; background: white; border-radius: 5px; padding: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <h5>Select Date Range</h5>
                        <button type="button" id="close-modal-btn" style="position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
                        
                        <div style="margin-bottom: 15px;">
                            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <div style="flex: 1;">
                                    <label for="simple-start-date">Start Date</label>
                                    <input type="date" id="simple-start-date" style="width: 100%; padding: 5px; border: 1px solid #ddd; border-radius: 4px;">
                                </div>
                                <div style="flex: 1;">
                                    <label for="simple-end-date">End Date</label>
                                    <input type="date" id="simple-end-date" style="width: 100%; padding: 5px; border: 1px solid #ddd; border-radius: 4px;">
                                </div>
                            </div>
                            
                            <div>
                                <label>Quick Selections</label>
                                <div style="display: flex; gap: 5px; margin-top: 5px; flex-wrap: wrap;">
                                    <button type="button" class="quick-range-btn" data-days="7" style="flex: 1; padding: 5px; border: 1px solid #ddd; background: #f8f9fa; border-radius: 4px; cursor: pointer;">Last 7 Days</button>
                                    <button type="button" class="quick-range-btn" data-days="30" style="flex: 1; padding: 5px; border: 1px solid #ddd; background: #f8f9fa; border-radius: 4px; cursor: pointer;">Last 30 Days</button>
                                    <button type="button" class="quick-range-btn" data-days="90" style="flex: 1; padding: 5px; border: 1px solid #ddd; background: #f8f9fa; border-radius: 4px; cursor: pointer;">Last 3 Months</button>
                                    <button type="button" class="quick-range-btn" data-days="all" style="flex: 1; padding: 5px; border: 1px solid #ddd; background: #f8f9fa; border-radius: 4px; cursor: pointer;">All Time</button>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: flex-end; gap: 10px;">
                            <button type="button" id="cancel-date-range-btn" style="padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                            <button type="button" id="simple-apply-date-range" style="padding: 6px 12px; background: #0d6efd; color: white; border: none; border-radius: 4px; cursor: pointer;">Apply</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modalElement);
                
                // Add event listeners to modal elements
                setTimeout(function() {
                    // Close modal buttons
                    const closeBtn = document.getElementById('close-modal-btn');
                    if (closeBtn) {
                        closeBtn.addEventListener('click', function() {
                            modalElement.style.display = 'none';
                        });
                    }
                    
                    const cancelBtn = document.getElementById('cancel-date-range-btn');
                    if (cancelBtn) {
                        cancelBtn.addEventListener('click', function() {
                            modalElement.style.display = 'none';
                        });
                    }
                    
                    // Quick range buttons
                    const quickRangeButtons = document.querySelectorAll('.quick-range-btn');
                    quickRangeButtons.forEach(button => {
                        button.addEventListener('click', function() {
                            const days = this.getAttribute('data-days');
                            console.log("Quick range selected:", days);
                            
                            const endDate = new Date();
                            let startDate = new Date();
                            
                            if (days === 'all') {
                                document.getElementById('simple-start-date').value = '';
                                document.getElementById('simple-end-date').value = '';
                            } else {
                                startDate.setDate(startDate.getDate() - parseInt(days));
                                
                                // Format dates for input fields (YYYY-MM-DD)
                                const formatDate = (date) => {
                                    const year = date.getFullYear();
                                    const month = String(date.getMonth() + 1).padStart(2, '0');
                                    const day = String(date.getDate()).padStart(2, '0');
                                    return `${year}-${month}-${day}`;
                                };
                                
                                document.getElementById('simple-start-date').value = formatDate(startDate);
                                document.getElementById('simple-end-date').value = formatDate(endDate);
                            }
                        });
                    });
                    
                    // Apply button
                    const applyBtn = document.getElementById('simple-apply-date-range');
                    if (applyBtn) {
                        applyBtn.addEventListener('click', function() {
                            console.log("Apply button clicked");
                            
                            const startDate = document.getElementById('simple-start-date').value;
                            const endDate = document.getElementById('simple-end-date').value;
                            
                            console.log("Selected dates:", startDate, "to", endDate);
                            
                            // Update config
                            config.dateRangeStart = startDate ? new Date(startDate) : null;
                            config.dateRangeEnd = endDate ? new Date(endDate) : null;
                            
                            // Update display
                            const dateRangeDisplay = document.getElementById('date-range-display');
                            if (dateRangeDisplay) {
                                if (startDate && endDate) {
                                    // Format dates for display
                                    const formatDisplayDate = (dateStr) => {
                                        const date = new Date(dateStr);
                                        return date.toLocaleDateString();
                                    };
                                    
                                    dateRangeDisplay.textContent = `${formatDisplayDate(startDate)} - ${formatDisplayDate(endDate)}`;
                                } else {
                                    dateRangeDisplay.textContent = 'All Time';
                                }
                            }
                            
                            // Close modal
                            modalElement.style.display = 'none';
                            
                            // Update charts with date range
                            updateCharts();
                            
                            // Alert for now
                            alert(`Date range set: ${startDate || 'All'} to ${endDate || 'All'}`);
                        });
                    }
                }, 100);
            }
            
            // Show modal when date range button is clicked
            dateRangeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent other handlers
                console.log("Date range button clicked");
                modalElement.style.display = 'block';
            });
            
            console.log("Date range picker setup complete");
        }
    });
})();
</script>