<?php
/**
 * Shared presentation helpers for admin order screens (badges + labels).
 * Pure output helpers – no database access.
 */

function fc_order_method(?string $m): string {
    $m = strtolower(trim((string)$m));
    return ($m === 'pickup') ? 'pickup' : 'cod';
}

function fc_status_label(string $status, string $method = 'cod'): string {
    $map = [
        'pending'          => 'Pending',
        'processing'       => 'Processing',
        'ready_for_pickup' => 'Ready for Pickup',
        'ready'            => 'Ready for Pickup',
        'collected'        => 'Collected',
        'shipped'          => ($method === 'pickup') ? 'Ready for Pickup' : 'Shipped',
        'delivered'        => 'Delivered',
        'cancelled'        => 'Cancelled',
    ];
    return $map[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function fc_method_badge(?string $method): string {
    if (fc_order_method($method) === 'pickup') {
        return '<span class="fc-badge method-pickup"><i class="bi bi-shop"></i>Store Pickup</span>';
    }
    return '<span class="fc-badge method-delivery"><i class="bi bi-truck"></i>Delivery / COD</span>';
}

function fc_payment_badge(?string $status, string $extraClass = ''): string {
    $s = strtolower((string)$status);
    $paid = ($s === 'paid');
    $cancelled = ($s === 'cancelled');
    $cls = $paid ? 'pay-paid' : ($cancelled ? 'status-cancelled' : 'pay-unpaid');
    $icon = $paid ? 'bi-check-circle-fill' : ($cancelled ? 'bi-x-circle-fill' : 'bi-exclamation-circle-fill');
    $label = $paid ? 'Paid' : ($cancelled ? 'Cancelled' : 'Unpaid');
    return '<span class="fc-badge ' . $cls . ' ' . $extraClass . '"><i class="bi ' . $icon . '"></i>' . $label . '</span>';
}

function fc_status_badge(?string $status, ?string $method = 'cod', string $extraClass = ''): string {
    $status = $status ?: 'pending';
    $m = fc_order_method($method);
    $cls = ($status === 'shipped' && $m === 'pickup') ? 'ready' : ($status === 'ready_for_pickup' ? 'ready' : $status);
    return '<span class="fc-badge st-' . htmlspecialchars($cls) . ' ' . $extraClass . '">' . htmlspecialchars(fc_status_label($status, $m)) . '</span>';
}

/**
 * The next action an admin can take for an order, or null when finished.
 * Returns [nextStatus, label, css-class, icon]
 */
function fc_next_action(string $status, string $method): ?array {
    $pickup = (fc_order_method($method) === 'pickup');
    switch ($status) {
        case 'pending':    return ['processing', 'Accept', 'accept', 'bi-arrow-right-short'];
        case 'processing': return $pickup ? ['ready_for_pickup', 'Mark Ready', 'ready', 'bi-box-seam']
                                          : ['shipped', 'Mark Shipped', 'ready', 'bi-truck'];
        case 'ready_for_pickup':
        case 'ready':
        case 'shipped':    return $pickup ? ['collected', 'Mark Collected', 'done', 'bi-bag-check']
                                          : ['delivered', 'Mark Delivered', 'done', 'bi-check-lg'];
        default:           return null;
    }
}

