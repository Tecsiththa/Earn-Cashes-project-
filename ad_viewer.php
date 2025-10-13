<?php
require_once 'config.php';
requireCustomer();

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get today's earnings
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT COALESCE(total_earned, 0) as today_earned, COALESCE(ads_viewed, 0) as ads_today FROM daily_earnings WHERE user_id = ? AND date = ?");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$today_data = $stmt->get_result()->fetch_assoc();
$today_earnings = $today_data ? $today_data['today_earned'] : 0;
$ads_today = $today_data ? $today_data['ads_today'] : 0;
$stmt->close();

// Get total rewards
$stmt = $conn->prepare("SELECT COALESCE(SUM(reward_earned), 0) as total FROM ad_views WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_rewards = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Get current balance
$stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$balance = $stmt->get_result()->fetch_assoc()['balance'];
$stmt->close();

// Get next available ad
$stmt = $conn->prepare("
    SELECT a.* FROM advertisements a 
    WHERE a.is_active = 1 
    AND a.video_url IS NOT NULL
    AND a.id NOT IN (SELECT ad_id FROM ad_views WHERE user_id = ?)
    ORDER BY a.created_at DESC
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$ad = $stmt->get_result()->fetch_assoc();
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advertisement Viewing - Earncash</title>
    <link rel="stylesheet" href="assets/css/ad_viewer.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1 class="logo">Earncash</h1>
            <nav class="nav">
                <a href="advertisements.php">HOME</a>
                <a href="#">CONTACT US</a>
                <a href="#">ABOUT US</a>
                <a href="logout.php">LOGOUT</a>
            </nav>
        </header>

        <main class="main-content">
            <div class="ad-section">
                <h2>Advertisement viewing</h2>
                <p class="reward-text">You will earn $0.05 for viewing this ad</p>

                <div class="stats-bar">
                    <div class="stat-item">
                        <div class="stat-label">Today's earnings</div>
                        <div class="stat-value" id="todayEarnings">$<?php echo number_format($today_earnings, 2); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Ads viewed(per day)</div>
                        <div class="stat-value" id="adsToday"><?php echo $ads_today; ?>/10</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Total rewards</div>
                        <div class="stat-value" id="totalRewards">$<?php echo number_format($total_rewards, 2); ?></div>
                    </div>
                </div>

                <div class="video-container" id="videoContainer">
                    <?php if ($ad): ?>
                        <video id="adVideo" controls>
                            <source src="<?php echo htmlspecialchars($ad['video_url']); ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    <?php else: ?>
                        <div class="no-ads-message">
                            <p>No more ads available today. Check back tomorrow!</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="progress-section">
                    <p class="progress-text">Minimum viewing time : 30 seconds</p>
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                </div>

                <div class="rewards-box">
                    <h3>Your Rewards</h3>
                    <p class="coins-amount">$<span id="pendingReward">0.05</span> coins</p>
                    <p class="claim-instruction">complete the ad to claim your rewards</p>
                </div>

                <div class="message" id="messageBox" style="display: none;"></div>

                <div class="action-buttons">
                    <button class="btn btn-claim" id="claimBtn" disabled>Claim Rewards</button>
                    <button class="btn btn-next" id="nextAdBtn">Next Ad</button>
                    <button class="btn btn-dashboard" onclick="window.location.href='advertisements.php'">Dashboard</button>
                </div>
            </div>
        </main>

        <footer class="footer">
            <p>FOOTER</p>
        </footer>
    </div>

    <script>
        const adData = <?php echo $ad ? json_encode($ad) : 'null'; ?>;
        const userId = <?php echo $user_id; ?>;
    </script>
    <script src="assets/js/ad_viewer.js"></script>
</body>
</html>