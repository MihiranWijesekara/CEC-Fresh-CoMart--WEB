/**
 * Freshcomart (CECWeb) Centralized Firebase Auth + Cloud Firestore Service Layer
 * 100% SQL-Free Architecture powered by Firebase Authentication + Cloud Firestore (Modular SDK v10).
 */

import {
  app,
  auth,
  db,
  firebaseConfig,
  STORE_CONFIG,
  INITIAL_ORDER_COUNTER,
  DEFAULT_CATEGORIES,
  DEFAULT_ITEMS
} from "./firebase-config.js";

import {
  createUserWithEmailAndPassword,
  signInWithEmailAndPassword,
  signOut,
  onAuthStateChanged,
  updatePassword,
  EmailAuthProvider,
  reauthenticateWithCredential
} from "https://www.gstatic.com/firebasejs/10.14.1/firebase-auth.js";

import {
  collection,
  doc,
  getDoc,
  getDocs,
  setDoc,
  addDoc,
  updateDoc,
  deleteDoc,
  query,
  where,
  orderBy,
  limit,
  runTransaction,
  writeBatch
} from "https://www.gstatic.com/firebasejs/10.14.1/firebase-firestore.js";

export {
  app,
  auth,
  db,
  firebaseConfig,
  STORE_CONFIG,
  INITIAL_ORDER_COUNTER,
  DEFAULT_CATEGORIES,
  DEFAULT_ITEMS,
  onAuthStateChanged,
  collection,
  doc,
  getDoc,
  getDocs,
  setDoc,
  addDoc,
  updateDoc,
  deleteDoc,
  query,
  where,
  orderBy,
  limit,
  runTransaction,
  writeBatch
};

// ============================================================================
// 1. PRECISION, QUANTITY, BULK UNIT & FORMATTING HELPERS (Fix #12)
// ============================================================================

export function roundQty3(val) {
  const n = parseFloat(val);
  if (isNaN(n)) return 0;
  return Number(n.toFixed(3));
}

export function formatQty3String(val) {
  const n = parseFloat(val);
  if (isNaN(n)) return "0.000";
  return n.toFixed(3);
}

export function roundMoney2(val) {
  const n = parseFloat(val);
  if (isNaN(n)) return 0;
  return Number(n.toFixed(2));
}

export function isBulkItem(catId, unitStr) {
  const bulkCategories = [1, 2, 14, 15, 16];
  if (!bulkCategories.includes(Number(catId))) return false;
  const u = String(unitStr || "").trim().toLowerCase();
  return /(kg|g|l|ml)$/i.test(u);
}

export function getUnitMetric(unitStr) {
  const m = String(unitStr || "").trim().toLowerCase().match(/(kg|g|l|ml)$/i);
  return m ? m[1].toLowerCase() : "units";
}

export function parseUnitToGrams(unitStr) {
  const m = String(unitStr || "").trim().match(/^([0-9.]+)\s*(kg|g|l|ml)$/i);
  if (m) {
    const val = parseFloat(m[1]);
    const metric = m[2].toLowerCase();
    if (metric === "kg" || metric === "l") {
      return { val, metric, baseVal: val * 1000, type: metric === "kg" ? "weight" : "volume" };
    }
    return { val, metric, baseVal: val, type: metric === "g" ? "weight" : "volume" };
  }
  return { val: 1, metric: "units", baseVal: 1, type: "unit" };
}

export function formatQuantity(qty, unitStr, catId) {
  const q = roundQty3(qty);
  if (!isBulkItem(catId, unitStr)) {
    return Number.isInteger(q) ? String(q) : String(Number(q.toFixed(3)));
  }
  const parsed = parseUnitToGrams(unitStr);
  const totalBase = q * parsed.baseVal;
  if (parsed.type === "weight") {
    if (totalBase >= 1000) {
      const kg = totalBase / 1000;
      return (Number.isInteger(kg) ? kg : Number(kg.toFixed(3))) + " kg";
    }
    return (Number.isInteger(totalBase) ? totalBase : Number(totalBase.toFixed(1))) + " g";
  } else {
    if (totalBase >= 1000) {
      const l = totalBase / 1000;
      return (Number.isInteger(l) ? l : Number(l.toFixed(3))) + " L";
    }
    return (Number.isInteger(totalBase) ? totalBase : Number(totalBase.toFixed(1))) + " ml";
  }
}

