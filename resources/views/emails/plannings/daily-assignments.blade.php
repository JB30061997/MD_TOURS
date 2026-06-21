<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Planning MD TOURS</title>
</head>
<body style="margin:0;background:#f6f7fb;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <div style="max-width:860px;margin:0 auto;padding:24px;">
        <div style="background:#7f1d1d;color:#fff;border-radius:14px 14px 0 0;padding:22px;">
            <h1 style="margin:0;font-size:22px;color:#ffffff;">SI MD TOURS - Planning du {{ $planningDate->format('d/m/Y') }}</h1>
            <p style="margin:8px 0 0;color:#fee2e2;">Bonjour {{ $recipientName }}, voici vos missions affectées.</p>
        </div>

        <div style="background:#fff;border:1px solid #e5e7eb;border-top:0;border-radius:0 0 14px 14px;padding:20px;">
            <p style="margin-top:0;">
                Type de destinataire :
                <strong>{{ $recipientType === 'driver' ? 'Driver' : 'Supplier Vehicle' }}</strong>
            </p>

            <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:#f9fafb;color:#374151;">
                        <th align="left" style="padding:10px;border:1px solid #e5e7eb;">Référence</th>
                        <th align="left" style="padding:10px;border:1px solid #e5e7eb;">Date</th>
                        <th align="left" style="padding:10px;border:1px solid #e5e7eb;">Heure</th>
                        <th align="left" style="padding:10px;border:1px solid #e5e7eb;">Service</th>
                        <th align="left" style="padding:10px;border:1px solid #e5e7eb;">Départ</th>
                        <th align="left" style="padding:10px;border:1px solid #e5e7eb;">Destination</th>
                        <th align="left" style="padding:10px;border:1px solid #e5e7eb;">Pax</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($plannings as $planning)
                        <tr>
                            <td style="padding:10px;border:1px solid #e5e7eb;font-weight:bold;">
                                {{ $planning->ref_dossier ?: '-' }}
                            </td>
                            <td style="padding:10px;border:1px solid #e5e7eb;">
                                {{ optional($planning->date_du)->format('d/m/Y') ?: '-' }}
                                @if ($planning->date_au)
                                    - {{ $planning->date_au->format('d/m/Y') }}
                                @endif
                            </td>
                            <td style="padding:10px;border:1px solid #e5e7eb;">
                                {{ $planning->heure ? $planning->heure->format('H:i') : '-' }}
                            </td>
                            <td style="padding:10px;border:1px solid #e5e7eb;">
                                {{ $planning->service?->designation ?: '-' }}
                            </td>
                            <td style="padding:10px;border:1px solid #e5e7eb;">
                                {{ $planning->point_depart ?: '-' }}
                            </td>
                            <td style="padding:10px;border:1px solid #e5e7eb;">
                                {{ $planning->destination?->name ?: ($planning->site ?: '-') }}
                            </td>
                            <td style="padding:10px;border:1px solid #e5e7eb;">
                                {{ $planning->nbr_personnes ?: '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p style="margin:18px 0 0;color:#6b7280;font-size:12px;">
                Ce message est envoyé automatiquement par MD TOURS.
            </p>
        </div>
    </div>
</body>
</html>
