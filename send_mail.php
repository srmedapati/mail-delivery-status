<?php
use Google\Service\Gmail\Message;
use Google\Service\Gmail\MessagePart;
use Google\Service\Gmail\MessagePartBody;

require __DIR__ . '/vendor/autoload.php';

use Google\Client;
use Google\Service\Gmail;

/**
 * Returns an authorized API client.
 * @return Client the authorized client object
 */

function getClient()
{
    $client = new Client();
    $client->setApplicationName('PHP GMail');
    $client->setScopes('https://mail.google.com/');
    $client->setAuthConfig('<<CREDENTIALS.json>>');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    // Load previously authorized token from a file, if it exists.
    // The file token.json stores the user's access and refresh tokens, and is
    // created automatically when the authorization flow completes for the first
    // time.
  
    $tokenPath = 'token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
        }
        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }
    return $client;
}

// Build the email
// Build the message
function makeMessage($to, $subject, $body) {
    $text = new MessagePart();
    $text->setBody(new MessagePartBody());
    $text->getBody()->setData($body);
    $message = new Message();
    $message->setRaw(base64_encode("To: $to\r\n".
                                   "Disposition-Notification-To: 'yourname@maildomain'\r\n".
                                   "Subject: $subject\r\n".
                                   "Content-Type: text/plain; charset=UTF-8\r\n".
                                   "\r\n" .
                                   "$body\r\n"));
    return $message;
}

// Get the API client and construct the service object.
$client = getClient();
$service = new Gmail($client);

try {
    $user = 'me';
    $to = 'RECIPIENT_MAIL@DOMAIN';
    $subject = 'Test Email';
    $email = makeMessage($to, $subject, 'Hello World..!');
    $result = $service->users_messages->send('me', $email);
    printf("Message sent to %s", $to);
    print_r($result);
}
catch(Exception $e) {
    echo 'Message: ' .$e->getMessage();
}