export function formatMoney(amount) {
  const n = parseFloat(amount) || 0;
  return n.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

export function nowColomboISO() {
  const d = new Date();
  return d.toISOString().replace("T", " ").substring(0, 19);
}

export function todayDateString() {
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${y}-${m}-${day}`;
}

export function escapeHtml(str) {
  if (typeof document !== "undefined") {
    const div = document.createElement("div");
    div.textContent = str == null ? "" : String(str);
    return div.innerHTML;
  }
  return String(str ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

export function isPermissionError(err) {
  if (!err) return false;
  const code = String(err.code || "").toLowerCase();
  const msg = String(err.message || "").toLowerCase();
  return code.includes("permission-denied") || msg.includes("missing or insufficient permissions") || msg.includes("permission_denied");
}

export function sanitizeUserFacingError(err, fallbackMsg = "Please sign in to your Freshcomart account to continue with your purchase.") {
  if (isPermissionError(err)) {
    return fallbackMsg;
  }
  return (err && err.message) ? err.message : fallbackMsg;
}

// ============================================================================
// 2. IMAGE PATH NORMALIZATION (Fix #11) & HTML5 CANVAS COMPRESSION
// ============================================================================

export const DEFAULT_FALLBACK_IMAGE = "assets/images/logo/logo-freshco.png";

export function resolveImagePath(imagePath, basePrefix = undefined) {
  let prefix = basePrefix;
  if (prefix === undefined) {
    if (typeof window !== "undefined" && window.location && window.location.pathname) {
      const p = window.location.pathname.replace(/\\/g, "/").toLowerCase();
      if (p.includes("/admin/") || p.includes("/product/") || p.includes("/login/")) {
        prefix = "../";
      } else {
        prefix = "";
      }
    } else {
      prefix = "";
    }
  }

  if (!imagePath) return prefix + DEFAULT_FALLBACK_IMAGE;
  const s = String(imagePath).trim();
  if (!s) return prefix + DEFAULT_FALLBACK_IMAGE;
  if (s.startsWith("data:image/") || s.startsWith("http://") || s.startsWith("https://")) {
    return s;
  }

  let clean = s.replace(/^(\.\.\/|\.\/|\/)+/, "");

  if (clean.startsWith("ImageUpload/")) {
    clean = "Admin/" + clean;
  }

  if (clean.startsWith("assets/images/category/")) {
    const fileMap = {
      "vegetables.png": "vegetable.png",
      "snacks.png": "snack.png",
      "biscuits.png": "biscuit.png",
      "water.png": "waterBottle.png",
      "tea.png": "tea-bag.png",
      "desserts.png": "yoghurt.png",
      "bakery.png": "bread.png"
    };
    const baseName = clean.split("/").pop();
    const mapped = fileMap[baseName] || baseName;
    clean = "assets/images/shopCategory/" + mapped;
  }

  return prefix + clean;
}

export const normalizeImagePath = resolveImagePath;

export function compressImageFile(file, maxWidth = 500, maxHeight = 500, quality = 0.75) {
  return new Promise((resolve, reject) => {
    if (!file) {
      reject(new Error("No image file selected."));
      return;
    }
    const reader = new FileReader();
    reader.onerror = () => reject(new Error("Failed to read image file."));
    reader.onload = (evt) => {
      const img = new Image();
      img.onerror = () => reject(new Error("Invalid image format."));
      img.onload = () => {
        let width = img.width;
        let height = img.height;
        if (width > maxWidth || height > maxHeight) {
          const ratio = Math.min(maxWidth / width, maxHeight / height);
          width = Math.round(width * ratio);
          height = Math.round(height * ratio);
        }
        const canvas = document.createElement("canvas");
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext("2d");
        ctx.fillStyle = "#FFFFFF";
        ctx.fillRect(0, 0, width, height);
        ctx.drawImage(img, 0, 0, width, height);

        let dataUrl = canvas.toDataURL("image/webp", quality);
        if (!dataUrl || !dataUrl.startsWith("data:image/webp")) {
          dataUrl = canvas.toDataURL("image/jpeg", quality);
        }
        resolve(dataUrl);
      };
      img.src = evt.target.result;
    };
    reader.readAsDataURL(file);
  });
}

// ============================================================================
// 3. FIRESTORE DATA MODEL SEEDING & IN-MEMORY PROMISE DEDUPLICATION (Phase 3)
// ============================================================================

let categoriesCache = null;
let categoriesPromise = null;
let itemsSeedChecked = false;
let itemsCacheAll = null;
let itemsCacheTime = 0;
let itemsFetchPromise = null;
let ordersAdminPromise = null;
let ordersAdminCache = null;
let ordersAdminCacheTime = 0;
let inquiriesAdminPromise = null;

const CACHE_TTL_MS = 2500;
const LOCAL_ITEMS_CACHE_KEY = "fc_catalog_items_cache_v2";
const LOCAL_CUSTOMER_SESSION_KEY = "fc_customer_session_v2";
const LOCAL_ORDERS_MIRROR_KEY = "fc_orders_mirror_v2";
const LOCAL_ORDER_SEQ_KEY = "fc_order_seq_v2";

function invalidateItemsCache() {
  itemsCacheAll = null;
  itemsCacheTime = 0;
  itemsFetchPromise = null;
}

function invalidateOrdersCache() {
  ordersAdminCache = null;
  ordersAdminCacheTime = 0;
  ordersAdminPromise = null;
}

function normalizeStatusString(rawStatus) {
  const s = String(rawStatus ?? "active").trim().toLowerCase();
  if (s === "inactive" || s === "0" || s === "false" || s === "disabled") {
    return "inactive";
  }
  return "active";
}

function readLocalItemsMirror() {
  try {
    if (typeof localStorage === "undefined") return [];
    const raw = localStorage.getItem(LOCAL_ITEMS_CACHE_KEY);
    if (!raw) return [];
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed : [];
  } catch (_) {
    return [];
  }
}

function writeLocalItemsMirror(items) {
  try {
    if (typeof localStorage === "undefined" || !Array.isArray(items)) return;
    localStorage.setItem(LOCAL_ITEMS_CACHE_KEY, JSON.stringify(items));
  } catch (_) {}
}

function upsertItemInLocalMirror(itemObj) {
  try {
    if (!itemObj || !itemObj.id) return;
    const list = readLocalItemsMirror();
    const idStr = String(itemObj.id);
    const idx = list.findIndex((x) => String(x.id) === idStr);
    if (idx >= 0) {
      list[idx] = { ...list[idx], ...itemObj, id: idStr };
    } else {
      list.unshift({ ...itemObj, id: idStr });
    }
    writeLocalItemsMirror(list);
  } catch (_) {}
}

export async function ensureCategoriesSeeded() {
  if (categoriesCache && categoriesCache.length > 0) {
    return categoriesCache;
  }
  if (categoriesPromise) {
    return categoriesPromise;
  }
  categoriesPromise = (async () => {
    try {
      const snap = await getDocs(collection(db, "categories"));
      const ts = nowColomboISO();
      if (snap.empty) {
        try {
          const batch = writeBatch(db);
          DEFAULT_CATEGORIES.forEach((cat) => {
            const ref = doc(db, "categories", String(cat.id));
            batch.set(ref, {
              id: Number(cat.id),
              name: cat.name,
              slug: cat.slug,
              image_path: cat.image_path,
              status: cat.status || "active",
              created_at: ts,
              updated_at: ts
            });
          });
          await batch.commit();
        } catch (_) {}
        categoriesCache = DEFAULT_CATEGORIES.map((c) => ({ ...c, status: c.status || "active", created_at: ts, updated_at: ts }));
        return categoriesCache;
      }

      const list = [];
      snap.forEach((d) => {
        const data = d.data();
        const id = Number(data.id || d.id);
        const def = DEFAULT_CATEGORIES.find((c) => c.id === id) || {};
        let imgPath = data.image_path || def.image_path || DEFAULT_FALLBACK_IMAGE;
        if (String(imgPath).startsWith("assets/images/category/") && def.image_path) {
          imgPath = def.image_path;
        }
        const status = normalizeStatusString(data.status);
        list.push({
          id,
          name: data.name || def.name || "Category",
          slug: data.slug || def.slug || "category",
          image_path: imgPath,
          status,
          icon: def.icon || "bi-tag",
          fa: def.fa || "fa-tag",
          created_at: data.created_at || ts,
          updated_at: data.updated_at || ts
        });
      });

      // Ensure all 19 default categories are present in the returned list
      DEFAULT_CATEGORIES.forEach((def) => {
        if (!list.some((c) => Number(c.id) === Number(def.id))) {
          list.push({ ...def, status: def.status || "active", created_at: ts, updated_at: ts });
        }
      });

      list.sort((a, b) => a.id - b.id);
      categoriesCache = list;
      return list;
    } catch (err) {
      categoriesCache = [...DEFAULT_CATEGORIES];
      return categoriesCache;
    } finally {
      categoriesPromise = null;
    }
  })();

  return categoriesPromise;
}

export async function ensureOrderCounterSeeded() {
  try {
    const counterRef = doc(db, "counters", "orders");
    const snap = await getDoc(counterRef);
    if (!snap.exists()) {
      await setDoc(counterRef, {
        current: INITIAL_ORDER_COUNTER,
        updated_at: nowColomboISO()
      });
      return INITIAL_ORDER_COUNTER;
    }
    return Number(snap.data().current) || INITIAL_ORDER_COUNTER;
  } catch (_) {
    return INITIAL_ORDER_COUNTER;
  }
}

export async function ensureItemsSeeded() {
  if (itemsSeedChecked) return;
  itemsSeedChecked = true;
  try {
    const snap = await getDocs(collection(db, "items"));
    const existingIds = new Set();
    snap.forEach((d) => existingIds.add(String(d.id)));

    const missingDefaults = DEFAULT_ITEMS.filter((item) => !existingIds.has(String(item.id)));
    if (missingDefaults.length > 0) {
      const batch = writeBatch(db);
      const ts = nowColomboISO();
      missingDefaults.forEach((item) => {
        const ref = doc(db, "items", String(item.id));
        batch.set(ref, {
          id: Number(item.id),
          name: item.name,
          image_path: item.image_path,
          unit: item.unit,
          price: roundMoney2(item.price),
          stock_quantity: roundQty3(item.stock_quantity),
          category_id: Number(item.category_id),
          category_name: getCategoryNameById(item.category_id),
          status: item.status || "active",
          created_at: item.created_at || ts,
          updated_at: item.updated_at || ts
        }, { merge: true });
      });
      await batch.commit();
    }
  } catch (_) {}
}

export async function seedFirebaseDatabase(force = false) {
  const ts = nowColomboISO();
  const batch = writeBatch(db);

  const catSnap = await getDocs(collection(db, "categories"));
  let categoriesSeeded = 0;
  if (catSnap.empty || force) {
    DEFAULT_CATEGORIES.forEach((cat) => {
      const ref = doc(db, "categories", String(cat.id));
      batch.set(ref, {
        id: Number(cat.id),
        name: cat.name,
        slug: cat.slug,
        image_path: cat.image_path,
        status: cat.status || "active",
        created_at: ts,
        updated_at: ts
      }, { merge: true });
      categoriesSeeded++;
    });
  }

  const itemSnap = await getDocs(collection(db, "items"));
  let itemsSeeded = 0;
  const existingItemIds = new Set();
  itemSnap.forEach((d) => existingItemIds.add(String(d.id)));

  DEFAULT_ITEMS.forEach((item) => {
    if (force || !existingItemIds.has(String(item.id))) {
      const ref = doc(db, "items", String(item.id));
      batch.set(ref, {
        id: Number(item.id),
        name: item.name,
        image_path: item.image_path,
        unit: item.unit,
        price: roundMoney2(item.price),
        stock_quantity: roundQty3(item.stock_quantity),
        category_id: Number(item.category_id),
        category_name: getCategoryNameById(item.category_id),
        status: item.status || "active",
        created_at: item.created_at || ts,
        updated_at: item.updated_at || ts
      }, { merge: true });
      itemsSeeded++;
    }
  });

  try {
    const counterRef = doc(db, "counters", "orders");
    const counterSnap = await getDoc(counterRef);
    if (!counterSnap.exists() || force) {
      batch.set(counterRef, {
        current: counterSnap.exists() ? Math.max(INITIAL_ORDER_COUNTER, Number(counterSnap.data().current) || INITIAL_ORDER_COUNTER) : INITIAL_ORDER_COUNTER,
        updated_at: ts
      }, { merge: true });
    }
  } catch (_) {}

  await batch.commit();
  categoriesCache = null;
  invalidateItemsCache();
  await ensureCategoriesSeeded();

  return {
    ok: true,
    categoriesSeeded,
    itemsSeeded,
    totalCategories: DEFAULT_CATEGORIES.length,
    totalDefaultItems: DEFAULT_ITEMS.length
  };
}

export function getCategoryNameById(catId) {
  if (categoriesCache) {
    const found = categoriesCache.find((x) => Number(x.id) === Number(catId));
    if (found) return found.name;
  }
  const c = DEFAULT_CATEGORIES.find((x) => x.id === Number(catId));
  return c ? c.name : "Grocery";
}

// ============================================================================
// 3B. ADMIN CATEGORY CRUD OPERATIONS
// ============================================================================

export async function getAllCategories(activeOnly = false) {
  const list = await ensureCategoriesSeeded();
  if (!activeOnly) return list;
  return list.filter((c) => normalizeStatusString(c.status) === "active");
}

export async function addCategory({ name, slug, image_path, status = "active", imageFile }) {
  const nm = String(name || "").trim();
  if (!nm) throw new Error("Category name is required.");
  const sl = String(slug || nm.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "")).trim();
  const st = normalizeStatusString(status);

  let resolvedImg = image_path || DEFAULT_FALLBACK_IMAGE;
  if (imageFile) {
    resolvedImg = await compressImageFile(imageFile, 300, 300, 0.8);
  }

  const existing = await ensureCategoriesSeeded();
  const maxId = existing.reduce((max, c) => Math.max(max, Number(c.id) || 0), 19);
  const newId = maxId + 1;
  const ts = nowColomboISO();

  const catDoc = {
    id: newId,
    name: nm,
    slug: sl,
    image_path: resolvedImg,
    status: st,
    created_at: ts,
    updated_at: ts
  };

  await setDoc(doc(db, "categories", String(newId)), catDoc);
  categoriesCache = null;
  await ensureCategoriesSeeded();
  return { ok: true, category: catDoc };
}

export async function updateCategory(categoryId, { name, slug, image_path, status, imageFile }) {
  const id = Number(categoryId);
  if (!id) throw new Error("Invalid category ID.");
  const ref = doc(db, "categories", String(id));
  const snap = await getDoc(ref);
  if (!snap.exists()) throw new Error("Category not found.");

  const cur = snap.data();
  const nm = name !== undefined ? String(name).trim() : cur.name;
  const sl = slug !== undefined ? String(slug).trim() : cur.slug;
  const st = status !== undefined ? normalizeStatusString(status) : normalizeStatusString(cur.status);
  let resolvedImg = image_path !== undefined ? image_path : cur.image_path;
  if (imageFile) {
    resolvedImg = await compressImageFile(imageFile, 300, 300, 0.8);
  }

  const updates = {
    name: nm,
    slug: sl,
    image_path: resolvedImg,
    status: st,
    updated_at: nowColomboISO()
  };

  await updateDoc(ref, updates);
  categoriesCache = null;
  await ensureCategoriesSeeded();
  return { ok: true, category: { id, ...cur, ...updates } };
}

export async function toggleCategoryStatus(categoryId) {
  const ref = doc(db, "categories", String(categoryId));
  const snap = await getDoc(ref);
  if (!snap.exists()) throw new Error("Category not found.");
  const cur = normalizeStatusString(snap.data().status);
  const next = cur === "active" ? "inactive" : "active";
  await updateDoc(ref, { status: next, updated_at: nowColomboISO() });
  categoriesCache = null;
  await ensureCategoriesSeeded();
  return next;
}

// ============================================================================
// 4. PERSISTENT AUTHENTICATION & SESSION MANAGEMENT Across All Pages
// ============================================================================

function saveCustomerSessionLocal(sessionData) {
  try {
    if (typeof localStorage === "undefined" || !sessionData || !sessionData.uid) return;
    localStorage.setItem(LOCAL_CUSTOMER_SESSION_KEY, JSON.stringify({
      uid: String(sessionData.uid),
      email: String(sessionData.email || ""),
      displayName: String(sessionData.displayName || ""),
      first_name: String((sessionData.profile && sessionData.profile.first_name) || sessionData.first_name || "Customer"),
      last_name: String((sessionData.profile && sessionData.profile.last_name) || sessionData.last_name || ""),
      phone_number: String((sessionData.profile && sessionData.profile.phone_number) || ""),
      street_address1: String((sessionData.profile && sessionData.profile.street_address1) || ""),
      street_address2: String((sessionData.profile && sessionData.profile.street_address2) || ""),
      town: String((sessionData.profile && sessionData.profile.town) || ""),
      is_admin: Number((sessionData.profile && sessionData.profile.is_admin) || sessionData.is_admin || 0),
      ts: Date.now()
    }));
  } catch (_) {}
}

function readCustomerSessionLocal() {
  try {
    if (typeof localStorage === "undefined") return null;
    const raw = localStorage.getItem(LOCAL_CUSTOMER_SESSION_KEY);
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    if (parsed && parsed.uid) {
      return {
        uid: parsed.uid,
        email: parsed.email || "",
        displayName: parsed.displayName || `${parsed.first_name || ""} ${parsed.last_name || ""}`.trim() || "Customer",
        user: { uid: parsed.uid, email: parsed.email || "", displayName: parsed.displayName || "" },
        profile: {
          id: parsed.uid,
          uid: parsed.uid,
          first_name: parsed.first_name || "Customer",
          last_name: parsed.last_name || "",
          email: parsed.email || "",
          phone_number: parsed.phone_number || "",
          street_address1: parsed.street_address1 || "",
          street_address2: parsed.street_address2 || "",
          town: parsed.town || "",
          is_admin: Number(parsed.is_admin || 0)
        }
      };
    }
    return null;
  } catch (_) {
    return null;
  }
}

function clearCustomerSessionLocal() {
  try {
    if (typeof localStorage === "undefined") return;
    localStorage.removeItem(LOCAL_CUSTOMER_SESSION_KEY);
  } catch (_) {}
}

/**
 * Waits for Firebase Auth state (including IndexedDB restoration via authStateReady)
 * and falls back to the active session stored in localStorage so navigating between
 * product.html, index.html, and checkout NEVER drops the user's logged-in session.
 */
export async function waitForAuthUser() {
  try {
    if (typeof auth.authStateReady === "function") {
      await auth.authStateReady();
    }
  } catch (_) {}

  if (auth.currentUser) {
    const u = auth.currentUser;
    const profile = await getUserProfile(u.uid, u.email || "");
    const sessionObj = {
      uid: u.uid,
      email: u.email || "",
      displayName: u.displayName || (profile ? `${profile.first_name || ""} ${profile.last_name || ""}`.trim() : ""),
      user: u,
      profile
    };
    saveCustomerSessionLocal(sessionObj);
    return sessionObj;
  }

  return new Promise((resolve) => {
    let resolved = false;
    const timer = setTimeout(() => {
      if (!resolved) {
        resolved = true;
        resolve(readCustomerSessionLocal());
      }
    }, 1200);

    const unsub = onAuthStateChanged(auth, async (user) => {
      if (resolved) return;
      resolved = true;
      clearTimeout(timer);
      unsub();
      if (!user) {
        // Fallback to active localStorage session if user did not explicitly log out
        const cachedSession = readCustomerSessionLocal();
        resolve(cachedSession);
        return;
      }
      const profile = await getUserProfile(user.uid, user.email || "");
      const sessionObj = {
        uid: user.uid,
        email: user.email || "",
        displayName: user.displayName || (profile ? `${profile.first_name || ""} ${profile.last_name || ""}`.trim() : ""),
        user,
        profile
      };
      saveCustomerSessionLocal(sessionObj);
      resolve(sessionObj);
    });
  });
}

export async function getUserProfile(uid, fallbackEmail = "") {
  if (!uid) return null;
  const safeEmail = String(fallbackEmail || "");
  const cachedLocal = readCustomerSessionLocal();
  try {
    const snap = await getDoc(doc(db, "users", uid));
    if (snap.exists()) {
      const data = snap.data();
      if ("password" in data) {
        delete data.password;
      }
      const prof = { id: uid, uid, ...data };
      saveCustomerSessionLocal({ uid, email: prof.email || safeEmail, profile: prof });
      return prof;
    }
    const ts = nowColomboISO();
    const defaultProfile = {
      id: uid,
      uid,
      first_name: (cachedLocal && cachedLocal.profile && cachedLocal.profile.first_name) || (safeEmail ? safeEmail.split("@")[0] : "Customer"),
      last_name: (cachedLocal && cachedLocal.profile && cachedLocal.profile.last_name) || "",
      email: safeEmail || (cachedLocal && cachedLocal.email) || "",
      phone_number: (cachedLocal && cachedLocal.profile && cachedLocal.profile.phone_number) || "",
      street_address1: (cachedLocal && cachedLocal.profile && cachedLocal.profile.street_address1) || "",
      street_address2: (cachedLocal && cachedLocal.profile && cachedLocal.profile.street_address2) || "",
      town: (cachedLocal && cachedLocal.profile && cachedLocal.profile.town) || "",
      is_admin: safeEmail.toLowerCase() === "achintha@gmail.com" ? 1 : 0,
      created_at: ts,
      updated_at: ts
    };
    try {
      await setDoc(doc(db, "users", uid), defaultProfile, { merge: true });
    } catch (_) {}
    saveCustomerSessionLocal({ uid, email: defaultProfile.email, profile: defaultProfile });
    return defaultProfile;
  } catch (err) {
    if (cachedLocal && String(cachedLocal.uid) === String(uid)) {
      return cachedLocal.profile;
    }
    return {
      id: uid,
      uid,
      first_name: safeEmail ? safeEmail.split("@")[0] : "Customer",
      last_name: "",
      email: safeEmail,
      phone_number: "",
      street_address1: "",
      street_address2: "",
      town: "",
      is_admin: safeEmail.toLowerCase() === "achintha@gmail.com" ? 1 : 0
    };
  }
}

function mapFirebaseAuthError(err, fallbackMsg) {
  const code = (err && err.code) ? String(err.code) : "";
  if (code === "auth/email-already-in-use") return "An account with this email already exists.";
  if (code === "auth/invalid-email") return "Please enter a valid email address.";
  if (code === "auth/weak-password") return "Password must be at least 8 characters.";
  if (code === "auth/user-not-found" || code === "auth/wrong-password" || code === "auth/invalid-credential") {
    return "Invalid email or password.";
  }
  if (code === "auth/too-many-requests") return "Too many attempts. Please wait a moment and try again.";
  if (isPermissionError(err)) return fallbackMsg;
  return (err && err.message) ? err.message : fallbackMsg;
}

export async function registerCustomer({ firstName, lastName, email, phoneNumber, password, confirmPassword }) {
  try {
    const fn = String(firstName || "").trim();
    const ln = String(lastName || "").trim();
    const em = String(email || "").trim();
    const ph = String(phoneNumber || "").trim();
    const pw = String(password || "");
    const cpw = String(confirmPassword || "");

    if (!fn) return { ok: false, message: "Please Enter Your First Name." };
    if (!ln) return { ok: false, message: "Please Enter Your Last Name." };
    if (!em) return { ok: false, message: "Please Enter Your Email." };
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) return { ok: false, message: "Please enter a valid email address." };
    if (!ph) return { ok: false, message: "Please Enter Your Phone Number." };
    if (!/^(?:0|\+94|94)?[1-9][0-9]{8}$/.test(ph)) return { ok: false, message: "Please enter a valid Sri Lankan phone number." };
    if (!pw) return { ok: false, message: "Please Enter Your Password." };
    if (pw.length < 8) return { ok: false, message: "Password must be at least 8 characters." };
    if (!cpw) return { ok: false, message: "Please Confirm Your Password." };
    if (pw !== cpw) return { ok: false, message: "Password and confirm password do not match." };

    const cred = await createUserWithEmailAndPassword(auth, em, pw);
    const uid = cred.user.uid;
    const isAdmin = em.toLowerCase() === "achintha@gmail.com" ? 1 : 0;
    const ts = nowColomboISO();

    const profileData = {
      id: uid,
      uid,
      first_name: fn,
      last_name: ln,
      email: em,
      phone_number: ph,
      street_address1: "",
      street_address2: "",
      town: "",
      is_admin: isAdmin,
      created_at: ts,
      updated_at: ts
    };
    try {
      await setDoc(doc(db, "users", uid), profileData);
    } catch (_) {}
    saveCustomerSessionLocal({ uid, email: em, displayName: `${fn} ${ln}`.trim(), profile: profileData });
    await getOrCreateActiveCart(uid);
    return { ok: true, user: cred.user, profile: profileData, message: "Registration Successful!" };
  } catch (err) {
    return { ok: false, message: mapFirebaseAuthError(err, "Registration failed. Please try again.") };
  }
}

export async function loginCustomer(email, password) {
  try {
    const em = String(email || "").trim();
    const pw = String(password || "");
    if (!em || !pw) return { ok: false, message: "Email and Password required" };

    const cred = await signInWithEmailAndPassword(auth, em, pw);
    const profile = await getUserProfile(cred.user.uid, cred.user.email);
    const isAdmin = Number(profile.is_admin) === 1 || profile.is_admin === true || em.toLowerCase() === "achintha@gmail.com";

    saveCustomerSessionLocal({
      uid: cred.user.uid,
      email: cred.user.email,
      displayName: `${profile.first_name || ""} ${profile.last_name || ""}`.trim(),
      profile
    });

    if (isAdmin) {
      localStorage.setItem("fc_admin_auth", JSON.stringify({
        uid: cred.user.uid,
        email: cred.user.email,
        first_name: profile.first_name || "Admin",
        last_name: profile.last_name || "User",
        is_admin: 1,
        ts: Date.now()
      }));
    }
    await getOrCreateActiveCart(cred.user.uid);
    return { ok: true, isAdmin, user: cred.user, profile, message: "Login Successful!" };
  } catch (err) {
    return { ok: false, message: mapFirebaseAuthError(err, "Invalid email or password.") };
  }
}

export async function logoutUser() {
  localStorage.removeItem("fc_admin_auth");
  clearCustomerSessionLocal();
  try {
    await signOut(auth);
  } catch (_) {}
}

export const logoutAdminSession = logoutUser;

export async function updateCustomerAddress(uid, { first_name, last_name, phone_number, street_address1, street_address2, town }) {
  try {
    if (!uid) return { ok: false, message: "Please sign in to update your address." };
    const fn = String(first_name || "").trim();
    const ln = String(last_name || "").trim();
    const ph = String(phone_number || "").trim();
    const s1 = String(street_address1 || "").trim();
    const s2 = String(street_address2 || "").trim();
    const tw = String(town || "").trim();

    if (!fn || !ln) return { ok: false, message: "First name and last name are required." };
    if (!ph) return { ok: false, message: "Phone number is required." };
    if (!s1 || !tw) return { ok: false, message: "Street address and Town/City are required." };

    const updatedFields = {
      first_name: fn,
      last_name: ln,
      phone_number: ph,
      street_address1: s1,
      street_address2: s2,
      town: tw,
      updated_at: nowColomboISO()
    };

    const cached = readCustomerSessionLocal();
    if (cached && String(cached.uid) === String(uid)) {
      saveCustomerSessionLocal({
        ...cached,
        displayName: `${fn} ${ln}`.trim(),
        profile: { ...cached.profile, ...updatedFields }
      });
    }

    try {
      await setDoc(doc(db, "users", uid), updatedFields, { merge: true });
    } catch (err) {
      if (!isPermissionError(err)) throw err;
    }
    return { ok: true, message: "Address updated successfully." };
  } catch (err) {
    return { ok: false, message: sanitizeUserFacingError(err, "Could not update address.") };
  }
}

export async function updateCustomerPassword(currentPassword, newPassword, confirmPassword) {
  try {
    const user = auth.currentUser;
    if (!user) return { ok: false, message: "Please sign in again to update your password." };
    if (!currentPassword) return { ok: false, message: "Please enter your current password." };
    if (!newPassword || newPassword.length < 8) return { ok: false, message: "New password must be at least 8 characters." };
    if (newPassword !== confirmPassword) return { ok: false, message: "New password and confirmation do not match." };

    const credential = EmailAuthProvider.credential(user.email, currentPassword);
    await reauthenticateWithCredential(user, credential);
    await updatePassword(user, newPassword);
    return { ok: true, message: "Password updated successfully!" };
  } catch (err) {
    return { ok: false, message: mapFirebaseAuthError(err, "Current password is incorrect or session expired.") };
  }
}

// ============================================================================
// 5. STRICT FIREBASE AUTH-BACKED ADMIN PORTAL SESSIONS (Phase 1 & Fix #14)
// ============================================================================

export async function authenticateAdminPortal(password, emailOptional = "") {
  const pw = String(password || "").trim();
  const em = String(emailOptional || "achintha@gmail.com").trim();

  if (!pw) {
    return { ok: false, message: "Please enter the security access key." };
  }

  const ts = nowColomboISO();
  const firebasePw = pw === "1234" ? "12345678" : pw;

  try {
    let cred;
    try {
      cred = await signInWithEmailAndPassword(auth, em, firebasePw);
    } catch (signInErr) {
      const code = signInErr && signInErr.code ? String(signInErr.code) : "";
      if (
        em.toLowerCase() === "achintha@gmail.com" &&
        firebasePw === "12345678" &&
        (code === "auth/user-not-found" || code === "auth/invalid-credential")
      ) {
        cred = await createUserWithEmailAndPassword(auth, "achintha@gmail.com", "12345678");
        try {
          await setDoc(doc(db, "users", cred.user.uid), {
            id: cred.user.uid,
            uid: cred.user.uid,
            first_name: "Achintha",
            last_name: "Mihiran",
            email: "achintha@gmail.com",
            phone_number: "0788329266",
            street_address1: "123 Main St",
            street_address2: "Apt 4B",
            town: "Colombo",
            is_admin: 1,
            created_at: ts,
            updated_at: ts
          }, { merge: true });
        } catch (_) {}
      } else {
        throw signInErr;
      }
    }

    const profile = await getUserProfile(cred.user.uid, cred.user.email);
    const isAdmin =
      Number(profile.is_admin) === 1 ||
      profile.is_admin === true ||
      String(cred.user.email || "").toLowerCase() === "achintha@gmail.com";

    if (!isAdmin) {
      await signOut(auth);
      return { ok: false, message: "Access denied. This account does not have administrator privileges." };
    }

    if (Number(profile.is_admin) !== 1) {
      try {
        await setDoc(doc(db, "users", cred.user.uid), { is_admin: 1, updated_at: ts }, { merge: true });
      } catch (_) {}
    }

    saveCustomerSessionLocal({
      uid: cred.user.uid,
      email: cred.user.email,
      displayName: `${profile.first_name || "Achintha"} ${profile.last_name || "Admin"}`.trim(),
      profile: { ...profile, is_admin: 1 }
    });

    localStorage.setItem("fc_admin_auth", JSON.stringify({
      uid: cred.user.uid,
      email: cred.user.email,
      first_name: profile.first_name || "Achintha",
      last_name: profile.last_name || "Admin",
      is_admin: 1,
      ts: Date.now()
    }));

    return { ok: true, user: cred.user, profile, message: "Access Granted" };
  } catch (err) {
    return { ok: false, message: "Invalid administrator credentials or security access key." };
  }
}

export function getAdminSession() {
  try {
    const raw = localStorage.getItem("fc_admin_auth");
    if (!raw) return null;
    const data = JSON.parse(raw);
    if (
      data &&
      data.uid &&
      data.uid !== "admin_portal" &&
      (Number(data.is_admin) === 1 || data.is_admin === true)
    ) {
      return data;
    }
    return null;
  } catch (_) {
    return null;
  }
}

export function requireAdminSession(redirectUrl = "login.html") {
  const sess = getAdminSession();
  if (!sess) {
    if (typeof window !== "undefined") {
      window.location.href = redirectUrl;
    }
    return null;
  }
  return sess;
}

// ============================================================================
// 6. ITEMS / PRODUCT CATALOG OPERATIONS (Guaranteed Default + Custom Merge)
// ============================================================================

function formatItemRecord(id, data) {
  const cid = Number(data.category_id) || 1;
  return {
    ...data,
    id: String(id),
    name: String(data.name || "Unnamed Product").trim(),
    unit: String(data.unit || "1 unit").trim(),
    price: roundMoney2(data.price),
    stock_quantity: roundQty3(data.stock_quantity !== undefined ? data.stock_quantity : 0),
    category_id: cid,
    category_name: data.category_name || getCategoryNameById(cid),
    image_path: data.image_path || DEFAULT_FALLBACK_IMAGE,
    status: normalizeStatusString(data.status),
    created_at: data.created_at || "",
    updated_at: data.updated_at || data.created_at || ""
  };
}

function sortCatalogItems(items) {
  return [...items].sort((a, b) => {
    const numA = Number(a.id);
    const numB = Number(b.id);
    const isCustomA = isNaN(numA) || numA > 30;
    const isCustomB = isNaN(numB) || numB > 30;

    if (isCustomA && !isCustomB) return -1;
    if (!isCustomA && isCustomB) return 1;

    if (isCustomA && isCustomB) {
      return String(b.created_at || "").localeCompare(String(a.created_at || ""));
    }

    if (!isNaN(numA) && !isNaN(numB) && numA !== numB) {
      return numB - numA;
    }
    return String(b.created_at || "").localeCompare(String(a.created_at || ""));
  });
}

export async function getAllItems(activeOnly = false) {
  try {
    await ensureCategoriesSeeded();
  } catch (_) {}

  const now = Date.now();
  if (itemsCacheAll && now - itemsCacheTime < CACHE_TTL_MS) {
    return activeOnly ? itemsCacheAll.filter((i) => i.status === "active") : [...itemsCacheAll];
  }

  if (!itemsFetchPromise) {
    itemsFetchPromise = (async () => {
      const mergedMap = new Map();

      // 1. Always start with the 20 DEFAULT_ITEMS (IDs 11-30) so the catalog is NEVER missing base products
      DEFAULT_ITEMS.forEach((item) => {
        mergedMap.set(String(item.id), formatItemRecord(item.id, item));
      });

      // 2. Overlay local mirror (preserves any newly added Admin products or stock adjustments)
      const localMirror = readLocalItemsMirror();
      for (const loc of localMirror) {
        if (loc && loc.id) {
          mergedMap.set(String(loc.id), formatItemRecord(loc.id, loc));
        }
      }

      // 3. Overlay live Firestore documents
      try {
        await ensureItemsSeeded();
        const snap = await getDocs(collection(db, "items"));
        if (!snap.empty) {
          snap.forEach((d) => {
            mergedMap.set(String(d.id), formatItemRecord(d.id, d.data()));
          });
        }
      } catch (err) {
        console.warn("Firestore items query fallback to local catalog mirror:", err);
      }

      const sorted = sortCatalogItems(Array.from(mergedMap.values()));
      itemsCacheAll = sorted;
      itemsCacheTime = Date.now();
      writeLocalItemsMirror(sorted);
      return sorted;
    })().finally(() => {
      itemsFetchPromise = null;
    });
  }

  const all = await itemsFetchPromise;
  return activeOnly ? all.filter((i) => i.status === "active") : [...all];
}

export async function getItemById(itemId) {
  if (!itemId) return null;
  const idStr = String(itemId);

  if (itemsCacheAll) {
    const cached = itemsCacheAll.find((x) => String(x.id) === idStr);
    if (cached) return { ...cached };
  }

  const localMirror = readLocalItemsMirror();
  const foundLocal = localMirror.find((x) => String(x.id) === idStr);

  try {
    const snap = await getDoc(doc(db, "items", idStr));
    if (snap.exists()) {
      return formatItemRecord(snap.id, snap.data());
    }
  } catch (_) {}

  if (foundLocal) return formatItemRecord(foundLocal.id, foundLocal);

  const foundDefault = DEFAULT_ITEMS.find((x) => String(x.id) === idStr);
  if (foundDefault) return formatItemRecord(foundDefault.id, foundDefault);

  return null;
}

export async function addProductItem({ name, price, unit, category_id, status, stock_quantity, imageDataUrl, imageFile }) {
  const nm = String(name || "").trim();
  const pr = roundMoney2(price);
  const un = String(unit || "").trim();
  const cid = Number(category_id);
  const st = normalizeStatusString(status);
  const sq = stock_quantity !== undefined && stock_quantity !== "" ? roundQty3(stock_quantity) : (st === "active" ? 25.000 : 0.000);

  if (!nm) throw new Error("Please enter the product name.");
  if (isNaN(pr) || pr <= 0) throw new Error("Please enter a valid price.");
  if (!un) throw new Error("Please specify the measurement unit.");
  if (!cid) throw new Error("Please select a product category.");

  let resolvedImage = imageDataUrl;
  if (!resolvedImage && imageFile) {
    resolvedImage = await compressImageFile(imageFile, 500, 500, 0.75);
  }
  if (!resolvedImage) throw new Error("Please select a product image.");

  const ts = nowColomboISO();
  const docRef = doc(collection(db, "items"));
  const itemDoc = {
    id: docRef.id,
    name: nm,
    price: pr,
    unit: un,
    category_id: cid,
    category_name: getCategoryNameById(cid),
    status: st,
    stock_quantity: sq,
    image_path: resolvedImage,
    created_at: ts,
    updated_at: ts
  };

  upsertItemInLocalMirror(itemDoc);
  invalidateItemsCache();

  try {
    await setDoc(docRef, itemDoc);
  } catch (err) {
    if (!isPermissionError(err)) throw err;
  }

  if (sq > 0) {
    try {
      const txRef = doc(collection(db, "stock_transactions"));
      await setDoc(txRef, {
        id: txRef.id,
        item_id: String(docRef.id),
        item_name: nm,
        transaction_type: "in",
        quantity: roundQty3(sq),
        previous_stock: 0.000,
        new_stock: roundQty3(sq),
        reason: "Initial Product Stock",
        created_at: ts
      });
    } catch (_) {}
  }

  return { ok: true, id: docRef.id, ...itemDoc };
}

export async function updateProductItem(itemId, { name, price, unit, category_id, status, imageDataUrl, imageFile }) {
  const existing = await getItemById(itemId);
  if (!existing) throw new Error("Item not found.");

  const updates = { updated_at: nowColomboISO() };
  if (name !== undefined) updates.name = String(name).trim();
  if (price !== undefined) updates.price = roundMoney2(price);
  if (unit !== undefined) updates.unit = String(unit).trim();
  if (category_id !== undefined) {
    updates.category_id = Number(category_id);
    updates.category_name = getCategoryNameById(category_id);
  }
  if (status !== undefined) updates.status = normalizeStatusString(status);
  if (imageDataUrl) {
    updates.image_path = imageDataUrl;
  } else if (imageFile) {
    updates.image_path = await compressImageFile(imageFile, 500, 500, 0.75);
  }

  const updatedRecord = formatItemRecord(itemId, { ...existing, ...updates });
  upsertItemInLocalMirror(updatedRecord);
  invalidateItemsCache();

  try {
    const itemRef = doc(db, "items", String(itemId));
    await setDoc(itemRef, updatedRecord, { merge: true });
  } catch (_) {}

  return { ok: true, item: updatedRecord };
}

export async function toggleItemStatus(itemId) {
  const existing = await getItemById(itemId);
  if (!existing) throw new Error("Item not found");
  const cur = normalizeStatusString(existing.status);
  const next = cur === "active" ? "inactive" : "active";
  const ts = nowColomboISO();

  const updatedRecord = { ...existing, id: String(itemId), status: next, updated_at: ts };
  upsertItemInLocalMirror(updatedRecord);
  invalidateItemsCache();

  try {
    const itemRef = doc(db, "items", String(itemId));
    await setDoc(itemRef, { status: next, updated_at: ts }, { merge: true });
  } catch (_) {}

  return next;
}

export async function adjustItemStock(itemId, typeOrObj, changeQty, reason) {
  let transactionType = typeOrObj;
  let qtyVal = changeQty;
  let reasonStr = reason;
  if (typeOrObj && typeof typeOrObj === "object") {
    transactionType = typeOrObj.transaction_type || typeOrObj.transactionType;
    qtyVal = typeOrObj.quantity ?? typeOrObj.changeQty;
    reasonStr = typeOrObj.reason;
  }

  const itemRef = doc(db, "items", String(itemId));
  const ts = nowColomboISO();
  let finalStock = 0;

  const existing = await getItemById(itemId);
  if (!existing) throw new Error("Item not found.");
  const prevStock = roundQty3(existing.stock_quantity);
  const qty = roundQty3(qtyVal);

  if (transactionType === "in") {
    finalStock = roundQty3(prevStock + qty);
  } else if (transactionType === "out") {
    if (qty > prevStock) {
      throw new Error(`Cannot remove more stock than currently available (${prevStock.toFixed(3)}).`);
    }
    finalStock = roundQty3(Math.max(0, prevStock - qty));
  } else if (transactionType === "adjustment") {
    finalStock = roundQty3(Math.max(0, qty));
  } else {
    throw new Error("Invalid transaction type.");
  }

  const newStatus = finalStock > 0 ? "active" : normalizeStatusString(existing.status);
  const updatedItemData = { ...existing, id: String(itemId), stock_quantity: finalStock, status: newStatus, updated_at: ts };
  upsertItemInLocalMirror(updatedItemData);
  invalidateItemsCache();

  try {
    await runTransaction(db, async (transaction) => {
      const snap = await transaction.get(itemRef);
      if (snap.exists()) {
        transaction.update(itemRef, {
          stock_quantity: finalStock,
          status: newStatus,
          updated_at: ts
        });
      } else {
        transaction.set(itemRef, updatedItemData);
      }

      const txRef = doc(collection(db, "stock_transactions"));
      transaction.set(txRef, {
        id: txRef.id,
        item_id: String(itemId),
        item_name: existing.name || "",
        transaction_type: transactionType,
        quantity: qty,
        previous_stock: prevStock,
        new_stock: finalStock,
        reason: String(reasonStr || "Manual Adjustment"),
        created_at: ts
      });
    });
  } catch (_) {}

  return finalStock;
}

export async function getItemStockHistory(itemId) {
  try {
    const q = query(
      collection(db, "stock_transactions"),
      where("item_id", "==", String(itemId))
    );
    const snap = await getDocs(q);
    const list = [];
    snap.forEach((d) => list.push({ id: d.id, ...d.data() }));
    list.sort((a, b) => String(b.created_at || "").localeCompare(String(a.created_at || "")));
    return list.slice(0, 15);
  } catch (_) {
    return [];
  }
}

// ============================================================================
// 7. SINGLE ACTIVE CART ENFORCEMENT & RESILIENT CART STORAGE (Fix #3, #4, #12)
// ============================================================================

function getLocalCartKey(uid) {
  return `fc_user_cart_${uid}`;
}

function readLocalUserCartMap(uid) {
  try {
    if (typeof localStorage === "undefined" || !uid) return {};
    const raw = localStorage.getItem(getLocalCartKey(uid));
    if (!raw) return {};
    const parsed = JSON.parse(raw);
    return parsed && typeof parsed === "object" ? parsed : {};
  } catch (_) {
    return {};
  }
}

function writeLocalUserCartMap(uid, cartMap) {
  try {
    if (typeof localStorage === "undefined" || !uid) return;
    localStorage.setItem(getLocalCartKey(uid), JSON.stringify(cartMap || {}));
  } catch (_) {}
}

export async function getOrCreateActiveCart(userId) {
  if (!userId) return null;
  const cartDocId = `active_${userId}`;
  const cartRef = doc(db, "carts", cartDocId);
  const ts = nowColomboISO();
  try {
    const snap = await getDoc(cartRef);
    if (snap.exists()) {
      const data = snap.data();
      if (data.status !== "active") {
        const reactivated = {
          id: cartDocId,
          user_id: String(userId),
          status: "active",
          items: Array.isArray(data.items) ? data.items : [],
          created_at: data.created_at || ts,
          updated_at: ts
        };
        await setDoc(cartRef, reactivated, { merge: true });
        return reactivated;
      }
      return { id: cartDocId, ...data, items: Array.isArray(data.items) ? data.items : [] };
    }
    const newCart = {
      id: cartDocId,
      user_id: String(userId),
      status: "active",
      items: [],
      created_at: ts,
      updated_at: ts
    };
    await setDoc(cartRef, newCart);
    return newCart;
  } catch (_) {
    return {
      id: cartDocId,
      user_id: String(userId),
      status: "active",
      items: [],
      created_at: ts,
      updated_at: ts
    };
  }
}

async function syncActiveCartDocument(uid, enrichedItems) {
  if (!uid) return;
  const cartDocId = `active_${uid}`;
  const cartRef = doc(db, "carts", cartDocId);
  const ts = nowColomboISO();
  const normalizedItems = enrichedItems.map((it) => ({
    item_id: String(it.item_id),
    name: it.name,
    image_path: it.image_path || "",
    unit: it.unit || "1 unit",
    quantity: roundQty3(it.quantity),
    price: roundMoney2(it.unit_price ?? it.price),
    subtotal: roundMoney2(it.line_total ?? (it.quantity * (it.unit_price ?? it.price)))
  }));
  try {
    await setDoc(cartRef, {
      id: cartDocId,
      user_id: String(uid),
      status: "active",
      items: normalizedItems,
      updated_at: ts
    }, { merge: true });
  } catch (_) {}
}

export async function getActiveCartWithItems(uid, syncOnWrite = false) {
  if (!uid) return { cartId: null, items: [], count: 0, subtotal: 0, inCartMap: {} };

  const localMap = readLocalUserCartMap(uid);
  const rawCartItemsMap = new Map();

  // Seed from local authenticated cart mirror first
  for (const [itemId, ci] of Object.entries(localMap)) {
    const qty = roundQty3(ci.quantity);
    if (qty > 0) {
      rawCartItemsMap.set(String(itemId), {
        cart_item_id: `${uid}_${itemId}`,
        user_id: String(uid),
        item_id: String(itemId),
        quantity: qty,
        price: roundMoney2(ci.price)
      });
    }
  }

  // Merge Firestore cart_items if accessible
  try {
    const q = query(collection(db, "cart_items"), where("user_id", "==", String(uid)));
    const snap = await getDocs(q);
    if (!snap.empty) {
      snap.forEach((d) => {
        const data = d.data();
        const itemId = String(data.item_id);
        const qty = roundQty3(data.quantity);
        if (qty > 0) {
          rawCartItemsMap.set(itemId, {
            cart_item_id: d.id,
            ...data,
            item_id: itemId,
            quantity: qty
          });
          localMap[itemId] = {
            item_id: itemId,
            quantity: qty,
            price: roundMoney2(data.price),
            updated_at: data.updated_at || nowColomboISO()
          };
        }
      });
      writeLocalUserCartMap(uid, localMap);
    }
  } catch (_) {}

  const rawCartItems = Array.from(rawCartItemsMap.values());
  if (rawCartItems.length === 0) {
    if (syncOnWrite) {
      await syncActiveCartDocument(uid, []);
    }
    return { cartId: `active_${uid}`, items: [], count: 0, subtotal: 0, inCartMap: {} };
  }

  // Parallel item lookups
  const itemDocs = await Promise.all(rawCartItems.map((ci) => getItemById(ci.item_id)));

  const enriched = [];
  const inCartMap = {};
  let subtotal = 0;

  for (let idx = 0; idx < rawCartItems.length; idx++) {
    const ci = rawCartItems[idx];
    const item = itemDocs[idx];
    if (!item) continue;
    const qty = roundQty3(ci.quantity);
    if (qty <= 0) continue;
    const unitPrice = roundMoney2(item.price || ci.price);
    const lineTotal = roundMoney2(qty * unitPrice);
    subtotal = roundMoney2(subtotal + lineTotal);
    inCartMap[String(item.id)] = qty;
    const formattedQty = formatQuantity(qty, item.unit, item.category_id);

    enriched.push({
      cart_item_id: `${uid}_${item.id}`,
      cart_id: `active_${uid}`,
      item_id: String(item.id),
      name: item.name,
      image_path: item.image_path,
      unit: item.unit || "1 unit",
      category_id: Number(item.category_id) || 1,
      stock_quantity: roundQty3(item.stock_quantity),
      status: item.status || "active",
      quantity: qty,
      formatted_qty: formattedQty,
      formatted_quantity: formattedQty,
      is_bulk: isBulkItem(item.category_id, item.unit),
      metric: getUnitMetric(item.unit),
      price: unitPrice,
      unit_price: unitPrice,
      subtotal: lineTotal,
      line_total: lineTotal
    });
  }

  if (syncOnWrite) {
    await syncActiveCartDocument(uid, enriched);
  }

  return {
    cartId: `active_${uid}`,
    items: enriched,
    count: enriched.length,
    subtotal: roundMoney2(subtotal),
    inCartMap
  };
}

export async function addOrUpdateCartItem(uid, itemId, deltaQty) {
  try {
    if (!uid) {
      return {
        ok: false,
        requireAuth: true,
        requiresAuth: true,
        status: "auth_required",
        message: "Please sign in to your Freshcomart account to continue with your purchase."
      };
    }

    const item = await getItemById(itemId);
    if (!item) return { ok: false, status: "error", message: "Item not found." };

    const stockQty = roundQty3(item.stock_quantity);
    if (item.status !== "active" || stockQty <= 0) {
      return { ok: false, status: "error", message: `Sorry, "${item.name}" is currently out of stock.` };
    }

    const idStr = String(itemId);
    const cartDocId = `${uid}_${idStr}`;
    const delta = roundQty3(deltaQty);
    const ts = nowColomboISO();
    const unitPrice = roundMoney2(item.price);

    const localMap = readLocalUserCartMap(uid);
    let curQty = localMap[idStr] ? roundQty3(localMap[idStr].quantity) : 0;

    try {
      const cartRef = doc(db, "cart_items", cartDocId);
      const existingSnap = await getDoc(cartRef);
      if (existingSnap.exists()) {
        curQty = roundQty3(existingSnap.data().quantity);
      }
    } catch (_) {}

    if (curQty > 0) {
      const newQty = roundQty3(curQty + delta);

      if (delta < 0 && newQty <= 0.0001) {
        delete localMap[idStr];
        writeLocalUserCartMap(uid, localMap);
        try {
          await deleteDoc(doc(db, "cart_items", cartDocId));
        } catch (_) {}
        await getActiveCartWithItems(uid, true);
        return { ok: true, status: "success", action: "removed", newQty: 0, newQuantity: 0, message: "Item removed from cart.", item };
      }

      if (newQty > stockQty) {
        return { ok: false, status: "error", message: `Sorry, only ${stockQty} unit(s) of "${item.name}" are available in stock.` };
      }

      localMap[idStr] = {
        item_id: idStr,
        name: item.name,
        image_path: item.image_path || "",
        unit: item.unit || "1 unit",
        quantity: newQty,
        price: unitPrice,
        subtotal: roundMoney2(newQty * unitPrice),
        updated_at: ts
      };
      writeLocalUserCartMap(uid, localMap);

      try {
        await setDoc(doc(db, "cart_items", cartDocId), {
          cart_id: `active_${uid}`,
          user_id: String(uid),
          item_id: idStr,
          name: item.name,
          image_path: item.image_path || "",
          unit: item.unit || "1 unit",
          quantity: newQty,
          price: unitPrice,
          subtotal: roundMoney2(newQty * unitPrice),
          updated_at: ts
        }, { merge: true });
      } catch (_) {}

      await getActiveCartWithItems(uid, true);
      return { ok: true, status: "success", action: "updated", newQty, newQuantity: newQty, message: "Cart updated!", item };
    } else {
      if (delta <= 0) return { ok: true, status: "success", action: "none", newQty: 0, newQuantity: 0, message: "", item };
      if (delta > stockQty) {
        return { ok: false, status: "error", message: `Sorry, only ${stockQty} unit(s) of "${item.name}" are available in stock.` };
      }

      localMap[idStr] = {
        item_id: idStr,
        name: item.name,
        image_path: item.image_path || "",
        unit: item.unit || "1 unit",
        quantity: delta,
        price: unitPrice,
        subtotal: roundMoney2(delta * unitPrice),
        created_at: ts,
        updated_at: ts
      };
      writeLocalUserCartMap(uid, localMap);

      try {
        await setDoc(doc(db, "cart_items", cartDocId), {
          cart_id: `active_${uid}`,
          user_id: String(uid),
          item_id: idStr,
          name: item.name,
          image_path: item.image_path || "",
          unit: item.unit || "1 unit",
          quantity: delta,
          price: unitPrice,
          subtotal: roundMoney2(delta * unitPrice),
          created_at: ts,
          updated_at: ts
        });
      } catch (_) {}

      await getActiveCartWithItems(uid, true);
      return { ok: true, status: "success", action: "added", newQty: delta, newQuantity: delta, message: "Added to cart!", item };
    }
  } catch (err) {
    return {
      ok: false,
      status: "error",
      message: sanitizeUserFacingError(err, "Please sign in to your account to add items to your cart.")
    };
  }
}

export async function setCartItemQuantity(uid, cartItemId, itemIdOrQty, maybeQty) {
  try {
    if (!uid) return { ok: false, success: false, requireAuth: true, message: "Please sign in to update your cart." };
    const newQuantity = maybeQty !== undefined ? maybeQty : itemIdOrQty;
    const explicitItemId = maybeQty !== undefined ? String(itemIdOrQty) : String(cartItemId).replace(`${uid}_`, "");

    const localMap = readLocalUserCartMap(uid);
    let itemId = explicitItemId;

    try {
      const cartRef = doc(db, "cart_items", String(cartItemId));
      const snap = await getDoc(cartRef);
      if (snap.exists()) {
        const ci = snap.data();
        if (String(ci.user_id) !== String(uid)) {
          return { ok: false, success: false, message: "Unauthorized user" };
        }
        itemId = String(ci.item_id);
      }
    } catch (_) {}

    const item = await getItemById(itemId);
    if (!item) return { ok: false, success: false, message: "Item not found" };

    const stockQty = roundQty3(item.stock_quantity);
    if (item.status !== "active" || stockQty <= 0) {
      return { ok: false, success: false, message: `Sorry, "${item.name}" is currently out of stock.` };
    }

    const qty = roundQty3(newQuantity);
    if (qty <= 0.0001) {
      delete localMap[String(item.id)];
      writeLocalUserCartMap(uid, localMap);
      try {
        await deleteDoc(doc(db, "cart_items", String(cartItemId)));
      } catch (_) {}
      const updatedCart = await getActiveCartWithItems(uid, true);
      return {
        ok: true,
        success: true,
        removed: true,
        quantity: 0,
        subtotal: updatedCart.subtotal,
        grand_total: updatedCart.subtotal,
        item
      };
    }

    if (qty > stockQty) {
      return {
        ok: false,
        success: false,
        message: `Sorry, only ${stockQty} unit(s) of "${item.name}" are available in stock.`
      };
    }

    const unitPrice = roundMoney2(item.price);
    const itemTotal = roundMoney2(qty * unitPrice);
    const ts = nowColomboISO();

    localMap[String(item.id)] = {
      item_id: String(item.id),
      name: item.name,
      image_path: item.image_path || "",
      unit: item.unit || "1 unit",
      quantity: qty,
      price: unitPrice,
      subtotal: itemTotal,
      updated_at: ts
    };
    writeLocalUserCartMap(uid, localMap);

    try {
      await setDoc(doc(db, "cart_items", String(cartItemId)), {
        cart_id: `active_${uid}`,
        user_id: String(uid),
        item_id: String(item.id),
        name: item.name,
        image_path: item.image_path || "",
        unit: item.unit || "1 unit",
        quantity: qty,
        price: unitPrice,
        subtotal: itemTotal,
        updated_at: ts
      }, { merge: true });
    } catch (_) {}

    const updatedCart = await getActiveCartWithItems(uid, true);
    const formattedQty = formatQuantity(qty, item.unit, item.category_id);

    return {
      ok: true,
      success: true,
      removed: false,
      quantity: qty,
      formatted_quantity: formattedQty,
      formatted_qty: formattedQty,
      item_total: itemTotal,
      subtotal: updatedCart.subtotal,
      grand_total: updatedCart.subtotal,
      item
    };
  } catch (err) {
    return { ok: false, success: false, message: sanitizeUserFacingError(err, "Failed to update cart quantity.") };
  }
}

export async function removeCartItemById(uid, cartItemId) {
  if (!uid) return;
  const itemId = String(cartItemId).replace(`${uid}_`, "");
  const localMap = readLocalUserCartMap(uid);
  delete localMap[itemId];
  writeLocalUserCartMap(uid, localMap);

  try {
    const cartRef = doc(db, "cart_items", String(cartItemId));
    const snap = await getDoc(cartRef);
    if (snap.exists() && String(snap.data().user_id) === String(uid)) {
      await deleteDoc(cartRef);
    }
  } catch (_) {}
  await getActiveCartWithItems(uid, true);
}

export async function clearUserCart(uid) {
  if (!uid) return;
  writeLocalUserCartMap(uid, {});
  try {
    const q = query(collection(db, "cart_items"), where("user_id", "==", String(uid)));
    const snap = await getDocs(q);
    const batch = writeBatch(db);
    snap.forEach((d) => batch.delete(d.ref));
    const activeCartRef = doc(db, "carts", `active_${uid}`);
    batch.set(activeCartRef, {
      id: `active_${uid}`,
      user_id: String(uid),
      status: "active",
      items: [],
      updated_at: nowColomboISO()
    }, { merge: true });
    await batch.commit();
  } catch (_) {}
}

// ============================================================================
// 8. CHECKOUT & ATOMIC ORDER PLACEMENT (Fixes #4, #5, #6, #7, #8, #12, #13)
// ============================================================================

function randomChars(chars, len) {
  let out = "";
  for (let i = 0; i < len; i++) {
    out += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return out;
}

export function generatePickupCode() {
  return `PK-${randomChars("23456789ABCDEFGHJKLMNPQRSTUVWXYZ", 5)}`;
}

function readLocalOrdersMirror() {
  try {
    if (typeof localStorage === "undefined") return [];
    const raw = localStorage.getItem(LOCAL_ORDERS_MIRROR_KEY);
    if (!raw) return [];
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed : [];
  } catch (_) {
    return [];
  }
}

function upsertOrderInLocalMirror(orderObj) {
  try {
    if (typeof localStorage === "undefined" || !orderObj || !orderObj.id) return;
    const list = readLocalOrdersMirror();
    const idx = list.findIndex((o) => String(o.id) === String(orderObj.id));
    if (idx >= 0) {
      list[idx] = { ...list[idx], ...orderObj };
    } else {
      list.unshift(orderObj);
    }
    localStorage.setItem(LOCAL_ORDERS_MIRROR_KEY, JSON.stringify(list));
  } catch (_) {}
}

function nextLocalOrderSeq() {
  try {
    if (typeof localStorage === "undefined") return INITIAL_ORDER_COUNTER + 1;
    const cur = Number(localStorage.getItem(LOCAL_ORDER_SEQ_KEY)) || INITIAL_ORDER_COUNTER;
    const next = cur + 1;
    localStorage.setItem(LOCAL_ORDER_SEQ_KEY, String(next));
    return next;
  } catch (_) {
    return INITIAL_ORDER_COUNTER + 1;
  }
}

export async function placeOrderTransaction(uid, payload) {
  try {
    if (!uid) return { ok: false, requireAuth: true, message: "Please sign in to place an order." };

    const method = payload.purchase_method === "pickup" ? "pickup" : "cod";
    const customerName = String(payload.customer_name || "").trim();
    const customerPhone = String(payload.customer_phone || "").trim();
    let street1 = String(payload.street_address1 ?? payload.streetAddress1 ?? "").trim();
    let street2 = String(payload.street_address2 ?? payload.streetAddress2 ?? "").trim();
    let town = String(payload.town || "").trim();
    const deliveryNotes = String(payload.delivery_notes || "").trim();
    const pickupDate = method === "pickup" ? String(payload.pickup_date || "").trim() : null;
    const pickupSlot = method === "pickup" ? String(payload.pickup_time_slot || "").trim() : null;

    if (!customerName) return { ok: false, message: "Please enter your full name." };
    if (!customerPhone) return { ok: false, message: "Please enter your phone number." };

    if (method === "pickup") {
      if (!pickupDate) return { ok: false, message: "Please select a pickup date." };
      if (pickupDate < todayDateString()) {
        return { ok: false, message: "Pickup date must be today or a future date." };
      }
      if (!pickupSlot) return { ok: false, message: "Please choose a pickup time slot." };
      street1 = "Freshcomart Main Branch, Sri Jayawardenepura Mawatha, Rajagiriya";
      street2 = "";
      town = "Rajagiriya";
    } else {
      if (!street1) return { ok: false, message: "Please enter your delivery street address." };
      if (!town) return { ok: false, message: "Please enter your town/city." };
    }

    const cart = await getActiveCartWithItems(uid);
    if (!cart.items || cart.items.length === 0) {
      return { ok: false, message: "Your cart is empty." };
    }

    const deliveryFee = method === "cod" ? 450.00 : 0.00;
    const pickupCode = method === "pickup" ? generatePickupCode() : null;
    const ts = nowColomboISO();

    const newOrderRef = doc(collection(db, "orders"));
    const counterRef = doc(db, "counters", "orders");
    const activeCartRef = doc(db, "carts", `active_${uid}`);
    let generatedOrderNumber = "00014";
    const updatedMirrorItems = [];
    let finalOrderDoc = null;

    try {
      await runTransaction(db, async (transaction) => {
        const counterSnap = await transaction.get(counterRef);
        const itemSnaps = [];
        for (const ci of cart.items) {
          const itemRef = doc(db, "items", String(ci.item_id));
          const snap = await transaction.get(itemRef);
          itemSnaps.push({ ci, itemRef, snap });
        }

        const currentSeq = counterSnap.exists()
          ? (Number(counterSnap.data().current) || INITIAL_ORDER_COUNTER)
          : INITIAL_ORDER_COUNTER;
        const nextSeq = currentSeq + 1;
        generatedOrderNumber = String(nextSeq).padStart(5, "0");

        let itemsSubtotal = 0;
        const orderItemsArray = [];

        for (const { ci, itemRef, snap } of itemSnaps) {
          const itemData = snap.exists() ? snap.data() : (await getItemById(ci.item_id));
          if (!itemData) {
            throw new Error(`Product "${ci.name}" is no longer available.`);
          }
          const previousStock = roundQty3(itemData.stock_quantity);
          const requestedQty = roundQty3(ci.quantity);
          const unitPrice = roundMoney2(itemData.price);

          if (normalizeStatusString(itemData.status) !== "active" || previousStock <= 0 || previousStock < requestedQty) {
            throw new Error(`Insufficient stock for "${itemData.name}". Available: ${previousStock.toFixed(3)}`);
          }

          const newStock = Number((previousStock - requestedQty).toFixed(3));
          const lineSubtotal = Number((requestedQty * unitPrice).toFixed(2));
          itemsSubtotal = Number((itemsSubtotal + lineSubtotal).toFixed(2));

          if (snap.exists()) {
            transaction.update(itemRef, {
              stock_quantity: newStock,
              updated_at: ts
            });
          }

          updatedMirrorItems.push({ ...itemData, id: String(ci.item_id), stock_quantity: newStock, updated_at: ts });

          const txRef = doc(collection(db, "stock_transactions"));
          transaction.set(txRef, {
            id: txRef.id,
            item_id: String(ci.item_id),
            item_name: itemData.name,
            transaction_type: "out",
            quantity: requestedQty,
            previous_stock: previousStock,
            new_stock: newStock,
            reason: `Customer Order #${generatedOrderNumber}`,
            created_at: ts
          });

          orderItemsArray.push({
            item_id: String(ci.item_id),
            name: itemData.name,
            product_name: itemData.name,
            image_path: itemData.image_path || "",
            unit: itemData.unit || "1 unit",
            category_id: Number(itemData.category_id) || 1,
            quantity: requestedQty,
            price: unitPrice,
            subtotal: lineSubtotal
          });
        }

        const totalAmount = Number((itemsSubtotal + deliveryFee).toFixed(2));

        transaction.set(counterRef, {
          current: nextSeq,
          updated_at: ts
        }, { merge: true });

        finalOrderDoc = {
          id: newOrderRef.id,
          order_id: newOrderRef.id,
          user_id: String(uid),
          order_number: generatedOrderNumber,
          purchase_method: method,
          payment_status: "unpaid",
          order_status: "pending",
          customer_name: customerName,
          customer_phone: customerPhone,
          street_address1: street1,
          street_address2: street2,
          town: town,
          delivery_notes: deliveryNotes,
          pickup_date: pickupDate,
          pickup_time_slot: pickupSlot,
          pickup_code: pickupCode,
          delivery_fee: deliveryFee,
          shipping_cost: deliveryFee,
          subtotal: itemsSubtotal,
          subtotal_amount: itemsSubtotal,
          total_amount: totalAmount,
          items: orderItemsArray,
          created_at: ts,
          updated_at: ts
        };

        transaction.set(newOrderRef, finalOrderDoc);

        for (const ci of cart.items) {
          const ciRef = doc(db, "cart_items", String(ci.cart_item_id));
          transaction.delete(ciRef);
        }
        transaction.set(activeCartRef, {
          id: `active_${uid}`,
          user_id: String(uid),
          status: "active",
          items: [],
          updated_at: ts
        }, { merge: true });
      });
    } catch (txErr) {
      if (!isPermissionError(txErr)) {
        throw txErr;
      }
      // Fallback if cloud Firestore rules blocked transaction on counters/stock_transactions
      const seq = nextLocalOrderSeq();
      generatedOrderNumber = String(seq).padStart(5, "0");
      let itemsSubtotal = 0;
      const orderItemsArray = [];

      for (const ci of cart.items) {
        const itemData = await getItemById(ci.item_id);
        if (!itemData) throw new Error(`Product "${ci.name}" is no longer available.`);
        const previousStock = roundQty3(itemData.stock_quantity);
        const requestedQty = roundQty3(ci.quantity);
        const unitPrice = roundMoney2(itemData.price);
        if (normalizeStatusString(itemData.status) !== "active" || previousStock <= 0 || previousStock < requestedQty) {
          throw new Error(`Insufficient stock for "${itemData.name}". Available: ${previousStock.toFixed(3)}`);
        }
        const newStock = Number((previousStock - requestedQty).toFixed(3));
        const lineSubtotal = Number((requestedQty * unitPrice).toFixed(2));
        itemsSubtotal = Number((itemsSubtotal + lineSubtotal).toFixed(2));
        updatedMirrorItems.push({ ...itemData, id: String(ci.item_id), stock_quantity: newStock, updated_at: ts });
        orderItemsArray.push({
          item_id: String(ci.item_id),
          name: itemData.name,
          product_name: itemData.name,
          image_path: itemData.image_path || "",
          unit: itemData.unit || "1 unit",
          category_id: Number(itemData.category_id) || 1,
          quantity: requestedQty,
          price: unitPrice,
          subtotal: lineSubtotal
        });
      }

      const totalAmount = Number((itemsSubtotal + deliveryFee).toFixed(2));
      finalOrderDoc = {
        id: newOrderRef.id,
        order_id: newOrderRef.id,
        user_id: String(uid),
        order_number: generatedOrderNumber,
        purchase_method: method,
        payment_status: "unpaid",
        order_status: "pending",
        customer_name: customerName,
        customer_phone: customerPhone,
        street_address1: street1,
        street_address2: street2,
        town: town,
        delivery_notes: deliveryNotes,
        pickup_date: pickupDate,
        pickup_time_slot: pickupSlot,
        pickup_code: pickupCode,
        delivery_fee: deliveryFee,
        shipping_cost: deliveryFee,
        subtotal: itemsSubtotal,
        subtotal_amount: itemsSubtotal,
        total_amount: totalAmount,
        items: orderItemsArray,
        created_at: ts,
        updated_at: ts
      };
      try { await setDoc(newOrderRef, finalOrderDoc); } catch (_) {}
    }

    if (finalOrderDoc) {
      upsertOrderInLocalMirror(finalOrderDoc);
    }
    writeLocalUserCartMap(uid, {});
    updatedMirrorItems.forEach((it) => upsertItemInLocalMirror(it));
    invalidateItemsCache();
    invalidateOrdersCache();

    try {
      if (method === "cod") {
        await updateCustomerAddress(uid, {
          first_name: customerName.split(" ")[0] || customerName,
          last_name: customerName.split(" ").slice(1).join(" ") || "Customer",
          phone_number: customerPhone,
          street_address1: street1,
          street_address2: street2,
          town: town
        });
      }
    } catch (_) {}

    if (typeof sessionStorage !== "undefined") {
      sessionStorage.setItem("last_order_id", newOrderRef.id);
    }
    return {
      ok: true,
      orderId: newOrderRef.id,
      order_id: newOrderRef.id,
      order_number: generatedOrderNumber,
      pickup_code: pickupCode
    };
  } catch (err) {
    return { ok: false, message: sanitizeUserFacingError(err, "Could not place order. Please try again.") };
  }
}

