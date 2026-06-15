<?php

namespace App\Services;

use App\Models\MailMessage;
use App\Models\ReservationDraft;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ReservationDraftParser
{
    public function createOrUpdateFromMessage(MailMessage $message): ?ReservationDraft
    {
        $payload = $this->parse($message);

        if (!$payload) {
            return null;
        }

        $draft = ReservationDraft::where('mail_message_id', $message->id)->first();

        if ($draft && $draft->status !== 'pending') {
            return $draft;
        }

        return ReservationDraft::updateOrCreate(
            ['mail_message_id' => $message->id],
            [
                'mail_account_id' => $message->mail_account_id,
                'status' => 'pending',
                'confidence' => $payload['confidence'],
                'source_from' => $message->from_email,
                'source_subject' => $message->subject,
                'parsed_payload' => $payload['fields'],
                'validation_notes' => $payload['notes'],
            ]
        );
    }

    public function parse(MailMessage $message): ?array
    {
        $subject = $message->subject ?: '';
        $body = $this->cleanText($message->body_text ?: strip_tags($message->body_html ?: ''));
        $haystack = mb_strtolower($subject . "\n" . $body);

        if (!$this->looksLikeReservation($haystack)) {
            return null;
        }

        $fields = [
            'date_du' => $this->dateValue($body, ['star date', 'start date', 'date début', 'date de début', 'date']),
            'date_au' => $this->dateValue($body, ['end date', 'date fin', 'date de fin']),
            'ref_dossier' => $this->textValue($body, ['voucher number', 'voucher no', 'invoice reference', 'reference', 'référence', 'reservation']) ?: $this->referenceFromSubject($subject),
            'nbr_personnes' => $this->numberValue($body, ['number pax', 'pax', 'n/p', 'passengers']),
            'flight' => $this->textValue($body, ['flight', 'vol']) ?: $this->flightFromText($body . ' ' . $subject),
            'heure' => $this->timeValue($body, ['time', 'heure']),
            'point_depart' => $this->textValue($body, ['start point', 'pickup', 'pick up', 'departure', 'départ']),
            'site' => $this->textValue($body, ['city', 'location', 'lieu']),
            'destination_name' => $this->textValue($body, ['end point', 'destination', 'drop off', 'dropoff', 'arrival', 'arrivée']),
            'service_name' => $this->textValue($body, ['service type', 'service']) ?: $this->serviceFromText($body . ' ' . $subject),
            'passenger_names' => $this->textValue($body, ['passenger names', 'passenger', 'client', 'customer', 'name']),
            'supplier_client_name' => $message->from_name ?: $message->from_email,
            'budget' => $this->moneyValue($body, ['budget', 'amount', 'price', 'total']),
            'supplier_price' => $this->moneyValue($body, ['supplier price', 'cost', 'supplier']),
            'mail_message_id' => $message->id,
        ];

        if (!$fields['date_du']) {
            $fields['date_du'] = $this->firstDate($body);
        }

        if (!$fields['heure']) {
            $fields['heure'] = $this->firstTime($body);
        }

        $filled = collect($fields)
            ->except(['date_au', 'budget', 'supplier_price', 'mail_message_id'])
            ->filter(fn ($value) => filled($value))
            ->count();

        $confidence = min(95, 25 + ($filled * 8));
        $notes = [];

        foreach (['date_du' => 'Date', 'ref_dossier' => 'Référence', 'service_name' => 'Service'] as $key => $label) {
            if (blank($fields[$key])) {
                $notes[] = "{$label} non détecté automatiquement.";
            }
        }

        return [
            'confidence' => $confidence,
            'fields' => $fields,
            'notes' => implode("\n", $notes),
        ];
    }

    private function looksLikeReservation(string $text): bool
    {
        foreach (['reservation', 'réservation', 'booking', 'voucher', 'circuit', 'transfer', 'transfert', 'arrival', 'arrivée', 'departure', 'départ'] as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function cleanText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\r\n|\r/", "\n", $text);
        $text = preg_replace("/[ \t]+/", ' ', $text);

        return trim($text ?: '');
    }

    private function textValue(string $text, array $labels): ?string
    {
        foreach ($labels as $label) {
            $pattern = '/(?:^|\n)\s*' . preg_quote($label, '/') . '\s*[:\-]?\s*(.+?)(?=\n|$)/iu';

            if (preg_match($pattern, $text, $matches)) {
                $value = trim($matches[1]);

                if ($value !== '' && !Str::startsWith($value, ['-', ':'])) {
                    return mb_substr($value, 0, 255);
                }
            }
        }

        return null;
    }

    private function numberValue(string $text, array $labels): ?int
    {
        $value = $this->textValue($text, $labels);

        if ($value && preg_match('/\d+/', $value, $matches)) {
            return (int) $matches[0];
        }

        return null;
    }

    private function moneyValue(string $text, array $labels): ?float
    {
        $value = $this->textValue($text, $labels);

        if (!$value || !preg_match('/\d[\d\s.,]*/', $value, $matches)) {
            return null;
        }

        $amount = str_replace(' ', '', $matches[0]);
        $amount = str_replace(',', '.', $amount);

        return is_numeric($amount) ? (float) $amount : null;
    }

    private function dateValue(string $text, array $labels): ?string
    {
        $value = $this->textValue($text, $labels);

        return $value ? $this->parseDate($value) : null;
    }

    private function firstDate(string $text): ?string
    {
        if (preg_match('/\b(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}|\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2})\b/u', $text, $matches)) {
            return $this->parseDate($matches[1]);
        }

        return null;
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'Y/m/d', 'd/m/y', 'd-m-y', 'd M Y', 'd-M-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->toDateString();
            } catch (\Throwable) {
                //
            }
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function timeValue(string $text, array $labels): ?string
    {
        $value = $this->textValue($text, $labels);

        return $value ? $this->parseTime($value) : null;
    }

    private function firstTime(string $text): ?string
    {
        if (preg_match('/\b([01]?\d|2[0-3])[:h]([0-5]\d)\b/u', $text, $matches)) {
            return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
        }

        return null;
    }

    private function parseTime(string $value): ?string
    {
        if (preg_match('/\b([01]?\d|2[0-3])[:h]([0-5]\d)\b/u', $value, $matches)) {
            return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
        }

        return null;
    }

    private function referenceFromSubject(string $subject): ?string
    {
        if (preg_match('/(?:reservation|booking|voucher|ref(?:erence)?)[^\w]*(.+)$/iu', $subject, $matches)) {
            return mb_substr(trim($matches[1]), 0, 255);
        }

        if (preg_match('/\b([A-Z]{2,}[A-Z0-9\-]{4,}|\d{5,}(?:\s*-\s*[^,]+)?)\b/u', $subject, $matches)) {
            return mb_substr(trim($matches[1]), 0, 255);
        }

        return null;
    }

    private function flightFromText(string $text): ?string
    {
        if (preg_match('/\b([A-Z]{2}\s?\d{2,5}|[A-Z]{1,3}\d{3,5})\b/u', $text, $matches)) {
            return str_replace(' ', '', $matches[1]);
        }

        return null;
    }

    private function serviceFromText(string $text): ?string
    {
        if (preg_match('/\b(circuit\s+\d+\s+jours?|arrival|arrivée|departure|départ|transfer|transfert)\b/iu', $text, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
