<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Bridge Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; }
        label { display: block; margin-top: 15px; font-weight: bold; }
        input, button { width: 100%; padding: 10px; margin-top: 5px; box-sizing: border-box; }
        .warning { background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>SMS Bridge Test</h1>
    <p>This is a local test form for the SMS bridge. The server-side endpoint at <code>function/sms.php</code> requires the API key configured in <code>.env</code>.</p>

    <form method="post" action="function/sms.php">
        <label for="api_key">API Key</label>
        <input type="text" id="api_key" name="api_key" required placeholder="Enter SMS_API_KEY from .env">

        <label for="username">Gateway Username</label>
        <input type="text" id="username" name="username" required placeholder="Android SMS Gateway username">

        <label for="password">Gateway Password</label>
        <input type="password" id="password" name="password" required placeholder="Android SMS Gateway password">

        <label for="number">Phone Number</label>
        <input type="text" id="number" name="number" required placeholder="9XXXXXXXXX (without +63)">

        <label for="message">Message</label>
        <input type="text" id="message" name="message" required placeholder="Your message">

        <button type="submit" style="margin-top: 20px;">Send</button>
    </form>

    <p class="warning">Do not store or share real credentials in source files. Use <code>.env</code> for secrets.</p>
</body>
</html>
