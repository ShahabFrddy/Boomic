<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$server_id = $_GET['id'];

// دریافت اطلاعات سرور
$stmt = $pdo->prepare("SELECT * FROM servers WHERE id = ?");
$stmt->execute([$server_id]);
$server = $stmt->fetch();

// دریافت کانال‌های سرور
$stmt = $pdo->prepare("SELECT * FROM channels WHERE server_id = ?");
$stmt->execute([$server_id]);
$channels = $stmt->fetchAll();

// دریافت پیام‌های کانال انتخاب شده
$selected_channel = $_GET['channel'] ?? $channels[0]['id'];
$stmt = $pdo->prepare("
    SELECT m.*, u.username 
    FROM messages m 
    JOIN users u ON m.user_id = u.id 
    WHERE m.channel_id = ? 
    ORDER BY m.created_at ASC
");
$stmt->execute([$selected_channel]);
$messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($server['name']); ?> - Discord Clone</title>
    <style>
        .container { display: flex; }
        .channels { width: 200px; }
        .messages { flex: 1; }
        .message { margin: 10px 0; }
    </style>
</head>
<body>
    <h2><?php echo htmlspecialchars($server['name']); ?></h2>
    
    <div class="container">
        <div class="channels">
            <h3>Channels</h3>
            <?php foreach ($channels as $channel): ?>
                <div>
                    <a href="server.php?id=<?php echo $server_id; ?>&channel=<?php echo $channel['id']; ?>">
                        # <?php echo htmlspecialchars($channel['name']); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="messages">
            <h3>Messages</h3>
            <?php foreach ($messages as $message): ?>
                <div class="message">
                    <strong><?php echo htmlspecialchars($message['username']); ?>:</strong>
                    <?php echo htmlspecialchars($message['content']); ?>
                    <small><?php echo $message['created_at']; ?></small>
                </div>
            <?php endforeach; ?>
            
            <form method="POST" action="send_message.php">
                <input type="hidden" name="channel_id" value="<?php echo $selected_channel; ?>">
                <input type="text" name="message" placeholder="Type your message..." required>
                <button type="submit">Send</button>
            </form>
        </div>
    </div>
</body>
</html>