<?php
// Function to process search, filter, and sort for expense records
function process_expense_records($records, $search_term = '', $filter_category = '', $filter_subcategory = '', $sort_by = '') {
    // If no records, return empty array
    if (empty($records)) {
        return [];
    }
    
    // Make a copy of the records to work with
    $filtered_records = $records;
    
    // Apply search - look in description, payee, and remarks fields
    if (!empty($search_term)) {
        $filtered_records = array_filter($filtered_records, function($record) use ($search_term) {
            // Search in record_description
            $desc_match = stripos($record['record_description'], $search_term) !== false;
            
            // Search in payee
            $payee_match = stripos($record['payee'], $search_term) !== false;
            
            // Search in remarks
            $remarks_match = stripos($record['remarks'], $search_term) !== false;
            
            // Return true if any field matches
            return $desc_match || $payee_match || $remarks_match;
        });
    }
    
    // Apply category filter
    if (!empty($filter_category)) {
        $filtered_records = array_filter($filtered_records, function($record) use ($filter_category) {
            return $record['category'] === $filter_category;
        });
        
        // Apply subcategory filter if provided
        if (!empty($filter_subcategory)) {
            $filtered_records = array_filter($filtered_records, function($record) use ($filter_subcategory) {
                return $record['subcategory'] === $filter_subcategory;
            });
        }
    }
    
    // Apply sorting
    if (!empty($sort_by)) {
        switch ($sort_by) {
            case 'a-z':
                usort($filtered_records, function($a, $b) {
                    return strcasecmp($a['record_description'], $b['record_description']);
                });
                break;
            case 'z-a':
                usort($filtered_records, function($a, $b) {
                    return strcasecmp($b['record_description'], $a['record_description']);
                });
                break;
            case 'oldest-newest':
                usort($filtered_records, function($a, $b) {
                    return strtotime($a['purchase_date']) - strtotime($b['purchase_date']);
                });
                break;
            case 'newest-oldest':
                usort($filtered_records, function($a, $b) {
                    return strtotime($b['purchase_date']) - strtotime($a['purchase_date']);
                });
                break;
            case 'highest-lowest':
                usort($filtered_records, function($a, $b) {
                    $a_amount = floatval($a['expense']) + floatval($a['rental_rate']);
                    $b_amount = floatval($b['expense']) + floatval($b['rental_rate']);
                    return $b_amount - $a_amount;
                });
                break;
            case 'lowest-highest':
                usort($filtered_records, function($a, $b) {
                    $a_amount = floatval($a['expense']) + floatval($a['rental_rate']);
                    $b_amount = floatval($b['expense']) + floatval($b['rental_rate']);
                    return $a_amount - $b_amount;
                });
                break;
        }
    }
    
    // Reset array keys
    return array_values($filtered_records);
}

// Get search, filter and sort parameters from request if available
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$filter_category = isset($_GET['category']) ? $_GET['category'] : '';
$filter_subcategory = isset($_GET['subcategory']) ? $_GET['subcategory'] : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'newest-oldest'; // Default sort

// Process records if they're available
if (isset($records) && is_array($records)) {
    $records = process_expense_records($records, $search_term, $filter_category, $filter_subcategory, $sort_by);
}
?>