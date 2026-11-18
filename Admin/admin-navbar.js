/**
 * Shared Admin Navbar & Session Guard for Freshcomart Admin Operations Portal
 * Renders the exact Admin navbar with live Firestore operational badge counts.
 */

import {
  requireAdminSession,
  logoutUser,
  getAllOrdersAdmin,
  getAllItems,
  getAllInquiriesAdmin,
  ORDER_LIFECYCLE_COD,
  ORDER_LIFECYCLE_PICKUP,
  normalizeOrderStatus,
  isOrderActive,
  isOrderArchived
} from '../assets/js/firebase-service.js';

export {
  ORDER_LIFECYCLE_COD,
  ORDER_LIFECYCLE_PICKUP,
  normalizeOrderStatus,
  isOrderActive,
  isOrderArchived
};

export function initAdminNavbar(activePage = '') {
  const session = requireAdminSession();
  if (!session) return null;

  document.body.classList.add('fc-admin');

  const first = session.first_name ? session.first_name.charAt(0).toUpperCase() : 'A';
  const last = session.last_name ? session.last_name.charAt(0).toUpperCase() : 'D';
  const initials = (first + last) || 'AD';

  const currentFile = activePage || (location.pathname.split('/').pop() || 'dashboard.html');

  const isDash = /dashboard\.(html|php)/i.test(currentFile);
  const isOrder = /^order\.(html|php)/i.test(currentFile);
  const isItem = /^(Item|ItemAdd)\.(html|php)/i.test(currentFile);
  const isComplete = /completOrder\.(html|php)/i.test(currentFile);
  const isSummary = /summary\.(html|php)/i.test(currentFile);
  const isInquiries = /inquiries\.(html|php)/i.test(currentFile);

  const navHtml = `
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="../assets/css/custom-ui.css">
    <style>
      .navbar-custom {
        background-color: #15803d !important;
        padding-top: 10px;
        padding-bottom: 10px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.08);
      }
      .navbar-brand img {
        height: 46px;
        width: auto;
      }
      .navbar-nav .nav-link {
        color: rgba(255, 255, 255, 0.92) !important;
        font-weight: 600;
        font-size: 0.95rem;
        border-radius: 999px;
        padding: 8px 16px !important;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
      }
      .navbar-nav .nav-link:hover, 
      .navbar-nav .nav-link.active {
        background-color: rgba(255, 255, 255, 0.18) !important;
        color: #ffffff !important;
      }
      .nav-pill-badge {
        font-size: 0.72rem;
        font-weight: 800;
        padding: 2px 7px;
        border-radius: 999px;
        line-height: 1.2;
      }
      .mt-logo {
        width: 40px;
        height: 40px;
        background: #0f172a;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
        color: white;
        font-size: 14px;
        font-weight: bold;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
      }
      .mt-status-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        position: absolute;
        top: 1px;
        right: 1px;
        background: #4ade80;
        border: 1.5px solid #0f172a;
      }
      @media (max-width: 991px) {
        .navbar-nav {
          padding-top: 10px;
          gap: 5px;
        }
        .mt-logo {
          width: 36px;
          height: 36px;
          font-size: 13px;
        }
      }
    </style>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
      <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.html">
          <img src="../assets/images/logo/logo-freshco.png" alt="Freshcomart Logo">
          <span class="badge bg-warning text-dark rounded-pill fw-bold d-none d-sm-inline-block" style="font-size:0.7rem;">ADMIN OPS</span>
        </a>
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav mx-auto gap-1">
            <li class="nav-item">
              <a class="nav-link ${isDash ? 'active' : ''}" href="dashboard.html">
                <i class="bi bi-speedometer2"></i>Dashboard
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link ${isOrder ? 'active' : ''}" href="order.html">
                <i class="bi bi-basket2"></i>Active Orders
                <span class="nav-pill-badge bg-warning text-dark" id="navActiveOrdersBadge" style="display:none;">0</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link ${isItem ? 'active' : ''}" href="Item.html">
                <i class="bi bi-box-seam"></i>Inventory
                <span class="nav-pill-badge bg-danger text-white" id="navLowStockBadge" style="display:none;" title="Low/out-of-stock items">0</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link ${isComplete ? 'active' : ''}" href="completOrder.html">
                <i class="bi bi-archive"></i>Order Archive
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link ${isSummary ? 'active' : ''}" href="summary.html">
                <i class="bi bi-bar-chart-line"></i>Analytics
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link ${isInquiries ? 'active' : ''}" href="inquiries.html">
                <i class="bi bi-envelope"></i>Inquiries
                <span class="nav-pill-badge bg-light text-dark" id="navInquiriesBadge" style="display:none;">0</span>
              </a>
            </li>
          </ul>
          
          <div class="d-flex align-items-center gap-2">
            <a href="../index.html" target="_blank" class="btn btn-sm btn-light rounded-pill px-3 fw-semibold text-success" title="Open Customer Storefront">
              <i class="bi bi-shop me-1"></i>View Store
            </a>
            <div class="mt-logo" title="Logged in as ${session.email || 'Admin'}">
              ${initials}
              <div class="mt-status-dot"></div>
            </div>
            <button type="button" id="adminLogoutBtn" class="btn btn-outline-light rounded-pill btn-sm px-3 fw-bold">
              <i class="bi bi-box-arrow-right me-1"></i>Logout
            </button>
          </div>
        </div>
      </div>
    </nav>
  `;

  const container = document.getElementById('adminNavbarContainer') || document.getElementById('adminNavbarMount');
  if (container) {
    container.innerHTML = navHtml;
  } else {
    document.body.insertAdjacentHTML('afterbegin', navHtml);
  }

  const logoutBtn = document.getElementById('adminLogoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', async () => {
      if (confirm('Are you sure you want to logout?')) {
        await logoutUser();
        window.location.href = 'login.html';
      }
    });
  }

  refreshAdminNavBadges();
  return session;
}