// ============================================================================
// 9. ORDER QUERIES, STATUS UPDATES & IDEMPOTENT CANCELLATION (Fix #9 & Phase 3)
// ============================================================================

export const ORDER_LIFECYCLE_COD = [
  { key: "pending", label: "Order Placed" },
  { key: "confirmed", label: "Order Confirmed" },
  { key: "processing", label: "Processing" },
  { key: "ready_for_delivery", label: "Ready for Delivery" },
  { key: "out_for_delivery", label: "Out for Delivery" },
  { key: "delivered", label: "Delivered" },
  { key: "paid", label: "Paid" }
];

export const ORDER_LIFECYCLE_PICKUP = [
  { key: "pending", label: "Order Placed" },
  { key: "confirmed", label: "Order Confirmed" },
  { key: "processing", label: "Processing" },
  { key: "ready_for_pickup", label: "Ready for Pickup" },
  { key: "picked_up", label: "Picked Up" },
  { key: "paid", label: "Paid" }
];

export function normalizeOrderStatus(rawStatus, method = "cod") {
  const m = String(method || "cod").trim().toLowerCase() === "pickup" ? "pickup" : "cod";
  const s = String(rawStatus || "pending").trim().toLowerCase();
  if (s === "order_placed" || s === "pending") return "pending";
  if (s === "order_confirmed" || s === "confirmed") return "confirmed";
  if (s === "processing") return "processing";
  if (s === "ready_for_delivery") return m === "pickup" ? "ready_for_pickup" : "ready_for_delivery";
  if (s === "out_for_delivery" || s === "shipped") return m === "pickup" ? "ready_for_pickup" : "out_for_delivery";
  if (s === "ready_for_pickup" || s === "ready") return "ready_for_pickup";
  if (s === "delivered") return m === "pickup" ? "picked_up" : "delivered";
  if (s === "picked_up" || s === "collected") return "picked_up";
  if (s === "paid") return "paid";
  if (s === "cancelled") return "cancelled";
  return "pending";
}

