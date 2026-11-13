/**
 * Freshcomart (CECWeb) Centralized Firebase Auth + Cloud Firestore Service Layer
 * Powers both the Customer Storefront and Supermarket Admin Panel on Vercel / Netlify.
 */

import {
  app,
  auth,
  db,
  STORE_CONFIG,
  DEFAULT_CATEGORIES
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
  STORE_CONFIG,
  DEFAULT_CATEGORIES,
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
// 1. QUANTITY, BULK UNIT & FORMATTING HELPERS (Mirrors connection.php)
// ============================================================================

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
    const unit = m[2].toLowerCase();
    if (unit === "kg" || unit === "l") {
      return { baseVal: val * 1000, type: unit === "kg" ? "weight" : "volume" };
    }
    return { baseVal: val, type: unit === "g" ? "weight" : "volume" };
  }
  return { baseVal: 1000, type: "weight" };
}

export function formatQuantity(qty, unitStr, catId) {
  const q = parseFloat(qty) || 0;
  if (!isBulkItem(catId, unitStr)) {
    return Number.isInteger(q) ? String(q) : String(Number(q.toFixed(2)));
  }
  const parsed = parseUnitToGrams(unitStr);
  const totalBase = q * parsed.baseVal;
  if (parsed.type === "weight") {
    if (totalBase >= 1000) {
      const kg = totalBase / 1000;
      return (Number.isInteger(kg) ? kg : Number(kg.toFixed(2))) + " kg";
    }
    return Math.round(totalBase) + " g";
  } else {
    if (totalBase >= 1000) {
      const l = totalBase / 1000;
      return (Number.isInteger(l) ? l : Number(l.toFixed(2))) + " L";
    }
    return Math.round(totalBase) + " ml";
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
  const div = document.createElement("div");
  div.textContent = str == null ? "" : String(str);
  return div.innerHTML;
}

// ============================================================================
// 2. CLIENT-SIDE HTML5 <CANVAS> IMAGE COMPRESSION (Zero Storage Cost)
// ============================================================================

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

        // Prefer WebP for compact Firestore document size, fallback to JPEG
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

export function resolveImagePath(imagePath, basePrefix = "") {
  if (!imagePath) return basePrefix + "assets/images/logo/logo-freshco.png";
  const s = String(imagePath).trim();
  if (s.startsWith("data:image/") || s.startsWith("http://") || s.startsWith("https://")) {
    return s;
  }
  const clean = s.replace(/^(\.\.\/|\.\/)+/, "");
  return basePrefix + clean;
}

// ============================================================================
// 3. AUTO-SEED 19 STANDARD CATEGORIES IF EMPTY
// ============================================================================

let categoriesCache = null;

export async function ensureCategoriesSeeded() {
  if (categoriesCache && categoriesCache.length > 0) {
    return categoriesCache;
  }
  try {
    const snap = await getDocs(collection(db, "categories"));
    if (snap.empty) {
      const batch = writeBatch(db);
      const ts = nowColomboISO();
      DEFAULT_CATEGORIES.forEach((cat) => {
        const ref = doc(db, "categories", String(cat.id));
        batch.set(ref, {
          id: cat.id,
          name: cat.name,
          slug: cat.slug,
          image_path: cat.image_path,
          created_at: ts
        });
      });
      await batch.commit();
      categoriesCache = [...DEFAULT_CATEGORIES];
      return categoriesCache;
    }
    const list = [];
    snap.forEach((d) => {
      const data = d.data();
      const id = Number(data.id || d.id);
      const def = DEFAULT_CATEGORIES.find((c) => c.id === id) || {};
      list.push({
        id,
        name: data.name || def.name || "Category",
        slug: data.slug || def.slug || "category",
        image_path: data.image_path || def.image_path || "assets/images/logo/logo-freshco.png",
        icon: def.icon || "bi-tag",
        fa: def.fa || "fa-tag"
      });
    });
    list.sort((a, b) => a.id - b.id);
    categoriesCache = list;
    return list;
  } catch (err) {
    console.warn("Using default categories fallback:", err);
    return DEFAULT_CATEGORIES;
  }
}

export function getCategoryNameById(catId) {
  const c = DEFAULT_CATEGORIES.find((x) => x.id === Number(catId));
  return c ? c.name : "Grocery";
}

// ============================================================================
// 4. AUTHENTICATION & USER PROFILE OPERATIONS
// ============================================================================

export function waitForAuthUser() {
  return new Promise((resolve) => {
    const unsub = onAuthStateChanged(auth, async (user) => {
      unsub();
      if (!user) {
        resolve(null);
        return;
      }
      const profile = await getUserProfile(user.uid, user.email);
      resolve({ user, profile });
    });
  });
}

export async function getUserProfile(uid, fallbackEmail = "") {
  if (!uid) return null;
  try {
    const snap = await getDoc(doc(db, "users", uid));
    if (snap.exists()) {
      return { id: uid, uid, ...snap.data() };
    }
    const defaultProfile = {
      id: uid,
      uid,
      first_name: fallbackEmail ? fallbackEmail.split("@")[0] : "Customer",
      last_name: "",
      email: fallbackEmail || "",
      phone_number: "",
      street_address1: "",
      street_address2: "",
      town: "",
      is_admin: fallbackEmail === "achintha@gmail.com" ? 1 : 0,
      created_at: nowColomboISO()
    };
    await setDoc(doc(db, "users", uid), defaultProfile, { merge: true });
    return defaultProfile;
  } catch (err) {
    console.error("Error fetching user profile:", err);
    return {
      id: uid,
      uid,
      first_name: "Customer",
      last_name: "",
      email: fallbackEmail,
      phone_number: "",
      street_address1: "",
      street_address2: "",
      town: "",
      is_admin: 0
    };
  }
}

export async function registerCustomer({ firstName, lastName, email, phoneNumber, password, confirmPassword }) {
  const fn = String(firstName || "").trim();
  const ln = String(lastName || "").trim();
  const em = String(email || "").trim();
  const ph = String(phoneNumber || "").trim();
  const pw = String(password || "");
  const cpw = String(confirmPassword || "");

  if (!fn) throw new Error("Please Enter Your First Name.");
  if (!ln) throw new Error("Please Enter Your Last Name.");
  if (!em) throw new Error("Please Enter Your Email.");
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) throw new Error("Please enter a valid email address.");
  if (!ph) throw new Error("Please Enter Your Phone Number.");
  if (!/^(?:0|\+94|94)?[1-9][0-9]{8}$/.test(ph)) throw new Error("Please enter a valid Sri Lankan phone number.");
  if (!pw) throw new Error("Please Enter Your Password.");
  if (pw.length < 8) throw new Error("Password must be at least 8 characters.");
  if (!cpw) throw new Error("Please Confirm Your Password.");
  if (pw !== cpw) throw new Error("Password and confirm password do not match.");

  const cred = await createUserWithEmailAndPassword(auth, em, pw);
  const uid = cred.user.uid;
  const isAdmin = em.toLowerCase() === "achintha@gmail.com" ? 1 : 0;
  const profileData = {
    uid,
    first_name: fn,
    last_name: ln,
    email: em,
    phone_number: ph,
    street_address1: "",
    street_address2: "",
    town: "",
    is_admin: isAdmin,
    created_at: nowColomboISO()
  };
  await setDoc(doc(db, "users", uid), profileData);
  return { user: cred.user, profile: profileData };
}

