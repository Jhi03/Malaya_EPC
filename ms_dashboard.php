<?php
    include('validate_login.php');
    $page_title = "Dashboard - Malaya Solar Energies Inc.";
    
    // Database connection
    include('db_connection.php');
    
    // Get current year and month for filtering
    $current_year = date('Y');
    $current_month = date('m');
    
    // 1. Total Expenses by Category
    $category_query = "
        SELECT 
            category,
            COUNT(*) as count,
            SUM(expense) as total_expense,
            SUM(budget) as total_budget,
            SUM(variance) as total_variance
        FROM expense 
        WHERE YEAR(purchase_date) = ? 
        GROUP BY category
    ";
    $stmt = $conn->prepare($category_query);
    $stmt->bind_param("i", $current_year);
    $stmt->execute();
    $category_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // 2. Monthly Expense Trends (Last 12 months)
    $monthly_query = "
        SELECT 
            DATE_FORMAT(purchase_date, '%Y-%m') as month,
            SUM(expense) as total_expense,
            COUNT(*) as transaction_count
        FROM expense 
        WHERE purchase_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(purchase_date, '%Y-%m')
        ORDER BY month ASC
    ";
    $monthly_result = $conn->query($monthly_query);
    $monthly_data = $monthly_result->fetch_all(MYSQLI_ASSOC);
    
    // 3. Project Summary
    $project_query = "
        SELECT 
            p.project_name,
            p.project_code,
            p.budget,
            COALESCE(SUM(e.expense), 0) as total_spent,
            COUNT(e.record_id) as transaction_count
        FROM projects p
        LEFT JOIN expense e ON p.project_id = e.project_id
        GROUP BY p.project_id, p.project_name, p.project_code, p.budget
        ORDER BY total_spent DESC
        LIMIT 10
    ";
    $project_result = $conn->query($project_query);
    $project_data = $project_result->fetch_all(MYSQLI_ASSOC);
    
    // 4. Recent Transactions
    $recent_query = "
        SELECT 
            e.record_description,
            e.expense,
            e.category,
            e.subcategory,
            e.purchase_date,
            p.project_name,
            emp.first_name,
            emp.last_name
        FROM expense e
        LEFT JOIN projects p ON e.project_id = p.project_id
        LEFT JOIN users u ON e.user_id = u.user_id
        LEFT JOIN employee emp ON u.employee_id = emp.employee_id
        ORDER BY e.creation_date DESC
        LIMIT 10
    ";
    $recent_result = $conn->query($recent_query);
    $recent_data = $recent_result->fetch_all(MYSQLI_ASSOC);
    
    // 5. Key Performance Indicators
    $kpi_query = "
        SELECT 
            COUNT(*) as total_transactions,
            SUM(expense) as total_expenses,
            SUM(budget) as total_budget,
            AVG(expense) as avg_transaction,
            SUM(CASE WHEN variance > 0 THEN 1 ELSE 0 END) as over_budget_count
        FROM expense 
        WHERE YEAR(purchase_date) = ?
    ";
    $stmt = $conn->prepare($kpi_query);
    $stmt->bind_param("i", $current_year);
    $stmt->execute();
    $kpi_data = $stmt->get_result()->fetch_assoc();
    
    // 6. Department Statistics
    $dept_query = "
        SELECT 
            department,
            COUNT(*) as employee_count,
            employment_status
        FROM employee 
        GROUP BY department, employment_status
        ORDER BY employee_count DESC
    ";
    $dept_result = $conn->query($dept_query);
    $dept_data = $dept_result->fetch_all(MYSQLI_ASSOC);
    
    // 7. Vendor Statistics
    $vendor_query = "
        SELECT 
            COUNT(*) as total_vendors,
            COUNT(DISTINCT vendor_type) as vendor_types
        FROM vendors
    ";
    $vendor_result = $conn->query($vendor_query);
    $vendor_stats = $vendor_result->fetch_assoc();
    
    // 8. Current Month Statistics
    $current_month_query = "
        SELECT 
            COUNT(*) as current_month_transactions,
            SUM(expense) as current_month_expenses
        FROM expense 
        WHERE YEAR(purchase_date) = ? AND MONTH(purchase_date) = ?
    ";
    $stmt = $conn->prepare($current_month_query);
    $stmt->bind_param("ii", $current_year, $current_month);
    $stmt->execute();
    $current_month_data = $stmt->get_result()->fetch_assoc();
    
    // Convert data to JSON for JavaScript
    $category_json = json_encode($category_data);
    $monthly_json = json_encode($monthly_data);
    $project_json = json_encode($project_data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="icon" href="images/Malaya_Logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible&display=swap" rel="stylesheet">
    <link href="css/ms_dashboard.css" rel="stylesheet">
    <link href="css/ms_sidebar.css" rel="stylesheet">
    <link href="css/ms_header.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Atkinson Hyperlegible', sans-serif;
            margin: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        .content-body {
            padding: 20px 40px 20px 40px;
            flex: 1;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            width: 100%;
            margin-left: auto;
            margin-right: auto;
            background: rgba(243, 243, 243, 0.8);
            background-image: repeating-linear-gradient(
                45deg,
                rgba(255, 255, 255, 0.700) 0px,
                rgba(255, 255, 255, 0.500) 1px,
                transparent 1px,
                transparent 20px
            );
            backdrop-filter: blur(12px) saturate(180%);
            -webkit-backdrop-filter: blur(12px) saturate(180%);
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <?php include 'sidebar.php'; ?>
    </div>

    <div class="content-area">
        <?php include 'header.php'; ?>
        <div class="content-body">

            <!-- KPI Cards Row -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon">₱</div>
                        <div class="kpi-content">
                            <h3>₱<?= number_format($kpi_data['total_expenses'], 2) ?></h3>
                            <p>Total Expenses (<?= $current_year ?>)</p>
                            <small class="text-success">
                                Current Month: ₱<?= number_format($current_month_data['current_month_expenses'], 2) ?>
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon">#</div>
                        <div class="kpi-content">
                            <h3><?= number_format($kpi_data['total_transactions']) ?></h3>
                            <p>Total Transactions</p>
                            <small class="text-info">
                                This Month: <?= $current_month_data['current_month_transactions'] ?>
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon">📊</div>
                        <div class="kpi-content">
                            <h3>₱<?= number_format($kpi_data['avg_transaction'], 2) ?></h3>
                            <p>Average Transaction</p>
                            <small class="text-warning">
                                Over Budget: <?= $kpi_data['over_budget_count'] ?> items
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-icon">🏢</div>
                        <div class="kpi-content">
                            <h3><?= count($project_data) ?></h3>
                            <p>Active Projects</p>
                            <small class="text-secondary">
                                <?= $vendor_stats['total_vendors'] ?> Vendors
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="chart-container">
                        <h4>Expenses by Category</h4>
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-container">
                        <h4>Monthly Expense Trends</h4>
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Data Tables Row -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="data-table-container">
                        <h4>Top Projects by Spending</h4>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Project</th>
                                        <th>Budget</th>
                                        <th>Spent</th>
                                        <th>Remaining</th>
                                        <th>Progress</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($project_data as $project): 
                                        $remaining = $project['budget'] - $project['total_spent'];
                                        $progress = $project['budget'] > 0 ? ($project['total_spent'] / $project['budget']) * 100 : 0;
                                        $progress_class = $progress > 100 ? 'bg-danger' : ($progress > 80 ? 'bg-warning' : 'bg-success');
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($project['project_name']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($project['project_code']) ?></small>
                                        </td>
                                        <td>₱<?= number_format($project['budget'], 2) ?></td>
                                        <td>₱<?= number_format($project['total_spent'], 2) ?></td>
                                        <td>₱<?= number_format($remaining, 2) ?></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar <?= $progress_class ?>" 
                                                     style="width: <?= min($progress, 100) ?>%">
                                                    <?= number_format($progress, 1) ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="data-table-container">
                        <h4>Recent Transactions</h4>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Category</th>
                                        <th>Date</th>
                                        <th>Project</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_data as $transaction): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($transaction['record_description']) ?></strong>
                                            <?php if($transaction['subcategory']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($transaction['subcategory']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>₱<?= number_format($transaction['expense'], 2) ?></td>
                                        <td>
                                            <span class="category-badge category-<?= strtolower($transaction['category']) ?>">
                                                <?= htmlspecialchars($transaction['category']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($transaction['purchase_date'])) ?></td>
                                        <td><?= htmlspecialchars($transaction['project_name'] ?? 'N/A') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Department Overview -->
            <div class="row">
                <div class="col-md-12">
                    <div class="data-table-container">
                        <h4>Department Overview</h4>
                        <div class="row">
                            <?php 
                            $dept_summary = [];
                            foreach($dept_data as $dept) {
                                if (!isset($dept_summary[$dept['department']])) {
                                    $dept_summary[$dept['department']] = ['active' => 0, 'total' => 0];
                                }
                                $dept_summary[$dept['department']]['total'] += $dept['employee_count'];
                                if ($dept['employment_status'] == 'active') {
                                    $dept_summary[$dept['department']]['active'] += $dept['employee_count'];
                                }
                            }
                            
                            foreach($dept_summary as $dept_name => $stats): 
                            ?>
                            <div class="col-md-4 mb-3">
                                <div class="dept-card">
                                    <h5><?= htmlspecialchars($dept_name) ?></h5>
                                    <p class="mb-1">Active: <strong><?= $stats['active'] ?></strong></p>
                                    <p class="mb-0">Total: <strong><?= $stats['total'] ?></strong></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="js/sidebar.js"></script>
    <script src="js/header.js"></script>
    <script>
        // Chart data from PHP
        const categoryData = <?= $category_json ?>;
        const monthlyData = <?= $monthly_json ?>;

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: categoryData.map(item => item.category),
                datasets: [{
                    data: categoryData.map(item => parseFloat(item.total_expense)),
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Monthly Trend Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Monthly Expenses',
                    data: monthlyData.map(item => parseFloat(item.total_expense)),
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>