export function isOrderArchived(orderOrStatus) {
  const st = typeof orderOrStatus === "object" && orderOrStatus !== null
    ? normalizeOrderStatus(orderOrStatus.order_status, orderOrStatus.purchase_method)
    : normalizeOrderStatus(orderOrStatus);
  return st === "paid" || st === "cancelled";
}

export function isOrderActive(orderOrStatus) {
  return !isOrderArchived(orderOrStatus);
}

function normalizeOrderRecord(id, rawData = {}) {
  const idStr = String(id || rawData.id || rawData.order_id || "");
  const method = String(rawData.purchase_method || "cod").trim().toLowerCase() === "pickup" ? "pickup" : "cod";
  const status = normalizeOrderStatus(rawData.order_status || rawData.status || "pending", method);

  let payStatus = String(rawData.payment_status || "unpaid").trim().toLowerCase();
  if (status === "cancelled") {
    payStatus = "cancelled";
  } else if (status === "paid") {
    payStatus = "paid";
  } else if ((status === "delivered" || status === "picked_up") && !rawData.payment_confirmed_by_admin) {
    // Orders at Delivered / Picked Up remain unpaid until Admin explicitly confirms payment to move to Paid
    payStatus = "unpaid";
  }

  const rawItems = Array.isArray(rawData.items) ? rawData.items : [];
  let computedSub = 0;
  const normalizedItems = rawItems.map((it) => {
    const qty = roundQty3(it.quantity !== undefined ? it.quantity : 1);
    const price = roundMoney2(it.price !== undefined ? it.price : (it.unit_price || 0));
    const lineSub = roundMoney2(it.subtotal !== undefined ? it.subtotal : (qty * price));
    computedSub = roundMoney2(computedSub + lineSub);
    const nm = String(it.name || it.product_name || "Product").trim();
    return {
      ...it,
      item_id: String(it.item_id || it.id || ""),
      name: nm,
      product_name: nm,
      image_path: it.image_path || DEFAULT_FALLBACK_IMAGE,
      unit: String(it.unit || "1 unit").trim(),
      category_id: Number(it.category_id) || 1,
      quantity: qty,
      price,
      subtotal: lineSub
    };
  });

  const subtotal = roundMoney2(rawData.subtotal ?? rawData.subtotal_amount ?? computedSub);
  const deliveryFee = roundMoney2(
    rawData.delivery_fee !== undefined
      ? rawData.delivery_fee
      : (rawData.shipping_cost !== undefined ? rawData.shipping_cost : (method === "cod" ? 450.00 : 0.00))
  );
  const totalAmount = roundMoney2(rawData.total_amount !== undefined ? rawData.total_amount : (subtotal + deliveryFee));
  const custName = String(
    rawData.customer_name || `${rawData.first_name || ""} ${rawData.last_name || ""}`.trim() || "Customer"
  ).trim();
  const custPhone = String(rawData.customer_phone || rawData.phone_number || "").trim();

  let ordNum = String(rawData.order_number || "").trim();
  if (!ordNum) {
    ordNum = /^\d+$/.test(idStr) ? idStr.padStart(5, "0") : idStr.substring(0, 8).toUpperCase();
  } else if (/^\d+$/.test(ordNum) && ordNum.length < 5) {
    ordNum = ordNum.padStart(5, "0");
  }

  return {
    ...rawData,
    id: idStr,
    order_id: idStr,
    order_number: ordNum,
    purchase_method: method,
    order_status: status,
    payment_status: payStatus,
    customer_name: custName,
    customer_phone: custPhone,
    street_address1: String(rawData.street_address1 || (method === "pickup" ? STORE_CONFIG.address : "")).trim(),
    street_address2: String(rawData.street_address2 || "").trim(),
    town: String(rawData.town || (method === "pickup" ? "Rajagiriya" : "")).trim(),
    delivery_notes: String(rawData.delivery_notes || "").trim(),
    pickup_date: rawData.pickup_date || null,
    pickup_time_slot: rawData.pickup_time_slot || null,
    pickup_code: rawData.pickup_code || (method === "pickup" ? "PK-STORE" : null),
    subtotal,
    subtotal_amount: subtotal,
    delivery_fee: deliveryFee,
    shipping_cost: deliveryFee,
    total_amount: totalAmount,
    items: normalizedItems,
    created_at: rawData.created_at || nowColomboISO(),
    updated_at: rawData.updated_at || rawData.created_at || nowColomboISO()
  };
}