export async function loginCustomer(email, password) {
  const em = String(email || "").trim();
  const pw = String(password || "");
  if (!em || !pw) throw new Error("Email and Password required");

  const cred = await signInWithEmailAndPassword(auth, em, pw);
  const profile = await getUserProfile(cred.user.uid, cred.user.email);
  if (Number(profile.is_admin) === 1 || em.toLowerCase() === "achintha@gmail.com") {
    localStorage.setItem("fc_admin_auth", JSON.stringify({
      uid: cred.user.uid,
      email: cred.user.email,
      first_name: profile.first_name || "Admin",
      last_name: profile.last_name || "User",
      is_admin: 1,
      ts: Date.now()
    }));
  }
  return { user: cred.user, profile };
}

export async function logoutUser() {
  localStorage.removeItem("fc_admin_auth");
  await signOut(auth);
}

export async function updateCustomerAddress(uid, { first_name, last_name, phone_number, street_address1, street_address2, town }) {
  const fn = String(first_name || "").trim();
  const ln = String(last_name || "").trim();
  const ph = String(phone_number || "").trim();
  const s1 = String(street_address1 || "").trim();
  const s2 = String(street_address2 || "").trim();
  const tw = String(town || "").trim();

  if (!fn || !ln) throw new Error("First name and last name are required.");
  if (!ph) throw new Error("Phone number is required.");
  if (!s1 || !tw) throw new Error("Street address and Town/City are required.");

  await setDoc(doc(db, "users", uid), {
    first_name: fn,
    last_name: ln,
    phone_number: ph,
    street_address1: s1,
    street_address2: s2,
    town: tw,
    updated_at: nowColomboISO()
  }, { merge: true });
}

