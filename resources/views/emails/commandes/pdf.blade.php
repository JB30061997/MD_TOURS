<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bon de commande {{ $commande->voucher_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <p>Bonjour {{ $commande->supplierVehicule?->name ?: $commande->supplierClient?->name }},</p>

    <p>
        Veuillez trouver ci-joint le bon de commande
        <strong>{{ $commande->voucher_number }}</strong>.
    </p>

    <p>
        Référence: <strong>{{ $commande->reference ?: '-' }}</strong><br>
        Date: <strong>{{ $commande->date?->format('d/m/Y') ?: '-' }}</strong>
    </p>

    <p>Cordialement,<br><strong>MD Tours</strong></p>
</body>
</html>
