<?php
// google_config.php — Google OAuth 2.0 configuration
//
// HOW TO SET UP:
// 1. Go to https://console.cloud.google.com/
// 2. Create a new project (or select existing)
// 3. Go to APIs & Services > Credentials
// 4. Click "Create Credentials" > "OAuth client ID"
// 5. Application type: "Web application"
// 6. Add Authorized redirect URI: http://localhost/CAPSTONE_AQUALARION/google_callback.php
// 7. Copy Client ID and Client Secret below

define('GOOGLE_CLIENT_ID',     'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI',  'http://localhost/CAPSTONE_AQUALARION/google_callback.php');
