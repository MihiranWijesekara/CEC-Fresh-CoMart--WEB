<?php
require_once __DIR__ . '/../connection.php';

echo "=== TASK 6 AUTOMATED INTEGRATION TESTS ===\n\n";

// 1. Test liveSearch.php
echo "--- 1. Testing Live Search Endpoint ---\n";
$_GET['q'] = 'water';
$_GET['category'] = '0';
ob_start();
include __DIR__ . '/../liveSearch.php';
$jsonStr = ob_get_clean();
$data = json_decode($jsonStr, true);

echo "Search query 'water' returned " . count($data['results']) . " results.\n";
assert($data['status'] === 'success', "Status must be success");
assert(count($data['results']) > 0, "Must return at least 1 result for 'water'");
$first = $data['results'][0];
echo "First result: {$first['name']} - {$first['price_formatted']} (In stock: " . ($first['in_stock'] ? 'Yes' : 'No') . ")\n";
echo ">>> Live Search Test PASSED! <<<\n\n";

// 2. Test updateProfileProcess.php
echo "--- 2. Testing Customer Profile & Address Update ---\n";
session_start();
$_SESSION['user_id'] = 13;
$_POST = [
    'first_name' => 'Nimal',
    'last_name' => 'Perera',
    'phone_number' => '0779998888',
    'street_address1' => '100 Green Gardens',
    'street_address2' => 'Tower A, Floor 5',
    'town' => 'Rajagiriya'
];

ob_start();
include __DIR__ . '/../updateProfileProcess.php';
$respProfile = trim(ob_get_clean());
echo "Update Profile output: {$respProfile}\n";
assert($respProfile === 'success', "Profile update should return 'success'");

// Verify in DB
$userCheck = Database::search("SELECT first_name, last_name, phone_number, street_address1, street_address2, town FROM users WHERE id = 13")->fetch_assoc();
echo "Updated address in DB: {$userCheck['street_address1']}, {$userCheck['town']}, Phone: {$userCheck['phone_number']}\n";
assert($userCheck['phone_number'] === '0779998888', "Phone must be updated");
assert($userCheck['street_address1'] === '100 Green Gardens', "Address 1 must be updated");
assert($userCheck['town'] === 'Rajagiriya', "Town must be updated");
echo ">>> Customer Profile & Address Test PASSED! <<<\n\n";

// 3. Test contact-usProcess.php & PRG
echo "--- 3. Testing Contact Us Form PRG Submission ---\n";
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
$_POST = [
    'contactName' => 'Fresh Tester',
    'email' => 'tester@example.com',
    'subject' => 'Store Pickup Query',
    'message' => 'Hello, can I collect my order at 8:30 PM today?'
];

ob_start();
include __DIR__ . '/../contact-usProcess.php';
$respContact = trim(ob_get_clean());
echo "Contact form output: {$respContact}\n";
assert($respContact === 'success', "Contact form should return 'success'");

$contactCheck = Database::search("SELECT * FROM contacts WHERE email = 'tester@example.com' ORDER BY id DESC LIMIT 1")->fetch_assoc();
echo "Saved inquiry in DB: #{$contactCheck['id']} - '{$contactCheck['subject']}' by {$contactCheck['name']}\n";
assert($contactCheck['subject'] === 'Store Pickup Query', "Subject must match");
assert(!empty($contactCheck['created_at']), "Timestamp must be saved");

// Clean up test inquiry
Database::iud("DELETE FROM contacts WHERE id = '{$contactCheck['id']}'");
echo "Cleaned up test inquiry #{$contactCheck['id']}.\n";
echo ">>> Contact Us Form Test PASSED! <<<\n\n";

echo "=== ALL TASK 6 INTEGRATION TESTS COMPLETED SUCCESSFULLY! ===\n";

