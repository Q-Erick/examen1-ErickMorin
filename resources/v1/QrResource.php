<?php

require_once __DIR__ . '/../../models/QrGenerator.php';

class QrResource
{
    private $generator;

    public function __construct()
    {
        $this->generator = new QrGenerator();
    }

    // Helpers 
    private function getCommonParams(object $data): array
    {
        $size       = isset($data->size) ? (int)$data->size : 300;
        $errorLevel = isset($data->error_level) ? strtoupper($data->error_level) : 'M';

        if (!QrGenerator::validateSize($size)) {
            $this->error(400, "El tamaño debe estar entre 100 y 1000 píxeles.");
        }
        if (!QrGenerator::validateErrorLevel($errorLevel)) {
            $this->error(400, "Nivel de corrección inválido. Use: L, M, Q o H.");
        }

        return [$size, $errorLevel];
    }

    private function sendImage(string $pngData): void
    {
        header("Content-Type: image/png");
        header("Content-Disposition: inline; filename=\"qr.png\"");
        echo $pngData;
    }

    private function error(int $code, string $message): void
    {
        http_response_code($code);
        header("Content-Type: application/json");
        echo json_encode(["message" => $message]);
        exit;
    }

    private function getBody(): object
    {
        $data = json_decode(file_get_contents("php://input"));
        if (json_last_error() !== JSON_ERROR_NONE || !is_object($data)) {
            $this->error(415, "El cuerpo de la petición debe ser JSON válido.");
        }
        return $data;
    }

    // POST /api/v1/qr/text
    public function text(): void
    {
        $data = $this->getBody();

        if (empty($data->text)) {
            $this->error(400, "El campo 'text' es requerido.");
        }

        [$size, $errorLevel] = $this->getCommonParams($data);

        try {
            $png = $this->generator->generateText($data->text, $size, $errorLevel);
            $this->sendImage($png);
        } catch (LengthException $e) {
            $this->error(413, $e->getMessage());
        } catch (Exception $e) {
            $this->error(500, "Error al generar el QR: " . $e->getMessage());
        }
    }

    // POST /api/v1/qr/url
    public function url(): void
    {
        $data = $this->getBody();

        if (empty($data->url)) {
            $this->error(400, "El campo 'url' es requerido.");
        }

        [$size, $errorLevel] = $this->getCommonParams($data);

        try {
            $png = $this->generator->generateUrl($data->url, $size, $errorLevel);
            $this->sendImage($png);
        } catch (InvalidArgumentException $e) {
            $this->error(400, $e->getMessage());
        } catch (LengthException $e) {
            $this->error(413, $e->getMessage());
        } catch (Exception $e) {
            $this->error(500, "Error al generar el QR: " . $e->getMessage());
        }
    }

    // POST /api/v1/qr/wifi
    public function wifi(): void
    {
        $data = $this->getBody();

        if (empty($data->ssid) || !isset($data->encryption)) {
            $this->error(400, "Los campos 'ssid' y 'encryption' son requeridos.");
        }

        $password   = $data->password ?? '';
        $encryption = $data->encryption;

        [$size, $errorLevel] = $this->getCommonParams($data);

        try {
            $png = $this->generator->generateWifi($data->ssid, $password, $encryption, $size, $errorLevel);
            $this->sendImage($png);
        } catch (InvalidArgumentException $e) {
            $this->error(400, $e->getMessage());
        } catch (LengthException $e) {
            $this->error(413, $e->getMessage());
        } catch (Exception $e) {
            $this->error(500, "Error al generar el QR: " . $e->getMessage());
        }
    }

    // POST /api/v1/qr/geo
    public function geo(): void
    {
        $data = $this->getBody();

        if (!isset($data->lat) || !isset($data->lng)) {
            $this->error(400, "Los campos 'lat' y 'lng' son requeridos.");
        }

        if (!is_numeric($data->lat) || !is_numeric($data->lng)) {
            $this->error(400, "Los campos 'lat' y 'lng' deben ser numéricos.");
        }

        [$size, $errorLevel] = $this->getCommonParams($data);

        try {
            $png = $this->generator->generateGeo((float)$data->lat, (float)$data->lng, $size, $errorLevel);
            $this->sendImage($png);
        } catch (InvalidArgumentException $e) {
            $this->error(400, $e->getMessage());
        } catch (Exception $e) {
            $this->error(500, "Error al generar el QR: " . $e->getMessage());
        }
    }
}
?>