export async function updateCustomerPassword(currentPassword, newPassword, confirmPassword) {
  const user = auth.currentUser;
  if (!user) throw new Error("Not signed in.");
  if (!currentPassword) throw new Error("Please enter your current password.");
  if (!newPassword || newPassword.length < 8) throw new Error("New password must be at least 8 characters.");
  if (newPassword !== confirmPassword) throw new Error("New password and confirmation do not match.");

  const credential = EmailAuthProvider.credential(user.email, currentPassword);
  await reauthenticateWithCredential(user, credential);
  await updatePassword(user, newPassword);
}

// ============================================================================
// 5. ADMIN PORTAL AUTHENTICATION HELPERS
// ============================================================================

export async function authenticateAdminPortal(password, emailOptional = "") {
  const pw = String(password || "").trim();
  const em = String(emailOptional || "achintha@gmail.com").trim();

  // Support standard Admin Portal Security Access Key ("1234" or "12345678") OR Firebase Admin user login
  if (pw === "1234" || pw === "12345678") {
    try {
      const cred = await signInWithEmailAndPassword(auth, em, pw === "1234" ? "12345678" : pw);
      const profile = await getUserProfile(cred.user.uid, cred.user.email);
      localStorage.setItem("fc_admin_auth", JSON.stringify({
        uid: cred.user.uid,
        email: cred.user.email,
        first_name: profile.first_name || "Achintha",
        last_name: profile.last_name || "Admin",
        is_admin: 1,
        ts: Date.now()
      }));
      return true;
    } catch (_) {
      // Even if Firebase Auth account for achintha@gmail.com hasn't been created yet, try creating it or grant admin session
      try {
        const cred = await createUserWithEmailAndPassword(auth, "achintha@gmail.com", "12345678");
        await setDoc(doc(db, "users", cred.user.uid), {
          uid: cred.user.uid,
          first_name: "Achintha",
          last_name: "Admin",
          email: "achintha@gmail.com",
          phone_number: "0771234567",
          street_address1: "No. 142, Parliament Road",
          street_address2: "",
          town: "Rajagiriya",
          is_admin: 1,
          created_at: nowColomboISO()
        }, { merge: true });
      } catch (_ignore) {}

      localStorage.setItem("fc_admin_auth", JSON.stringify({
        uid: "admin_portal",
        email: "achintha@gmail.com",
        first_name: "Achintha",
        last_name: "Admin",
        is_admin: 1,
        ts: Date.now()
      }));
      return true;
    }
  }

  // Otherwise try authenticating via Firebase Auth with provided email & password
  const cred = await signInWithEmailAndPassword(auth, em, pw);
  const profile = await getUserProfile(cred.user.uid, cred.user.email);
  if (Number(profile.is_admin) !== 1 && cred.user.email.toLowerCase() !== "achintha@gmail.com") {
    throw new Error("Access denied. This account does not have administrator privileges.");
  }
  localStorage.setItem("fc_admin_auth", JSON.stringify({
    uid: cred.user.uid,
    email: cred.user.email,
    first_name: profile.first_name || "Admin",
    last_name: profile.last_name || "User",
    is_admin: 1,
    ts: Date.now()
  }));
  return true;
}

export function getAdminSession() {
  try {
    const raw = localStorage.getItem("fc_admin_auth");
    if (!raw) return null;
    const data = JSON.parse(raw);
    if (data && Number(data.is_admin) === 1) return data;
    return null;
  } catch (_) {
    return null;
  }
}

