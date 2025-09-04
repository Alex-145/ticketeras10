<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use thiagoalessio\TesseractOCR\TesseractOCR;

class OcrService
{
    public function extract(string $absoluteImagePath): ?string
    {
        $tessPath = config('services.tesseract.path', 'tesseract');
        $langsStr = (string) config('services.tesseract.langs', 'eng+spa');
        $langs    = array_filter(explode('+', $langsStr));

        try {
            $version = @shell_exec('"' . $tessPath . '" --version');
            $hasWrapper = class_exists(TesseractOCR::class);

            Log::info('OcrService@info', [
                'path'    => $tessPath,
                'wrapper' => $hasWrapper,
                'version' => $version ? trim($version) : null,
            ]);

            $psmCandidates = [6, 4, 11, 3]; // reintentos si el texto es muy corto

            if ($hasWrapper) {
                foreach ($psmCandidates as $psm) {
                    $ocr = (new TesseractOCR($absoluteImagePath))
                        ->executable($tessPath)
                        ->oem(1)
                        ->psm($psm);

                    if (!empty($langs)) $ocr->lang(...$langs);

                    $text = $ocr->run();
                    if ($text && mb_strlen(trim($text)) >= 25) {
                        Log::info('OcrService@done', ['mode' => 'wrapper', 'psm' => $psm, 'len' => mb_strlen($text)]);
                        return $text;
                    }
                }

                // último intento: devuelve lo mejor conseguido aunque sea corto
                $ocr = (new TesseractOCR($absoluteImagePath))
                    ->executable($tessPath)
                    ->oem(1)
                    ->psm(6);
                if (!empty($langs)) $ocr->lang(...$langs);
                $text = $ocr->run();
                Log::info('OcrService@done', ['mode' => 'wrapper', 'psm' => 6, 'len' => $text ? mb_strlen($text) : 0]);
                return $text ?: null;
            }

            // Fallback CLI simple
            $tmpTxt = storage_path('app/' . uniqid('tess_', true));
            $cmd = '"' . $tessPath . '" "' . $absoluteImagePath . '" "' . $tmpTxt . '" -l "' . $langsStr . '" --oem 1 --psm 6 2>&1';
            $out = shell_exec($cmd);
            Log::debug('OcrService@cli_out', ['out' => $out]);

            $txtFile = $tmpTxt . '.txt';
            if (is_file($txtFile)) {
                $text = file_get_contents($txtFile) ?: null;
                @unlink($txtFile);
                Log::info('OcrService@done', ['mode' => 'cli', 'len' => $text ? mb_strlen($text) : 0]);
                return $text ?: null;
            }
        } catch (\Throwable $e) {
            Log::error('OcrService@error', ['msg' => $e->getMessage()]);
        }

        return null;
    }
}
