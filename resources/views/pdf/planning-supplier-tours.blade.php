<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>

    <style>
        @page {
            margin: 18px 20px 24px 20px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 8px;
            background: #ffffff;
        }

        .page {
            page-break-after: always;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .header {
            background: #0f172a;
            color: #ffffff;
            border-radius: 12px;
            padding: 16px 20px;
            border-bottom: 6px solid #dc2626;
            margin-bottom: 14px;
        }

        .logo {
            font-size: 28px;
            font-weight: 900;
            letter-spacing: 2px;
            color: #ffffff;
        }

        .logo-red {
            color: #ef4444;
        }

        .report-title {
            margin-top: 6px;
            font-size: 11px;
            color: #fecaca;
            font-weight: 800;
        }

        .meta {
            margin-top: 5px;
            font-size: 8px;
            color: #cbd5e1;
            font-weight: 700;
        }

        .supplier-box {
            border: 1px solid #fecaca;
            border-left: 8px solid #dc2626;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 12px;
            background: #fff7f7;
        }

        .supplier-name {
            font-size: 18px;
            font-weight: 900;
            color: #991b1b;
            margin-bottom: 8px;
        }

        .pill {
            display: inline-block;
            border: 1px solid #fecaca;
            border-radius: 20px;
            padding: 5px 10px;
            margin-right: 5px;
            background: #ffffff;
            color: #334155;
            font-weight: 900;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #cbd5e1;
        }

        th {
            background: #be123c;
            color: #ffffff;
            padding: 8px 4px;
            border: 1px solid #9f1239;
            font-size: 7px;
            font-weight: 900;
            text-transform: uppercase;
        }

        td {
            padding: 7px 5px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
            font-size: 7.3px;
            line-height: 1.35;
            word-wrap: break-word;
        }

        tr:nth-child(even) td {
            background: #fff7f7;
        }

        .ref {
            font-weight: 900;
            color: #0f172a;
        }

        .type {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            border-radius: 14px;
            padding: 4px 6px;
            font-weight: 900;
        }

        .clients {
            font-size: 7.2px;
            font-weight: 800;
            line-height: 1.45;
        }

        .money {
            color: #15803d;
            font-weight: 900;
        }

        .total-row td {
            background: #0f172a !important;
            color: #ffffff;
            font-weight: 900;
            padding: 9px 6px;
        }

        .signature-zone {
            margin-top: 28px;
            width: 100%;
            page-break-inside: avoid;
        }

        .signature-card {
            width: 36%;
            float: right;
            height: 105px;
            border: 2px dashed #94a3b8;
            border-radius: 14px;
            padding: 14px;
            background: #f8fafc;
        }

        .signature-title {
            font-size: 10px;
            font-weight: 900;
            color: #991b1b;
            margin-bottom: 48px;
        }

        tr {
            page-break-inside: avoid;
        }

        .signature-line {
            border-top: 1px solid #111827;
            padding-top: 7px;
            text-align: center;
            font-weight: 900;
            color: #334155;
        }

        .footer {
            position: fixed;
            bottom: -16px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7px;
            color: #64748b;
            font-weight: 700;
        }

        .clearfix {
            clear: both;
        }
    </style>
</head>

<body>
    @foreach ($groups as $supplierName => $rows)
        <div class="page">
            <div class="header">
                <div class="logo">MD <span class="logo-red">TOURS</span></div>
                <div class="report-title">
                    {{ $title }} | Period: {{ $dateFrom->format('d/m/Y') }} - {{ $dateTo->format('d/m/Y') }}
                </div>
                <div class="meta">
                    Professional supplier tour report • Generated on {{ $generatedAt->format('d/m/Y H:i') }}
                </div>
            </div>

            <div class="supplier-box">
                <div class="supplier-name">{{ $supplierName }}</div>
                <span class="pill">Tours: {{ $rows->count() }}</span>
                <span class="pill">Budget: {{ number_format($rows->sum('budget'), 2) }}</span>
                <span class="pill">Supplier Price: {{ number_format($rows->sum('supplier_price'), 2) }}</span>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 6%;">Start</th>
                        <th style="width: 6%;">End</th>
                        <th style="width: 10%;">Ref</th>
                        <th style="width: 4%;">PAX</th>
                        <th style="width: 8%;">Type</th>
                        <th style="width: 6%;">Flight</th>
                        <th style="width: 5%;">Time</th>
                        <th style="width: 8%;">Start Point</th>
                        <th style="width: 8%;">End Point</th>
                        <th style="width: 5%;">Loc</th>
                        <th style="width: 8%;">MD Driver</th>
                        <th style="width: 16%;">Clients</th>
                        <th style="width: 5%;">Budget</th>
                        <th style="width: 5%;">Price</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($rows as $planning)
                        <tr>
                            <td>{{ $planning->date_du ? \Carbon\Carbon::parse($planning->date_du)->format('d/m/Y') : '-' }}
                            </td>
                            <td>{{ $planning->date_au ? \Carbon\Carbon::parse($planning->date_au)->format('d/m/Y') : '-' }}
                            </td>
                            <td class="ref">{{ $planning->ref_dossier ?: '-' }}</td>
                            <td>{{ $planning->nbr_personnes ?: '-' }}</td>
                            <td><span class="type">{{ optional($planning->service)->designation ?: '-' }}</span></td>
                            <td>{{ $planning->flight ?: '-' }}</td>
                            <td>{{ $planning->heure ? substr($planning->heure, 0, 5) : '-' }}</td>
                            <td>{{ $planning->point_depart ?: '-' }}</td>
                            <td>{{ optional($planning->destination)->name ?: '-' }}</td>
                            <td>{{ $planning->site ?: '-' }}</td>

                            <td>
                                @if ($type === 'client')
                                    {{ optional($planning->supplierVehicule)->name ?: '-' }}
                                @else
                                    {{ optional($planning->supplierClient)->name ?: '-' }}
                                @endif
                            </td>

                            <td class="clients">
                                @forelse($planning->planningClients as $clientRel)
                                    {{ optional($clientRel->client)->full_name }}@if (!$loop->last)
                                        <br>
                                    @endif
                                    @empty
                                        -
                                    @endforelse
                                </td>

                                <td class="money">{{ $planning->budget ? number_format($planning->budget, 2) : '-' }}</td>
                                <td class="money">
                                    {{ $planning->supplier_price ? number_format($planning->supplier_price, 2) : '-' }}
                                </td>
                            </tr>
                        @endforeach

                        <tr class="total-row">
                            <td colspan="12">TOTAL</td>
                            <td>{{ number_format($rows->sum('budget'), 2) }}</td>
                            <td>{{ number_format($rows->sum('supplier_price'), 2) }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="signature-zone">
                    <div class="signature-card">
                        <div class="signature-title">Responsible Signature</div>
                        <div class="signature-line">Name, signature and stamp</div>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
        @endforeach

        <div class="footer">
            MD TOURS • Confidential supplier tour report
        </div>
    </body>

    </html>
