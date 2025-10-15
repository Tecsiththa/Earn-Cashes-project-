<?php
// ============================================
// process_withdrawal.php
// Process withdrawal approval/rejection
// ============================================
require_once 'config.php';
requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$withdrawal_id = intval($_POST['withdrawal_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($withdrawal_id <= 0 || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

$conn = getDBConnection();
$conn->begin_transaction();

try {
    // Get withdrawal details
    $stmt = $conn->prepare("
        SELECT w.*, u.balance 
        FROM withdrawals w
        JOIN users u ON w.user_id = u.id
        WHERE w.id = ? AND w.status = 'pending'
    ");
    $stmt->bind_param("i", $withdrawal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Withdrawal request not found or already processed');
    }
    
    $withdrawal = $result->fetch_assoc();
    $stmt->close();
    
    if ($action === 'approve') {
        // Check if user has sufficient balance
        if ($withdrawal['balance'] < $withdrawal['amount']) {
            throw new Exception('Insufficient balance');
        }
        
        // Deduct amount from user balance
        $stmt = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $stmt->bind_param("di", $withdrawal['amount'], $withdrawal['user_id']);
        $stmt->execute();
        $stmt->close();
        
        // Update withdrawal status to approved
        $status = 'approved';
        $stmt = $conn->prepare("
            UPDATE withdrawals 
            SET status = ?, processed_at = NOW(), processed_by = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sii", $status, $_SESSION['user_id'], $withdrawal_id);
        $stmt->execute();
        $stmt->close();
        
        $message = 'Withdrawal approved successfully';
        
    } else {
        // Reject withdrawal (no balance change)
        $status = 'rejected';
        $stmt = $conn->prepare("
            UPDATE withdrawals 
            SET status = ?, processed_at = NOW(), processed_by = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sii", $status, $_SESSION['user_id'], $withdrawal_id);
        $stmt->execute();
        $stmt->close();
        
        $message = 'Withdrawal rejected';
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>