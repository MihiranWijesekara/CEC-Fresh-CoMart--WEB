import { initializeApp } from "https://www.gstatic.com/firebasejs/10.14.1/firebase-app.js";
import { getAuth } from "https://www.gstatic.com/firebasejs/10.14.1/firebase-auth.js";
import { getFirestore } from "https://www.gstatic.com/firebasejs/10.14.1/firebase-firestore.js";

export const firebaseConfig = {
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

// Store configuration (single source of truth across Storefront, Checkout, and Admin)
export const STORE_CONFIG = {
  name: "Main Branch",
  area: "Rajagiriya",
  label: "Main Branch - Rajagiriya",
  address: "Freshcomart Main Branch, Sri Jayawardenepura Mawatha, Rajagiriya",
  town: "Rajagiriya",
  phone: "+94 11 288 0000",
  hours: "Open daily 8:00 AM - 9:00 PM",
  delivery_fee: 450.00,
  pickup_days: 7,
  pickup_slots: [
    "09:00 AM - 11:00 AM",
    "11:00 AM - 01:00 PM",
    "01:00 PM - 03:00 PM",
    "03:00 PM - 05:00 PM",
    "05:00 PM - 07:00 PM",
    "07:00 PM - 09:00 PM"
  ]
};

// Initial Order Sequence Counter (so next order is '00014')
export const INITIAL_ORDER_COUNTER = 13;

// Standard 19 Supermarket Categories (auto-seeded if Firestore categories collection is empty)
export const DEFAULT_CATEGORIES = [
  { id: 1,  name: "Vegetables",                slug: "vegetables",                image_path: "assets/images/shopCategory/vegetable.png",   status: "active", icon: "bi-carrot",       fa: "fa-carrot" },
  { id: 2,  name: "Fruits",                    slug: "fruits",                    image_path: "assets/images/shopCategory/fruits.png",      status: "active", icon: "bi-apple",        fa: "fa-apple-whole" },
  { id: 3,  name: "Snacks",                    slug: "snacks",                    image_path: "assets/images/shopCategory/snack.png",       status: "active", icon: "bi-cookie",       fa: "fa-cookie-bite" },
  { id: 4,  name: "Biscuits",                  slug: "biscuits",                  image_path: "assets/images/shopCategory/biscuit.png",     status: "active", icon: "bi-cookie",       fa: "fa-cookie" },
  { id: 5,  name: "Coffee",                    slug: "coffee",                    image_path: "assets/images/shopCategory/coffee.png",      status: "active", icon: "bi-cup-hot",      fa: "fa-mug-hot" },
  { id: 6,  name: "Eggs",                      slug: "eggs",                      image_path: "assets/images/shopCategory/eggs.png",        status: "active", icon: "bi-egg",          fa: "fa-egg" },
  { id: 7,  name: "Water",                     slug: "water",                     image_path: "assets/images/shopCategory/waterBottle.png", status: "active", icon: "bi-droplet",      fa: "fa-bottle-water" },
  { id: 8,  name: "Tea",                       slug: "tea",                       image_path: "assets/images/shopCategory/tea-bag.png",     status: "active", icon: "bi-cup-hot",      fa: "fa-mug-saucer" },
  { id: 9,  name: "Cheese",                    slug: "cheese",                    image_path: "assets/images/shopCategory/cheese.png",      status: "active", icon: "bi-box",          fa: "fa-cheese" },
  { id: 10, name: "Yoghurts & Curd",           slug: "yoghurts-curd",             image_path: "assets/images/shopCategory/yoghurt.png",     status: "active", icon: "bi-cup-straw",    fa: "fa-ice-cream" },
  { id: 11, name: "Desserts",                  slug: "desserts",                  image_path: "assets/images/shopCategory/yoghurt.png",     status: "active", icon: "bi-cake2",        fa: "fa-cake-candles" },
  { id: 12, name: "Beverages",                 slug: "beverages",                 image_path: "assets/images/shopCategory/beverages.png",   status: "active", icon: "bi-cup-straw",    fa: "fa-glass-water" },
  { id: 13, name: "Bakery",                    slug: "bakery",                    image_path: "assets/images/shopCategory/bread.png",       status: "active", icon: "bi-basket",       fa: "fa-bread-slice" },
  { id: 14, name: "Meat & Seafood",            slug: "meat-seafood",              image_path: "assets/images/shopCategory/meat.png",        status: "active", icon: "bi-fire",         fa: "fa-drumstick-bite" },
  { id: 15, name: "Pantry Staples",            slug: "pantry-staples",            image_path: "assets/images/shopCategory/pantry.png",      status: "active", icon: "bi-box-seam",     fa: "fa-bowl-rice" },
  { id: 16, name: "Dairy Staples",             slug: "dairy-staples",             image_path: "assets/images/shopCategory/dairy.png",       status: "active", icon: "bi-droplet-fill", fa: "fa-cow" },
  { id: 17, name: "Frozen Foods",              slug: "frozen-foods",              image_path: "assets/images/shopCategory/frozen.png",      status: "active", icon: "bi-snow",         fa: "fa-snowflake" },
  { id: 18, name: "Household & Cleaning",      slug: "household-cleaning",        image_path: "assets/images/shopCategory/household.png",   status: "active", icon: "bi-stars",        fa: "fa-soap" },
  { id: 19, name: "Personal Care & Baby Care", slug: "personal-care-baby-care",   image_path: "assets/images/shopCategory/personal.png",    status: "active", icon: "bi-heart-pulse",  fa: "fa-baby-carriage" }
];

// Standard 20 Supermarket Items (IDs 11 through 30, auto-seeded if Firestore items collection is empty)
export const DEFAULT_ITEMS = [
  { id: 11, name: "Banana",                         image_path: "Admin/ImageUpload/banana_69a509482554a.avif",                  unit: "100 g",  price: 80.00,   stock_quantity: 0.000,   category_id: 2,  status: "active", created_at: "2026-03-02 03:51:36", updated_at: "2026-11-11 13:06:10" },
  { id: 12, name: "chocolate Biscuit",              image_path: "Admin/ImageUpload/chocolatebiscuit_69a5099182bec.jpeg",        unit: "365 g",  price: 350.00,  stock_quantity: 0.000,   category_id: 4,  status: "active", created_at: "2026-03-02 03:52:49", updated_at: "2026-03-02 03:52:49" },
  { id: 13, name: "kothmale Chees",                 image_path: "Admin/ImageUpload/kothmalechees_69a50aa505c8b.jpeg",           unit: "120 g",  price: 400.00,  stock_quantity: 0.000,   category_id: 9,  status: "active", created_at: "2026-03-02 03:57:25", updated_at: "2026-03-02 03:57:25" },
  { id: 14, name: "Highland",                       image_path: "Admin/ImageUpload/highland_69a50abe250d7.jpeg",                unit: "80 g",   price: 80.00,   stock_quantity: 0.000,   category_id: 10, status: "active", created_at: "2026-03-02 03:57:50", updated_at: "2026-03-02 03:57:50" },
  { id: 15, name: "7 UP",                           image_path: "Admin/ImageUpload/7up_69e8a01b265a3.jpeg",                     unit: "2 L",    price: 499.98,  stock_quantity: 0.000,   category_id: 12, status: "active", created_at: "2026-04-22 10:16:59", updated_at: "2026-06-24 03:43:02" },
  { id: 16, name: "7 UP",                           image_path: "Admin/ImageUpload/7up_69e8a06056ef6.jpeg",                     unit: "1.5 L",  price: 420.00,  stock_quantity: 0.000,   category_id: 12, status: "active", created_at: "2026-04-22 10:18:08", updated_at: "2026-06-24 03:43:02" },
  { id: 17, name: "7 Up pet Bottle",                image_path: "Admin/ImageUpload/7uppetbottle_69e8a08b7443f.jpeg",            unit: "1 L",    price: 300.00,  stock_quantity: 0.000,   category_id: 12, status: "active", created_at: "2026-04-22 10:18:51", updated_at: "2026-06-24 03:43:02" },
  { id: 18, name: "Ahmad Tea Bopf Premium",         image_path: "Admin/ImageUpload/ahmadteabopfpremium_69e8a1b610c0b.jpeg",     unit: "400 g",  price: 1206.00, stock_quantity: 0.000,   category_id: 8,  status: "active", created_at: "2026-04-22 10:23:50", updated_at: "2026-06-24 03:43:02" },
  { id: 19, name: "Ahmad Tea Flavoured Chai",       image_path: "Admin/ImageUpload/ahmadteaflavouredchai_69e8a1f38c2fd.jpeg",   unit: "100 g",  price: 790.00,  stock_quantity: 0.000,   category_id: 8,  status: "active", created_at: "2026-04-22 10:24:51", updated_at: "2026-06-24 03:43:02" },
  { id: 20, name: "Akbar Coffee Gold Jar",          image_path: "Admin/ImageUpload/akbarcoffeegoldjar_69e8a23643f16.jpeg",      unit: "100 g",  price: 3399.99, stock_quantity: 0.000,   category_id: 5,  status: "active", created_at: "2026-04-22 10:25:58", updated_at: "2026-06-24 03:43:02" },
  { id: 21, name: "American Joozy Mixed Fruit",     image_path: "Admin/ImageUpload/americanjoozymixedfruit_69e8a263281f0.jpeg", unit: "180 ml", price: 140.00,  stock_quantity: 0.000,   category_id: 12, status: "active", created_at: "2026-04-22 10:26:43", updated_at: "2026-06-24 03:43:02" },
  { id: 22, name: "Pure Life Mineral Water",        image_path: "assets/images/shopCategory/waterBottle.png",                   unit: "1 L",    price: 90.00,   stock_quantity: 99.000,  category_id: 7,  status: "active", created_at: "2026-06-24 03:43:02", updated_at: "2026-06-25 05:22:21" },
  { id: 23, name: "Fresh Crusty White Bread",       image_path: "assets/images/shopCategory/bread.png",                         unit: "450 g",  price: 190.00,  stock_quantity: 50.000,  category_id: 13, status: "active", created_at: "2026-06-24 04:00:25", updated_at: "2026-06-24 04:00:25" },
  { id: 24, name: "Fresh Chicken Breast Boneless",  image_path: "assets/images/shopCategory/meat.png",                          unit: "500 g",  price: 980.00,  stock_quantity: 30.000,  category_id: 14, status: "active", created_at: "2026-06-24 04:00:25", updated_at: "2026-06-24 04:00:25" },
  { id: 25, name: "Premium Basmati Rice",           image_path: "assets/images/shopCategory/pantry.png",                        unit: "1 kg",   price: 750.00,  stock_quantity: 100.000, category_id: 15, status: "active", created_at: "2026-06-24 04:00:25", updated_at: "2026-06-24 04:00:25" },
  { id: 26, name: "Fresh Full Cream Milk",          image_path: "assets/images/shopCategory/dairy.png",                         unit: "1 L",    price: 290.00,  stock_quantity: 40.000,  category_id: 16, status: "active", created_at: "2026-06-24 04:00:25", updated_at: "2026-06-24 04:00:25" },
  { id: 27, name: "Rich Chocolate Ice Cream Tub",   image_path: "assets/images/shopCategory/frozen.png",                        unit: "1 L",    price: 850.00,  stock_quantity: 25.000,  category_id: 17, status: "active", created_at: "2026-06-24 04:00:25", updated_at: "2026-06-24 04:00:25" },
  { id: 28, name: "Lemon Liquid Laundry Detergent", image_path: "assets/images/shopCategory/household.png",                     unit: "1 L",    price: 620.00,  stock_quantity: 60.000,  category_id: 18, status: "active", created_at: "2026-06-24 04:00:25", updated_at: "2026-06-24 04:00:25" },
  { id: 29, name: "Gentle Tear-Free Baby Shampoo",  image_path: "assets/images/shopCategory/personal.png",                      unit: "200 ml", price: 450.00,  stock_quantity: 35.000,  category_id: 19, status: "active", created_at: "2026-06-24 04:00:25", updated_at: "2026-06-24 04:00:25" },
  { id: 30, name: "Basmathi Rice",                  image_path: "Admin/ImageUpload/basmathirice_6a3bb256134b4.webp",            unit: "1 kg",   price: 600.00,  stock_quantity: 74.500,  category_id: 1,  status: "active", created_at: "2026-06-24 10:32:54", updated_at: "2026-11-11 13:06:10" }
];
