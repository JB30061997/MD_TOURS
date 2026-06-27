<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bon de commande {{ $commande->voucher_number }}</title>
    <style>
        @page { size: A5 portrait; margin: 9mm 8mm 10mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 8.2px;
            line-height: 1.26;
            background: #fff;
        }
        .topbar {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .brand-cell { width: 45%; vertical-align: top; }
        .title-cell { width: 55%; text-align: right; vertical-align: middle; }
        .logo { width: 82px; display: block; }
        .logo-fallback {
            font-size: 17px;
            font-weight: 900;
            letter-spacing: .06em;
        }
        .logo-fallback span { color: #c1121f; }
        .doc-title {
            margin: 0;
            font-family: DejaVu Serif, serif;
            font-size: 18px;
            font-weight: 900;
            color: #991b1f;
            text-transform: uppercase;
            letter-spacing: .025em;
        }
        .red-rule {
            height: 2.4px;
            background: #c1121f;
            margin: 4px 0 8px;
        }
        .summary {
            border: 1px solid #fecdd3;
            border-left: 5px solid #c1121f;
            border-radius: 9px;
            background: #fff7f7;
            padding: 7px 9px;
            margin-bottom: 8px;
        }
        .supplier-name {
            margin: 0 0 4px;
            font-family: DejaVu Serif, serif;
            font-size: 13px;
            font-weight: 900;
            color: #111827;
        }
        .summary-line {
            font-size: 8.5px;
            color: #1f2937;
        }
        .summary-line strong { font-weight: 900; }
        .separator {
            color: #991b1f;
            font-weight: 900;
            padding: 0 6px;
        }
        .section-title {
            margin: 7px 0 4px;
            padding: 5px 8px;
            border-radius: 7px;
            background: #101827;
            color: #fff;
            font-family: DejaVu Serif, serif;
            font-size: 9px;
            font-weight: 900;
        }
        .grid,
        .details,
        .signature-wrap {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 4px;
        }
        .grid td {
            width: 50%;
            vertical-align: top;
            padding-right: 4px;
        }
        .grid td:last-child {
            padding-right: 0;
            padding-left: 4px;
        }
        .details td {
            width: 33.333%;
            vertical-align: top;
            padding-right: 4px;
        }
        .details td:last-child { padding-right: 0; }
        .field {
            min-height: 30px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 6px 7px;
            background: #fff;
            overflow: hidden;
        }
        .field.tall { min-height: 38px; }
        .label {
            display: block;
            margin-bottom: 3px;
            color: #728099;
            font-family: DejaVu Serif, serif;
            font-size: 6.8px;
            font-weight: 900;
            letter-spacing: .035em;
            text-transform: uppercase;
        }
        .value {
            display: block;
            color: #111827;
            font-family: DejaVu Serif, serif;
            font-size: 8.8px;
            font-weight: 900;
            word-wrap: break-word;
        }
        .value.small {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            font-weight: 800;
        }
        .price {
            color: #07855f;
            font-size: 11px;
        }
        .notes-cell {
            width: 56%;
            vertical-align: top;
            padding-right: 8px;
        }
        .signature-cell {
            width: 44%;
            vertical-align: top;
        }
        .note-box {
            min-height: 43px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 7px;
            background: #fbfdff;
        }
        .signature-box {
            height: 50px;
            border: 1.3px dashed #aab4c4;
            border-radius: 8px;
            padding: 7px;
            text-align: center;
        }
        .signature-label {
            text-align: left;
            color: #64748b;
            font-size: 6.8px;
            font-weight: 900;
            text-transform: uppercase;
        }
        .signature-line {
            border-top: 1px solid #111827;
            margin-top: 18px;
            padding-top: 5px;
            font-family: DejaVu Serif, serif;
            font-size: 8px;
            font-weight: 900;
        }
        .footer {
            position: fixed;
            left: 8mm;
            right: 8mm;
            bottom: 4mm;
            border-top: 1px solid #e5e7eb;
            padding-top: 3px;
            color: #738096;
            font-size: 6.2px;
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
@endphp

<table class="topbar">
    <tr>
        <td class="brand-cell">
            @if ($logoDataUri)
                <img class="logo" src="{{ $logoDataUri }}" alt="MD Tours">
            @else
                <div class="logo-fallback"><span>MD</span> TOURS</div>
            @endif
        </td>
        <td class="title-cell">
            <h1 class="doc-title">Bon de commande</h1>
        </td>
    </tr>
</table>

<div class="red-rule"></div>

<div class="summary">
    <h2 class="supplier-name">{{ $supplierName }}</h2>
    <div class="summary-line">
        Voucher: <strong>{{ $commande->voucher_number }}</strong>
        <span class="separator">|</span>
        Date: <strong>{{ $formatDate($commande->date) }}</strong>
        <span class="separator">|</span>
        Ref: <strong>{{ $commande->reference ?: '-' }}</strong>
    </div>
</div>

<div class="section-title">Informations générales</div>
<table class="grid">
    <tr>
        <td><div class="field"><span class="label">Supplier</span><span class="value">{{ $supplierName }}</span></div></td>
        <td><div class="field"><span class="label">Voucher number</span><span class="value">{{ $commande->voucher_number }}</span></div></td>
    </tr>
    <tr>
        <td><div class="field"><span class="label">Start date</span><span class="value">{{ $formatDate($commande->start_date) }}</span></div></td>
        <td><div class="field"><span class="label">End date</span><span class="value">{{ $formatDate($commande->end_date) }}</span></div></td>
    </tr>
    <tr>
        <td><div class="field"><span class="label">Service type</span><span class="value">{{ $commande->service?->designation ?: '-' }}</span></div></td>
        <td><div class="field"><span class="label">Supplier price</span><span class="value price">{{ number_format((float) $commande->supplier_price, 2, ',', ' ') }} MAD</span></div></td>
    </tr>
</table>

<div class="section-title">Départ</div>
<table class="grid">
    <tr>
        <td><div class="field"><span class="label">Start point</span><span class="value small">{{ $commande->start_point ?: '-' }}</span></div></td>
        <td><div class="field"><span class="label">Flight</span><span class="value small">{{ $commande->start_point_flight ?: '-' }}</span></div></td>
    </tr>
    <tr>
        <td><div class="field"><span class="label">City</span><span class="value small">{{ $commande->start_point_city ?: '-' }}</span></div></td>
        <td><div class="field"><span class="label">Time</span><span class="value small">{{ $formatTime($commande->start_point_time) }}</span></div></td>
    </tr>
</table>

<div class="section-title">Arrivée</div>
<table class="grid">
    <tr>
        <td><div class="field"><span class="label">End point</span><span class="value small">{{ $commande->end_point ?: '-' }}</span></div></td>
        <td><div class="field"><span class="label">Flight</span><span class="value small">{{ $commande->end_point_flight ?: '-' }}</span></div></td>
    </tr>
    <tr>
        <td><div class="field"><span class="label">City</span><span class="value small">{{ $commande->end_point_city ?: '-' }}</span></div></td>
        <td><div class="field"><span class="label">Time</span><span class="value small">{{ $formatTime($commande->end_point_time) }}</span></div></td>
    </tr>
</table>

<div class="section-title">Équipe et réservation</div>
<table class="details">
    <tr>
        <td><div class="field tall"><span class="label">MD Driver</span><span class="value small">{{ $commande->driver?->name ?: '-' }}</span></div></td>
        <td><div class="field tall"><span class="label">Vehicle</span><span class="value small">{{ $vehicleLabel }}</span></div></td>
        <td><div class="field tall"><span class="label">Tour guide</span><span class="value small">{{ $commande->guide?->name ?: '-' }}</span></div></td>
    </tr>
    <tr>
        <td colspan="2"><div class="field tall"><span class="label">Passenger</span><span class="value small">{{ $commande->passenger ?: '-' }}</span></div></td>
        <td><div class="field tall"><span class="label">Number pax</span><span class="value small">{{ $commande->number_pax ?: '-' }}</span></div></td>
    </tr>
</table>

<table class="signature-wrap">
    <tr>
        <td class="notes-cell">
            <div class="note-box">
                <span class="label">Reference</span>
                <span class="value small">{{ $commande->reference ?: '-' }}</span>
            </div>
        </td>
        <td class="signature-cell">
            <div class="signature-box">
                <div class="signature-label">Signature</div>
                <div class="signature-line">{{ $commande->signature ?: 'MD Tours' }}</div>
            </div>
        </td>
    </tr>
</table>

<div class="footer">
    MD Tours transport touristique - Bon généré automatiquement depuis l'application.
</div>
</body>
</html>
