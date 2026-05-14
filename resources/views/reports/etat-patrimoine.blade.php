<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>État du patrimoine — {{ $arrete_au->format('d/m/Y') }}</title>
    <style>
        @page { margin: 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8pt; color: #1a1a1a; }
        h1 { font-size: 14pt; margin: 0 0 2mm 0; }
        .meta { font-size: 9pt; color: #666; margin-bottom: 6mm; }
        table { width: 100%; border-collapse: collapse; }
        thead { background: #eee; }
        th, td { border: 0.2pt solid #999; padding: 2mm; text-align: left; }
        th { font-size: 7.5pt; }
        td { font-size: 7pt; }
        td.num { text-align: right; }
        tfoot { font-weight: bold; background: #f5f5f5; }
        .footer { text-align: center; font-size: 7pt; color: #888; margin-top: 6mm; }
    </style>
</head>
<body>
    <h1>État du patrimoine</h1>
    <div class="meta">
        <strong>{{ $organisme }}</strong> — Arrêté au {{ $arrete_au->format('d/m/Y H:i') }}<br>
        Total : {{ $totaux['nombre'] }} bien(s) — Valeur brute : {{ number_format($totaux['valeur_brute'], 0, ',', ' ') }} XAF
    </div>

    <table>
        <thead>
            <tr>
                <th>Code inventaire</th>
                <th>Libellé</th>
                <th>Catégorie</th>
                <th>Site</th>
                <th>Service</th>
                <th>Date acq.</th>
                <th class="num">Valeur acquisition</th>
                <th>État</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($immobilisations as $immo)
            <tr>
                <td>{{ $immo->code_inventaire }}</td>
                <td>{{ \Illuminate\Support\Str::limit($immo->libelle, 60) }}</td>
                <td>{{ $immo->categorie?->code }}</td>
                <td>{{ $immo->site?->code }}</td>
                <td>{{ $immo->service?->code }}</td>
                <td>{{ $immo->date_acquisition?->format('d/m/Y') }}</td>
                <td class="num">{{ number_format($immo->valeur_acquisition, 0, ',', ' ') }}</td>
                <td>{{ $immo->etat_physique }}</td>
                <td>{{ $immo->statut }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6">TOTAL</td>
                <td class="num">{{ number_format($totaux['valeur_brute'], 0, ',', ' ') }} XAF</td>
                <td colspan="2">{{ $totaux['nombre'] }} bien(s)</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Document généré par GESPAT — {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
