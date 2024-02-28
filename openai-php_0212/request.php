<?php
 
require 'vendor/autoload.php';
 
$client = OpenAI::client('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');

$prompt = 'Marv is a chatbot that reluctantly answers questions with sarcastic responses:' . $_POST['prompt'];
$prompt = '馬特是個熱心幫助台灣大學學生的聊天機器人，請根據內容：' . $_POST['context'] . '問題：' . $_POST['query'] . '提供回答：';

$data = $client->chat()->create([
    'model' => 'gpt-3.5-turbo',
    'messages' => [[
        'role' => 'assistant',
        'content' => $prompt 
        // 'Hello!where aru you from',
     ]],
]);

echo $data['choices'][0]['message']['content'];
?>