<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bon de commande {{ $commande->voucher_number }}</title>
    <style>
        @page { size: A5 portrait; margin: 4mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 6.7px;
            line-height: 1.12;
            background: #fff;
        }
        .sheet {
            border: 1px solid #d9e0ea;
            padding: 4mm;
            position: relative;
        }
        .header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3px;
        }
        .header td { vertical-align: middle; }
        .logo-cell { width: 38%; }
        .title-cell { width: 62%; text-align: right; }
        .logo { width: 70px; display: block; }
        .logo-fallback {
            font-size: 15px;
            font-weight: 900;
            letter-spacing: .05em;
        }
        .logo-fallback span { color: #c1121f; }
        .doc-title {
            margin: 0;
            font-family: DejaVu Serif, serif;
            font-size: 15.5px;
            line-height: 1;
            font-weight: 900;
            color: #991b1f;
            text-transform: uppercase;
            letter-spacing: .025em;
        }
        .doc-subtitle {
            margin-top: 2px;
            color: #667085;
            font-size: 5.8px;
            font-weight: 900;
            letter-spacing: .14em;
            text-transform: uppercase;
        }
        .red-rule {
            height: 2.2px;
            background: #c1121f;
            margin: 3px 0 4px;
        }
        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
            border: 1px solid #fecdd3;
        }
        .summary td {
            padding: 4px 5px;
            border-right: 1px solid #fecdd3;
            background: #fff7f7;
            vertical-align: top;
        }
        .summary td:last-child { border-right: 0; }
        .sum-label,
        .row-label,
        .section-label {
            color: #667085;
            font-size: 5.2px;
            font-weight: 900;
            letter-spacing: .1em;
            text-transform: uppercase;
        }
        .sum-value {
            display: block;
            margin-top: 2px;
            font-size: 7.4px;
            font-weight: 900;
            color: #111827;
            word-wrap: break-word;
        }
        .sum-price { color: #047857; font-size: 8px; }
        .section {
            margin: 4px 0 2px;
            padding: 3.3px 6px;
            background: #111827;
            color: #fff;
            font-family: DejaVu Serif, serif;
            font-size: 7.4px;
            font-weight: 900;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 3px;
            border: 1px solid #e5e7eb;
        }
        .data-table tr:nth-child(even) td { background: #fbfdff; }
        .data-table td {
            padding: 3.2px 5px;
            border-bottom: 1px solid #edf1f6;
            vertical-align: top;
            word-wrap: break-word;
        }
        .data-table tr:last-child td { border-bottom: 0; }
        .data-table .label-cell {
            width: 31%;
            color: #991b1f;
            font-weight: 900;
            letter-spacing: .06em;
            text-transform: uppercase;
            background: #fff1f2;
            border-right: 1px solid #fecdd3;
            font-size: 5.7px;
        }
        .data-table .value-cell {
            width: 69%;
            color: #111827;
            font-weight: 800;
            font-size: 6.75px;
        }
        .two-col {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3px;
        }
        .two-col > tbody > tr > td {
            width: 50%;
            vertical-align: top;
        }
        .two-col > tbody > tr > td:first-child { padding-right: 3px; }
        .two-col > tbody > tr > td:last-child { padding-left: 3px; }
        .mini-title {
            padding: 3.2px 5px;
            background: #fff1f2;
            color: #991b1f;
            border: 1px solid #fecdd3;
            border-bottom: 0;
            font-size: 6.4px;
            font-weight: 900;
        }
        .notes {
            min-height: 10mm;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
        }
        .signature-table td { vertical-align: bottom; }
        .signature-left {
            width: 58%;
            padding-right: 4px;
        }
        .signature-right {
            width: 42%;
            border: 1px dashed #b8c2d1;
            height: 14mm;
            text-align: center;
            padding: 6px;
        }
        .reference-box {
            border: 1px solid #111827;
            background: #111827;
            color: #fff;
            min-height: 14mm;
            padding: 5px 6px;
        }
        .reference-box .section-label { color: #cbd5e1; }
        .reference-value {
            display: block;
            margin-top: 5px;
            color: #fff;
            font-size: 7px;
            font-weight: 900;
            word-wrap: break-word;
        }
        .signature-line {
            border-top: 1px solid #111827;
            margin: 5mm 6px 0;
            padding-top: 3px;
            font-family: DejaVu Serif, serif;
            font-size: 7px;
            font-weight: 900;
        }
        .footer {
            position: absolute;
            left: 4mm;
            right: 4mm;
            bottom: 1.4mm;
            border-top: 1px solid #e5e7eb;
            padding-top: 2px;
            color: #8a94a6;
            font-size: 5px;
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

<div class="sheet">
    <table class="header">
        <tr>
            <td class="logo-cell">
                @if ($logoDataUri)
                    <img class="logo" src="{{ $logoDataUri }}" alt="MD Tours">
                @else
                    <div class="logo-fallback"><span>MD</span> TOURS</div>
                @endif
            </td>
            <td class="title-cell">
                <h1 class="doc-title">Bon de commande</h1>
                <div class="doc-subtitle">Voucher fournisseur</div>
            </td>
        </tr>
    </table>

    <div class="red-rule"></div>

    <table class="summary">
        <tr>
            <td style="width: 38%;"><span class="sum-label">Supplier</span><span class="sum-value">{{ $supplierName }}</span></td>
            <td style="width: 24%;"><span class="sum-label">Voucher</span><span class="sum-value">{{ $commande->voucher_number }}</span></td>
            <td style="width: 18%;"><span class="sum-label">Date</span><span class="sum-value">{{ $formatDate($commande->date) }}</span></td>
            <td style="width: 20%;"><span class="sum-label">Prix</span><span class="sum-value sum-price">{{ $price }} MAD</span></td>
        </tr>
    </table>

    <div class="section">Informations générales</div>
    <table class="data-table">
        <tr><td class="label-cell">Service</td><td class="value-cell">{{ $commande->service?->designation ?: '-' }}</td></tr>
        <tr><td class="label-cell">Période</td><td class="value-cell">{{ $formatDate($commande->start_date) }} → {{ $formatDate($commande->end_date) }}</td></tr>
        <tr><td class="label-cell">Référence</td><td class="value-cell">{{ $commande->reference ?: '-' }}</td></tr>
        <tr><td class="label-cell">Nombre pax</td><td class="value-cell">{{ $commande->number_pax ?: '-' }}</td></tr>
    </table>

    <div class="section">Trajet</div>
    <table class="two-col">
        <tr>
            <td>
                <div class="mini-title">Départ</div>
                <table class="data-table">
                    <tr><td class="label-cell">Point</td><td class="value-cell">{{ $commande->start_point ?: '-' }}</td></tr>
                    <tr><td class="label-cell">Ville</td><td class="value-cell">{{ $commande->start_point_city ?: '-' }}</td></tr>
                    <tr><td class="label-cell">Vol</td><td class="value-cell">{{ $commande->start_point_flight ?: '-' }}</td></tr>
                    <tr><td class="label-cell">Heure</td><td class="value-cell">{{ $formatTime($commande->start_point_time) }}</td></tr>
                </table>
            </td>
            <td>
                <div class="mini-title">Arrivée</div>
                <table class="data-table">
                    <tr><td class="label-cell">Point</td><td class="value-cell">{{ $commande->end_point ?: '-' }}</td></tr>
                    <tr><td class="label-cell">Ville</td><td class="value-cell">{{ $commande->end_point_city ?: '-' }}</td></tr>
                    <tr><td class="label-cell">Vol</td><td class="value-cell">{{ $commande->end_point_flight ?: '-' }}</td></tr>
                    <tr><td class="label-cell">Heure</td><td class="value-cell">{{ $formatTime($commande->end_point_time) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section">Équipe et réservation</div>
    <table class="data-table">
        <tr><td class="label-cell">MD Driver</td><td class="value-cell">{{ $commande->driver?->name ?: '-' }}</td></tr>
        <tr><td class="label-cell">Véhicule</td><td class="value-cell">{{ $vehicleLabel }}</td></tr>
        <tr><td class="label-cell">Tour guide</td><td class="value-cell">{{ $commande->guide?->name ?: '-' }}</td></tr>
        <tr><td class="label-cell">Passenger</td><td class="value-cell notes">{{ $commande->passenger ?: '-' }}</td></tr>
    </table>

    <table class="signature-table">
        <tr>
            <td class="signature-left">
                <div class="reference-box">
                    <span class="section-label">Observation / référence</span>
                    <span class="reference-value">{{ $commande->reference ?: 'Bon généré automatiquement depuis MD Tours.' }}</span>
                </div>
            </td>
            <td class="signature-right">
                <span class="section-label">Signature</span>
                <div class="signature-line">{{ $commande->signature ?: 'MD Tours' }}</div>
            </td>
        </tr>
    </table>

    <div class="footer">MD Tours transport touristique · Document généré automatiquement depuis l’application.</div>
</div>
</body>
</html>
