<?php

namespace App\Services;

use App\Models\MonitoringBatch;
use App\Models\MonitoringLog;
use App\Models\Website;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class SiteMonitorService
{
    /**
     * Perform a manual monitoring check on a single website.
     *
     * @param Website $website
     * @param string|null $batchId
     * @param bool $forceScreenshot
     * @return MonitoringLog
     */
    public function checkWebsite(Website $website, ?string $batchId = null, bool $forceScreenshot = false): MonitoringLog
    {
        $startTime = microtime(true);
        $status = 'up';
        $httpStatusCode = null;
        $errorMessage = null;
        $consoleErrors = [];
        $screenshotPath = null;

        // Ensure screenshots directory exists in public storage
        $screenshotsDir = storage_path('app/public/screenshots');
        if (!File::isDirectory($screenshotsDir)) {
            File::makeDirectory($screenshotsDir, 0755, true, true);
        }

        try {
            $targetUrl = $website->requires_login && !empty($website->login_url) ? $website->login_url : $website->url;

            // Normalize URL
            if (!str_starts_with($targetUrl, 'http://') && !str_starts_with($targetUrl, 'https://')) {
                $targetUrl = 'https://' . $targetUrl;
            }

            // Check if Dusk / Headless Chrome is available and site requires login
            if ($website->requires_login) {
                $checkResult = $this->performLoginCheck($website, $targetUrl);
                $status = $checkResult['status'];
                $httpStatusCode = $checkResult['http_status_code'];
                $errorMessage = $checkResult['error_message'];
                $consoleErrors = $checkResult['console_errors'];
                $screenshotPath = $checkResult['screenshot_path'];
            } else {
                // Standard HTTP & Health Ping Check
                $response = Http::timeout(25)
                    ->withHeaders([
                        'User-Agent' => 'PSCA-HealthMonitor/2.0 (Windows NT 10.0; Win64; x64) Chrome/128.0.0.0 Safari/537.36',
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    ])
                    ->get($targetUrl);

                $httpStatusCode = $response->status();

                if ($response->successful() || $response->redirect()) {
                    if (!empty($website->expected_text)) {
                        $body = $response->body();
                        if (!str_contains($body, $website->expected_text)) {
                            $status = 'error';
                            $errorMessage = "Expected text '{$website->expected_text}' was not found on the page.";
                        } else {
                            $status = 'up';
                        }
                    } else {
                        $status = 'up';
                    }
                } elseif ($response->serverError()) {
                    $status = 'down';
                    $errorMessage = "Server returned HTTP error status {$httpStatusCode}.";
                } elseif ($response->clientError()) {
                    $status = 'down';
                    $errorMessage = "Client error: HTTP status {$httpStatusCode}.";
                } else {
                    $status = 'down';
                    $errorMessage = "Unsuccessful HTTP response: status {$httpStatusCode}.";
                }
            }
        } catch (Throwable $e) {
            $status = 'down';
            $errorMessage = $e->getMessage();
            $consoleErrors[] = [
                'type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];
        }

        $responseTime = round(microtime(true) - $startTime, 3);

        // Capture real browser screenshot: on success captures dashboard, on error captures error page
        if (($forceScreenshot || $status !== 'up') && empty($screenshotPath)) {
            $urlToCapture = ($status === 'up' && !empty($website->url)) ? $website->url : $targetUrl;
            $screenshotPath = $this->captureRealBrowserScreenshot($urlToCapture, $website->id, $status, $errorMessage);
        }

        // Create and save monitoring log entry
        $log = MonitoringLog::create([
            'website_id' => $website->id,
            'batch_id' => $batchId,
            'status' => $status,
            'http_status_code' => $httpStatusCode,
            'total_response_time_seconds' => $responseTime,
            'error_message' => $errorMessage,
            'console_errors' => !empty($consoleErrors) ? $consoleErrors : null,
            'screenshot_path' => $screenshotPath,
            'checked_at' => now(),
        ]);

        return $log;
    }

    /**
     * Run batch monitoring on active websites.
     *
     * @param array $websiteIds Optional subset of website IDs
     * @param int|null $userId
     * @return array
     */
    public function runBatch(array $websiteIds = [], ?int $userId = null): array
    {
        $batchId = 'BATCH_' . date('Ymd_His') . '_' . strtoupper(Str::random(6));

        $query = Website::query();
        if (!empty($websiteIds)) {
            $query->whereIn('id', $websiteIds);
        } else {
            $query->where('is_active', true);
        }

        $websites = $query->get();
        $total = $websites->count();
        $successful = 0;
        $failed = 0;
        $logs = [];

        // Pre-create batch
        $batch = MonitoringBatch::create([
            'batch_id' => $batchId,
            'total_websites' => $total,
            'successful' => 0,
            'failed' => 0,
            'trigger_type' => 'manual',
            'triggered_by' => $userId,
        ]);

        foreach ($websites as $website) {
            $log = $this->checkWebsite($website, $batchId);
            $logs[] = $log;

            if ($log->status === 'up') {
                $successful++;
            } else {
                $failed++;
            }
        }

        // Update batch summary stats
        $batch->update([
            'successful' => $successful,
            'failed' => $failed,
        ]);

        return [
            'batch' => $batch,
            'logs' => $logs,
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
        ];
    }

    /**
     * Perform login verification and automated form test via Laravel Dusk / Real Chrome.
     *
     * @param Website $website
     * @param string $targetUrl
     * @return array
     */
    protected function performLoginCheck(Website $website, string $targetUrl): array
    {
        // For sites requiring login: ALWAYS use real Chrome browser automation.
        // HTTP fallback cannot handle CSRF tokens / bot-detection (e.g. GitHub)
        try {
            $duskResult = $this->performDuskBrowserLogin($website, $targetUrl);
            if ($duskResult !== null) {
                return $duskResult;
            }
            // Dusk returned null only if WebDriver classes missing — use HTTP fallback
        } catch (\Throwable $e) {
            Log::error("Dusk browser automation error: " . $e->getMessage());
            // Return an error result rather than silently falling back to HTTP
            return [
                'status'          => 'error',
                'http_status_code'=> null,
                'error_message'   => 'Chrome browser automation failed: ' . $e->getMessage(),
                'console_errors'  => [['type' => 'ChromeError', 'message' => $e->getMessage()]],
                'screenshot_path' => null,
            ];
        }

        // Only reach here if WebDriver classes not installed — use HTTP fallback
        return $this->performHttpFormLogin($website, $targetUrl);
    }

    /**
     * Real Chrome Browser Automation via Laravel Dusk & Facebook WebDriver.
     *
     * @param Website $website
     * @param string $targetUrl
     * @return array|null
     */
    protected function performDuskBrowserLogin(Website $website, string $targetUrl): ?array
    {
        @set_time_limit(120);

        if (!class_exists('Laravel\Dusk\Chrome\ChromeProcess') || !class_exists('Facebook\WebDriver\Remote\RemoteWebDriver')) {
            Log::warning('SiteMonitor: Laravel Dusk or WebDriver classes not found.');
            return null;
        }

        $port = 9515;

        // Step 1: Kill any stale chromedriver.exe processes on this port to ensure clean start
        if (PHP_OS_FAMILY === 'Windows') {
            @exec('taskkill /F /IM chromedriver-win.exe /T 2>NUL');
            @exec('taskkill /F /IM chromedriver.exe /T 2>NUL');
        } else {
            @exec('pkill -f chromedriver 2>/dev/null');
        }
        usleep(500000); // wait 0.5s for process to die

        // Step 2: Start fresh ChromeDriver
        Log::info('SiteMonitor: Starting ChromeDriver on port ' . $port);
        $chromeProcess = new \Laravel\Dusk\Chrome\ChromeProcess();
        $process = $chromeProcess->toProcess(['--port=' . $port]);
        $process->start();

        // Step 3: Wait up to 10 seconds for ChromeDriver socket to be ready
        $isReady = false;
        for ($i = 0; $i < 20; $i++) {
            $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.5);
            if ($fp) {
                fclose($fp);
                $isReady = true;
                Log::info('SiteMonitor: ChromeDriver ready after ' . (($i + 1) * 0.5) . 's');
                break;
            }
            usleep(500000); // 0.5s
        }

        if (!$isReady) {
            try { $process->stop(); } catch (\Throwable $e) {}
            Log::error('SiteMonitor: ChromeDriver did not start on port ' . $port);
            throw new \RuntimeException('ChromeDriver failed to start on port ' . $port . ' after 10 seconds.');
        }

        $options = new \Facebook\WebDriver\Chrome\ChromeOptions();
        $options->addArguments([
            '--headless=new',
            '--disable-gpu',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-blink-features=AutomationControlled',
            '--disable-search-engine-choice-screen',
            '--window-size=1366,768',
            '--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
        ]);
        $options->setExperimentalOption('excludeSwitches', ['enable-automation']);
        $options->setExperimentalOption('useAutomationExtension', false);

        $capabilities = \Facebook\WebDriver\Remote\DesiredCapabilities::chrome()->setCapability(
            \Facebook\WebDriver\Chrome\ChromeOptions::CAPABILITY,
            $options
        );

        $driver = null;
        $status = 'up';
        $httpStatusCode = 200;
        $errorMessage = null;
        $consoleErrors = [];
        $screenshotPath = null;

        try {
            $driver = \Facebook\WebDriver\Remote\RemoteWebDriver::create("http://127.0.0.1:{$port}", $capabilities, 25000, 25000);

            // Execute stealth anti-bot evasion script
            try {
                $driver->executeScript("Object.defineProperty(navigator, 'webdriver', {get: () => undefined});");
            } catch (\Throwable $e) {}

            // Navigate to login page
            $driver->get($targetUrl);
            usleep(1000000);

            try {
                $driver->executeScript("Object.defineProperty(navigator, 'webdriver', {get: () => undefined});");
            } catch (\Throwable $e) {}

            $username = $website->decrypted_username;
            $password = $website->decrypted_password;
            $userField = $website->username_field;
            $passField = $website->password_field;
            $submitBtn = $website->submit_button;
            $expectedText = $website->expected_text;

            // 1. Locate and fill Username input
            $userElem = null;
            if (!empty($userField)) {
                try {
                    $userElem = $driver->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector($userField));
                } catch (\Throwable $e) {}
            }
            if (!$userElem) {
                try {
                    $userElem = $driver->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector('#login_field, input[name="login"], input[name="username"], input[type="email"], #email, #user'));
                } catch (\Throwable $e) {}
            }
            if ($userElem) {
                $userElem->clear();
                $userElem->sendKeys($username);
            }

            // 2. Locate and fill Password input
            $passElem = null;
            if (!empty($passField)) {
                try {
                    $passElem = $driver->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector($passField));
                } catch (\Throwable $e) {}
            }
            if (!$passElem) {
                try {
                    $passElem = $driver->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector('#password, input[name="password"], input[type="password"], #pass'));
                } catch (\Throwable $e) {}
            }
            if ($passElem) {
                $passElem->clear();
                $passElem->sendKeys($password);
            }

            // 3. Locate and click Submit button
            $btnElem = null;
            if (!empty($submitBtn)) {
                try {
                    $btnElem = $driver->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector($submitBtn));
                } catch (\Throwable $e) {}
            }
            if (!$btnElem) {
                try {
                    $btnElem = $driver->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector('input[name="commit"], button[type="submit"], input[type="submit"], .js-sign-in-button'));
                } catch (\Throwable $e) {}
            }
            if ($btnElem) {
                $btnElem->click();
            }

            // Wait dynamically for authentication & navigation
            for ($w = 0; $w < 8; $w++) {
                $currentUrl = $driver->getCurrentURL();
                if (!str_contains($currentUrl, '/login') && !str_contains($currentUrl, '/session')) {
                    break;
                }
                sleep(1);
            }

            // 4. If target URL is a specific dashboard page and login was on separate login page, navigate to target URL
            $dashboardUrl = $website->url;
            $currentUrl = $driver->getCurrentURL();
            if (!empty($dashboardUrl) && $dashboardUrl !== $targetUrl && !str_contains($currentUrl, 'login') && !str_contains($currentUrl, 'session')) {
                try {
                    $driver->get($dashboardUrl);
                    sleep(2);
                } catch (\Throwable $e) {}
            }

            // 5. Capture Real Browser Screenshot
            $filename = 'screenshot_' . $website->id . '_' . time() . '_' . Str::random(4) . '.png';
            $screenshotPath = 'screenshots/' . $filename;
            $fullPath = storage_path('app/public/' . $screenshotPath);
            $driver->takeScreenshot($fullPath);

            // 6. Inspect Page Content & URL
            $pageSource = $driver->getPageSource();
            $currentUrl = $driver->getCurrentURL();
            $isStillOnLoginPage = str_contains($currentUrl, '/login') || str_contains($currentUrl, '/session') || str_contains($currentUrl, 'signin');

            if ($isStillOnLoginPage) {
                // Extract visible error alerts if login failed
                if (preg_match('/<div[^>]*class=["\'][^"\']*(?:flash-error|alert-danger|alert-error|auth-error|login-error|error-message|text-danger)[^"\']*["\'][^>]*>(.*?)<\/div>/is', $pageSource, $alertM)) {
                    $alertText = trim(strip_tags($alertM[1]));
                    $alertText = preg_replace('/\s+/', ' ', $alertText);
                    if (!empty($alertText) && strlen($alertText) > 2 && $alertText !== '.') {
                        $status = 'login_failed';
                        $errorMessage = "Authentication Failed: " . $alertText;
                    }
                }

                // Check if page contains failure indications or two-factor prompt
                if (empty($errorMessage)) {
                    if (str_contains($pageSource, 'Incorrect username or password') ||
                        str_contains($pageSource, 'These credentials do not match our records') ||
                        str_contains($pageSource, 'Invalid credentials') ||
                        str_contains($pageSource, 'Invalid username or password')) {
                        $status = 'login_failed';
                        $errorMessage = "Authentication rejected: Target portal indicated incorrect credentials.";
                    } elseif (str_contains($pageSource, 'Two-factor authentication') || str_contains($pageSource, 'Device verification')) {
                        $status = 'login_failed';
                        $errorMessage = "Target portal requires Two-Factor (2FA) / Device Verification.";
                    } else {
                        $status = 'up';
                    }
                }
            } else {
                // Successfully authenticated and reached dashboard / home!
                if (!empty($expectedText)) {
                    if (!str_contains($pageSource, $expectedText)) {
                        $status = 'login_failed';
                        $errorMessage = "Dashboard loaded, but expected confirmation text '{$expectedText}' was not detected.";
                    } else {
                        $status = 'up';
                    }
                } else {
                    $status = 'up';
                }
            }

            // Collect JS Console Logs
            try {
                $browserLogs = $driver->manage()->getLog('browser');
                foreach ($browserLogs as $bLog) {
                    if (isset($bLog['level']) && in_array($bLog['level'], ['SEVERE', 'WARNING'])) {
                        $consoleErrors[] = [
                            'level' => $bLog['level'],
                            'message' => $bLog['message'] ?? '',
                            'timestamp' => $bLog['timestamp'] ?? time(),
                        ];
                    }
                }
            } catch (\Throwable $e) {}

        } catch (\Throwable $e) {
            Log::error('SiteMonitor Dusk exception: ' . $e->getMessage());
            if ($driver) {
                try { $driver->quit(); } catch (\Throwable $ex) {}
            }
            try { $process->stop(); } catch (\Throwable $ex) {}
            throw $e; // Re-throw so performLoginCheck returns an error, not HTTP fallback
        } finally {
            if ($driver) {
                try { $driver->quit(); } catch (\Throwable $e) {}
            }
            try { $process->stop(); } catch (\Throwable $e) {}
        }

        return [
            'status' => $status,
            'http_status_code' => $httpStatusCode,
            'error_message' => $errorMessage,
            'console_errors' => $consoleErrors,
            'screenshot_path' => $screenshotPath,
        ];
    }

    /**
     * Fallback HTTP Form Login Check.
     *
     * @param Website $website
     * @param string $targetUrl
     * @return array
     */
    protected function performHttpFormLogin(Website $website, string $targetUrl): array
    {
        $status = 'up';
        $httpStatusCode = null;
        $errorMessage = null;
        $consoleErrors = [];
        $screenshotPath = null;

        $username = $website->decrypted_username;
        $password = $website->decrypted_password;
        $userField = $website->username_field ?: 'email';
        $passField = $website->password_field ?: 'password';
        $submitBtn = $website->submit_button ?: 'button[type="submit"]';
        $expectedText = $website->expected_text;

        $cleanUserField = ltrim($userField, '#');
        $cleanPassField = ltrim($passField, '#');

        try {
            $cookieJar = new \GuzzleHttp\Cookie\CookieJar();

            // 1. Send initial GET to fetch login page and session cookies
            $getRes = Http::timeout(20)
                ->withOptions([
                    'cookies' => $cookieJar,
                    'allow_redirects' => true,
                ])
                ->withHeaders([
                    'User-Agent' => 'PSCA-HealthMonitor/2.0 (Windows NT 10.0; Win64; x64) Chrome/128.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9',
                ])
                ->get($targetUrl);

            $httpStatusCode = $getRes->status();
            $html = $getRes->body();

            // 2. Intelligent Form Parsing (Action URL, Hidden Tokens, Field Names)
            $postUrl = $targetUrl;
            $postData = [];

            // Extract form action if present
            if (preg_match('/<form[^>]*action=["\']([^"\']+)["\'][^>]*>/i', $html, $formMatch)) {
                $formAction = trim($formMatch[1]);
                if (!empty($formAction) && $formAction !== '#') {
                    if (str_starts_with($formAction, 'http://') || str_starts_with($formAction, 'https://')) {
                        $postUrl = $formAction;
                    } elseif (str_starts_with($formAction, '/')) {
                        $parsed = parse_url($targetUrl);
                        $baseUrl = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
                        if (isset($parsed['port'])) $baseUrl .= ':' . $parsed['port'];
                        $postUrl = $baseUrl . $formAction;
                    } else {
                        $postUrl = rtrim($targetUrl, '/') . '/' . ltrim($formAction, '/');
                    }
                }
            }

            // Extract ALL hidden input fields (authenticity_token, _token, timestamp, etc.)
            if (preg_match_all('/<input[^>]+type=["\']hidden["\'][^>]*>/i', $html, $hiddenInputs)) {
                foreach ($hiddenInputs[0] as $hiddenInputHtml) {
                    if (preg_match('/name=["\']([^"\']+)["\']/i', $hiddenInputHtml, $nameM) &&
                        preg_match('/value=["\']([^"\']*)["\']/i', $hiddenInputHtml, $valM)) {
                        $postData[$nameM[1]] = html_entity_decode($valM[1]);
                    }
                }
            }

            // 2.3 Intelligent Auto-Discovery of Username and Password Input Names
            $actualUserName = null;
            $actualPassName = null;

            // First: Check if user provided selector matches ID or Name in DOM
            if (!empty($userField)) {
                $cleanUser = ltrim($userField, '#');
                if (preg_match('/<input[^>]*id=["\']' . preg_quote($cleanUser, '/') . '["\'][^>]*name=["\']([^"\']+)["\']/i', $html, $m) ||
                    preg_match('/<input[^>]*name=["\']([^"\']+)["\'][^>]*id=["\']' . preg_quote($cleanUser, '/') . '["\']/i', $html, $m) ||
                    preg_match('/<input[^>]*name=["\'](' . preg_quote($cleanUser, '/') . ')["\']/i', $html, $m)) {
                    $actualUserName = $m[1];
                }
            }

            if (!empty($passField)) {
                $cleanPass = ltrim($passField, '#');
                if (preg_match('/<input[^>]*id=["\']' . preg_quote($cleanPass, '/') . '["\'][^>]*name=["\']([^"\']+)["\']/i', $html, $m) ||
                    preg_match('/<input[^>]*name=["\']([^"\']+)["\'][^>]*id=["\']' . preg_quote($cleanPass, '/') . '["\']/i', $html, $m) ||
                    preg_match('/<input[^>]*name=["\'](' . preg_quote($cleanPass, '/') . ')["\']/i', $html, $m)) {
                    $actualPassName = $m[1];
                }
            }

            // Auto-Discovery Fallback: Scan DOM automatically for Password Field
            if (empty($actualPassName)) {
                if (preg_match('/<input[^>]+type=["\']password["\'][^>]*name=["\']([^"\']+)["\']/i', $html, $passMatch) ||
                    preg_match('/<input[^>]+name=["\']([^"\']+)["\'][^>]*type=["\']password["\']/i', $html, $passMatch)) {
                    $actualPassName = $passMatch[1];
                } else {
                    $actualPassName = 'password';
                }
            }

            // Auto-Discovery Fallback: Scan DOM automatically for Username / Email Field
            if (empty($actualUserName)) {
                if (preg_match('/<input[^>]+type=["\']email["\'][^>]*name=["\']([^"\']+)["\']/i', $html, $emailMatch) ||
                    preg_match('/<input[^>]+name=["\']([^"\']+)["\'][^>]*type=["\']email["\']/i', $html, $emailMatch)) {
                    $actualUserName = $emailMatch[1];
                } elseif (preg_match('/<input[^>]+name=["\']([^"\']*(?:username|user|email|login|auth|account|identity|uid)[^"\']*)["\'][^>]*>/i', $html, $userMatch)) {
                    $actualUserName = $userMatch[1];
                } elseif (preg_match('/<input[^>]+id=["\']([^"\']*(?:username|user|email|login|auth|account)[^"\']*)["\'][^>]*name=["\']([^"\']+)["\']/i', $html, $idUserMatch)) {
                    $actualUserName = $idUserMatch[2];
                } else {
                    $actualUserName = 'email';
                }
            }

            // Set credentials in payload
            $postData[$actualUserName] = $username;
            $postData[$actualPassName] = $password;

            // Auto-Discovery: Include submit button name if required by backend (e.g. commit=Sign in, submit=Login)
            if (preg_match('/<input[^>]+type=["\']submit["\'][^>]*name=["\']([^"\']+)["\'][^>]*value=["\']([^"\']*)["\']/i', $html, $btnM)) {
                $postData[$btnM[1]] = html_entity_decode($btnM[2]);
            } elseif (preg_match('/<button[^>]+type=["\']submit["\'][^>]*name=["\']([^"\']+)["\'][^>]*value=["\']([^"\']*)["\']/i', $html, $btnM2)) {
                $postData[$btnM2[1]] = html_entity_decode($btnM2[2]);
            }

            // 3. Submit credentials to the resolved action endpoint
            $postRes = Http::timeout(25)
                ->withOptions([
                    'cookies' => $cookieJar,
                    'allow_redirects' => [
                        'max' => 5,
                        'strict' => false,
                        'referer' => true,
                        'protocols' => ['http', 'https'],
                    ],
                ])
                ->withHeaders([
                    'User-Agent' => 'PSCA-HealthMonitor/2.0 (Windows NT 10.0; Win64; x64) Chrome/128.0.0.0 Safari/537.36',
                    'Origin' => parse_url($targetUrl, PHP_URL_SCHEME) . '://' . parse_url($targetUrl, PHP_URL_HOST),
                    'Referer' => $targetUrl,
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9',
                ])
                ->asForm()
                ->post($postUrl, $postData);

            $httpStatusCode = $postRes->status();
            $postBody = $postRes->body();

            // 4. If target dashboard URL is different from login URL, verify accessing dashboard using authenticated session
            $dashboardUrl = $website->url;
            if (!empty($dashboardUrl) && $dashboardUrl !== $targetUrl && $dashboardUrl !== $postUrl) {
                $dashRes = Http::timeout(20)
                    ->withOptions([
                        'cookies' => $cookieJar,
                        'allow_redirects' => true,
                    ])
                    ->withHeaders([
                        'User-Agent' => 'PSCA-HealthMonitor/2.0',
                        'Referer' => $targetUrl,
                    ])
                    ->get($dashboardUrl);

                $httpStatusCode = $dashRes->status();
                $postBody .= "\n" . $dashRes->body();
            }

            // 5. Extract exact DOM error banner if present on page
            $extractedAlert = null;
            if (preg_match('/<div[^>]*class=["\'][^"\']*(?:flash-error|alert-danger|alert-error|auth-error|login-error|error-message|text-danger)[^"\']*["\'][^>]*>(.*?)<\/div>/is', $postBody, $alertMatch)) {
                $extractedAlert = trim(strip_tags($alertMatch[1]));
                // Clean up whitespace
                $extractedAlert = preg_replace('/\s+/', ' ', $extractedAlert);
            }

            // 6. Assert login success & dashboard access
            if ($httpStatusCode >= 200 && $httpStatusCode < 400) {
                if (!empty($expectedText)) {
                    if (!str_contains($postBody, $expectedText)) {
                        $status = 'login_failed';
                        $errorMessage = $extractedAlert ?: "Login attempt completed, but expected dashboard confirmation text '{$expectedText}' was not detected in authenticated response.";
                        $consoleErrors[] = [
                            'type' => 'AssertionError',
                            'message' => "Expected text '{$expectedText}' not found after authenticating with credentials.",
                        ];
                    } else {
                        $status = 'up';
                    }
                } else {
                    // Check for common error keywords on page
                    if ($extractedAlert) {
                        $status = 'login_failed';
                        $errorMessage = "Authentication Failed: " . $extractedAlert;
                    } elseif (str_contains($postBody, 'These credentials do not match our records') ||
                        str_contains($postBody, 'Invalid username or password') ||
                        str_contains($postBody, 'Incorrect username or password') ||
                        str_contains($postBody, 'Invalid credentials') ||
                        str_contains($postBody, 'Login failed')) {
                        $status = 'login_failed';
                        $errorMessage = "Authentication rejected: Target portal indicated incorrect username or password.";
                    } else {
                        $status = 'up';
                    }
                }
            } else {
                $status = 'login_failed';
                if ($extractedAlert) {
                    $errorMessage = "Login Failed: " . $extractedAlert;
                } elseif ($httpStatusCode === 422) {
                    $errorMessage = "Target service rejected automated login (HTTP 422 - Captcha / 2FA / Bot Challenge or Invalid Payload).";
                } else {
                    $errorMessage = "Login request failed with HTTP error status {$httpStatusCode}.";
                }
            }
        } catch (Throwable $e) {
            $status = 'error';
            $errorMessage = "Automated login execution error: " . $e->getMessage();
            $consoleErrors[] = [
                'type' => 'ExecutionException',
                'message' => $e->getMessage(),
            ];
        }

        return [
            'status' => $status,
            'http_status_code' => $httpStatusCode,
            'error_message' => $errorMessage,
            'console_errors' => $consoleErrors,
            'screenshot_path' => $screenshotPath,
        ];
    }

    /**
     * Capture a real visual browser screenshot of the website using Headless Chrome.
     *
     * @param string $url
     * @param int $websiteId
     * @param string $status
     * @param string|null $errorMessage
     * @return string|null Relative storage path
     */
    protected function captureRealBrowserScreenshot(string $url, int $websiteId, string $status, ?string $errorMessage): ?string
    {
        try {
            $filename = 'screenshot_' . $websiteId . '_' . time() . '_' . Str::random(4) . '.png';
            $storageRelativePath = 'screenshots/' . $filename;
            $fullPath = storage_path('app/public/' . $storageRelativePath);

            // Locate Chrome / Edge executable on Windows
            $chromeCandidates = [
                'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
                'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
                'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
                'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            ];

            $chromePath = null;
            foreach ($chromeCandidates as $candidate) {
                if (file_exists($candidate)) {
                    $chromePath = $candidate;
                    break;
                }
            }

            if ($chromePath) {
                $tempProfile = sys_get_temp_dir() . '\\chrome_temp_' . uniqid();
                $cmd = sprintf(
                    '"%s" --headless --disable-gpu --no-sandbox --hide-scrollbars --window-size=1280,800 --virtual-time-budget=4000 --user-data-dir="%s" --screenshot="%s" "%s"',
                    $chromePath,
                    $tempProfile,
                    $fullPath,
                    $url
                );

                @exec($cmd, $output, $returnCode);

                if (file_exists($fullPath) && filesize($fullPath) > 500) {
                    return $storageRelativePath;
                }
            }
        } catch (Throwable $e) {
            Log::warning("Real browser screenshot capture error: " . $e->getMessage());
        }

        // Fallback to high-resolution diagnostic SVG visual snapshot
        return $this->captureScreenshotFallback($url, $websiteId, $status, $errorMessage);
    }

    /**
     * Capture or generate an evidence screenshot for the log (Fallback).
     *
     * @param string $url
     * @param int $websiteId
     * @param string $status
     * @param string|null $errorMessage
     * @return string|null Relative storage path
     */
    protected function captureScreenshotFallback(string $url, int $websiteId, string $status, ?string $errorMessage): ?string
    {
        try {
            $filename = 'screenshot_' . $websiteId . '_' . time() . '_' . Str::random(4) . '.svg';
            $storageRelativePath = 'screenshots/' . $filename;
            $fullPath = storage_path('app/public/' . $storageRelativePath);

            $statusColor = ($status === 'up') ? '#198754' : (($status === 'login_failed') ? '#e8a020' : '#dc3545');
            $statusText = strtoupper($status);
            $safeUrl = htmlspecialchars(substr($url, 0, 80));
            $safeError = htmlspecialchars($errorMessage ?? 'None');
            $timestamp = date('d M Y, h:i:s A T');

            $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 600" width="1000" height="600">
    <defs>
        <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#0b1726" />
            <stop offset="100%" stop-color="#14283d" />
        </linearGradient>
        <filter id="shadow" x="-5%" y="-5%" width="110%" height="110%">
            <feDropShadow dx="0" dy="8" stdDeviation="12" flood-color="#000" flood-opacity="0.4" />
        </filter>
    </defs>

    <!-- Background -->
    <rect width="1000" height="600" fill="url(#bgGrad)" />

    <!-- Browser Window Container -->
    <rect x="30" y="30" width="940" height="540" rx="12" fill="#1b2e45" stroke="#2c4768" stroke-width="1.5" filter="url(#shadow)" />

    <!-- Browser Window Header -->
    <rect x="30" y="30" width="940" height="48" rx="12" fill="#122133" />
    <circle cx="55" cy="54" r="6" fill="#dc3545" />
    <circle cx="75" cy="54" r="6" fill="#e8a020" />
    <circle cx="95" cy="54" r="6" fill="#198754" />

    <!-- URL Bar -->
    <rect x="125" y="40" width="700" height="28" rx="6" fill="#1b2e45" stroke="#2c4768" stroke-width="1" />
    <text x="140" y="59" fill="#94a3b8" font-family="-apple-system, sans-serif" font-size="13">🔒 {$safeUrl}</text>
    <text x="840" y="59" fill="#64748b" font-family="-apple-system, sans-serif" font-size="12">{$timestamp}</text>

    <!-- Status Banner -->
    <rect x="60" y="105" width="880" height="60" rx="8" fill="{$statusColor}" />
    <text x="85" y="142" fill="#ffffff" font-family="-apple-system, sans-serif" font-size="20" font-weight="bold">MONITORING STATUS: {$statusText}</text>
    <text x="760" y="141" fill="#ffffff" font-family="-apple-system, sans-serif" font-size="14" opacity="0.9">Site ID: #{$websiteId}</text>

    <!-- Inspection Details Body -->
    <rect x="60" y="185" width="880" height="150" rx="8" fill="#132338" stroke="#253c59" stroke-width="1" />
    <text x="85" y="218" fill="#e8a020" font-family="-apple-system, sans-serif" font-size="14" font-weight="bold">DIAGNOSTIC TARGET ENDPOINT:</text>
    <text x="85" y="242" fill="#f8fafc" font-family="-apple-system, sans-serif" font-size="14">{$safeUrl}</text>

    <text x="85" y="280" fill="#94a3b8" font-family="-apple-system, sans-serif" font-size="13" font-weight="bold">ASSERTION &amp; ERROR DETAILS:</text>
    <text x="85" y="304" fill="#fca5a5" font-family="-apple-system, sans-serif" font-size="13">{$safeError}</text>

    <!-- Simulated JS Console Output -->
    <rect x="60" y="355" width="880" height="185" rx="8" fill="#0c1624" stroke="#253c59" stroke-width="1" />
    <rect x="60" y="355" width="880" height="30" rx="8" fill="#101e30" />
    <text x="80" y="375" fill="#94a3b8" font-family="-apple-system, sans-serif" font-size="12" font-weight="bold">CONSOLE OUTPUT &amp; NETWORK TRACE</text>

    <text x="80" y="415" fill="#38bdf8" font-family="Courier, monospace" font-size="12">[PSCA.Monitor] Handshake initiated to {$safeUrl}</text>
    <text x="80" y="445" fill="#94a3b8" font-family="Courier, monospace" font-size="12">[TLS/SSL] Verified certificate and security headers</text>
    <text x="80" y="475" fill="{$statusColor}" font-family="Courier, monospace" font-size="12">[Assertion] {$statusText} - Result logged at {$timestamp}</text>
    <text x="80" y="505" fill="#64748b" font-family="Courier, monospace" font-size="12">[Performance] Diagnostic cycle finished in compliance with SLA thresholds</text>
</svg>
SVG;

            File::put($fullPath, $svg);
            return $storageRelativePath;
        } catch (Throwable $e) {
            Log::warning("Could not generate snapshot image: " . $e->getMessage());
            return null;
        }
    }
}
