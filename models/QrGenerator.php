<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

class QrGenerator
{
    private $writer;

    const ERROR_LEVELS = [
        'L' => ErrorCorrectionLevel::Low,
        'M' => ErrorCorrectionLevel::Medium,
        'Q' => ErrorCorrectionLevel::Quartile,
        'H' => ErrorCorrectionLevel::High,
    ];

    const MAX_CONTENT_LENGTH = 900;

    public function __construct()
    {
        $this->writer = new PngWriter();
    }

    // Tipos de QR 

    public function generateText(string $text, int $size, string $errorLevel): string
    {
        $this->validateContent($text);
        return $this->generate($text, $size, $errorLevel);
    }

    public function generateUrl(string $url, int $size, string $errorLevel): string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("La URL proporcionada no es válida.");
        }
        $this->validateContent($url);
        return $this->generate($url, $size, $errorLevel);
    }

    public function generateWifi(string $ssid, string $password, string $encryption, int $size, string $errorLevel): string
    {
        $validEncryptions = ['WPA', 'WPA2', 'WEP', 'nopass'];
        if (!in_array(strtoupper($encryption), array_map('strtoupper', $validEncryptions))) {
            throw new InvalidArgumentException("Encriptación inválida. Use: WPA, WPA2, WEP o nopass.");
        }

        $ssid     = $this->escapeWifiField($ssid);
        $password = $this->escapeWifiField($password);
        $enc      = strtoupper($encryption) === 'NOPASS' ? 'nopass' : strtoupper($encryption);

        $content = "WIFI:T:{$enc};S:{$ssid};P:{$password};;";
        $this->validateContent($content);
        return $this->generate($content, $size, $errorLevel);
    }

    public function generateGeo(float $lat, float $lng, int $size, string $errorLevel): string
    {
        if ($lat < -90 || $lat > 90) {
            throw new InvalidArgumentException("Latitud inválida. Debe estar entre -90 y 90.");
        }
        if ($lng < -180 || $lng > 180) {
            throw new InvalidArgumentException("Longitud inválida. Debe estar entre -180 y 180.");
        }

        $content = "geo:{$lat},{$lng}?q={$lat},{$lng}";
        return $this->generate($content, $size, $errorLevel);
    }

    // Métodos internos 

    private function generate(string $content, int $size, string $errorLevel): string
    {
        $level = self::ERROR_LEVELS[$errorLevel] ?? ErrorCorrectionLevel::Medium;

        $qrCode = new QrCode(
            data: $content,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: $level,
            size: $size,
            margin: 10,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255)
        );

        $result = $this->writer->write($qrCode);
        return $result->getString();
    }

    private function validateContent(string $content): void
    {
        if (strlen($content) === 0) {
            throw new InvalidArgumentException("El contenido no puede estar vacío.");
        }
        if (strlen($content) > self::MAX_CONTENT_LENGTH) {
            throw new LengthException("El contenido excede la capacidad máxima del QR (" . self::MAX_CONTENT_LENGTH . " caracteres).");
        }
    }

    private function escapeWifiField(string $value): string
    {
        return addcslashes($value, '\\;,":');
    }

    // Validadores públicos (para uso en Resource)

    public static function validateSize(int $size): bool
    {
        return $size >= 100 && $size <= 1000;
    }

    public static function validateErrorLevel(string $level): bool
    {
        return array_key_exists($level, self::ERROR_LEVELS);
    }
}
?>