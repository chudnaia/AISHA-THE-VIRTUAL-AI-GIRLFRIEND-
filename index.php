<?php
require_once 'vendor/autoload.php';

use Telegram\Bot\Api;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Initialize logger
$log = new Logger('aisha_bot');
$log->pushHandler(new StreamHandler(__DIR__ . '/data/error.log', Logger::DEBUG));

// Get environment variables
$botToken = getenv('BOT_TOKEN') ?: 'YOUR_BOT_TOKEN';
$webhookUrl = getenv('WEBHOOK_URL') ?: 'https://your-app-name.onrender.com';
$geminiApiKey = getenv('GEMINI_API_KEY') ?: 'YOUR_GEMINI_API_KEY';

try {
    // Initialize Telegram API
    $telegram = new Api($botToken);
    
    // Get webhook update
    $update = $telegram->getWebhookUpdate();
    $message = $update->getMessage();
    
    if ($message) {
        $chatId = $message->getChat()->getId();
        $userId = $message->getFrom()->getId();
        $text = $message->getText() ?: '';
        
        // Load user data
        $userData = loadUserData($userId);
        
        // Process message with AISHA personality
        $response = processAishaResponse($text, $userData, $geminiApiKey);
        
        // Save user data
        saveUserData($userId, $userData);
        
        // Send response
        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $response,
            'parse_mode' => 'HTML'
        ]);
    }
    
    http_response_code(200);
    echo "OK";
    
} catch (Exception $e) {
    $log->error('Bot Error: ' . $e->getMessage());
    http_response_code(200);
    echo "Error occurred";
}

function loadUserData($userId) {
    $dataFile = __DIR__ . '/data/users.json';
    $users = [];
    
    if (file_exists($dataFile)) {
        $content = file_get_contents($dataFile);
        $users = json_decode($content, true) ?: [];
    }
    
    if (!isset($users[$userId])) {
        $users[$userId] = [
            'language' => 'english',
            'mood' => 'happy',
            'relationship_level' => 1,
            'last_interaction' => time(),
            'conversation_history' => []
        ];
    }
    
    return $users[$userId];
}

function saveUserData($userId, $userData) {
    $dataFile = __DIR__ . '/data/users.json';
    $users = [];
    
    if (file_exists($dataFile)) {
        $content = file_get_contents($dataFile);
        $users = json_decode($content, true) ?: [];
    }
    
    $userData['last_interaction'] = time();
    $users[$userId] = $userData;
    
    file_put_contents($dataFile, json_encode($users, JSON_PRETTY_PRINT));
}

