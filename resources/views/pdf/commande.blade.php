<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bon de commande {{ $commande->voucher_number }}</title>
    <style>
        @page { size: A4 portrait; margin: 8mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            background: #ffffff;
            font-size: 8.4px;
            line-height: 1.12;
        }
        .page {
            min-height: 0;
            padding: 5mm 7mm 5mm;
            position: relative;
            overflow: hidden;
            background: #ffffff;
        }
        .top-red {
            position: absolute;
            top: 0;
            left: 0;
            width: 96mm;
            height: 6mm;
            background: #c1121f;
        }
        .top-dark {
            position: absolute;
            top: 0;
            right: 0;
            width: 92mm;
            height: 25mm;
            background: #111827;
        }
        .top-dark-slope {
            position: absolute;
            top: 0;
            left: 77mm;
            width: 0;
            height: 0;
            border-top: 25mm solid #111827;
            border-left: 22mm solid transparent;
        }
        .bottom-line {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12mm;
        }
        .bottom-line td { height: 4.5mm; padding: 0; }
        .bottom-line .dark { width: 68%; background: #111827; }
        .bottom-line .red { width: 32%; background: #c1121f; }
        .header {
            width: 100%;
            border-collapse: collapse;
            margin: 5mm 0 5mm;
            position: relative;
            z-index: 2;
        }
        .header td { vertical-align: middle; }
        .logo-cell { width: 42%; }
        .title-cell {
            width: 58%;
            text-align: right;
            color: #ffffff;
            padding-right: 3mm;
        }
        .logo { width: 98px; display: block; }
        .logo-fallback {
            font-size: 19px;
            font-weight: 900;
            letter-spacing: .05em;
            color: #111827;
        }
        .logo-fallback span { color: #c1121f; }
        .doc-title {
            margin: 0;
            color: #ffffff;
            font-size: 21px;
            line-height: 1;
            text-transform: uppercase;
            letter-spacing: .035em;
            font-weight: 900;
        }
        .doc-subtitle {
            margin-top: 4px;
            color: #e5e7eb;
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: .24em;
            font-weight: 900;
        }
        .identity {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3mm;
        }
        .identity td { vertical-align: top; }
        .identity-left {
            width: 50%;
            color: #667085;
            font-size: 8.5px;
            font-weight: 700;
            line-height: 1.32;
        }
        .identity-right {
            width: 50%;
            text-align: right;
            color: #667085;
            font-size: 8.5px;
            font-weight: 700;
            line-height: 1.32;
        }
        .identity strong {
            display: block;
            color: #111827;
            font-size: 11px;
            line-height: 1.3;
            text-transform: uppercase;
            font-weight: 900;
        }
        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3mm;
            border: 1px dashed #f5a8b2;
        }
        .meta td {
            padding: 4px 7px;
            border-right: 1px dashed #f5a8b2;
            background: #fff5f6;
            vertical-align: top;
        }
        .meta td:last-child { border-right: 0; }
        .label {
            display: block;
            margin-bottom: 3px;
            color: #8a94a6;
            font-size: 6.4px;
            font-weight: 900;
            letter-spacing: .13em;
            text-transform: uppercase;
        }
        .value {
            color: #111827;
            font-size: 9px;
            font-weight: 900;
            word-wrap: break-word;
        }
        .price {
            color: #087f5b;
            font-size: 10.5px;
            font-weight: 900;
        }
        .bar {
            height: 5.8mm;
            padding: 1.4mm 3mm;
            background: #c1121f;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: 7.5px;
            font-weight: 900;
            margin-top: 4mm;
        }
        .section-title {
            height: 5.8mm;
            padding: 1.4mm 3mm;
            background: #111827;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: 7.5px;
            font-weight: 900;
            margin-top: 2.5mm;
        }
        .info-table,
        .journey-table,
        .team-table,
        .items-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .info-table td,
        .journey-table td,
        .team-table td,
        .items-table td,
        .items-table th {
            border-bottom: 1px solid #e6eaf0;
            padding: 3.6px 6px;
            vertical-align: top;
            word-wrap: break-word;
        }
        .items-table th {
            background: #c1121f;
            color: #ffffff;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: .08em;
            font-weight: 900;
            text-align: left;
        }
        .items-table td {
            height: 6.4mm;
            color: #344054;
            font-size: 8px;
            font-weight: 800;
        }
        .items-table tr:nth-child(even) td,
        .info-table tr:nth-child(even) td,
        .journey-table tr:nth-child(even) td,
        .team-table tr:nth-child(even) td {
            background: #f5f7fa;
        }
        .k {
            width: 26%;
            color: #991b1f;
            background: #fff5f6;
            text-transform: uppercase;
            letter-spacing: .07em;
            font-size: 6.4px;
            font-weight: 900;
        }
        .v {
            color: #111827;
            font-size: 8.4px;
            font-weight: 850;
        }
        .two-col {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2mm;
        }
        .two-col > tbody > tr > td {
            width: 50%;
            vertical-align: top;
        }
        .two-col > tbody > tr > td:first-child { padding-right: 3mm; }
        .two-col > tbody > tr > td:last-child { padding-left: 3mm; }
        .subhead {
            padding: 4px 6px;
            background: #fff5f6;
            border-left: 4px solid #c1121f;
            color: #991b1f;
            font-size: 8px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .footer-zone {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3mm;
        }
        .footer-zone td { vertical-align: bottom; }
        .terms {
            width: 54%;
            padding-right: 5mm;
        }
        .signature {
            width: 46%;
            text-align: center;
        }
        .terms-title {
            color: #c1121f;
            text-transform: uppercase;
            font-size: 8.5px;
            font-weight: 900;
            margin-bottom: 3px;
        }
        .terms-box {
            min-height: 29mm;
            border: 1.2px dashed #c4cede;
            padding: 6px 8px;
            color: #475467;
            font-size: 7.4px;
            line-height: 1.26;
            background: #fbfdff;
        }
        .signature-box {
            min-height: 29mm;
            border: 1.2px dashed #f5a8b2;
            padding: 7px 10px;
            background: #fff5f6;
        }
        .signature-title {
            color: #8a94a6;
            font-size: 6.4px;
            text-transform: uppercase;
            letter-spacing: .14em;
            font-weight: 900;
        }
        .signature-line {
            margin: 18mm 10mm 0;
            border-top: 1.2px solid #111827;
            padding-top: 5px;
            color: #111827;
            font-size: 8.5px;
            font-weight: 900;
        }
        .small-note {
            margin-top: 2.5mm;
            color: #98a2b3;
            text-align: center;
            font-size: 6.4px;
            letter-spacing: .03em;
        }
    </style>
</head>
<body>
@php
    $supplierName = $commande->supplierVehicule?->name ?: ($commande->supplierClient?->name ?: '-');
    $vehicleLabel = trim(($commande->vehicule?->matricule ?: '') . ' ' . ($commande->vehicule?->marque ?: '') . ' ' . ($commande->vehicule?->modele ?: '')) ?: '-';
    $formatDate = fn ($date) => $date ? $date->format('d/m/Y') : '-';
    $formatTime = fn ($time) => $time ? substr((string) $time, 0, 5) : '-';
    $period = $formatDate($commande->start_date) . ' → ' . $formatDate($commande->end_date);
    $price = number_format((float) $commande->supplier_price, 2, ',', ' ');
@endphp

<div class="page">
    <div class="top-red"></div>
    <div class="top-dark-slope"></div>
    <div class="top-dark"></div>

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

    <table class="identity">
        <tr>
            <td class="identity-left">
                <span>Commande fournisseur</span>
                <strong>{{ $commande->voucher_number }}</strong>
                Date : {{ $formatDate($commande->date) }}
            </td>
            <td class="identity-right">
                <span>Commande à</span>
                <strong>{{ $supplierName }}</strong>
                Référence : {{ $commande->reference ?: '-' }}
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td style="width: 34%;"><span class="label">Supplier</span><span class="value">{{ $supplierName }}</span></td>
            <td style="width: 23%;"><span class="label">Voucher</span><span class="value">{{ $commande->voucher_number }}</span></td>
            <td style="width: 20%;"><span class="label">Date</span><span class="value">{{ $formatDate($commande->date) }}</span></td>
            <td style="width: 23%;"><span class="label">Prix fournisseur</span><span class="price">{{ $price }} MAD</span></td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 36%;">Service</th>
                <th style="width: 18%;">Départ</th>
                <th style="width: 18%;">Arrivée</th>
                <th style="width: 10%;">Heure</th>
                <th style="width: 18%; text-align: right;">Montant</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $commande->service?->designation ?: '-' }}</td>
                <td>{{ $commande->start_point ?: '-' }}</td>
                <td>{{ $commande->end_point ?: '-' }}</td>
                <td>{{ $formatTime($commande->start_point_time) }}</td>
                <td style="text-align: right; color: #087f5b; font-weight: 900;">{{ $price }} MAD</td>
            </tr>
            <tr>
                <td>Période</td>
                <td colspan="2">{{ $period }}</td>
                <td>Pax</td>
                <td style="text-align: right;">{{ $commande->number_pax ?: '-' }}</td>
            </tr>
            <tr>
                <td>Référence dossier</td>
                <td colspan="4">{{ $commande->reference ?: '-' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Informations générales</div>
    <table class="info-table">
        <tr><td class="k">Service</td><td class="v">{{ $commande->service?->designation ?: '-' }}</td><td class="k">Période</td><td class="v">{{ $period }}</td></tr>
        <tr><td class="k">Référence</td><td class="v">{{ $commande->reference ?: '-' }}</td><td class="k">Nombre pax</td><td class="v">{{ $commande->number_pax ?: '-' }}</td></tr>
    </table>

    <div class="section-title">Trajet</div>
    <table class="two-col">
        <tr>
            <td>
                <div class="subhead">Départ</div>
                <table class="journey-table">
                    <tr><td class="k">Point</td><td class="v">{{ $commande->start_point ?: '-' }}</td></tr>
                    <tr><td class="k">Ville</td><td class="v">{{ $commande->start_point_city ?: '-' }}</td></tr>
                    <tr><td class="k">Vol</td><td class="v">{{ $commande->start_point_flight ?: '-' }}</td></tr>
                    <tr><td class="k">Heure</td><td class="v">{{ $formatTime($commande->start_point_time) }}</td></tr>
                </table>
            </td>
            <td>
                <div class="subhead">Arrivée</div>
                <table class="journey-table">
                    <tr><td class="k">Point</td><td class="v">{{ $commande->end_point ?: '-' }}</td></tr>
                    <tr><td class="k">Ville</td><td class="v">{{ $commande->end_point_city ?: '-' }}</td></tr>
                    <tr><td class="k">Vol</td><td class="v">{{ $commande->end_point_flight ?: '-' }}</td></tr>
                    <tr><td class="k">Heure</td><td class="v">{{ $formatTime($commande->end_point_time) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title">Équipe et réservation</div>
    <table class="team-table">
        <tr><td class="k">MD Driver</td><td class="v">{{ $commande->driver?->name ?: '-' }}</td><td class="k">Véhicule</td><td class="v">{{ $vehicleLabel }}</td></tr>
        <tr><td class="k">Tour guide</td><td class="v">{{ $commande->guide?->name ?: '-' }}</td><td class="k">Signature</td><td class="v">{{ $commande->signature ?: 'MD Tours' }}</td></tr>
        <tr><td class="k">Passenger</td><td class="v" colspan="3">{{ $commande->passenger ?: '-' }}</td></tr>
    </table>

    <table class="footer-zone">
        <tr>
            <td class="terms">
                <div class="terms-title">Observation / référence</div>
                <div class="terms-box">
                    <strong>{{ $commande->reference ?: 'Bon généré automatiquement depuis MD Tours.' }}</strong><br>
                    Ce bon de commande reprend les informations validées dans le planning MD Tours.
                    Merci de confirmer le service, le trajet, l'heure de prise en charge et le prix fournisseur avant exécution.
                </div>
            </td>
            <td class="signature">
                <div class="signature-box">
                    <div class="signature-title">Signature autorisée</div>
                    <div class="signature-line">{{ $commande->signature ?: 'MD Tours' }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="small-note">MD Tours transport touristique - Document généré automatiquement depuis l'application.</div>

    <table class="bottom-line">
        <tr>
            <td class="dark"></td>
            <td class="red"></td>
        </tr>
    </table>
</div>
</body>
</html>
