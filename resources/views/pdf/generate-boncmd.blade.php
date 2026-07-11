<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bon de commande fournisseur</title>
    <style>
        @page { size:A4 portrait; margin:8mm; }
        * { box-sizing:border-box; }
        body { margin:0; color:#111827; background:#fff; font-family:DejaVu Sans,sans-serif; font-size:8.2px; line-height:1.25; }
        .page { position:relative; min-height:279mm; padding:5mm 7mm 6mm; overflow:hidden; }
        .top-red { position:absolute; top:0; left:0; width:96mm; height:6mm; background:#c1121f; }
        .top-dark { position:absolute; top:0; right:0; width:92mm; height:25mm; background:#111827; }
        .top-slope { position:absolute; top:0; left:77mm; width:0; height:0; border-top:25mm solid #111827; border-left:22mm solid transparent; }
        .header { position:relative; z-index:2; width:100%; margin:5mm 0 6mm; border-collapse:collapse; }
        .header td { vertical-align:middle; }
        .logo { display:block; width:98px; }
        .brand { color:#111827; font-size:19px; font-weight:900; letter-spacing:.05em; }.brand span{color:#c1121f}
        .title-cell { text-align:right; color:#fff; padding-right:3mm; }
        h1 { margin:0; font-size:20px; line-height:1; text-transform:uppercase; letter-spacing:.035em; }
        .subtitle { margin-top:4px; color:#e5e7eb; font-size:7px; font-weight:900; letter-spacing:.22em; text-transform:uppercase; }
        .intro { width:100%; margin-bottom:4mm; border-collapse:collapse; }
        .intro td { vertical-align:top; }
        .company { width:52%; color:#667085; font-size:8px; font-weight:700; line-height:1.42; }
        .company strong { display:block; color:#111827; font-size:10px; text-transform:uppercase; }
        .document { width:48%; text-align:right; color:#667085; font-weight:700; line-height:1.42; }
        .document strong { display:block; color:#c1121f; font-size:10px; }
        .meta { width:100%; margin-bottom:4mm; border:1px dashed #f5a8b2; border-collapse:collapse; }
        .meta td { padding:6px 8px; background:#fff5f6; border-right:1px dashed #f5a8b2; vertical-align:top; }.meta td:last-child{border-right:0}
        .label { display:block; margin-bottom:3px; color:#8a94a6; font-size:6.2px; font-weight:900; letter-spacing:.13em; text-transform:uppercase; }
        .value { color:#111827; font-size:9px; font-weight:900; word-wrap:break-word; }.amount{color:#087f5b;font-size:10.5px}
        .section { height:6mm; padding:1.5mm 3mm; background:#c1121f; color:#fff; font-size:7.5px; font-weight:900; letter-spacing:.1em; text-transform:uppercase; }
        .lines { width:100%; border-collapse:collapse; table-layout:fixed; }
        .lines th { padding:6px 5px; color:#fff; background:#111827; font-size:6.5px; letter-spacing:.06em; text-transform:uppercase; text-align:left; }
        .lines td { padding:6px 5px; color:#344054; border-bottom:1px solid #e6eaf0; font-size:7.7px; font-weight:750; vertical-align:top; word-wrap:break-word; }
        .lines tr:nth-child(even) td { background:#f7f8fa; }
        .date{width:10%}.dossier{width:22%}.bus{width:12%;text-align:center}.service{width:30%}.price{width:13%;text-align:right;white-space:nowrap}
        .total td { padding:8px 5px; color:#fff; background:#111827!important; border:0; font-size:9px; font-weight:900; }.total .grand{color:#fda4af;font-size:10px}
        .footer { width:100%; margin-top:5mm; border-collapse:collapse; }.footer td{vertical-align:bottom}
        .note { width:55%; padding-right:6mm; color:#667085; line-height:1.5; }.note strong{display:block;margin-bottom:4px;color:#c1121f;font-size:8px;text-transform:uppercase}
        .signature { width:45%; height:29mm; padding:6px; border:1px dashed #c4cede; text-align:center; color:#667085; font-weight:900; }
        .bottom { width:100%; margin-top:7mm; border-collapse:collapse; }.bottom td{height:4.5mm;padding:0}.bottom .dark{width:68%;background:#111827}.bottom .red{width:32%;background:#c1121f}
    </style>
</head>
<body>
@php
    $money = fn ($value) => number_format((float) $value, 2, ',', ' ');
    $from = $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : '-';
    $to = $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : '-';
@endphp
<main class="page">
    <div class="top-red"></div><div class="top-slope"></div><div class="top-dark"></div>
    <table class="header"><tr>
        <td style="width:42%">@if($logoDataUri)<img class="logo" src="{{ $logoDataUri }}" alt="MD Tours">@else<div class="brand"><span>MD</span> TOURS</div>@endif</td>
        <td class="title-cell"><h1>Bon de commande</h1><div class="subtitle">Fournisseur véhicule</div></td>
    </tr></table>
    <table class="intro"><tr>
        <td class="company"><strong>MD Tours</strong>Transport touristique & organisation de voyages<br>Document de commande fournisseur</td>
        <td class="document"><strong>Émis le {{ $generatedAt->format('d/m/Y') }}</strong>à {{ $generatedAt->format('H:i') }} · Référence automatique</td>
    </tr></table>
    <table class="meta"><tr>
        <td style="width:38%"><span class="label">Fournisseur</span><span class="value">{{ $supplier?->name ?: '-' }}</span></td>
        <td style="width:27%"><span class="label">Période de prestation</span><span class="value">{{ $from }} — {{ $to }}</span></td>
        <td style="width:14%"><span class="label">Prestations</span><span class="value">{{ $totals['count'] }}</span></td>
        <td style="width:21%"><span class="label">Montant total TTC</span><span class="value amount">{{ $money($totals['total_price']) }} MAD</span></td>
    </tr></table>
    <div class="section">Détail des prestations commandées</div>
    <table class="lines">
        <thead><tr><th class="date">Date</th><th class="dossier">Dossier / Client</th><th class="bus">Véhicule</th><th class="service">Service & trajet</th><th class="price">Prix unitaire</th><th class="price">Total TTC</th></tr></thead>
        <tbody>
        @foreach($rows as $row)<tr>
            <td class="date">{{ $row['du'] }}</td><td class="dossier">{{ $row['au'] }}</td><td class="bus">{{ $row['bus'] }}</td><td class="service">{{ $row['service'] }}</td><td class="price">{{ $money($row['unit_price']) }}</td><td class="price">{{ $money($row['total_price']) }}</td>
        </tr>@endforeach
        <tr class="total"><td colspan="4">TOTAL GÉNÉRAL</td><td class="price">{{ $money($totals['unit_price']) }}</td><td class="price grand">{{ $money($totals['total_price']) }} MAD</td></tr>
        </tbody>
    </table>
    <table class="footer"><tr><td class="note"><strong>Conditions & validation</strong>Ce document confirme les prestations sélectionnées pour la période indiquée. Toute modification doit être validée par MD Tours avant exécution.</td><td class="signature">BON POUR ACCORD<br><br><br>Signature et cachet du fournisseur</td></tr></table>
    <table class="bottom"><tr><td class="dark"></td><td class="red"></td></tr></table>
</main>
</body>
</html>
