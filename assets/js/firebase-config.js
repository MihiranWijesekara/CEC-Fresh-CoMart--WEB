import { initializeApp } from "https://www.gstatic.com/firebasejs/10.14.1/firebase-app.js";
import { getAuth } from "https://www.gstatic.com/firebasejs/10.14.1/firebase-auth.js";
import { getFirestore } from "https://www.gstatic.com/firebasejs/10.14.1/firebase-firestore.js";

const firebaseConfig = {
  apiKey: "AIzaSyC9NzyCr2MoDMmcDUqplBou0FNT2Dxx5Ds",
  authDomain: "fresh-co-mart.firebaseapp.com",
  projectId: "fresh-co-mart",
  storageBucket: "fresh-co-mart.firebasestorage.app",
  messagingSenderId: "269666462088",
  appId: "1:269666462088:web:3e62ca93cc95335f31f23d"
};

export const app = initializeApp(firebaseConfig);
export const auth = getAuth(app);
export const db = getFirestore(app);

// Store configuration (mirrors storeConfig.php)
export const STORE_CONFIG = {
  name: "Freshcomart – Main Branch (Rajagiriya)",
  label: "Rajagiriya",
  address: "No. 142, Parliament Road, Rajagiriya",
  town: "Rajagiriya",
  phone: "+94 11 234 5678",
  hours: "Open daily 8:00 AM – 9:00 PM",
  delivery_fee: 450.00,
  pickup_days: 5,
  pickup_slots: [
    "09:00 AM - 11:00 AM",
    "11:00 AM - 01:00 PM",
    "01:00 PM - 03:00 PM",
    "03:00 PM - 05:00 PM",
    "05:00 PM - 07:00 PM"
  ]
};

// Standard 19 Supermarket Categories (auto-seeded if Firestore categories collection is empty)
export const DEFAULT_CATEGORIES = [
  { id: 1,  name: "Vegetables",               slug: "vegetables",               image_path: "assets/images/category/vegetables.png", icon: "bi-carrot",        fa: "fa-carrot" },
  { id: 2,  name: "Fruits",                   slug: "fruits",                   image_path: "assets/images/category/fruits.png",     icon: "bi-apple",         fa: "fa-apple-whole" },
  { id: 3,  name: "Snacks",                   slug: "snacks",                   image_path: "assets/images/category/snacks.png",     icon: "bi-cookie",        fa: "fa-cookie-bite" },
  { id: 4,  name: "Biscuits",                 slug: "biscuits",                 image_path: "assets/images/category/biscuits.png",   icon: "bi-cookie",        fa: "fa-cookie" },
  { id: 5,  name: "Coffee",                   slug: "coffee",                   image_path: "assets/images/category/coffee.png",     icon: "bi-cup-hot",       fa: "fa-mug-hot" },
  { id: 6,  name: "Eggs",                     slug: "eggs",                     image_path: "assets/images/category/eggs.png",       icon: "bi-egg",           fa: "fa-egg" },
  { id: 7,  name: "Water",                    slug: "water",                    image_path: "assets/images/category/water.png",      icon: "bi-droplet",       fa: "fa-bottle-water" },
  { id: 8,  name: "Tea",                      slug: "tea",                      image_path: "assets/images/category/tea.png",        icon: "bi-cup-hot",       fa: "fa-mug-saucer" },
  { id: 9,  name: "Cheese",                   slug: "cheese",                   image_path: "assets/images/category/cheese.png",     icon: "bi-box",           fa: "fa-cheese" },
  { id: 10, name: "Yoghurts & Curd",          slug: "yoghurts-curd",            image_path: "assets/images/category/yoghurt.png",    icon: "bi-cup-straw",     fa: "fa-ice-cream" },
  { id: 11, name: "Desserts",                 slug: "desserts",                 image_path: "assets/images/category/desserts.png",   icon: "bi-cake2",         fa: "fa-cake-candles" },
  { id: 12, name: "Beverages",                slug: "beverages",                image_path: "assets/images/category/beverages.png",  icon: "bi-cup-straw",     fa: "fa-glass-water" },
  { id: 13, name: "Bakery",                   slug: "bakery",                   image_path: "assets/images/category/bakery.png",     icon: "bi-basket",        fa: "fa-bread-slice" },
  { id: 14, name: "Meat & Seafood",           slug: "meat-seafood",             image_path: "assets/images/category/meat.png",       icon: "bi-fire",          fa: "fa-drumstick-bite" },
  { id: 15, name: "Pantry Staples",           slug: "pantry-staples",           image_path: "assets/images/category/pantry.png",     icon: "bi-box-seam",      fa: "fa-bowl-rice" },
  { id: 16, name: "Dairy Staples",            slug: "dairy-staples",            image_path: "assets/images/category/dairy.png",      icon: "bi-droplet-fill",  fa: "fa-cow" },
  { id: 17, name: "Frozen Foods",             slug: "frozen-foods",             image_path: "assets/images/category/frozen.png",     icon: "bi-snow",          fa: "fa-snowflake" },
  { id: 18, name: "Household & Cleaning",     slug: "household-cleaning",       image_path: "assets/images/category/household.png",  icon: "bi-stars",         fa: "fa-soap" },
  { id: 19, name: "Personal Care & Baby Care",slug: "personal-care-baby-care",  image_path: "assets/images/category/personal.png",   icon: "bi-heart-pulse",   fa: "fa-baby-carriage" }
];

