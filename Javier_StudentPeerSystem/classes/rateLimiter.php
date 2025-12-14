<?php

class RateLimiter
{

    /**
     * Check if an action is rate limited for a specific identifier
     * @param string $identifier Unique identifier (e.g., IP address, user ID, email)
     * @param string $action Action being performed (e.g., 'login', 'register')
     * @param int $maxAttempts Maximum allowed attempts
     * @param int $timeWindow Time window in seconds
     * @return bool True if action is allowed, False if rate limited
     */
    public static function checkLimit($identifier, $action, $maxAttempts = 5, $timeWindow = 300)
    {
        $key = "ratelimit_{$action}_{$identifier}";

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'first_attempt' => time()
            ];
        }

        $data = $_SESSION[$key];
        $currentTime = time();
        $timePassed = $currentTime - $data['first_attempt'];

        // Reset if time window has passed
        if ($timePassed > $timeWindow) {
            $_SESSION[$key] = [
                'attempts' => 1,
                'first_attempt' => $currentTime
            ];
            return true;
        }

        // Check if limit exceeded
        if ($data['attempts'] >= $maxAttempts) {
            return false;
        }

        // Increment attempts
        $_SESSION[$key]['attempts']++;
        return true;
    }

    /**
     * Get the remaining wait time in seconds before retry is allowed
     * @param string $identifier Unique identifier
     * @param string $action Action being performed
     * @param int $timeWindow Time window in seconds
     * @return int Seconds remaining before retry
     */
    public static function getWaitTime($identifier, $action, $timeWindow = 300)
    {
        $key = "ratelimit_{$action}_{$identifier}";

        if (!isset($_SESSION[$key])) {
            return 0;
        }

        $data = $_SESSION[$key];
        $currentTime = time();
        $timePassed = $currentTime - $data['first_attempt'];
        $remainingTime = $timeWindow - $timePassed;

        return max(0, $remainingTime);
    }

    /**
     * Clear rate limit data for an identifier and action
     * Useful after successful authentication
     * @param string $identifier Unique identifier
     * @param string $action Action being performed
     */
    public static function clearLimit($identifier, $action)
    {
        $key = "ratelimit_{$action}_{$identifier}";
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
}
