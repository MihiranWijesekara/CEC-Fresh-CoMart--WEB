/**
 * Admin Product Management Helper (Firebase Cloud Firestore)
 * Delegates to handleProductAdd() in Admin/ItemAdd.html
 */
function productAdd() {
  if (typeof window.handleProductAdd === "function") {
    window.handleProductAdd();
  }
}