export function requireAdminSession(redirectUrl = "login.html") {
  const sess = getAdminSession();
  if (!sess) {
    window.location.href = redirectUrl;
    return null;
  }
  return sess;
}

// ============================================================================
// 6. ITEMS / PRODUCT CATALOG OPERATIONS
// ============================================================================

export async function getAllItems() {
  const snap = await getDocs(collection(db, "items"));
  const items = [];
  snap.forEach((d) => {
    const data = d.data();
    items.push({
      id: d.id,
      ...data,
      price: parseFloat(data.price) || 0,
      stock_quantity: parseFloat(data.stock_quantity) || 0,
      category_id: Number(data.category_id) || 1,
      category_name: data.category_name || getCategoryNameById(data.category_id),
      status: data.status || "active"
    });
  });
  items.sort((a, b) => String(b.created_at || "").localeCompare(String(a.created_at || "")));
  return items;
}

export async function getItemById(itemId) {
  if (!itemId) return null;
  const snap = await getDoc(doc(db, "items", String(itemId)));
  if (!snap.exists()) return null;
  const data = snap.data();
  return {
    id: snap.id,
    ...data,
    price: parseFloat(data.price) || 0,
    stock_quantity: parseFloat(data.stock_quantity) || 0,
    category_id: Number(data.category_id) || 1,
    category_name: data.category_name || getCategoryNameById(data.category_id),
    status: data.status || "active"
  };
}

export async function addProductItem({ name, price, unit, category_id, status, stock_quantity, imageDataUrl }) {
  const nm = String(name || "").trim();
  const pr = parseFloat(price);
  const un = String(unit || "").trim();
  const cid = Number(category_id);
  const st = status === "inactive" ? "inactive" : "active";
  const sq = stock_quantity !== undefined && stock_quantity !== "" ? parseFloat(stock_quantity) : (st === "active" ? 25 : 0);

  if (!nm) throw new Error("Please enter the product name.");
  if (isNaN(pr) || pr <= 0) throw new Error("Please enter a valid price.");
  if (!un) throw new Error("Please specify the measurement unit.");
  if (!cid) throw new Error("Please select a product category.");
  if (!imageDataUrl) throw new Error("Please select a product image.");

  const ts = nowColomboISO();
  const itemDoc = {
    name: nm,
    price: pr,
    unit: un,
    category_id: cid,
    category_name: getCategoryNameById(cid),
    status: st,
    stock_quantity: sq,
    image_path: imageDataUrl,
    created_at: ts,
    updated_at: ts
  };
  const docRef = await addDoc(collection(db, "items"), itemDoc);
  // Record initial stock transaction
  if (sq > 0) {
    await addDoc(collection(db, "stock_transactions"), {
      item_id: docRef.id,
      transaction_type: "in",
      quantity: sq,
      previous_stock: 0,
      new_stock: sq,
      reason: "Initial Product Stock",
      created_at: ts
    });
  }
  return { id: docRef.id, ...itemDoc };
}

export async function toggleItemStatus(itemId) {
  const itemRef = doc(db, "items", String(itemId));
  const snap = await getDoc(itemRef);
  if (!snap.exists()) throw new Error("Item not found");
  const cur = snap.data().status === "active" ? "active" : "inactive";
  const next = cur === "active" ? "inactive" : "active";
  await updateDoc(itemRef, { status: next, updated_at: nowColomboISO() });
  return next;
}

export async function adjustItemStock(itemId, transactionType, changeQty, reason) {
  const itemRef = doc(db, "items", String(itemId));
  const ts = nowColomboISO();
  let finalStock = 0;

  await runTransaction(db, async (transaction) => {
    const snap = await transaction.get(itemRef);
    if (!snap.exists()) throw new Error("Item not found.");
    const data = snap.data();
    const prevStock = parseFloat(data.stock_quantity) || 0;
    const qty = parseFloat(changeQty) || 0;

    if (transactionType === "in") {
      finalStock = prevStock + qty;
    } else if (transactionType === "out") {
      if (qty > prevStock) {
        throw new Error(`Cannot remove more stock than currently available (${prevStock}).`);
      }
      finalStock = Math.max(0, prevStock - qty);
    } else if (transactionType === "adjustment") {
      finalStock = Math.max(0, qty);
    } else {
      throw new Error("Invalid transaction type.");
    }

    const newStatus = finalStock > 0 ? "active" : data.status;
    transaction.update(itemRef, {
      stock_quantity: finalStock,
      status: newStatus,
      updated_at: ts
    });

    const txRef = doc(collection(db, "stock_transactions"));
    transaction.set(txRef, {
      item_id: String(itemId),
      transaction_type: transactionType,
      quantity: qty,
      previous_stock: prevStock,
      new_stock: finalStock,
      reason: String(reason || "Manual Adjustment"),
      created_at: ts
    });
  });

  return finalStock;
}