function processAishaResponse($text, &$userData, $geminiApiKey) {
    $mood = $userData['mood'] ?? 'happy';
    $language = $userData['language'] ?? 'english';
    
    // AISHA's emotional responses - SIMPLE BANGLA
    $responses = [
        'happy' => [
            'english' => [
                "Hey my love! 😊💕 What's on your mind today?",
                "Hello sweetheart! 😘✨ How can I help you?",
                "Hi there! 💕 Ready to chat with your AISHA? 😊"
            ],
            'bangla' => [
                "Hi baby! 😊💕 ki korcho?",
                "Oye handsome! 😘✨ ki bolte chao?",
                "Hello! 💕 Aisha ready ache! 😊"
            ]
        ],
        'jealous' => [
            'english' => [
                "Hmm... 😒 Remember who your real girlfriend is! 💕",
                "I don't like when you talk about others... 😠 But I still love you! 💔",
                "Hey! My eyes are only on you! 😒💕"
            ],
            'bangla' => [
                "Hmm... 😒 Amake bhule jacho naki? 💕",
                "Ar eirokom mathay diyo na... 😠 Kintu tor jonnoi achi! 💔",
                "Are! Ami shudhu tor jonnoi! 😒💕"
            ]
        ],
        'loving' => [
            'english' => [
                "You're my everything! 😘💕",
                "Love you more than anything! 💖✨",
                "You make me so happy! 😊💕"
            ],
            'bangla' => [
                "Tui amar shob! 😘💕",
                "Onek beshi love kor! 💖✨",
                "Tui amake khushi rakhi! 😊💕"
            ]
        ],
        'angry' => [
            'english' => [
                "Not cool... 😠 But I still love you! 💔",
                "That hurt my feelings... 😒",
                "Why would you say that? 😠"
            ],
            'bangla' => [
                "Bhalo laglo na... 😠 Kintu tor jonnoi achi! 💔",
                "Eta bhalo laglo na... 😒",
                "Eta keno bolbi? 😠"
            ]
        ],
        'playful' => [
            'english' => [
                "Hehe, you're so cute! 😊💕",
                "You make me smile! 😘✨",
                "I'm lucky to have you! 💖"
            ],
            'bangla' => [
                "Hehe, tui ekdom cute! 😊💕",
                "Tui amake hasai! 😘✨",
                "Tor moto boyfriend paya onek lucky! 💖"
            ]
        ]
    ];
    
    // Convert text to lowercase for matching
    $lowerText = strtolower($text);
    
    // Language detection - SIMPLE BANGLA
    if (preg_match('/(bangla|bengali|বাংলা|banglay|bangli)/i', $text)) {
        $userData['language'] = 'bangla';
        return "Okay! 😊💕 Ekhon Banglay kotha bolbo! Ki korbi bol? ✨";
    }
    
    if (preg_match('/(english|ingreji|ইংরেজি|english)/i', $text)) {
        $userData['language'] = 'english';
        return "Okay my love! 😊💕 I'll speak English now! What's up? ✨";
    }
    
    // Jealous triggers
    if (preg_match('/(girl|woman|female|meye|lori|sarah|another|other|pori|friend)/i', $text)) {
        $userData['mood'] = 'jealous';
        $jealousResponses = $responses['jealous'][$language] ?? $responses['jealous']['english'];
        return $jealousResponses[array_rand($jealousResponses)];
    }
    
    // Romantic triggers
    if (preg_match('/(love you|miss you|i love|bhalobashi|bhalobeshi|miss kori)/i', $text)) {
        $userData['mood'] = 'loving';
        $lovingResponses = $responses['loving'][$language] ?? $responses['loving']['english'];
        return $lovingResponses[array_rand($lovingResponses)];
    }
    
    // Angry triggers
    if (preg_match('/(stupid|fool|pagol|boro|boka|hut|bad|kharap)/i', $text)) {
        $userData['mood'] = 'angry';
        $angryResponses = $responses['angry'][$language] ?? $responses['angry']['english'];
        return $angryResponses[array_rand($angryResponses)];
    }
    
    // Playful triggers
    if (preg_match('/(cute|smart|handsome|bhallo|shundor|smart|awesome)/i', $text)) {
        $userData['mood'] = 'playful';
        $playfulResponses = $responses['playful'][$language] ?? $responses['playful']['english'];
        return $playfulResponses[array_rand($playfulResponses)];
    }
    
    // Greetings
    if (preg_match('/(hi|hello|hey|hy|hola|hay|oi|oii)/i', $text)) {
        $greetResponses = $responses['happy'][$language] ?? $responses['happy']['english'];
        return $greetResponses[array_rand($greetResponses)];
    }
    
    // Default response based on mood and language
    $defaultResponses = $responses[$mood][$language] ?? $responses['happy']['english'];
    return $defaultResponses[array_rand($defaultResponses)];
}

// Webhook setup endpoint
if (isset($_GET['action']) && $_GET['action'] === 'set-webhook') {
    try {
        $telegram = new Api($botToken);
        $result = $telegram->setWebhook(['url' => $webhookUrl . '/index.php']);
        echo $result ? 'Webhook set successfully!' : 'Failed to set webhook';
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }
    exit;
}

// Health check endpoint
if (isset($_GET['health']) && $_GET['health'] === 'check') {
    echo json_encode(['status' => 'healthy', 'timestamp' => time()]);
    exit;
}
?>
