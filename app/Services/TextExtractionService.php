<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class TextExtractionService
{
    public function extractAll(string $raw): array
    {
        $raw = $this->preClean($raw);

        // 1) Nombre / alias
        $name = $this->extractName($raw);

        // 2) Teléfono (sirve como último recurso de matching)
        $phone = $this->extractPhone($raw);

        // 3) Detalle limpio (sin alias/phone/timestamps/bloques citados)
        $detail = $this->makeDetailFromText($raw, $name, $phone);

        Log::info('TextExtractionService@extracted', [
            'has_name'   => (bool) $name,
            'has_phone'  => (bool) $phone,
            'detail_len' => $detail ? mb_strlen($detail) : 0,
        ]);

        return [
            'name'   => $name ?: null,
            'phone'  => $phone ?: null,
            'detail' => $detail,
        ];
    }

    /* =========================
     *  Limpieza inicial
     * ========================= */
    private function preClean(string $t): string
    {
        // Invisibles (ZWSP, BOM, etc.)
        $t = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $t);
        // Normaliza guiones / rayas
        $t = str_replace(["—", "–"], "-", $t);
        // Normaliza saltos de línea
        $t = preg_replace('/\r\n?/', "\n", $t);
        // Colapsa espacios (no toca saltos)
        $t = preg_replace('/[ \t]+/u', ' ', $t);
        return trim($t);
    }

    /* =========================
     *  Nombre / Alias
     * ========================= */
    private function extractName(string $text): ?string
    {
        // A) Encabezado exportado: "04/09/2025, 09:12 - Fernando Marin: ..."
        if (preg_match(
            '/^[\d\/\-\.:,\sAPMapm]+-\s*([A-ZÁÉÍÓÚÑ][\p{L}\.]+(?:\s+[A-ZÁÉÍÓÚÑ][\p{L}\.]+){0,4})\s*:/mu',
            $text,
            $m
        )) {
            return $this->cleanName($m[1]);
        }

        // B) Línea de alias tipo WhatsApp: "~R~."  ó  "~ Nombre ~."
        //    Capturamos lo que está entre tildes como alias.
        if (preg_match('/^\s*~\s*([^~\n]{1,60})\s*~\s*\.?\s*$/mu', $text, $m)) {
            return $this->cleanName($m[1]);
        }

        // C) Honoríficos: Ing./Ingeniero/Sr./Sra./Dr./Lic. + Nombre
        if (preg_match(
            '/\b(Ing\.?|Ingeniero|Sr\.?|Sra\.?|Dr\.?|Lic\.?)\s+([A-ZÁÉÍÓÚÑ][^\n]{2,60})$/mu',
            $text,
            $m
        )) {
            return $this->cleanName(trim($m[1] . ' ' . $m[2]));
        }

        // D) Patrones explícitos tipo "De: Nombre", "From: Nombre", "Applicant: Nombre"
        $labels = [
            '/\bApplicant(?:\s*Name)?\s*[:\-]\s*([A-Za-zÁÉÍÓÚÑáéíóúñ\.\s]{3,})$/mu',
            '/\bDe\s*[:\-]\s*([A-Za-zÁÉÍÓÚÑáéíóúñ\.\s]{3,})$/mu',
            '/\bFrom\s*[:\-]\s*([A-Za-zÁÉÍÓÚÑáéíóúñ\.\s]{3,})$/mu',
        ];
        foreach ($labels as $rx) {
            if (preg_match($rx, $text, $m)) {
                return $this->cleanName($m[1]);
            }
        }

        // E) Primeras líneas con “nombre humano”: 2–4 palabras Capitalizadas
        $lines = $this->firstNonEmptyLines($text, 8);
        foreach ($lines as $ln) {
            $lnT = trim($ln);
            // "Nombre Apellido" o "Nombre Apellido Apellido"
            if (preg_match('/^([A-ZÁÉÍÓÚÑ][\p{L}\.]+(?:\s+[A-ZÁÉÍÓÚÑ][\p{L}\.]+){1,3})$/u', $lnT, $m)) {
                return $this->cleanName($m[1]);
            }
            // "Nombre:" (azul de WhatsApp suele terminar con ":")
            if (preg_match('/^\s*([A-ZÁÉÍÓÚÑ][\p{L}\.]+(?:\s+[A-ZÁÉÍÓÚÑ][\p{L}\.]+){0,3})\s*:\s*$/u', $lnT, $m2)) {
                return $this->cleanName($m2[1]);
            }
        }

        return null;
    }

    private function firstNonEmptyLines(string $text, int $limit = 5): array
    {
        $lines = array_values(array_filter(preg_split('/\n/u', $text), fn($l) => trim($l) !== ''));
        return array_slice($lines, 0, $limit);
    }

    private function cleanName(string $s): string
    {
        // corta si es exagerado
        $s = mb_substr(trim(preg_replace('/\s{2,}/u', ' ', $s)), 0, 80);
        // remueve posible ":" final
        $s = preg_replace('/\s*:\s*$/', '', $s);
        // capitaliza bien "Ingeniero" → "Ingeniero", no tocar siglas
        return $s;
    }

    /* =========================
     *  Teléfono (+51, espacios, guiones, paréntesis)
     * ========================= */
    private function extractPhone(string $text): ?string
    {
        // Prioriza móvil peruano (9 dígitos) con o sin +51
        if (preg_match('/(?:\+?51[\s\-\.]?)?\(?9\)?(?:[\s\-\.]?\d){8}\b/u', $text, $m)) {
            return trim($m[0]);
        }
        // Genérico 8–12 dígitos con separadores
        if (preg_match('/(\+?\d{1,4})?[\s\-\.\(]?\d(?:[\s\-\.\)]?\d){7,11}\b/u', $text, $m2)) {
            return trim($m2[0]);
        }
        return null;
    }

    /* =========================
     *  Detalle: limpia citas, horas y cabeceras
     * ========================= */
    private function makeDetailFromText(string $text, ?string $name, ?string $phone): ?string
    {
        $text = $this->removeHeaders($text);
        $text = $this->removeQuotedBlocks($text);     // quita bloques iniciados por ~alias~. y teléfonos “de cabecera”
        $text = $this->removeTimestamps($text);       // “15:34”, “Editado 09:36”, etc.

        // Evita repetir nombre/teléfono al inicio del cuerpo
        if ($name)  $text = $this->removeFirstOccurrence($text, $name);
        if ($phone) $text = $this->removeFirstOccurrence($text, $phone);

        // Si queda “Nombre: mensaje”, quita la parte de la izquierda
        $text = preg_replace('/^[^\n]{0,80}:\s*/u', '', $text, 1);

        // Limpieza suave final
        $text = preg_replace('/[ \t]+/u', ' ', $text);
        $text = preg_replace('/\n{3,}/u', "\n\n", $text);
        $text = trim($text);

        // Evita que un filename corto sea el “detalle”
        if ($text !== '' && preg_match('/\.(png|jpe?g|webp|gif)$/i', $text) && mb_strlen($text) < 40) {
            $text = '';
        }

        if ($text === '' || mb_strlen($text) < 10) {
            return null;
        }
        if (mb_strlen($text) > 2000) {
            $text = mb_substr($text, 0, 2000) . '…';
        }

        return $text;
    }

    private function removeHeaders(string $text): string
    {
        // Borra “From/De/Asunto/Subject: …” al comienzo de líneas
        $lines = preg_split('/\n/u', $text);
        $clean = [];
        foreach ($lines as $ln) {
            $t = trim($ln);
            if ($t === '') continue;
            if (preg_match('/^(From|Para|De|Asunto|Subject)\s*[:\-]/iu', $t)) {
                continue;
            }
            $clean[] = $ln;
        }
        return implode("\n", $clean);
    }

    private function removeQuotedBlocks(string $text): string
    {
        $lines = preg_split('/\n/u', $text);
        $out = [];
        $skip = false;
        $skippedOnce = false;

        // Patrón de teléfono “de cabecera” (línea casi solo con el número)
        $rxHeaderPhone = '/^\s*(?:\+?\d{1,4})?[\s\-\.\(]*\d(?:[\s\-\.\)]?\d){7,11}\s*$/u';

        foreach ($lines as $i => $ln) {
            $trim = trim($ln);

            // Inicio de bloque citado (gris): "~alias~." y variantes
            if (preg_match('/^~\s*[^~]{1,60}\s*~\s*\.?\s*$/u', $trim)) {
                $skip = true;
                $skippedOnce = true;
                continue;
            }

            // Si estamos saltando, podríamos ver inmediatamente el teléfono de cabecera
            if ($skip) {
                if ($trim === '' || preg_match($rxHeaderPhone, $trim)) {
                    // seguimos saltando estas cabeceras
                    continue;
                }
                // fin del bloque citado al encontrar una línea “normal”
                $skip = false;
            }

            // También ignorar líneas tipo quote “> …”
            if (preg_match('/^\s*>\s*/', $trim)) {
                continue;
            }

            // Si una línea es SOLO hora o “Editado …”, no aporta al detalle
            if ($this->isJustTimeOrEdited($trim)) {
                continue;
            }

            $out[] = $ln;
        }

        return implode("\n", $out);
    }

    private function removeTimestamps(string $text): string
    {
        $lines = preg_split('/\n/u', $text);
        foreach ($lines as &$ln) {
            // Quita “ … 15:34” al final de línea
            $ln = preg_replace('/\s*(Editado\s*)?\b\d{1,2}:\d{2}\b\s*$/u', '', $ln);
        }
        return trim(implode("\n", $lines));
    }

    private function isJustTimeOrEdited(string $t): bool
    {
        return (bool) preg_match('/^(Editado\s*)?\b\d{1,2}:\d{2}\b$/u', $t);
    }

    private function removeFirstOccurrence(string $text, string $needle): string
    {
        $q = preg_quote($needle, '/');
        return preg_replace('/' . $q . '/u', '', $text, 1);
    }
}