export async function getItemStockHistory(itemId) {
  const q = query(
    collection(db, "stock_transactions"),
    where("item_id", "==", String(itemId))
  );
  const snap = await getDocs(q);
  const list = [];
  snap.forEach((d) => list.push({ id: d.id, ...d.data() }));
  list.sort((a, b) => String(b.created_at || "").localeCompare(String(a.created_at || "")));
  return list.slice(0, 15);
}

// ============================================================================
// 7. SHOPPING CART OPERATIONS (Unit Price Storage + Stock Validation)
// ============================================================================

export async function getActiveCartWithItems(uid) {
  if (!uid) return { items: [], count: 0, subtotal: 0, inCartMap: {} };

  const q = query(collection(db, "cart_items"), where("user_id", "==", String(uid)));
  const snap = await getDocs(q);
  if (snap.empty) {
    return { items: [], count: 0, subtotal: 0, inCartMap: {} };
  }

  const rawCartItems = [];
  snap.forEach((d) => rawCartItems.push({ cart_item_id: d.id, ...d.data() }));

  const enriched = [];
  const inCartMap = {};
  let subtotal = 0;

  for (const ci of rawCartItems) {
    const item = await getItemById(ci.item_id);
    if (!item) continue;
    const qty = parseFloat(ci.quantity) || 0;
    if (qty <= 0) continue;
    const unitPrice = parseFloat(item.price) || parseFloat(ci.price) || 0;
    const lineTotal = qty * unitPrice;
    subtotal += lineTotal;
    inCartMap[String(item.id)] = qty;

    enriched.push({
      cart_item_id: ci.cart_item_id,
      item_id: String(item.id),
      name: item.name,
      image_path: item.image_path,
      unit: item.unit || "1 unit",
      category_id: Number(item.category_id) || 1,
      stock_quantity: parseFloat(item.stock_quantity) || 0,
      status: item.status || "active",
      quantity: qty,
      formatted_quantity: formatQuantity(qty, item.unit, item.category_id),
      unit_price: unitPrice,
      line_total: lineTotal
    });
  }

  return {
    items: enriched,
    count: enriched.length,
    subtotal,
    inCartMap
  };
}

export async function addOrUpdateCartItem(uid, itemId, deltaQty) {
  if (!uid) throw new Error("User not logged in");
  const item = await getItemById(itemId);
  if (!item) throw new Error("Item not found");

  const stockQty = parseFloat(item.stock_quantity) || 0;
  if (item.status !== "active" || stockQty <= 0) {
    throw new Error(`Sorry, "${item.name}" is currently out of stock.`);
  }

  const cartDocId = `${uid}_${itemId}`;
  const cartRef = doc(db, "cart_items", cartDocId);
  const existingSnap = await getDoc(cartRef);
  const delta = parseFloat(deltaQty) || 0;
  const ts = nowColomboISO();

  if (existingSnap.exists()) {
    const curQty = parseFloat(existingSnap.data().quantity) || 0;
    const newQty = curQty + delta;

    if (delta < 0 && newQty <= 0.0001) {
      await deleteDoc(cartRef);
      return { action: "removed", newQty: 0, item };
    }
    if (newQty > stockQty) {
      throw new Error(`Sorry, only ${stockQty} unit(s) of "${item.name}" are available in stock.`);
    }
    // Always store single unit price in cart_items.price
    await updateDoc(cartRef, {
      quantity: newQty,
      price: item.price,
      updated_at: ts
    });
    return { action: "updated", newQty, item };
  } else {
    if (delta <= 0) return { action: "none", newQty: 0, item };
    if (delta > stockQty) {
      throw new Error(`Sorry, only ${stockQty} unit(s) of "${item.name}" are available in stock.`);
    }
    await setDoc(cartRef, {
      user_id: String(uid),
      item_id: String(itemId),
      quantity: delta,
      price: item.price, // Single unit price
      created_at: ts,
      updated_at: ts
    });
    return { action: "added", newQty: delta, item };
  }
}