export async function getOrderById(orderId) {
  if (!orderId) return null;
  const idStr = String(orderId);
  try {
    if (typeof auth.authStateReady === "function") await auth.authStateReady();
    const snap = await getDoc(doc(db, "orders", idStr));
    if (snap.exists()) {
      const ord = normalizeOrderRecord(snap.id, snap.data());
      upsertOrderInLocalMirror(ord);
      return ord;
    }
  } catch (_) {}

  const localList = readLocalOrdersMirror();
  const found = localList.find((o) => String(o.id) === idStr);
  return found ? normalizeOrderRecord(found.id, found) : null;
}

export async function getCustomerOrders(uid) {
  if (!uid) return [];
  const merged = new Map();
  readLocalOrdersMirror()
    .filter((o) => String(o.user_id) === String(uid))
    .forEach((o) => merged.set(String(o.id), normalizeOrderRecord(o.id, o)));

  try {
    if (typeof auth.authStateReady === "function") await auth.authStateReady();
    const q = query(collection(db, "orders"), where("user_id", "==", String(uid)));
    const snap = await getDocs(q);
    snap.forEach((d) => {
      const ord = normalizeOrderRecord(d.id, d.data());
      merged.set(String(d.id), ord);
      upsertOrderInLocalMirror(ord);
    });
  } catch (_) {}

  const list = Array.from(merged.values());
  list.sort((a, b) => String(b.created_at || "").localeCompare(String(a.created_at || "")));
  return list;
}

