<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Roadsheet {{ $roadSheet->voucher_number }}</title>
    <style>
        @page { margin: 20px 28px 26px; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 11px;
            line-height: 1.4;
        }
        .topbar { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .brand-cell { width: 45%; }
        .title-cell { width: 55%; text-align: right; vertical-align: middle; }
        .logo { width: 150px; display: block; }
        .logo-fallback { font-size: 28px; font-weight: 900; color: #111827; }
        .logo-fallback span { color: #be1220; }
        .doc-title {
            font-family: DejaVu Serif, serif;
            font-size: 30px;
            font-weight: 900;
            color: #991b1f;
            text-transform: uppercase;
        }
        .red-rule { height: 4px; background: #c51122; margin: 0 0 16px; }
        .summary {
            border: 1.4px solid #ffc9cc;
            border-left: 8px solid #c51122;
            border-radius: 14px;
            background: #fff7f7;
            padding: 14px 18px;
            margin-bottom: 14px;
        }
        .summary h1 {
            margin: 0 0 7px;
            font-family: DejaVu Serif, serif;
            font-size: 21px;
        }
        .section-title {
            background: #101827;
            color: #fff;
            border-radius: 10px;
            padding: 9px 13px;
            margin: 13px 0 7px;
            font-family: DejaVu Serif, serif;
            font-weight: 900;
            font-size: 14px;
        }
        .grid { width: 100%; border-collapse: separate; border-spacing: 0 6px; }
        .grid td { width: 50%; vertical-align: top; padding-right: 8px; }
        .grid td:last-child { padding-right: 0; padding-left: 8px; }
        .field {
            min-height: 44px;
            border: 1px solid #e2e8f0;
            border-radius: 11px;
            padding: 9px 12px;
            background: #fff;
        }
        .label {
            display: block;
            margin-bottom: 5px;
            color: #728099;
            font-family: DejaVu Serif, serif;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .value {
            display: block;
            font-family: DejaVu Serif, serif;
            font-weight: 900;
            font-size: 13.5px;
        }
        table.lines {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .lines th {
            background: #fff1f2;
            color: #991b1f;
            padding: 9px 8px;
            border-bottom: 1px solid #fecdd3;
            text-align: left;
            font-weight: 900;
        }
        .lines td {
            padding: 9px 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        .signature-wrap { width: 100%; border-collapse: collapse; margin-top: 18px; }
        .signature-cell { width: 45%; vertical-align: top; }
        .notes-cell { width: 55%; vertical-align: top; padding-right: 16px; }
        .box {
            min-height: 86px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 13px;
        }
        .signature-line {
            border-top: 1.4px solid #111827;
            margin-top: 38px;
            padding-top: 8px;
            text-align: center;
            font-family: DejaVu Serif, serif;
            font-weight: 900;
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
    $planning = $roadSheet->planning;
    $vehicleLabel = trim(($planning?->vehicule?->matricule ?: '') . ' ' . ($planning?->vehicule?->marque ?: '') . ' ' . ($planning?->vehicule?->modele ?: '')) ?: '-';
    $clients = $planning?->planningClients?->map(fn ($item) => $item->client?->full_name)->filter()->implode(', ');
    $formatTime = fn ($time) => $time
        ? (is_object($time) && method_exists($time, 'format') ? $time->format('H:i') : substr((string) $time, 0, 5))
        : '-';
@endphp

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
            <div class="doc-title">Roadsheet</div>
        </td>
    </tr>
</table>

<div class="red-rule"></div>

<div class="summary">
    <h1>{{ $roadSheet->voucher_number ?: $planning?->ref_dossier ?: 'Fiche de route' }}</h1>
    <div>
        Date: <strong>{{ $planning?->date_du?->format('d/m/Y') ?: '-' }}</strong>
        &nbsp; | &nbsp; Supplier: <strong>{{ $planning?->supplierVehicule?->name ?: $planning?->supplierClient?->name ?: '-' }}</strong>
        &nbsp; | &nbsp; Service: <strong>{{ $planning?->service?->designation ?: '-' }}</strong>
    </div>
</div>

<div class="section-title">Informations planning</div>
<table class="grid">
    <tr>
        <td><div class="field"><span class="label">Reference</span><span class="value">{{ $planning?->ref_dossier ?: '-' }}</span></div></td>
        <td><div class="field"><span class="label">Pax</span><span class="value">{{ $planning?->nbr_personnes ?: '-' }}</span></div></td>
    </tr>
    <tr>
        <td><div class="field"><span class="label">Départ</span><span class="value">{{ $roadSheet->start_city ?: $planning?->point_depart ?: '-' }}</span></div></td>
        <td><div class="field"><span class="label">Arrivée</span><span class="value">{{ $roadSheet->end_city ?: $planning?->destination?->name ?: '-' }}</span></div></td>
    </tr>
    <tr>
        <td><div class="field"><span class="label">Flight départ</span><span class="value">{{ $roadSheet->start_flight ?: '-' }}</span></div></td>
        <td><div class="field"><span class="label">Flight arrivée</span><span class="value">{{ $roadSheet->end_flight ?: '-' }}</span></div></td>
    </tr>
    <tr>
        <td><div class="field"><span class="label">Heure départ</span><span class="value">{{ $formatTime($roadSheet->start_time) }}</span></div></td>
        <td><div class="field"><span class="label">Heure arrivée</span><span class="value">{{ $formatTime($roadSheet->end_time) }}</span></div></td>
    </tr>
    <tr>
        <td><div class="field"><span class="label">Driver</span><span class="value">{{ $planning?->driver?->name ?: '-' }}</span></div></td>
        <td><div class="field"><span class="label">Véhicule</span><span class="value">{{ $vehicleLabel }}</span></div></td>
    </tr>
    <tr>
        <td><div class="field"><span class="label">Guide</span><span class="value">{{ $planning?->guide?->name ?: '-' }}</span></div></td>
        <td><div class="field"><span class="label">Passenger</span><span class="value">{{ $clients ?: '-' }}</span></div></td>
    </tr>
</table>

<div class="section-title">Lignes de route</div>
<table class="lines">
    <thead>
        <tr>
            <th>Date</th>
            <th>Départ KM</th>
            <th>Arrivée KM</th>
            <th>Distance</th>
            <th>Gasoil</th>
            <th>Jawaz</th>
            <th>Autres</th>
            <th>Notes</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($roadSheet->lines as $line)
            <tr>
                <td>{{ $line->date?->format('d/m/Y') ?: '-' }}</td>
                <td>{{ $line->departure_kms ?: '-' }}</td>
                <td>{{ $line->arrival_kms ?: '-' }}</td>
                <td>{{ $line->distance ?: '-' }}</td>
                <td>{{ $line->gasoline ? number_format((float) $line->gasoline, 2, ',', ' ') : '-' }}</td>
                <td>{{ $line->jawaz ? number_format((float) $line->jawaz, 2, ',', ' ') : '-' }}</td>
                <td>{{ $line->other_expenses ? number_format((float) $line->other_expenses, 2, ',', ' ') : '-' }}</td>
                <td>{{ $line->notes ?: '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="8">Aucune ligne renseignée.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<table class="signature-wrap">
    <tr>
        <td class="notes-cell">
            <div class="box">
                <span class="label">Notes</span>
                <span class="value">{{ $roadSheet->notes ?: 'Fiche de route générée automatiquement depuis MD Tours.' }}</span>
            </div>
        </td>
        <td class="signature-cell">
            <div class="box">
                <span class="label">Signature</span>
                <div class="signature-line">{{ $roadSheet->signature_name ?: 'MD Tours' }}</div>
            </div>
        </td>
    </tr>
</table>

<div class="footer">MD Tours transport touristique - Roadsheet générée automatiquement</div>
</body>
</html>
