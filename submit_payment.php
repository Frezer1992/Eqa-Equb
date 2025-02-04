<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $transaction_id = $_POST['transaction_id'];

    // File Upload Handling
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $target_file = $target_dir . basename($_FILES["payment_screenshot"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Debug: Print file information
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";

    // Validate Image
    $check = getimagesize($_FILES["payment_screenshot"]["tmp_name"]);
    if ($check === false) {
        die("File is not an image.");
    }
    if (!in_array($imageFileType, ["jpg", "jpeg", "png"])) {
        die("Only JPG, JPEG, and PNG files are allowed.");
    }
    if ($_FILES["payment_screenshot"]["size"] > 5 * 1024 * 1024) {
        die("File is too large.");
    }

    // Save the file
    if (move_uploaded_file($_FILES["payment_screenshot"]["tmp_name"], $target_file)) {
        $screenshot_url = "http://" . $_SERVER['HTTP_HOST'] . "/" . $target_file;

        // Telegram Bot API
        $bot_token = "7587807727:AAHR0QYcE6oJgOY5FYdKUvVbq26F0VxT_Zk"; // Replace with a new bot token
        $chat_id = "6430610372"; // Your Telegram chat ID
        $message = "📌 *New Payment Proof Submitted* 📌\n\n"
                 . "💳 *Transaction ID:* `$transaction_id`\n"
                 . "🖼 *Screenshot:* [View Image]($screenshot_url)";

        // Send Text Message
        $telegram_api_url = "https://api.telegram.org/bot$bot_token/sendMessage";
        $data = [
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'Markdown',
        ];
        $response = file_get_contents($telegram_api_url . "?" . http_build_query($data));

        // Debugging the response
        if ($response === FALSE) {
            echo "Error sending message to Telegram.";
        } else {
            echo "Message sent to Telegram successfully.";
            echo "<pre>";
            print_r(json_decode($response, true));  // To see the full response from Telegram API
            echo "</pre>";
        }

        // Send Image
        $telegram_photo_url = "https://api.telegram.org/bot$bot_token/sendPhoto";
        $photo_data = [
            'chat_id' => $chat_id,
            'photo' => $screenshot_url,
            'caption' => "💳 *Transaction ID:* `$transaction_id`",
            'parse_mode' => 'Markdown',
        ];
        file_get_contents($telegram_photo_url . "?" . http_build_query($photo_data));

        echo "Payment proof submitted successfully! We will verify and notify you soon.";
    } else {
        echo "Error uploading file.";
    }
} else {
    echo "Invalid request.";
}
?>