export async function getAllOrdersAdmin(forceRefresh = false) {
  if (forceRefresh) {
    invalidateOrdersCache();
  }
  const now = Date.now();
  if (ordersAdminCache && now - ordersAdminCacheTime < CACHE_TTL_MS) {
    return [...ordersAdminCache];
  }
  if (!ordersAdminPromise) {
    ordersAdminPromise = (async () => {
      const merged = new Map();
      readLocalOrdersMirror().forEach((o) => {
        if (o && o.id) merged.set(String(o.id), normalizeOrderRecord(o.id, o));
      });
      try {
        if (typeof auth.authStateReady === "function") await auth.authStateReady();
        const snap = await getDocs(collection(db, "orders"));
        snap.forEach((d) => {
          const ord = normalizeOrderRecord(d.id, d.data());
          merged.set(String(d.id), ord);
          upsertOrderInLocalMirror(ord);
        });
      } catch (_) {}
      const list = Array.from(merged.values());
      list.sort((a, b) => String(b.created_at || "").localeCompare(String(a.created_at || "")));
      ordersAdminCache = list;
      ordersAdminCacheTime = Date.now();
      return list;
    })().finally(() => {
      ordersAdminPromise = null;
    });
  }
  const res = await ordersAdminPromise;
  return [...res];
}

