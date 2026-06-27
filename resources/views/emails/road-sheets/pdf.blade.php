<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Roadsheet {{ $roadSheet->voucher_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    @php
        $planning = $roadSheet->planning;
        $recipientName = $planning?->supplierVehicule?->name ?: $planning?->supplierClient?->name;
    @endphp

    <p>Bonjour {{ $recipientName ?: 'Madame, Monsieur' }},</p>

    <p>
        Veuillez trouver ci-joint la fiche de route
        <strong>{{ $roadSheet->voucher_number ?: $planning?->ref_dossier }}</strong>.
    </p>

    <p>
        Référence: <strong>{{ $planning?->ref_dossier ?: '-' }}</strong><br>
        Date: <strong>{{ $planning?->date_du?->format('d/m/Y') ?: '-' }}</strong>
    </p>

    <p>Cordialement,<br><strong>MD Tours</strong></p>
</body>
</html>
