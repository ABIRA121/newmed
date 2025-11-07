<?php
/**
 * Security utilities to standardize login request handling
 */

class SecurityUtils
{
    /**
     * Process a login POST request and return the outcome.
     *
     * @param Auth $auth
     * @param CSRF $csrf
     * @return array{success:bool,error:string,email:string,regenerate_csrf:bool}
     */
    public static function handleLoginPost(Auth $auth, CSRF $csrf): array
    {
        $result = [
            'success' => false,
            'error' => '',
            'email' => '',
            'regenerate_csrf' => false,
        ];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $result;
        }

        // Validate CSRF token before touching input data
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!$csrf->validateToken($csrfToken)) {
            $result['error'] = 'Security token expired. Please refresh the page and try again.';
            $result['regenerate_csrf'] = true;
            return $result;
        }

        [$email, $password, $validationError] = self::validateLoginInput($_POST);
        $result['email'] = $email;

        if ($validationError !== '') {
            $result['error'] = $validationError;
            return $result;
        }

        try {
            $ipAddress = self::getClientIp();
            $userAgent = self::getUserAgent();

            if ($auth->login($email, $password, $ipAddress, $userAgent)) {
                $result['success'] = true;
            }
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Basic validation and sanitization for login input.
     *
     * @param array $input
     * @return array{0:string,1:string,2:string} [email, password, error]
     */
    private static function validateLoginInput(array $input): array
    {
        $email = filter_var(trim($input['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $input['password'] ?? '';

        if ($email === '' || $password === '') {
            return [$email, $password, 'Please enter both email and password.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [$email, $password, 'Please enter a valid email address.'];
        }

        return [$email, $password, ''];
    }

    /**
     * Determine the most reliable client IP address.
     */
    public static function getClientIp(): string
    {
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];

        foreach ($ipHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                $value = $_SERVER[$header];

                // When multiple IPs are present, take the first one
                if (strpos($value, ',') !== false) {
                    $value = explode(',', $value)[0];
                }

                $value = trim($value);
                if (filter_var($value, FILTER_VALIDATE_IP)) {
                    return $value;
                }
            }
        }

        return 'unknown';
    }

    /**
     * Retrieve the current user agent string.
     */
    public static function getUserAgent(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        return is_string($userAgent) ? substr($userAgent, 0, 512) : 'unknown';
    }
}


