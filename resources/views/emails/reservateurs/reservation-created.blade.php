<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Nouvelle réservation réservateur</title>
</head>
<body style="margin:0;background:#f6f7fb;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <div style="max-width:760px;margin:0 auto;padding:24px;">
        <div style="background:#7f1d1d;color:#fff;border-radius:14px 14px 0 0;padding:22px;">
            <h1 style="margin:0;font-size:22px;color:#ffffff;">SI MD TOURS - Nouvelle reservation reservateur</h1>
            <p style="margin:8px 0 0;color:#fee2e2;">Reference : {{ $reservation->reference }}</p>
        </div>

        <div style="background:#fff;border:1px solid #e5e7eb;border-top:0;border-radius:0 0 14px 14px;padding:20px;">
            <h2 style="font-size:16px;margin:0 0 12px;">Réservateur</h2>
            <p style="margin:0 0 4px;"><strong>Nom :</strong> {{ $reservation->reservateur?->nom ?: '-' }}</p>
            <p style="margin:0 0 4px;"><strong>Référence :</strong> {{ $reservation->reservateur?->reference ?: '-' }}</p>
            <p style="margin:0 0 4px;"><strong>Téléphone :</strong> {{ $reservation->reservateur?->telephone ?: '-' }}</p>
            <p style="margin:0 0 18px;"><strong>Email :</strong> {{ $reservation->reservateur?->email ?: '-' }}</p>

            <h2 style="font-size:16px;margin:0 0 12px;">Détails réservation</h2>
            <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:14px;">
                <tbody>
                    <tr>
                        <td style="padding:9px;border:1px solid #e5e7eb;background:#f9fafb;font-weight:bold;">Service</td>
                        <td style="padding:9px;border:1px solid #e5e7eb;">{{ $reservation->service }}</td>
                    </tr>
                    <tr>
                        <td style="padding:9px;border:1px solid #e5e7eb;background:#f9fafb;font-weight:bold;">Départ</td>
                        <td style="padding:9px;border:1px solid #e5e7eb;">{{ $reservation->lieu_depart }}</td>
                    </tr>
                    <tr>
                        <td style="padding:9px;border:1px solid #e5e7eb;background:#f9fafb;font-weight:bold;">Arrivée</td>
                        <td style="padding:9px;border:1px solid #e5e7eb;">{{ $reservation->lieu_arrivee }}</td>
                    </tr>
                    <tr>
                        <td style="padding:9px;border:1px solid #e5e7eb;background:#f9fafb;font-weight:bold;">Date service</td>
                        <td style="padding:9px;border:1px solid #e5e7eb;">{{ optional($reservation->date_service)->format('d/m/Y') ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:9px;border:1px solid #e5e7eb;background:#f9fafb;font-weight:bold;">Heure souhaitée</td>
                        <td style="padding:9px;border:1px solid #e5e7eb;">{{ $reservation->heure_souhaitee ? $reservation->heure_souhaitee->format('H:i') : '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:9px;border:1px solid #e5e7eb;background:#f9fafb;font-weight:bold;">Personnes</td>
                        <td style="padding:9px;border:1px solid #e5e7eb;">{{ $reservation->nombre_personnes ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:9px;border:1px solid #e5e7eb;background:#f9fafb;font-weight:bold;">Contact</td>
                        <td style="padding:9px;border:1px solid #e5e7eb;">{{ $reservation->contact ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:9px;border:1px solid #e5e7eb;background:#f9fafb;font-weight:bold;">Informations</td>
                        <td style="padding:9px;border:1px solid #e5e7eb;">{{ $reservation->informations_complementaires ?: '-' }}</td>
                    </tr>
                </tbody>
            </table>

            <p style="margin:18px 0 0;color:#6b7280;font-size:12px;">
                Ce message est envoyé automatiquement depuis le portail réservateur MD TOURS.
            </p>
        </div>
    </div>
</body>
</html>
