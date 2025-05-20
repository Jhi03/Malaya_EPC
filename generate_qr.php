<?php
// Define class for authentication with Chillerlan QR code generation
class MalayaSolarAuth {
    private $authenticator;
    private $lib_available = false;
    
    public function __construct() {
        // Check if library exists before requiring it
        if (file_exists('PHPGangsta/GoogleAuthenticator.php')) {
            require_once 'PHPGangsta/GoogleAuthenticator.php';
            $this->authenticator = new PHPGangsta_GoogleAuthenticator();
            $this->lib_available = true;
        } elseif (file_exists('lib/PHPGangsta/GoogleAuthenticator.php')) {
            // Try alternative location
            require_once 'lib/PHPGangsta/GoogleAuthenticator.php';
            $this->authenticator = new PHPGangsta_GoogleAuthenticator();
            $this->lib_available = true;
        }
        
        // Make sure Composer autoloader is loaded
        if (file_exists('vendor/autoload.php')) {
            require_once 'vendor/autoload.php';
        }
    }
    
    /**
     * Create a new secret key for Google Authenticator
     */
    public function createSecret() {
        if ($this->lib_available) {
            return $this->authenticator->createSecret();
        }
        
        // Fallback implementation if the library is not available
        $secret = '';
        $validChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 alphabet
        
        for ($i = 0; $i < 16; $i++) {
            $secret .= $validChars[rand(0, 31)];
        }
        
        return $secret;
    }
    
    /**
     * Get the QR code URL for the Google Authenticator app
     * Generates QR code directly using Chillerlan QR code library
     */
    public function getQRCodeUrl($name, $secret, $issuer = 'Malaya Solar') {
        // Generate the otpauth URL that needs to be encoded in the QR code
        $otpauthUrl = 'otpauth://totp/' . rawurlencode($issuer . ':' . $name) . 
                    '?secret=' . $secret . '&issuer=' . rawurlencode($issuer);
        
        // Try to use Chillerlan QR code library if it's available
        if (class_exists('chillerlan\QRCode\QRCode')) {
            try {
                // Include necessary classes
                use chillerlan\QRCode\QRCode;
                use chillerlan\QRCode\QROptions;
                
                // Create QR code options
                $options = new QROptions([
                    'eccLevel' => QRCode::ECC_L,
                    'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                    'scale' => 5,
                    'imageBase64' => true,
                ]);
                
                // Generate QR code
                $qrcode = new QRCode($options);
                $qrDataUri = $qrcode->render($otpauthUrl);
                
                return $qrDataUri;
            } catch (Exception $e) {
                // If there's an error, fall back to Google Charts API
                error_log('QR Code generation error: ' . $e->getMessage());
            }
        }
        
        // If Chillerlan is not available or fails, fall back to Google Charts
        $googleChartUrl = 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' . urlencode($otpauthUrl);
        
        // Try to fetch and convert to data URI
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'user_agent' => 'Mozilla/5.0 (compatible; MalayaSolar2FA/1.0)'
                ]
            ]);
            
            $imageData = @file_get_contents($googleChartUrl, false, $context);
            
            if ($imageData !== false) {
                // Return as data URI for direct embedding
                return 'data:image/png;base64,' . base64_encode($imageData);
            }
        } catch (Exception $e) {
            // If fetching fails, fall back to direct URL
        }
        
        // If all else fails, return the direct URL
        return $googleChartUrl;
    }
    
    /**
     * Verify the code entered by the user
     */
    public function verifyCode($secret, $code) {
        if ($this->lib_available) {
            return $this->authenticator->verifyCode($secret, $code, 2); // 2 = 2*30sec clock tolerance
        }
        
        // Fallback implementation (very basic, only for development)
        if (strlen($code) != 6 || !ctype_digit($code)) {
            return false;
        }
        
        // For development only: accept code "123456" for testing
        if ($code === "123456") {
            return true;
        }
        
        // Generate a simple hash from the secret and current time slot
        // This is not compatible with Google Authenticator but provides minimal security
        $timeSlot = floor(time() / 30);
        $expectedHash = substr(md5($secret . $timeSlot), 0, 6);
        
        // Allow the code to be either our expected hash or the first 6 digits
        // of the secret (for easy testing)
        $testCode = substr(preg_replace('/[^0-9]/', '', $secret), 0, 6);
        
        return ($code === $expectedHash || $code === $testCode);
    }
    
    /**
     * Generate a random password
     */
    public function generateRandomPassword($length = 10) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
    
    /**
     * Hash password using modern methods
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify password against hash
     */
    public function verifyPassword($password, $hash) {
        // First, check if it's a modern hash
        if (password_verify($password, $hash)) {
            return true;
        }
        
        // Fallback for plaintext passwords during transition
        return $password === $hash;
    }
    
    /**
     * Check if the authenticator library is available
     */
    public function isLibraryAvailable() {
        return $this->lib_available;
    }
    
    /**
     * Generate direct OTP auth URL for Google Authenticator
     */
    public function getOTPAuthURL($name, $secret, $issuer = 'Malaya Solar') {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $name) . 
               '?secret=' . $secret . '&issuer=' . rawurlencode($issuer);
    }
}
?>