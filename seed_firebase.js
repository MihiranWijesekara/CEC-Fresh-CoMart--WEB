/**
 * Freshcomart (CECWeb) — Firebase Firestore Seeding Script
 * Populates Firestore with:
 *  1. `categories` collection: All 19 categories (IDs 1–19)
 *  2. `items` collection: All 20 items (IDs 11–30 with exact prices, units, categories, and 3-decimal stock_quantity)
 *  3. `counters/orders` document: { current: 13 } so next order is sequential 5-digit '00014'
 */

import {
  DEFAULT_CATEGORIES,
  DEFAULT_ITEMS,
  INITIAL_ORDER_COUNTER,
  ensureCategoriesSeeded,
  ensureItemsSeeded,
  ensureOrderCounterSeeded,
  seedFirebaseDatabase
} from "./assets/js/firebase-service.js";

export {
  DEFAULT_CATEGORIES,
  DEFAULT_ITEMS,
  INITIAL_ORDER_COUNTER,
  ensureCategoriesSeeded,
  ensureItemsSeeded,
  ensureOrderCounterSeeded,
  seedFirebaseDatabase
};

// Auto-expose on window for one-click console or button invocation in browser
if (typeof window !== "undefined") {
  window.seedFirebaseDatabase = seedFirebaseDatabase;
}

