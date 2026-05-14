<?php

namespace App\Services\Codification;

use App\Models\Etiquette;
use App\Models\Immobilisation;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Picqer\Barcode\BarcodeGeneratorPNG;

class EtiquetteService
{
    /**
     * Construit le payload signé du QR code (URL + HMAC).
     */
    public function buildQrPayload(string $codeInventaire): string
    {
        $baseUrl = rtrim(config('gespat.qr.base_url'), '/').'/';
        $secret = (string) config('gespat.qr.hmac_secret');
        $signature = substr(hash_hmac('sha256', $codeInventaire, $secret), 0, 16);

        return $baseUrl.$codeInventaire.'?sig='.$signature;
    }

    public function buildBarcodePayload(string $codeInventaire): string
    {
        return $codeInventaire;
    }

    /**
     * Génère QR (PNG base64) pour intégration dans un PDF.
     */
    public function qrCodeBase64(string $payload, int $size = 200): string
    {
        $qrCode = new QrCode(data: $payload, size: $size, margin: 10);
        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return 'data:image/png;base64,'.base64_encode($result->getString());
    }

    /**
     * Génère un code-barres CODE128 (PNG base64).
     */
    public function barcodeBase64(string $payload, int $widthFactor = 2, int $height = 60): string
    {
        $gen = new BarcodeGeneratorPNG();
        $png = $gen->getBarcode($payload, $gen::TYPE_CODE_128, $widthFactor, $height);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    /**
     * Crée une étiquette en base pour une immobilisation (mode "réservation"
     * possible si $immo est null — utile pour pré-générer un lot).
     */
    public function creerEtiquette(string $codeInventaire, ?Immobilisation $immo = null, ?int $lotId = null): Etiquette
    {
        return Etiquette::create([
            'lot_id' => $lotId,
            'immobilisation_id' => $immo?->id,
            'code_inventaire' => $codeInventaire,
            'qr_payload' => $this->buildQrPayload($codeInventaire),
            'barcode_payload' => $this->buildBarcodePayload($codeInventaire),
            'etat' => 'genere',
        ]);
    }
}