export async function refreshAdminNavBadges() {
  try {
    const [orders, items, inquiries] = await Promise.all([
      getAllOrdersAdmin(),
      getAllItems(false),
      getAllInquiriesAdmin()
    ]);

    const activeOrdersCount = orders.filter(o => isOrderActive(o)).length;

    const lowStockCount = items.filter(i => Number(i.stock_quantity || 0) <= 5).length;
    const inquiriesCount = inquiries.length;

    const aoBadge = document.getElementById('navActiveOrdersBadge');
    if (aoBadge) {
      aoBadge.textContent = activeOrdersCount;
      aoBadge.style.display = activeOrdersCount > 0 ? '' : 'none';
    }

    const lsBadge = document.getElementById('navLowStockBadge');
    if (lsBadge) {
      lsBadge.textContent = lowStockCount;
      lsBadge.style.display = lowStockCount > 0 ? '' : 'none';
    }

    const inqBadge = document.getElementById('navInquiriesBadge');
    if (inqBadge) {
      inqBadge.textContent = inquiriesCount;
      inqBadge.style.display = inquiriesCount > 0 ? '' : 'none';
    }
  } catch (e) {
    console.warn('Could not refresh admin nav badges:', e);
  }
}

export function fcOrderMethod(m) {
  return String(m || '').trim().toLowerCase() === 'pickup' ? 'pickup' : 'cod';
}

export function fcLifecycleSteps(method = 'cod') {
  return fcOrderMethod(method) === 'pickup' ? ORDER_LIFECYCLE_PICKUP : ORDER_LIFECYCLE_COD;
}

