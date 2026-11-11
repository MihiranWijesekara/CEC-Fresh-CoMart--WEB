<?php
/**
 * Shared presentation helpers for admin order screens (badges + labels).
 * Pure output helpers – no database access.
 */

function fc_order_method(?string $m): string {
    return ($m === 'pickup') ? 'pickup' : 'delivery';
}

function fc_status_label(string $status, string $method = 'delivery'): string {
    $map = [
        'pending'    => 'Pending',
        'processing' => 'Processing',
        'ready'      => 'Ready for Pickup',
        'collected'  => 'Collected',
        'shipped'    => ($method === 'pickup') ? 'Ready for Pickup' : 'Shipped',
        'delivered'  => 'Delivered',
        'cancelled'  => 'Cancelled',
    ];
    return $map[$status] ?? ucfirst($status);
}

function fc_method_badge(?string $method): string {
    if (fc_order_method($method) === 'pickup') {
        return '<span class="fc-badge method-pickup"><i class="bi bi-shop"></i>Store Pickup</span>';
    }
    return '<span class="fc-badge method-delivery"><i class="bi bi-truck"></i>Delivery / COD</span>';
}

function fc_payment_badge(?string $status, string $extraClass = ''): string {
    $paid = (strtolower((string)$status) === 'paid');
    return '<span class="fc-badge ' . ($paid ? 'pay-paid' : 'pay-unpaid') . ' ' . $extraClass . '"><i class="bi ' . ($paid ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill') . '"></i>' . ($paid ? 'Paid' : 'Unpaid') . '</span>';
}

function fc_status_badge(?string $status, ?string $method = 'delivery', string $extraClass = ''): string {
    $status = $status ?: 'pending';
    $m = fc_order_method($method);
    $cls = ($status === 'shipped' && $m === 'pickup') ? 'ready' : $status;
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
        case 'processing': return $pickup ? ['ready', 'Mark Ready', 'ready', 'bi-box-seam']
                                          : ['shipped', 'Mark Shipped', 'ready', 'bi-truck'];
        case 'ready':
        case 'shipped':    return $pickup ? ['collected', 'Mark Collected', 'done', 'bi-bag-check']
                                          : ['delivered', 'Mark Delivered', 'done', 'bi-check-lg'];
        default:           return null;
    }
}

