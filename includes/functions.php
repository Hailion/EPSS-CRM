<?php
/**
 * Safely format a number with thousand separators
 * @param mixed $number The number to format
 * @param int $decimals Number of decimal points
 * @return string Formatted number
 */
function safe_number_format($number, $decimals = 0) {
    if ($number === null || $number === '') {
        return '0';
    }
    return number_format((float)$number, $decimals, '.', ',');
}

/**
 * Format a number as currency
 * @param mixed $amount The amount to format
 * @param string $currency The currency symbol (default: ETB)
 * @return string Formatted currency string
 */
function format_currency($amount, $currency = 'ETB') {
    if ($amount === null || $amount === '') {
        return $currency . ' 0.00';
    }
    return $currency . ' ' . safe_number_format((float)$amount, 2);
}

/**
 * Format a date
 * @param string $date The date to format
 * @param string $format The format to use (default: Y-m-d)
 * @return string The formatted date
 */
function formatDate($date, $format = 'Y-m-d') {
    return date($format, strtotime($date));
}

/**
 * Get the number of days between two dates
 * @param string $date1 The first date
 * @param string $date2 The second date
 * @return int The number of days
 */
function getDaysBetween($date1, $date2) {
    $datetime1 = new DateTime($date1);
    $datetime2 = new DateTime($date2);
    $interval = $datetime1->diff($datetime2);
    return $interval->days;
}

/**
 * Check if a date is overdue
 * @param string $due_date The due date
 * @return bool Whether the date is overdue
 */
function isOverdue($due_date) {
    return strtotime($due_date) < time();
}

/**
 * Get the status badge class
 * @param string $status The status
 * @return string The badge class
 */
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'active':
            return 'status-active';
        case 'overdue':
            return 'status-overdue';
        case 'paid':
            return 'status-paid';
        case 'pending':
            return 'status-pending';
        default:
            return 'status-default';
    }
}

/**
 * Sanitize user input
 * @param string $input The input to sanitize
 * @return string The sanitized input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input));
}

/**
 * Validate email address
 * @param string $email The email to validate
 * @return bool Whether the email is valid
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate a random string
 * @param int $length The length of the string
 * @return string The random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $string;
}

/**
 * Convert timestamp to human readable time difference string
 * @param string $datetime MySQL datetime string
 * @param bool $full Whether to show full detail
 * @return string
 */
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    // Calculate weeks from days without creating dynamic property
    $weeks = floor($diff->d / 7);
    $remaining_days = $diff->d % 7;

    $string = array();
    
    if ($diff->y > 0) {
        $string['y'] = $diff->y . ' year' . ($diff->y > 1 ? 's' : '');
    }
    if ($diff->m > 0) {
        $string['m'] = $diff->m . ' month' . ($diff->m > 1 ? 's' : '');
    }
    if ($weeks > 0) {
        $string['w'] = $weeks . ' week' . ($weeks > 1 ? 's' : '');
    }
    if ($remaining_days > 0) {
        $string['d'] = $remaining_days . ' day' . ($remaining_days > 1 ? 's' : '');
    }
    if ($diff->h > 0) {
        $string['h'] = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '');
    }
    if ($diff->i > 0) {
        $string['i'] = $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
    }
    if ($diff->s > 0) {
        $string['s'] = $diff->s . ' second' . ($diff->s > 1 ? 's' : '');
    }

    if (!$full) {
        $string = array_slice($string, 0, 1);
    }
    
    return $string ? implode(', ', $string) . ' ago' : 'just now';
} 