export async function setCartItemQuantity(uid, cartItemId, newQuantity) {
  if (!uid) throw new Error("User not logged in");
  const cartRef = doc(db, "cart_items", String(cartItemId));
  const snap = await getDoc(cartRef);
  if (!snap.exists()) throw new Error("Cart item not found");

  const ci = snap.data();
  if (String(ci.user_id) !== String(uid)) throw new Error("Unauthorized user");

  const item = await getItemById(ci.item_id);
  if (!item) throw new Error("Item not found");

  const stockQty = parseFloat(item.stock_quantity) || 0;
  if (item.status !== "active" || stockQty <= 0) {
    throw new Error(`Sorry, "${item.name}" is currently out of stock.`);
  }

  const qty = parseFloat(newQuantity) || 0;
  if (qty <= 0.0001) {
    await deleteDoc(cartRef);
    return { removed: true, quantity: 0, item };
  }
  if (qty > stockQty) {
    throw new Error(`Sorry, only ${stockQty} unit(s) of "${item.name}" are available in stock.`);
  }

  await updateDoc(cartRef, {
    quantity: qty,
    price: item.price,
    updated_at: nowColomboISO()
  });

  return {
    removed: false,
    quantity: qty,
    formatted_quantity: formatQuantity(qty, item.unit, item.category_id),
    item_total: qty * item.price,
    item
  };
}

export async function removeCartItemById(uid, cartItemId) {
  const cartRef = doc(db, "cart_items", String(cartItemId));
  const snap = await getDoc(cartRef);
  if (snap.exists() && String(snap.data().user_id) === String(uid)) {
    await deleteDoc(cartRef);
  }
}

export async function clearUserCart(uid) {
  const q = query(collection(db, "cart_items"), where("user_id", "==", String(uid)));
  const snap = await getDocs(q);
  const batch = writeBatch(db);
  snap.forEach((d) => batch.delete(d.ref));
  await batch.commit();
}

// ============================================================================
// 8. CHECKOUT & ATOMIC ORDER PLACEMENT (COD & STORE PICKUP)
// ============================================================================

