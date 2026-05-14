<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Planche d'étiquettes — {{ $lot->libelle }}</title>
    <style>
        @page { margin: 8mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8pt; margin: 0; padding: 0; }
        .etiquette {
            display: inline-block;
            box-sizing: border-box;
            width: {{ $format['width_mm'] ?? 70 }}mm;
            height: {{ $format['height_mm'] ?? 37 }}mm;
            padding: 2mm;
            border: 0.2pt dashed #aaa;
            vertical-align: top;
            text-align: center;
            page-break-inside: avoid;
        }
        .etiquette .libelle { font-weight: bold; font-size: 7pt; margin-bottom: 1mm; max-height: 8mm; overflow: hidden; }
        .etiquette .code { font-family: monospace; font-size: 7pt; margin-bottom: 1mm; }
        .etiquette img.qr { width: 14mm; height: 14mm; vertical-align: middle; }
        .etiquette img.barcode { width: 45mm; height: 8mm; vertical-align: middle; margin-top: 1mm; }
    </style>
</head>
<body>
@foreach ($etiquettes as $e)
    <div class="etiquette">
        <div class="libelle">{{ \Illuminate\Support\Str::limit($e['libelle'], 50) }}</div>
        <div class="code">{{ $e['code'] }}</div>
        <img class="qr" src="{{ $e['qr'] }}" alt="QR">
        <br>
        <img class="barcode" src="{{ $e['barcode'] }}" alt="barcode">
    </div>
@endforeach
</body>
</html>