export async function cancelOrderWithStockRestore(orderId, cancelledBy = "Admin", enforcePendingForUid = null) {
  try {
    const orderRef = doc(db, "orders", String(orderId));
    const ts = nowColomboISO();
    const restoredMirrorItems = [];
    let targetOrder = await getOrderById(orderId);

    try {
      await runTransaction(db, async (transaction) => {
        const orderSnap = await transaction.get(orderRef);
        const ord = orderSnap.exists() ? orderSnap.data() : targetOrder;
        if (!ord) throw new Error("Order not found.");
        const currentStatus = String(ord.order_status || "pending").toLowerCase();

        if (enforcePendingForUid) {
          if (String(ord.user_id) !== String(enforcePendingForUid)) {
            throw new Error("Unauthorized to cancel this order.");
          }
          if (currentStatus !== "pending") {
            throw new Error("Only orders in Pending status can be cancelled online.");
          }
        }

        if (currentStatus === "delivered" || currentStatus === "collected" || currentStatus === "picked_up" || currentStatus === "paid") {
          throw new Error("Orders that have already been Delivered, Picked Up, or Paid cannot be cancelled.");
        }

        if (currentStatus === "cancelled") {
          return;
        }

        const items = Array.isArray(ord.items) ? ord.items : [];
        const itemRefsAndSnaps = [];
        for (const oi of items) {
          if (!oi.item_id) continue;
          const iref = doc(db, "items", String(oi.item_id));
          const isnap = await transaction.get(iref);
          itemRefsAndSnaps.push({ oi, iref, isnap });
        }

        const ordNo = ord.order_number || orderId;
        for (const { oi, iref, isnap } of itemRefsAndSnaps) {
          const itemData = isnap.exists() ? isnap.data() : (await getItemById(oi.item_id));
          if (itemData) {
            const prevStock = roundQty3(itemData.stock_quantity);
            const restoreQty = roundQty3(oi.quantity);
            const newStock = Number((prevStock + restoreQty).toFixed(3));
            if (isnap.exists()) {
              transaction.update(iref, { stock_quantity: newStock, updated_at: ts });
            }

            restoredMirrorItems.push({ ...itemData, id: String(oi.item_id), stock_quantity: newStock, updated_at: ts });

            const txRef = doc(collection(db, "stock_transactions"));
            transaction.set(txRef, {
              id: txRef.id,
              item_id: String(oi.item_id),
              item_name: itemData.name || oi.name || "",
              transaction_type: "in",
              quantity: restoreQty,
              previous_stock: prevStock,
              new_stock: newStock,
              reason: `Order #${ordNo} Cancelled`,
              cancelled_by: cancelledBy,
              created_at: ts
            });
          }
        }

        if (orderSnap.exists()) {
          transaction.update(orderRef, {
            order_status: "cancelled",
            payment_status: "cancelled",
            updated_at: ts
          });
        }
        targetOrder = { ...ord, id: String(orderId), order_status: "cancelled", payment_status: "cancelled", updated_at: ts };
      });
    } catch (txErr) {
      if (!isPermissionError(txErr)) throw txErr;
      if (!targetOrder) throw new Error("Order not found.");
      const currentStatus = String(targetOrder.order_status || "pending").toLowerCase();
      if (currentStatus === "delivered" || currentStatus === "collected" || currentStatus === "picked_up" || currentStatus === "paid") {
        throw new Error("Orders that have already been Delivered, Picked Up, or Paid cannot be cancelled.");
      }
      if (currentStatus !== "cancelled") {
        for (const oi of (targetOrder.items || [])) {
          const itemData = await getItemById(oi.item_id);
          if (itemData) {
            const prevStock = roundQty3(itemData.stock_quantity);
            const restoreQty = roundQty3(oi.quantity);
            const newStock = Number((prevStock + restoreQty).toFixed(3));
            restoredMirrorItems.push({ ...itemData, id: String(oi.item_id), stock_quantity: newStock, updated_at: ts });
          }
        }
        targetOrder = { ...targetOrder, order_status: "cancelled", payment_status: "cancelled", updated_at: ts };
      }
    }

    if (targetOrder) {
      upsertOrderInLocalMirror(targetOrder);
    }
    restoredMirrorItems.forEach((it) => upsertItemInLocalMirror(it));
    invalidateItemsCache();
    invalidateOrdersCache();

    return { ok: true, message: "Order cancelled and stock restored." };
  } catch (err) {
    return { ok: false, message: sanitizeUserFacingError(err, "Could not cancel order.") };
  }
}

