<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bon de commande {{ $commande->voucher_number }}</title>
    <style>
        @page { margin: 26px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 12px;
            line-height: 1.35;
        }
        .header {
            width: 100%;
            border-bottom: 4px solid #c1121f;
            padding-bottom: 14px;
            margin-bottom: 22px;
        }
        .logo {
            width: 150px;
            display: inline-block;
            vertical-align: middle;
        }
        .logo-text {
            font-size: 28px;
            font-weight: 900;
            letter-spacing: 1px;
            color: #111827;
        }
        .logo-text span { color: #c1121f; }
        .title {
            float: right;
            text-align: right;
            font-size: 24px;
            font-weight: 900;
            color: #991b1b;
            margin-top: 12px;
        }
        .meta-box {
            background: #fff5f5;
            border: 1px solid #fecaca;
            border-left: 7px solid #c1121f;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 18px;
        }
        .meta-title {
            font-size: 17px;
            font-weight: 900;
            color: #111827;
            margin-bottom: 4px;
        }
        .grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .grid td {
            width: 50%;
            vertical-align: top;
            padding: 0 7px 10px 0;
        }
        .field {
            border: 1px solid #e5e7eb;
            border-radius: 9px;
            padding: 10px 12px;
            min-height: 38px;
        }
        .label {
            display: block;
            color: #64748b;
            font-size: 9px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 4px;
        }
        .value {
            color: #111827;
            font-size: 13px;
            font-weight: 800;
            word-wrap: break-word;
        }
        .section-title {
            background: #111827;
            color: #ffffff;
            border-radius: 9px;
            padding: 9px 12px;
            font-weight: 900;
            margin: 14px 0 10px;
        }
        .price {
            color: #047857;
            font-size: 16px;
            font-weight: 900;
        }
        .signature-box {
            margin-top: 28px;
            width: 42%;
            float: right;
            height: 120px;
            border: 2px dashed #94a3b8;
            border-radius: 12px;
            padding: 14px;
        }
        .signature-line {
            border-top: 1px solid #111827;
            margin-top: 62px;
            padding-top: 8px;
            text-align: center;
            font-weight: 900;
        }
        .footer {
            position: fixed;
            bottom: -10px;
            left: 0;
            right: 0;
            text-align: center;
            color: #64748b;
            font-size: 9px;
        }
        .clearfix { clear: both; }
    </style>
</head>
<body>
    <div class="header">
        @if ($logoDataUri)
            <img class="logo" src="{{ $logoDataUri }}" alt="MD Tours">
        @else
            <div class="logo-text">MD <span>TOURS</span></div>
        @endif
        <div class="title">BON DE COMMANDE</div>
        <div class="clearfix"></div>
    </div>

    <div class="meta-box">
        <div class="meta-title">{{ $commande->supplier?->name ?: '-' }}</div>
        <div>
            Voucher: <strong>{{ $commande->voucher_number }}</strong>
            &nbsp; | &nbsp;
            Date: <strong>{{ $commande->date?->format('d/m/Y') ?: '-' }}</strong>
            &nbsp; | &nbsp;
            Reference: <strong>{{ $commande->reference ?: '-' }}</strong>
        </div>
    </div>

    <div class="section-title">Informations générales</div>
    <table class="grid">
        <tr>
            <td><div class="field"><span class="label">Supplier</span><span class="value">{{ $commande->supplier?->name ?: '-' }}</span></div></td>
            <td><div class="field"><span class="label">Voucher number</span><span class="value">{{ $commande->voucher_number }}</span></div></td>
        </tr>
        <tr>
            <td><div class="field"><span class="label">Start Date</span><span class="value">{{ $commande->start_date?->format('d/m/Y') ?: '-' }}</span></div></td>
            <td><div class="field"><span class="label">End Date</span><span class="value">{{ $commande->end_date?->format('d/m/Y') ?: '-' }}</span></div></td>
        </tr>
        <tr>
            <td><div class="field"><span class="label">Service Type</span><span class="value">{{ $commande->service?->designation ?: '-' }}</span></div></td>
            <td><div class="field"><span class="label">Supplier Price</span><span class="value price">{{ number_format((float) $commande->supplier_price, 2, ',', ' ') }} MAD</span></div></td>
        </tr>
    </table>

    <div class="section-title">Départ</div>
    <table class="grid">
        <tr>
            <td><div class="field"><span class="label">Start Point</span><span class="value">{{ $commande->start_point ?: '-' }}</span></div></td>
            <td><div class="field"><span class="label">Start Point Flight</span><span class="value">{{ $commande->start_point_flight ?: '-' }}</span></div></td>
        </tr>
        <tr>
            <td><div class="field"><span class="label">Start Point City</span><span class="value">{{ $commande->start_point_city ?: '-' }}</span></div></td>
            <td><div class="field"><span class="label">Start Point Time</span><span class="value">{{ $commande->start_point_time ?: '-' }}</span></div></td>
        </tr>
    </table>

    <div class="section-title">Arrivée</div>
    <table class="grid">
        <tr>
            <td><div class="field"><span class="label">End Point</span><span class="value">{{ $commande->end_point ?: '-' }}</span></div></td>
            <td><div class="field"><span class="label">End Point Flight</span><span class="value">{{ $commande->end_point_flight ?: '-' }}</span></div></td>
        </tr>
        <tr>
            <td><div class="field"><span class="label">End Point City</span><span class="value">{{ $commande->end_point_city ?: '-' }}</span></div></td>
            <td><div class="field"><span class="label">End Point Time</span><span class="value">{{ $commande->end_point_time ?: '-' }}</span></div></td>
        </tr>
    </table>

    <div class="section-title">Équipe et passagers</div>
    <table class="grid">
        <tr>
            <td><div class="field"><span class="label">MD Driver</span><span class="value">{{ $commande->driver?->name ?: '-' }}</span></div></td>
            <td><div class="field"><span class="label">MD Tours Vehicle</span><span class="value">{{ trim(($commande->vehicule?->matricule ?: '') . ' ' . ($commande->vehicule?->marque ?: '') . ' ' . ($commande->vehicule?->modele ?: '')) ?: '-' }}</span></div></td>
        </tr>
        <tr>
            <td><div class="field"><span class="label">Tour Guide</span><span class="value">{{ $commande->guide?->name ?: '-' }}</span></div></td>
            <td><div class="field"><span class="label">Number Pax</span><span class="value">{{ $commande->number_pax ?: '-' }}</span></div></td>
        </tr>
        <tr>
            <td colspan="2"><div class="field"><span class="label">Passenger</span><span class="value">{{ $commande->passenger ?: '-' }}</span></div></td>
        </tr>
    </table>

    <div class="signature-box">
        <strong>Signature</strong>
        <div class="signature-line">{{ $commande->signature ?: 'MD Tours' }}</div>
    </div>
    <div class="clearfix"></div>

    <div class="footer">MD Tours transport touristique - Bon de commande généré automatiquement</div>
</body>
</html>
