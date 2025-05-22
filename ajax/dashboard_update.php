<?php
// AJAX endpoint for dashboard updates
header('Content-Type: application/json');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include('../db_connection.php');

$current_year = date('Y');
$current_month = date('m');

try {
    // Get updated KPI data
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

    // Get updated category data
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

    // Get updated monthly data
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

    // Get updated recent transactions
    $recent_query = "
        SELECT 
            e.record_description,
            e.expense,
            e.category,
            e.subcategory,
            e.purchase_date,
            p.project_name
        FROM expense e
        LEFT JOIN projects p ON e.project_id = p.project_id
        ORDER BY e.creation_date DESC
        LIMIT 10
    ";
    $recent_result = $conn->query($recent_query);
    $recent_data = $recent_result->fetch_all(MYSQLI_ASSOC);

    // Return JSON response
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'kpi' => $kpi_data,
        'charts' => [
            'category' => $category_data,
            'monthly' => $monthly_data
        ],
        'recent' => $recent_data
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>