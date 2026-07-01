<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bon de commande {{ $commande->voucher_number }}</title>
    <style>
        @page { size: A5 portrait; margin: 6mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 7.25px;
            line-height: 1.18;
            background: #fff;
        }
        .page {
            position: relative;
            min-height: 198mm;
            border: 1.1px solid #e5e7eb;
            border-radius: 13px;
            padding: 7mm 6.5mm 5mm;
            overflow: hidden;
        }
        .page:before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 18mm;
            background: #111827;
            z-index: -2;
        }
        .page:after {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 54mm;
            height: 35mm;
            background: #c1121f;
            border-bottom-left-radius: 55mm;
            z-index: -1;
        }
        .header {
            width: 100%;
            border-collapse: collapse;
            color: #fff;
            margin-bottom: 6mm;
        }
        .header td { vertical-align: top; }
        .logo-cell { width: 38%; }
        .title-cell { width: 62%; text-align: right; }
        .logo-box {
            display: inline-block;
            background: #fff;
            border-radius: 8px;
            padding: 4px 7px;
        }
        .logo { width: 76px; display: block; }
        .logo-fallback {
            color: #111827;
            font-size: 15px;
            font-weight: 900;
            letter-spacing: .05em;
        }
        .logo-fallback span { color: #c1121f; }
        .kicker {
            margin: 1px 0 2px;
            color: #fecdd3;
            font-size: 6.3px;
            font-weight: 800;
            letter-spacing: .18em;
            text-transform: uppercase;
        }
        .doc-title {
            margin: 0;
            color: #fff;
            font-family: DejaVu Serif, serif;
            font-size: 16.5px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .025em;
        }
        .voucher {
            margin-top: 3px;
            color: #ffe4e6;
            font-size: 7px;
            font-weight: 800;
        }
        .hero {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4.5mm;
        }
        .hero td { vertical-align: top; }
        .supplier-card {
            width: 57%;
            padding-right: 3mm;
        }
        .meta-card { width: 43%; }
        .supplier-panel {
            min-height: 25mm;
            border-radius: 12px;
            padding: 8px 10px;
            background: #fff7f7;
            border: 1px solid #fecdd3;
            border-left: 5px solid #c1121f;
        }
        .supplier-label,
        .section-label,
        .tiny-label {
            color: #667085;
            font-size: 5.7px;
            font-weight: 900;
            letter-spacing: .12em;
            text-transform: uppercase;
        }
        .supplier-name {
            margin: 2px 0 5px;
            font-family: DejaVu Serif, serif;
            font-size: 13.3px;
            font-weight: 900;
            color: #111827;
        }
        .service-name {
            margin: 0;
            color: #991b1f;
            font-size: 7.9px;
            font-weight: 900;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 12px;
            overflow: hidden;
        }
        .meta-table td {
            border: 1px solid #e8edf5;
            padding: 6px 7px;
            background: #fbfdff;
        }
        .meta-table td:nth-child(1) { border-top-left-radius: 12px; }
        .meta-table td:nth-child(2) { border-top-right-radius: 12px; }
        .meta-table tr:last-child td:nth-child(1) { border-bottom-left-radius: 12px; }
        .meta-table tr:last-child td:nth-child(2) { border-bottom-right-radius: 12px; }
        .meta-value {
            display: block;
            margin-top: 2px;
            color: #111827;
            font-size: 8px;
            font-weight: 900;
        }
        .price-value {
            color: #047857;
            font-size: 9.7px;
        }
        .band {
            margin: 0 0 3mm;
            padding: 5px 8px;
            border-radius: 9px;
            background: #111827;
            color: #fff;
            font-family: DejaVu Serif, serif;
            font-size: 8.3px;
            font-weight: 900;
        }
        .route {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3.5mm;
        }
        .route td {
            width: 50%;
            vertical-align: top;
        }
        .route td:first-child { padding-right: 2.5mm; }
        .route td:last-child { padding-left: 2.5mm; }
        .route-card {
            position: relative;
            min-height: 31mm;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            background: #fff;
            padding: 7px 8px 7px 12px;
        }
        .route-card:before {
            content: "";
            position: absolute;
            top: 8px;
            bottom: 8px;
            left: 6px;
            width: 3px;
            border-radius: 10px;
            background: #c1121f;
        }
        .route-card.arrival:before { background: #2563eb; }
        .route-title {
            margin: 0 0 6px;
            font-size: 8.6px;
            font-weight: 900;
            color: #111827;
        }
        .route-line {
            margin-bottom: 3.2px;
            color: #111827;
            font-size: 7.2px;
            font-weight: 800;
            word-wrap: break-word;
        }
        .route-line span {
            color: #667085;
            font-weight: 900;
            text-transform: uppercase;
            font-size: 5.8px;
            letter-spacing: .08em;
        }
        .info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3mm;
        }
        .info th {
            padding: 5px 6px;
            background: #fff1f2;
            border-top: 1px solid #fecdd3;
            border-bottom: 1px solid #fecdd3;
            color: #991b1f;
            font-size: 6.4px;
            font-weight: 900;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        .info td {
            padding: 6px;
            border-bottom: 1px solid #eef2f7;
            color: #111827;
            font-size: 7.3px;
            font-weight: 800;
            vertical-align: top;
            word-wrap: break-word;
        }
        .info .wide { width: 42%; }
        .passenger-box {
            border: 1px solid #e5e7eb;
            border-radius: 11px;
            padding: 7px 8px;
            min-height: 16mm;
            margin-bottom: 3.5mm;
            background: #fbfdff;
        }
        .passenger-text {
            margin-top: 3px;
            color: #111827;
            font-size: 7.3px;
            font-weight: 800;
        }
        .footer-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-grid td { vertical-align: bottom; }
        .reference-box {
            width: 58%;
            padding-right: 4mm;
        }
        .signature-box {
            width: 42%;
            border: 1.2px dashed #cbd5e1;
            border-radius: 11px;
            padding: 7px;
            text-align: center;
            height: 22mm;
        }
        .reference-panel {
            border-radius: 10px;
            background: #111827;
            color: #fff;
            padding: 7px 8px;
            min-height: 22mm;
        }
        .reference-panel .tiny-label { color: #cbd5e1; }
        .reference-value {
            display: block;
            margin-top: 4px;
            color: #fff;
            font-size: 8.4px;
            font-weight: 900;
            word-wrap: break-word;
        }
        .signature-line {
            border-top: 1px solid #111827;
            margin: 9mm 6px 0;
            padding-top: 4px;
            font-family: DejaVu Serif, serif;
            font-size: 8px;
            font-weight: 900;
        }
        .legal {
            position: absolute;
            left: 6.5mm;
            right: 6.5mm;
            bottom: 2.3mm;
            color: #8a94a6;
            font-size: 5.7px;
            text-align: center;
        }
    </style>
</head>
<body>
@php
    $supplierName = $commande->supplierVehicule?->name ?: ($commande->supplierClient?->name ?: '-');
    $vehicleLabel = trim(($commande->vehicule?->matricule ?: '') . ' ' . ($commande->vehicule?->marque ?: '') . ' ' . ($commande->vehicule?->modele ?: '')) ?: '-';
    $formatDate = fn ($date) => $date ? $date->format('d/m/Y') : '-';
    $formatTime = fn ($time) => $time ? substr((string) $time, 0, 5) : '-';
    $price = number_format((float) $commande->supplier_price, 2, ',', ' ');
@endphp

<div class="page">
    <table class="header">
        <tr>
            <td class="logo-cell">
                <div class="logo-box">
                    @if ($logoDataUri)
                        <img class="logo" src="{{ $logoDataUri }}" alt="MD Tours">
                    @else
                        <div class="logo-fallback"><span>MD</span> TOURS</div>
                    @endif
                </div>
            </td>
            <td class="title-cell">
                <div class="kicker">Gestion fournisseur</div>
                <h1 class="doc-title">Bon de commande</h1>
                <div class="voucher">{{ $commande->voucher_number }} · {{ $formatDate($commande->date) }}</div>
            </td>
        </tr>
    </table>

    <table class="hero">
        <tr>
            <td class="supplier-card">
                <div class="supplier-panel">
                    <div class="supplier-label">Supplier</div>
                    <h2 class="supplier-name">{{ $supplierName }}</h2>
                    <p class="service-name">{{ $commande->service?->designation ?: 'Service non renseigné' }}</p>
                </div>
            </td>
            <td class="meta-card">
                <table class="meta-table">
                    <tr>
                        <td><span class="tiny-label">Voucher</span><span class="meta-value">{{ $commande->voucher_number }}</span></td>
                        <td><span class="tiny-label">Pax</span><span class="meta-value">{{ $commande->number_pax ?: '-' }}</span></td>
                    </tr>
                    <tr>
                        <td><span class="tiny-label">Date</span><span class="meta-value">{{ $formatDate($commande->start_date) }}</span></td>
                        <td><span class="tiny-label">Prix supplier</span><span class="meta-value price-value">{{ $price }} MAD</span></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="band">Trajet</div>
    <table class="route">
        <tr>
            <td>
                <div class="route-card">
                    <h3 class="route-title">Départ</h3>
                    <div class="route-line"><span>Point</span><br>{{ $commande->start_point ?: '-' }}</div>
                    <div class="route-line"><span>Ville</span><br>{{ $commande->start_point_city ?: '-' }}</div>
                    <div class="route-line"><span>Vol / Heure</span><br>{{ $commande->start_point_flight ?: '-' }} · {{ $formatTime($commande->start_point_time) }}</div>
                </div>
            </td>
            <td>
                <div class="route-card arrival">
                    <h3 class="route-title">Arrivée</h3>
                    <div class="route-line"><span>Point</span><br>{{ $commande->end_point ?: '-' }}</div>
                    <div class="route-line"><span>Ville</span><br>{{ $commande->end_point_city ?: '-' }}</div>
                    <div class="route-line"><span>Vol / Heure</span><br>{{ $commande->end_point_flight ?: '-' }} · {{ $formatTime($commande->end_point_time) }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="band">Équipe et réservation</div>
    <table class="info">
        <thead>
            <tr>
                <th>Driver</th>
                <th>Véhicule</th>
                <th>Guide</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $commande->driver?->name ?: '-' }}</td>
                <td>{{ $vehicleLabel }}</td>
                <td>{{ $commande->guide?->name ?: '-' }}</td>
            </tr>
        </tbody>
    </table>

    <table class="info">
        <thead>
            <tr>
                <th>Début</th>
                <th>Fin</th>
                <th class="wide">Référence dossier</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $formatDate($commande->start_date) }}</td>
                <td>{{ $formatDate($commande->end_date) }}</td>
                <td>{{ $commande->reference ?: '-' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="passenger-box">
        <span class="section-label">Passengers</span>
        <div class="passenger-text">{{ $commande->passenger ?: '-' }}</div>
    </div>

    <table class="footer-grid">
        <tr>
            <td class="reference-box">
                <div class="reference-panel">
                    <span class="tiny-label">Observation / référence</span>
                    <span class="reference-value">{{ $commande->reference ?: 'Bon généré automatiquement depuis MD Tours.' }}</span>
                </div>
            </td>
            <td class="signature-box">
                <span class="tiny-label">Signature</span>
                <div class="signature-line">{{ $commande->signature ?: 'MD Tours' }}</div>
            </td>
        </tr>
    </table>

    <div class="legal">MD Tours transport touristique · Document généré automatiquement depuis l’application.</div>
</div>
</body>
</html>
