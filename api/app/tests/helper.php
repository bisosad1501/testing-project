<?php
/**
 * Helper functions for tests
 *
 * This file contains helper functions that are needed for tests but are missing in the original code.
 * These functions are only used for testing and should not be used in production code.
 */

// Define __ (gettext) function if it doesn't exist
if (!function_exists('__')) {
    /**
     * Mock gettext function
     *
     * @param string $text Text to translate
     * @param array $params Parameters to replace in the text
     * @return string Translated text
     */
    function __($text, $params = []) {
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $text = str_replace($key, $value, $text);
            }
        }
        return $text;
    }
}

// Define readableRandomString function if it doesn't exist
if (!function_exists('readableRandomString')) {
    /**
     * Generate a readable random string
     *
     * This is a mock implementation for testing purposes only.
     *
     * @param int $length Length of the random string
     * @return string Random string
     */
    function readableRandomString($length = 10) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}

// Define Email class if it doesn't exist
if (!class_exists('Email')) {
    /**
     * Mock Email class for testing
     *
     * This is a mock implementation for testing purposes only.
     */
    class Email {
        public $to = [];
        public $subject = '';

        /**
         * Add an email address to the recipient list
         *
         * @param string $email Email address
         * @return void
         */
        public function addAddress($email) {
            $this->to[] = $email;
        }

        /**
         * Send an email
         *
         * @param string $body Email body
         * @return bool Always returns true for testing
         */
        public function sendmail($body) {
            // Just return true for testing
            return true;
        }
    }
}

// Define GeneralData class if it doesn't exist
if (!class_exists('GeneralData')) {
    /**
     * Mock GeneralData class for testing
     *
     * This is a mock implementation for testing purposes only.
     */
    class GeneralData extends DataEntry {
        /**
         * Constructor
         */
        public function __construct() {
            parent::__construct();
            $this->set('data', json_encode(['site_name' => 'Test Site']));
            $this->is_available = true;
        }
    }
}
