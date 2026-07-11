<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Generate BonCMD</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 10px; }
        .sheet { border: 2px solid #111827; min-height: 276mm; padding: 8mm; position: relative; }
        .header { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .header td { vertical-align: middle; }
        .logo { width: 115px; }
        .title { text-align: right; color: #991b1f; font-family: DejaVu Serif, serif; font-size: 24px; font-weight: 900; letter-spacing: .03em; text-transform: uppercase; }
        .subtitle { text-align: right; color: #64748b; font-size: 10px; font-weight: 700; margin-top: 3px; }
        .red-rule { height: 4px; background: #c1121f; margin: 6px 0 10px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 10px; border: 1px dashed #f3a4ad; }
        .meta td { padding: 7px 8px; background: #fff5f6; border-right: 1px dashed #f3a4ad; }
        .meta td:last-child { border-right: none; }
        .label { display:block; color:#7f1d1d; font-size: 8px; font-weight: 900; text-transform: uppercase; letter-spacing: .08em; }
        .value { display:block; margin-top:3px; font-weight: 900; font-size: 11px; }
        table.lines { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .lines th { background: #f1f5f9; border: 1.5px solid #111827; padding: 9px 5px; text-align: center; font-size: 11px; font-weight: 900; }
        .lines td { border: 1.2px solid #111827; padding: 7px 6px; vertical-align: middle; font-weight: 700; }
        .lines tbody tr:nth-child(even) td { background: #fafafa; }
        .date-col { width: 10%; text-align:center; }
        .au-col { width: 21%; }
        .bus-col { width: 9%; text-align:center; }
        .service-col { width: 30%; text-align:center; }
        .price-col { width: 15%; text-align:right; white-space: nowrap; }
        .total-col { width: 15%; text-align:right; white-space: nowrap; font-weight: 900; }
        .total-row td { background:#fff1f2 !important; color:#7f1d1d; font-size: 12px; font-weight: 900; }
        .footer { position:absolute; left:8mm; right:8mm; bottom:6mm; display: table; width: calc(100% - 16mm); }
        .footer-left, .footer-right { display: table-cell; width: 50%; vertical-align: bottom; }
        .signature { border: 1px dashed #94a3b8; height: 30mm; text-align: center; padding-top: 19mm; font-family: DejaVu Serif, serif; font-weight: 900; }
        .small { color:#64748b; font-size:8px; font-weight:700; }
    </style>
</head>
<body>
@php
    $formatMoney = fn ($value) => number_format((float) $value, 2, ',', ' ');
    $period = trim(($dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : '-') . ' → ' . ($dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : '-'));
@endphp
<div class="sheet">
    <table class="header">
        <tr>
            <td style="width:40%;">
                @if ($logoDataUri)
                    <img class="logo" src="{{ $logoDataUri }}" alt="MD Tours">
                @else
                    <strong style="font-size:20px;color:#c1121f;">MD</strong> <strong style="font-size:20px;">TOURS</strong>
                @endif
            </td>
            <td style="width:60%;">
                <div class="title">Bon de commande</div>
                <div class="subtitle">Récapitulatif fournisseur véhicule</div>
            </td>
        </tr>
    </table>
    <div class="red-rule"></div>

    <table class="meta">
        <tr>
            <td style="width:38%;"><span class="label">Fournisseur</span><span class="value">{{ $supplier?->name ?: '-' }}</span></td>
            <td style="width:28%;"><span class="label">Période</span><span class="value">{{ $period }}</span></td>
            <td style="width:16%;"><span class="label">Services</span><span class="value">{{ $totals['count'] }}</span></td>
            <td style="width:18%;"><span class="label">Total TTC</span><span class="value">{{ $formatMoney($totals['total_price']) }} MAD</span></td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th class="date-col">Du</th>
                <th class="au-col">AU</th>
                <th class="bus-col">Bus</th>
                <th class="service-col">Services</th>
                <th class="price-col">Prix Unitaire TTC</th>
                <th class="total-col">Prix Total TTC</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td class="date-col">{{ $row['du'] }}</td>
                    <td class="au-col">{{ $row['au'] }}</td>
                    <td class="bus-col">{{ $row['bus'] }}</td>
                    <td class="service-col">{{ $row['service'] }}</td>
                    <td class="price-col">{{ $formatMoney($row['unit_price']) }}</td>
                    <td class="total-col">{{ $formatMoney($row['total_price']) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4">TOTAL</td>
                <td class="price-col">{{ $formatMoney($totals['unit_price']) }}</td>
                <td class="total-col">{{ $formatMoney($totals['total_price']) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <div class="footer-left small">Document généré le {{ $generatedAt->format('d/m/Y H:i') }} depuis MD Tours.</div>
        <div class="footer-right"><div class="signature">Signature / Cachet</div></div>
    </div>
</div>
</body>
</html>
