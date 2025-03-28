<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';;

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=health_facilities_' . date('Y-m-d') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Define CSV headers
$headers = [
    'facility_name',
    'credit_invoice_number',
    'invoice_date',
    'total_invoice_amount',
    'paid_amount',
    'remaining_balance',
    'payment_status',
    'contact_person',
    'phone',
    'email',
    'address',
    'status'
];

// Write headers
fputcsv($output, $headers);

try {
    // Get all facilities
    $stmt = $pdo->prepare("SELECT * FROM health_facility ORDER BY facility_name");
    $stmt->execute();
    $facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Write data rows
    foreach ($facilities as $facility) {
        $row = [
            $facility['facility_name'],
            $facility['credit_invoice_number'],
            $facility['invoice_date'],
            $facility['total_invoice_amount'],
            $facility['paid_amount'],
            $facility['remaining_balance'],
            $facility['payment_status'],
            $facility['contact_person'],
            $facility['phone'],
            $facility['email'],
            $facility['address'],
            $facility['status']
        ];
        fputcsv($output, $row);
    }

} catch (PDOException $e) {
    error_log("Error exporting facilities: " . $e->getMessage());
    // Write error message to CSV
    fputcsv($output, [t('error_exporting_facilities')]);
}

// Close output stream
fclose($output);
exit; 