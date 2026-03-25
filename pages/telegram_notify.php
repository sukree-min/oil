<?php
// pages/booking/api_car/telegram_notify.php

function sendTelegramNotification($message, $conn) {
    try {
        // Fetch Telegram configs (Filtered by 'sukree' as requested)
        $stmt = $conn->query("SELECT bot_token, chat_id, bot_name FROM telegram WHERE bot_name IN ('oil', '1')");
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($configs)) return;

        foreach ($configs as $config) {
            $token = $config['bot_token'];
            $chat_id = $config['chat_id'];
            $bot_name = $config['bot_name'];
            
            if (empty($token) || empty($chat_id)) continue;

            $url = "https://api.telegram.org/bot{$token}/sendMessage";
            $data = [
                'chat_id' => $chat_id,
                'text' => "{$bot_name}: {$message}",
                'parse_mode' => 'HTML' 
            ];

            // Use curl to send request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local dev
            $result = curl_exec($ch);
            curl_close($ch);
        }
    } catch (Exception $e) {
        // Silently fail to not interrupt the main process
        error_log("Telegram Error: " . $e->getMessage());
    }
}
?>
