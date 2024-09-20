<?php
// OpenAIService.php
namespace ACG\Services;
class OpenAIService {

    private $api_key;

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    public function generateWithChatGPT($prompt) {
        $endpoint = 'https://api.openai.com/v1/chat/completions';
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_key,
        ];

        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful assistant.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 500,
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if (!$response) {
            error_log('Failed to request response from ChatGPT.');
            return ['content' => 'Error communicating with ChatGPT.', 'logs' => ['Communication error']];
        }

        if ($http_status !== 200) {
            error_log('Unexpected HTTP status from ChatGPT: ' . $http_status);
            error_log('Response from ChatGPT: ' . print_r($response, true));
            return ['content' => 'Error communicating with ChatGPT. HTTP Status: ' . $http_status, 'logs' => ['Unexpected HTTP status']];
        }

        $decoded_response = json_decode($response, true);
        if (isset($decoded_response['choices'][0]['message']['content'])) {
            return ['content' => $decoded_response['choices'][0]['message']['content'], 'logs' => []];
        } else {
            error_log('Unexpected ChatGPT response structure: ' . print_r($decoded_response, true));
            return ['content' => 'Unexpected response structure from ChatGPT.', 'logs' => ['Unexpected response structure']];
        }
    }
}
?>