export function fcStatusLabel(status, method = 'cod') {
  const m = fcOrderMethod(method);
  const s = normalizeOrderStatus(status, m);
  const map = {
    pending: 'Order Placed',
    order_placed: 'Order Placed',
    confirmed: 'Order Confirmed',
    order_confirmed: 'Order Confirmed',
    processing: 'Processing',
    ready_for_delivery: 'Ready for Delivery',
    out_for_delivery: 'Out for Delivery',
    shipped: m === 'pickup' ? 'Ready for Pickup' : 'Out for Delivery',
    ready_for_pickup: 'Ready for Pickup',
    ready: 'Ready for Pickup',
    delivered: 'Delivered',
    picked_up: 'Picked Up',
    collected: 'Picked Up',
    paid: 'Paid',
    cancelled: 'Cancelled'
  };
  return map[s] || s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

export function fcMethodBadge(method) {
  if (fcOrderMethod(method) === 'pickup') {
    return '<span class="fc-badge method-pickup"><i class="bi bi-shop"></i>Store Pickup</span>';
  }
  return '<span class="fc-badge method-delivery"><i class="bi bi-truck"></i>Cash on Delivery</span>';
}

export function fcPaymentBadge(status, extraClass = '') {
  const s = String(status || 'unpaid').toLowerCase();
  const paid = s === 'paid';
  const cancelled = s === 'cancelled';
  const cls = paid ? 'pay-paid' : (cancelled ? 'status-cancelled' : 'pay-unpaid');
  const icon = paid ? 'bi-check-circle-fill' : (cancelled ? 'bi-x-circle-fill' : 'bi-exclamation-circle-fill');
  const label = paid ? 'Paid' : (cancelled ? 'Cancelled' : 'Unpaid');
  return `<span class="fc-badge ${cls} ${extraClass}"><i class="bi ${icon}"></i>${label}</span>`;
}

export function fcStatusBadge(status, method = 'cod', extraClass = '') {
  const m = fcOrderMethod(method);
  const s = normalizeOrderStatus(status, m);
  const iconMap = {
    pending: 'bi-clock-history',
    confirmed: 'bi-check2-square',
    processing: 'bi-box-seam',
    ready_for_delivery: 'bi-box2-heart',
    out_for_delivery: 'bi-truck',
    ready_for_pickup: 'bi-bag-check',
    delivered: 'bi-house-check-fill',
    picked_up: 'bi-person-check-fill',
    paid: 'bi-check2-all',
    cancelled: 'bi-x-circle-fill'
  };
  const icon = iconMap[s] || 'bi-circle-fill';
  return `<span class="fc-badge st-${s} ${extraClass}"><i class="bi ${icon}"></i>${fcStatusLabel(s, m)}</span>`;
}

/**
 * Computes the next transition action in the order lifecycle:
 * Cash on Delivery (7 steps):
 *   1. Order Placed (pending) -> 2. Order Confirmed (confirmed) -> 3. Processing (processing)
 *   -> 4. Ready for Delivery (ready_for_delivery) -> 5. Out for Delivery (out_for_delivery)
 *   -> 6. Delivered (delivered) -> 7. Paid (paid)
 *
 * Store Pickup (6 steps):
 *   1. Order Placed (pending) -> 2. Order Confirmed (confirmed) -> 3. Processing (processing)
 *   -> 4. Ready for Pickup (ready_for_pickup) -> 5. Picked Up (picked_up) -> 6. Paid (paid)
 */
export function fcNextAction(status, method = 'cod') {
  const pickup = fcOrderMethod(method) === 'pickup';
  const s = normalizeOrderStatus(status, method);
  let tuple = null;
  let btnClass = 'btn-success';
  let requiresPaymentConfirm = false;

  switch (s) {
    case 'pending':
      tuple = ['confirmed', 'Confirm Order', 'confirm', 'bi-check2-square'];
      btnClass = 'btn-warning text-dark';
      break;
    case 'confirmed':
      tuple = ['processing', 'Start Processing', 'process', 'bi-box-seam'];
      btnClass = 'btn-info text-dark';
      break;
    case 'processing':
      tuple = pickup
        ? ['ready_for_pickup', 'Mark Ready for Pickup', 'ready_pickup', 'bi-bag-check']
        : ['ready_for_delivery', 'Mark Ready for Delivery', 'ready_delivery', 'bi-box2-heart'];
      btnClass = 'btn-primary';
      break;
    case 'ready_for_delivery':
      tuple = ['out_for_delivery', 'Out for Delivery', 'dispatch', 'bi-truck'];
      btnClass = 'btn-primary';
      break;
    case 'out_for_delivery':
      tuple = ['delivered', 'Mark Delivered', 'deliver', 'bi-house-check-fill'];
      btnClass = 'btn-success';
      break;
    case 'ready_for_pickup':
      tuple = ['picked_up', 'Mark Picked Up', 'pickup_collect', 'bi-person-check-fill'];
      btnClass = 'btn-success';
      break;
    case 'delivered':
    case 'picked_up':
      tuple = ['paid', 'Confirm Payment & Mark Paid', 'confirm_paid', 'bi-cash-coin'];
      btnClass = 'btn-dark';
      requiresPaymentConfirm = true;
      break;
    default:
      return null;
  }

  tuple.next_status = tuple[0];
  tuple.label = tuple[1];
  tuple.action_key = tuple[2];
  tuple.icon = tuple[3];
  tuple.btn_class = btnClass;
  tuple.requires_payment_confirm = requiresPaymentConfirm;
  return tuple;
}

export function fcRenderOrderStepper(status, method = 'cod') {
  const m = fcOrderMethod(method);
  const steps = fcLifecycleSteps(m);
  const s = normalizeOrderStatus(status, m);
  if (s === 'cancelled') {
    return `<div class="small text-danger fw-bold"><i class="bi bi-x-circle-fill me-1"></i>Order Cancelled</div>`;
  }
  const activeIdx = steps.findIndex(st => st.key === s);
  const curIdx = activeIdx >= 0 ? activeIdx : 0;

  return `
    <div class="d-flex flex-wrap align-items-center gap-1" style="font-size: 11px;">
      ${steps.map((step, idx) => {
        const isDone = idx < curIdx || s === 'paid';
        const isCurrent = idx === curIdx && s !== 'paid';
        let pillStyle = 'background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;';
        let iconHtml = `<span class="fw-bold">${idx + 1}.</span>`;
        if (isDone) {
          pillStyle = 'background:#dcfce7;color:#15803d;border:1px solid #86efac;font-weight:700;';
          iconHtml = `<i class="bi bi-check-circle-fill text-success"></i>`;
        } else if (isCurrent) {
          pillStyle = 'background:#0f172a;color:#ffffff;border:1px solid #0f172a;font-weight:700;box-shadow:0 0 0 2px rgba(22,163,74,0.2);';
          iconHtml = `<i class="bi bi-arrow-right-circle-fill text-warning"></i>`;
        }
        return `
          <span class="px-2 py-1 rounded-pill d-inline-flex align-items-center gap-1" style="${pillStyle}">
            ${iconHtml} <span>${step.label}</span>
          </span>
          ${idx < steps.length - 1 ? '<i class="bi bi-chevron-right text-muted" style="font-size:9px;"></i>' : ''}
        `;
      }).join('')}
    </div>
  `;
}

// Auto-initialize if script is included directly with data-auto-init
if (document.currentScript && document.currentScript.hasAttribute('data-auto-init')) {
  initAdminNavbar();
}