function randomChars(chars, len) {
  let out = "";
  for (let i = 0; i < len; i++) {
    out += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return out;
}

export async function placeOrderTransaction(uid, payload) {
  if (!uid) throw new Error("Please sign in to place an order.");

  const method = payload.purchase_method === "pickup" ? "pickup" : "cod";
  const customerName = String(payload.customer_name || "").trim();
  const customerPhone = String(payload.customer_phone || "").trim();
  let street1 = String(payload.street_address1 || "").trim();
  let street2 = String(payload.street_address2 || "").trim();
  let town = String(payload.town || "").trim();
  const deliveryNotes = String(payload.delivery_notes || "").trim();
  const pickupDate = method === "pickup" ? String(payload.pickup_date || "").trim() : null;
  const pickupSlot = method === "pickup" ? String(payload.pickup_time_slot || "").trim() : null;

  if (!customerName) throw new Error("Please enter your full name.");
  if (!customerPhone) throw new Error("Please enter your phone number.");

  if (method === "pickup") {
    if (!pickupDate) throw new Error("Please select a pickup date.");
    if (!pickupSlot) throw new Error("Please choose a pickup time slot.");
    street1 = STORE_CONFIG.address;
    street2 = "";
    town = STORE_CONFIG.town;
  } else {
    if (!street1) throw new Error("Please enter your delivery street address.");
    if (!town) throw new Error("Please enter your town/city.");
  }

  const cart = await getActiveCartWithItems(uid);
  if (!cart.items || cart.items.length === 0) {
    throw new Error("Your cart is empty.");
  }

  const datePart = todayDateString().replace(/-/g, "");
  const orderNumber = `ORD-${datePart}-${randomChars("0123456789", 4)}`;
  const pickupCode = method === "pickup" ? `PK-${randomChars("23456789ABCDEFGHJKLMNPQRSTUVWXYZ", 5)}` : null;
  const deliveryFee = method === "pickup" ? 0.0 : STORE_CONFIG.delivery_fee;
  const ts = nowColomboISO();

  const newOrderRef = doc(collection(db, "orders"));

  await runTransaction(db, async (transaction) => {
    // 1. Read all item docs first (Firestore transaction requirement: reads before writes)
    const itemSnaps = [];
    for (const ci of cart.items) {
      const itemRef = doc(db, "items", String(ci.item_id));
      const snap = await transaction.get(itemRef);
      itemSnaps.push({ ci, itemRef, snap });
    }

    let computedSubtotal = 0;
    const orderItemsArray = [];

    // 2. Validate stock & prepare stock deductions
    for (const { ci, itemRef, snap } of itemSnaps) {
      if (!snap.exists()) {
        throw new Error(`Product "${ci.name}" is no longer available.`);
      }
      const itemData = snap.data();
      const availStock = parseFloat(itemData.stock_quantity) || 0;
      const unitPrice = parseFloat(itemData.price) || 0;

      if (itemData.status !== "active" || availStock < ci.quantity) {
        throw new Error(`Insufficient stock for "${itemData.name}". Available: ${availStock}`);
      }

      const newStock = availStock - ci.quantity;
      const lineSubtotal = ci.quantity * unitPrice;
      computedSubtotal += lineSubtotal;

      transaction.update(itemRef, {
        stock_quantity: newStock,
        updated_at: ts
      });

      const txRef = doc(collection(db, "stock_transactions"));
      transaction.set(txRef, {
        item_id: String(ci.item_id),
        transaction_type: "out",
        quantity: ci.quantity,
        previous_stock: availStock,
        new_stock: newStock,
        reason: `Order #${orderNumber} (${method.toUpperCase()})`,
        created_at: ts
      });

      orderItemsArray.push({
        item_id: String(ci.item_id),
        product_name: itemData.name,
        image_path: itemData.image_path || "",
        unit: itemData.unit || "1 unit",
        category_id: Number(itemData.category_id) || 1,
        quantity: ci.quantity,
        price: unitPrice,
        subtotal: lineSubtotal
      });
    }

    const grandTotal = computedSubtotal + deliveryFee;

    // 3. Create Order document
    transaction.set(newOrderRef, {
      user_id: String(uid),
      order_number: orderNumber,
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
      subtotal_amount: computedSubtotal,
      total_amount: grandTotal,
      items: orderItemsArray,
      created_at: ts,
      updated_at: ts
    });

    // 4. Delete user's cart items
    for (const ci of cart.items) {
      const ciRef = doc(db, "cart_items", String(ci.cart_item_id));
      transaction.delete(ciRef);
    }
  });

  // Also save customer's address/phone to their profile if not yet saved
  try {
    if (method === "cod") {
      await setDoc(doc(db, "users", String(uid)), {
        phone_number: customerPhone,
        street_address1: street1,
        street_address2: street2,
        town: town
      }, { merge: true });
    }
  } catch (_) {}

  sessionStorage.setItem("last_order_id", newOrderRef.id);
  return { order_id: newOrderRef.id, order_number: orderNumber, pickup_code: pickupCode };
}

// ============================================================================
// 9. ORDER QUERIES, STATUS UPDATES & CANCELLATION WITH STOCK RESTORATION
// ============================================================================

export async function getOrderById(orderId) {
  if (!orderId) return null;
  const snap = await getDoc(doc(db, "orders", String(orderId)));
  if (!snap.exists()) return null;
  return { id: snap.id, order_id: snap.id, ...snap.data() };
}

export async function getCustomerOrders(uid) {
  if (!uid) return [];
  const q = query(collection(db, "orders"), where("user_id", "==", String(uid)));
  const snap = await getDocs(q);
  const list = [];
  snap.forEach((d) => list.push({ id: d.id, order_id: d.id, ...d.data() }));
  list.sort((a, b) => String(b.created_at || "").localeCompare(String(a.created_at || "")));
  return list;
}

export async function getAllOrdersAdmin() {
  const snap = await getDocs(collection(db, "orders"));
  const list = [];
  snap.forEach((d) => list.push({ id: d.id, order_id: d.id, ...d.data() }));
  list.sort((a, b) => String(b.created_at || "").localeCompare(String(a.created_at || "")));
  return list;
}

export async function cancelOrderWithStockRestore(orderId, cancelledBy = "Admin", enforcePendingForUid = null) {
  const orderRef = doc(db, "orders", String(orderId));
  const ts = nowColomboISO();

  await runTransaction(db, async (transaction) => {
    const orderSnap = await transaction.get(orderRef);
    if (!orderSnap.exists()) throw new Error("Order not found.");
    const ord = orderSnap.data();

    if (enforcePendingForUid) {
      if (String(ord.user_id) !== String(enforcePendingForUid)) {
        throw new Error("Unauthorized to cancel this order.");
      }
      if (ord.order_status !== "pending") {
        throw new Error("Only orders in Pending status can be cancelled online.");
      }
    }

    if (ord.order_status === "cancelled") {
      return; // Already cancelled; prevent double stock restoration
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
      if (isnap.exists()) {
        const prevStock = parseFloat(isnap.data().stock_quantity) || 0;
        const restoreQty = parseFloat(oi.quantity) || 0;
        const newStock = prevStock + restoreQty;
        transaction.update(iref, { stock_quantity: newStock, updated_at: ts });

        const txRef = doc(collection(db, "stock_transactions"));
        transaction.set(txRef, {
          item_id: String(oi.item_id),
          transaction_type: "in",
          quantity: restoreQty,
          previous_stock: prevStock,
          new_stock: newStock,
          reason: `Order #${ordNo} Cancelled by ${cancelledBy}`,
          created_at: ts
        });
      }
    }

    transaction.update(orderRef, {
      order_status: "cancelled",
      payment_status: "cancelled",
      updated_at: ts
    });
  });
}

export async function updateOrderStatusAdmin(orderId, { status, payment_status }) {
  if (status === "cancelled") {
    await cancelOrderWithStockRestore(orderId, "Admin", null);
    return;
  }
  const orderRef = doc(db, "orders", String(orderId));
  const updates = { updated_at: nowColomboISO() };
  if (status) {
    updates.order_status = status;
    if (status === "delivered" || status === "collected") {
      updates.payment_status = "paid";
    }
  }
  if (payment_status) {
    updates.payment_status = String(payment_status).toLowerCase();
  }
  await updateDoc(orderRef, updates);
}

// ============================================================================
// 10. CONTACT INQUIRIES & ADMIN CUSTOMERS
// ============================================================================

export async function submitContactInquiry({ name, email, subject, message }) {
  const nm = String(name || "").trim();
  const em = String(email || "").trim();
  const sb = String(subject || "General Inquiry").trim();
  const ms = String(message || "").trim();

  if (!nm) throw new Error("Please enter your name.");
  if (!em || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) throw new Error("Please enter a valid email address.");
  if (!ms) throw new Error("Please enter your message.");

  await addDoc(collection(db, "contacts"), {
    name: nm,
    email: em,
    subject: sb || "General Inquiry",
    message: ms,
    created_at: nowColomboISO()
  });
}

export async function getAllInquiriesAdmin() {
  const snap = await getDocs(collection(db, "contacts"));
  const list = [];
  snap.forEach((d) => list.push({ id: d.id, ...d.data() }));
  list.sort((a, b) => String(b.created_at || "").localeCompare(String(a.created_at || "")));
  return list;
}

export async function deleteInquiryAdmin(inquiryId) {
  await deleteDoc(doc(db, "contacts", String(inquiryId)));
}

export async function getAllCustomersAdmin() {
  const snap = await getDocs(collection(db, "users"));
  const list = [];
  snap.forEach((d) => {
    const data = d.data();
    if (Number(data.is_admin) !== 1) {
      list.push({ id: d.id, ...data });
    }
  });
  list.sort((a, b) => String(b.created_at || "").localeCompare(String(a.created_at || "")));
  return list;
}

