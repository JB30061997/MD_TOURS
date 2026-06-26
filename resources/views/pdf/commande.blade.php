<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bon de commande {{ $commande->voucher_number }}</title>
    <style>
        @page { margin: 18px 28px 24px; }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #121827;
            font-size: 10.5px;
            line-height: 1.35;
            background: #ffffff;
        }

        .page {
            position: relative;
            min-height: 100%;
        }

        .topbar {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .brand-cell {
            width: 45%;
            vertical-align: top;
        }

        .title-cell {
            width: 55%;
            vertical-align: middle;
            text-align: right;
        }

        .logo {
            width: 150px;
            display: block;
        }

        .logo-fallback {
            font-size: 28px;
            font-weight: 900;
            letter-spacing: .08em;
            color: #111827;
        }

        .logo-fallback span { color: #be1220; }

        .doc-title {
            font-family: DejaVu Serif, serif;
            font-size: 29px;
            font-weight: 900;
            letter-spacing: .03em;
            color: #991b1f;
            text-transform: uppercase;
            margin: 11px 0 0;
        }

        .red-rule {
            height: 4px;
            background: #c51122;
            margin: 0 0 16px;
        }

        .summary {
            position: relative;
            background: #fff7f7;
            border: 1.4px solid #ffc9cc;
            border-left: 8px solid #c51122;
            border-radius: 14px;
            padding: 14px 20px;
            margin-bottom: 14px;
        }

        .supplier-name {
            font-family: DejaVu Serif, serif;
            font-size: 22px;
            font-weight: 900;
            margin: 0 0 7px;
            color: #111827;
        }

        .summary-line {
            font-size: 15px;
            color: #111827;
        }

        .summary-line strong {
            font-weight: 900;
        }

        .separator {
            color: #991b1f;
            font-weight: 900;
            padding: 0 14px;
        }

        .section-title {
            background: #101827;
            color: #ffffff;
            border-radius: 10px;
            padding: 9px 14px;
            margin: 13px 0 7px;
            font-family: DejaVu Serif, serif;
            font-size: 14px;
            font-weight: 900;
        }

        .grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 6px;
        }

        .grid td {
            width: 50%;
            vertical-align: top;
            padding-right: 8px;
        }

        .grid td:last-child {
            padding-right: 0;
            padding-left: 8px;
        }

        .field {
            min-height: 48px;
            border: 1px solid #e2e8f0;
            border-radius: 11px;
            padding: 10px 14px 9px;
            background: #ffffff;
        }

        .label {
            display: block;
            margin-bottom: 5px;
            color: #728099;
            font-family: DejaVu Serif, serif;
            font-size: 10.5px;
            font-weight: 900;
            letter-spacing: .045em;
            text-transform: uppercase;
        }

        .value {
            display: block;
            color: #111827;
            font-family: DejaVu Serif, serif;
            font-size: 14px;
            font-weight: 900;
            word-break: break-word;
        }

        .value.small {
            font-size: 12.5px;
            font-family: DejaVu Sans, sans-serif;
            font-weight: 800;
        }

        .price {
            color: #07855f;
            font-size: 18px;
            letter-spacing: .02em;
        }

        .compact td .field {
            min-height: 43px;
        }

        .details {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 6px;
            margin-top: 2px;
        }

        .details td {
            width: 33.333%;
            vertical-align: top;
            padding-right: 8px;
        }

        .details td:last-child { padding-right: 0; }

        .signature-wrap {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }

        .notes-cell {
            width: 56%;
            vertical-align: top;
            padding-right: 16px;
        }

        .signature-cell {
            width: 44%;
            vertical-align: top;
        }

        .note-box {
            min-height: 88px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
            background: #fbfdff;
        }

        .signature-box {
            height: 98px;
            border: 2px dashed #aab4c4;
            border-radius: 14px;
            padding: 14px 16px;
            text-align: center;
            background: #ffffff;
        }

        .signature-label {
            text-align: left;
            font-weight: 900;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .04em;
            font-size: 10px;
        }

        .signature-line {
            border-top: 1.4px solid #111827;
            margin-top: 39px;
            padding-top: 9px;
            font-family: DejaVu Serif, serif;
            font-size: 13px;
            font-weight: 900;
            color: #111827;
        }

        .footer {
            position: fixed;
            left: 28px;
            right: 28px;
            bottom: -12px;
            padding-top: 7px;
            border-top: 1px solid #e5e7eb;
            color: #738096;
            font-size: 8.5px;
            text-align: center;
        }
    </style>
</head>
<body>
@php
    $supplierName = $commande->supplierVehicule?->name ?: '-';
    $vehicleLabel = trim(($commande->vehicule?->matricule ?: '') . ' ' . ($commande->vehicule?->marque ?: '') . ' ' . ($commande->vehicule?->modele ?: '')) ?: '-';
    $dateValue = $commande->date?->format('d/m/Y') ?: '-';
    $startDate = $commande->start_date?->format('d/m/Y') ?: '-';
    $endDate = $commande->end_date?->format('d/m/Y') ?: '-';
    $formatTime = fn ($time) => $time ? substr((string) $time, 0, 5) : '-';
@endphp

<div class="page">
    <table class="topbar">
        <tr>
            <td class="brand-cell">
                @if ($logoDataUri)
                    <img class="logo" src="{{ $logoDataUri }}" alt="MD Tours">
                @else
                    <div class="logo-fallback">MD <span>TOURS</span></div>
                @endif
            </td>
            <td class="title-cell">
                <div class="doc-title">Bon de commande</div>
            </td>
        </tr>
    </table>

    <div class="red-rule"></div>

    <div class="summary">
        <div class="supplier-name">{{ $supplierName }}</div>
        <div class="summary-line">
            Voucher: <strong>{{ $commande->voucher_number }}</strong>
            <span class="separator">|</span>
            Date: <strong>{{ $dateValue }}</strong>
            <span class="separator">|</span>
            Reference: <strong>{{ $commande->reference ?: '-' }}</strong>
        </div>
    </div>

    <div class="section-title">Informations générales</div>
    <table class="grid">
        <tr>
            <td><div class="field"><span class="label">Supplier</span><span class="value">{{ $supplierName }}</span></div></td>
            <td><div class="field"><span class="label">Voucher number</span><span class="value">{{ $commande->voucher_number }}</span></div></td>
        </tr>
        <tr>
            <td><div class="field"><span class="label">Start Date</span><span class="value">{{ $startDate }}</span></div></td>
            <td><div class="field"><span class="label">End Date</span><span class="value">{{ $endDate }}</span></div></td>
        </tr>
        <tr>
            <td><div class="field"><span class="label">Service Type</span><span class="value small">{{ $commande->service?->designation ?: '-' }}</span></div></td>
            <td><div class="field"><span class="label">Supplier Price</span><span class="value price">{{ number_format((float) $commande->supplier_price, 2, ',', ' ') }} MAD</span></div></td>
        </tr>
    </table>

    <div class="section-title">Départ</div>
    <table class="grid compact">
        <tr>
            <td><div class="field"><span class="label">Start Point</span><span class="value small">{{ $commande->start_point ?: '-' }}</span></div></td>
            <td><div class="field"><span class="label">Start Point Flight</span><span class="value small">{{ $commande->start_point_flight ?: '-' }}</span></div></td>
        </tr>
        <tr>
            <td><div class="field"><span class="label">Start Point City</span><span class="value small">{{ $commande->start_point_city ?: '-' }}</span></div></td>
            <td><div class="field"><span class="label">Start Point Time</span><span class="value small">{{ $formatTime($commande->start_point_time) }}</span></div></td>
        </tr>
    </table>

    <div class="section-title">Arrivée</div>
    <table class="grid compact">
        <tr>
            <td><div class="field"><span class="label">End Point</span><span class="value small">{{ $commande->end_point ?: '-' }}</span></div></td>
            <td><div class="field"><span class="label">End Point Flight</span><span class="value small">{{ $commande->end_point_flight ?: '-' }}</span></div></td>
        </tr>
        <tr>
            <td><div class="field"><span class="label">End Point City</span><span class="value small">{{ $commande->end_point_city ?: '-' }}</span></div></td>
            <td><div class="field"><span class="label">End Point Time</span><span class="value small">{{ $formatTime($commande->end_point_time) }}</span></div></td>
        </tr>
    </table>

    <div class="section-title">Équipe et passagers</div>
    <table class="details">
        <tr>
            <td><div class="field"><span class="label">MD Driver</span><span class="value small">{{ $commande->driver?->name ?: '-' }}</span></div></td>
            <td><div class="field"><span class="label">MD Tours Vehicle</span><span class="value small">{{ $vehicleLabel }}</span></div></td>
            <td><div class="field"><span class="label">Tour Guide</span><span class="value small">{{ $commande->guide?->name ?: '-' }}</span></div></td>
        </tr>
        <tr>
            <td><div class="field"><span class="label">Number Pax</span><span class="value">{{ $commande->number_pax ?: '-' }}</span></div></td>
            <td colspan="2"><div class="field"><span class="label">Passenger</span><span class="value small">{{ $commande->passenger ?: '-' }}</span></div></td>
        </tr>
    </table>

    <table class="signature-wrap">
        <tr>
            <td class="notes-cell">
                <div class="note-box">
                    <span class="label">Observation</span>
                    <span class="value small">Bon de commande généré automatiquement depuis MD Tours.</span>
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

    <div class="footer">MD Tours transport touristique - Bon de commande généré automatiquement</div>
</div>
</body>
</html>