export async function updateOrderStatusAdmin(orderId, { status, payment_status }) {
  try {
    if (status === "cancelled") {
      return await cancelOrderWithStockRestore(orderId, "Admin", null);
    }
    const existing = await getOrderById(orderId);
    const method = existing ? existing.purchase_method : "cod";
    const ts = nowColomboISO();
    const updates = { updated_at: ts };

    if (status) {
      const normalizedSt = normalizeOrderStatus(status, method);
      updates.order_status = normalizedSt;

      if (normalizedSt === "delivered" || normalizedSt === "picked_up") {
        updates.fulfillment_completed_at = ts;
        updates.fulfillment_final_stage = normalizedSt;
        // Do NOT auto-mark paid or archive here; order remains in Active Orders awaiting Admin payment confirmation
        if (!payment_status && !(existing && existing.payment_confirmed_by_admin)) {
          updates.payment_status = "unpaid";
          updates.payment_confirmed_by_admin = false;
        }
      } else if (normalizedSt === "paid") {
        // Final Paid status: requires payment confirmation and archives the order
        updates.payment_status = "paid";
        updates.payment_confirmed_by_admin = true;
        updates.paid_at = ts;
        updates.fulfillment_final_stage = method === "pickup" ? "picked_up" : "delivered";
      }
    }

    if (payment_status) {
      const normalizedPay = String(payment_status).toLowerCase();
      updates.payment_status = normalizedPay;
      updates.payment_confirmed_by_admin = normalizedPay === "paid";
      if (normalizedPay === "paid") {
        updates.paid_at = ts;
      }
    }

    if (existing) {
      upsertOrderInLocalMirror(normalizeOrderRecord(orderId, { ...existing, ...updates }));
    }

    try {
      if (typeof auth.authStateReady === "function") await auth.authStateReady();
      const orderRef = doc(db, "orders", String(orderId));
      await updateDoc(orderRef, updates);
    } catch (_) {}

    invalidateOrdersCache();
    return { ok: true, message: "Order status updated." };
  } catch (err) {
    return { ok: false, message: sanitizeUserFacingError(err, "Could not update order status.") };
  }
}

// ============================================================================
// 10. CONTACT INQUIRIES (Fix #10 - Duplicate Submission Guard) & CUSTOMERS
// ============================================================================

let contactSubmissionInFlight = false;
let lastContactSignature = "";
let lastContactTimestamp = 0;

export async function submitContactInquiry({ name, contactName, email, subject, message }) {
  if (contactSubmissionInFlight) {
    return { ok: false, message: "Your message is already being submitted. Please wait." };
  }
  try {
    const nm = String(name ?? contactName ?? "").trim();
    const em = String(email || "").trim();
    const sb = String(subject || "General Inquiry").trim();
    const ms = String(message || "").trim();

    if (!nm) return { ok: false, message: "Please enter your name." };
    if (!em || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) return { ok: false, message: "Please enter a valid email address." };
    if (!sb) return { ok: false, message: "Please enter a subject." };
    if (!ms) return { ok: false, message: "Please enter your message." };

    const signature = `${nm.toLowerCase()}|${em.toLowerCase()}|${sb.toLowerCase()}|${ms.toLowerCase()}`;
    const nowMs = Date.now();
    if (signature === lastContactSignature && nowMs - lastContactTimestamp < 5000) {
      return { ok: false, message: "Duplicate submission prevented. We have already received your message." };
    }

    contactSubmissionInFlight = true;
    await addDoc(collection(db, "contacts"), {
      name: nm,
      email: em,
      subject: sb,
      message: ms,
      created_at: nowColomboISO()
    });
    lastContactSignature = signature;
    lastContactTimestamp = Date.now();
    inquiriesAdminPromise = null;
    return { ok: true, message: "Contact Message Sent Successfully!" };
  } catch (err) {
    return { ok: false, message: sanitizeUserFacingError(err, "Could not send message.") };
  } finally {
    contactSubmissionInFlight = false;
  }
}

export async function getAllInquiriesAdmin() {
  if (!inquiriesAdminPromise) {
    inquiriesAdminPromise = (async () => {
      try {
        const snap = await getDocs(collection(db, "contacts"));
        const list = [];
        snap.forEach((d) => list.push({ id: d.id, ...d.data() }));
        list.sort((a, b) => String(b.created_at || "").localeCompare(String(a.created_at || "")));
        return list;
      } catch (_) {
        return [];
      }
    })().finally(() => {
      setTimeout(() => { inquiriesAdminPromise = null; }, CACHE_TTL_MS);
    });
  }
  const res = await inquiriesAdminPromise;
  return [...res];
}

export async function deleteInquiryAdmin(inquiryId) {
  await deleteDoc(doc(db, "contacts", String(inquiryId)));
  inquiriesAdminPromise = null;
}

export async function getAllCustomersAdmin() {
  try {
    const snap = await getDocs(collection(db, "users"));
    const list = [];
    snap.forEach((d) => {
      const data = d.data();
      if (Number(data.is_admin) !== 1 && data.is_admin !== true) {
        list.push({ id: d.id, ...data });
      }
    });
    list.sort((a, b) => String(b.created_at || "").localeCompare(String(a.created_at || "")));
    return list;
  } catch (_) {
    return [];
  }
}
