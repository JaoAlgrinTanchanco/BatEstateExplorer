<?php
/**
 * cleanup_database.php
 * ✅ Cleans up all orphaned database records tied to invalid or removed accounts
 * ✅ Follows account logic:
 *    - KEEP: 
 *        - User exists in `users`
 *        - User exists in `applications` (not rejected)
 *    - DELETE (cleanup):
 *        - User only in rejected `applications`
 *        - User exists in both tables, but `applications.status = 'rejected'`
 * ✅ Removes all DB traces (agents, messages, properties, drafts, transactions, reviews, etc.)
 */

require_once __DIR__ . '/../config/database.php';

// 🔧 Toggle simulation (true = preview, false = actual cleanup)
$simulate = false;

/**
 * Fetch helper returning an array of IDs from a query
 */
function fetchIds(mysqli $conn, string $query, string $col): array {
    $ids = [];
    $res = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($res)) {
        $ids[] = (int)$row[$col];
    }
    return $ids;
}

/**
 * Delete helper
 */
function deleteByIds(mysqli $conn, string $table, array $ids, bool $simulate): int {
    if (empty($ids)) return 0;
    $idList = implode(',', $ids);
    if (!$simulate) {
        mysqli_query($conn, "DELETE FROM `$table` WHERE id IN ($idList)");
    }
    return count($ids);
}

/**
 * Core cleanup by reference column
 */
function cleanupByUserRef(mysqli $conn, string $table, string $column, array $validSet, bool $simulate): int {
    $res = mysqli_query($conn, "SELECT id, $column FROM `$table`");
    $deleteIds = [];
    while ($row = mysqli_fetch_assoc($res)) {
        if (!isset($validSet[(int)$row[$column]])) {
            $deleteIds[] = (int)$row['id'];
        }
    }
    return deleteByIds($conn, $table, $deleteIds, $simulate);
}

/**
 * Cleanup by property reference
 */
function cleanupByPropRef(mysqli $conn, string $table, string $column, array $propSet, bool $simulate): int {
    $res = mysqli_query($conn, "SELECT id, $column FROM `$table`");
    $deleteIds = [];
    while ($row = mysqli_fetch_assoc($res)) {
        if (!isset($propSet[(int)$row[$column]])) {
            $deleteIds[] = (int)$row['id'];
        }
    }
    return deleteByIds($conn, $table, $deleteIds, $simulate);
}

// 🧩 STEP 1: Identify user validity

// All existing users
$users = fetchIds($conn, "SELECT id FROM users", 'id');
$userSet = array_flip($users);

// Applications by status
$appApproved = fetchIds($conn, "SELECT user_id FROM applications WHERE status = 'approved' AND user_id IS NOT NULL", 'user_id');
$appPending  = fetchIds($conn, "SELECT user_id FROM applications WHERE status = 'pending' AND user_id IS NOT NULL", 'user_id');
$appRejected = fetchIds($conn, "SELECT user_id FROM applications WHERE status = 'rejected' AND user_id IS NOT NULL", 'user_id');

$appApprovedSet = array_flip($appApproved);
$appPendingSet  = array_flip($appPending);
$appRejectedSet = array_flip($appRejected);

// Build valid user set based on logic
$validUserSet = [];
foreach ($users as $uid) {
    $validUserSet[$uid] = true; // ✅ user in `users`
}
foreach ($appApproved as $uid) {
    $validUserSet[$uid] = true; // ✅ approved app
}
foreach ($appPending as $uid) {
    if (!isset($validUserSet[$uid])) $validUserSet[$uid] = true; // ✅ pending app only
}

// Determine invalid users (cleanup target)
$allRefs = array_unique(array_merge($users, $appApproved, $appPending, $appRejected));
$invalidUserIds = [];
foreach ($allRefs as $uid) {
    if (!isset($validUserSet[$uid])) {
        $invalidUserIds[] = $uid; // ❌ invalid account
    }
}

// --- 🧹 STEP 2: Cleanup all user-dependent tables ---
$totalDeleted = 0;

// Messages (sender & receiver)
$totalDeleted += cleanupByUserRef($conn, 'messages', 'sender_id', $validUserSet, $simulate);
$totalDeleted += cleanupByUserRef($conn, 'messages', 'receiver_id', $validUserSet, $simulate);

// Agents
$totalDeleted += cleanupByUserRef($conn, 'agents', 'user_id', $validUserSet, $simulate);

// Transactions
$totalDeleted += cleanupByUserRef($conn, 'transactions', 'user_id', $validUserSet, $simulate);

// Property drafts
$totalDeleted += cleanupByUserRef($conn, 'property_drafts', 'user_id', $validUserSet, $simulate);

// Property reviews
$totalDeleted += cleanupByUserRef($conn, 'property_reviews', 'user_id', $validUserSet, $simulate);

// --- 🏠 STEP 3: Cleanup properties owned by invalid users or agents ---

$deletePropIds = [];
$res = mysqli_query($conn, "SELECT id, user_id, agent_id, listed_by_agent_id, sold_by_agent_id FROM properties");
while ($row = mysqli_fetch_assoc($res)) {
    $uid = (int)$row['user_id'];
    $aid = (int)$row['agent_id'];
    $lid = (int)$row['listed_by_agent_id'];
    $sid = (int)$row['sold_by_agent_id'];
    // If any reference invalid → mark property for deletion
    if (
        ($uid && !isset($validUserSet[$uid])) ||
        ($aid && !isset($validUserSet[$aid])) ||
        ($lid && !isset($validUserSet[$lid])) ||
        ($sid && !isset($validUserSet[$sid]))
    ) {
        $deletePropIds[] = (int)$row['id'];
    }
}
$totalDeleted += deleteByIds($conn, 'properties', $deletePropIds, $simulate);

// --- 🧹 STEP 4: Cascade delete orphaned property data ---
$validProps = fetchIds($conn, "SELECT id FROM properties", 'id');
$propSet = array_flip($validProps);

$totalDeleted += cleanupByPropRef($conn, 'property_images', 'property_id', $propSet, $simulate);
$totalDeleted += cleanupByPropRef($conn, 'property_documents', 'property_id', $propSet, $simulate);
$totalDeleted += cleanupByPropRef($conn, 'property_reviews', 'property_id', $propSet, $simulate);

// --- 🧹 STEP 5: Cleanup saved_properties if exists ---
$tables = mysqli_query($conn, "SHOW TABLES LIKE 'saved_properties'");
if (mysqli_num_rows($tables) > 0) {
    $totalDeleted += cleanupByUserRef($conn, 'saved_properties', 'user_id', $validUserSet, $simulate);
    $totalDeleted += cleanupByPropRef($conn, 'saved_properties', 'property_id', $propSet, $simulate);
}

// --- 🧹 STEP 6: Finally, remove invalid users (if safe) ---
$totalDeleted += deleteByIds($conn, 'users', $invalidUserIds, $simulate);
?>
