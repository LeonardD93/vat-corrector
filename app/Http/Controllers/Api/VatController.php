<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VatController extends Controller
{
    public function check(Request $request)
    {
        $request->validate([
            'vat' => 'required|string'
        ]);

        $vat = strtoupper(trim($request->input('vat')));
        $result = $this->checkSingle($vat);

        $statusCode = $result['status'] === 'invalid' ? 422 : 200;
        return response()->json($result, $statusCode);
    }


    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048' // max 2MB
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        // Legge la riga di intestazione
        $headers = fgetcsv($handle, 1000, ',');
        $headerMap = array_flip($headers);

        if (!isset($headerMap['id']) || !isset($headerMap['vat_number'])) {
            return response()->json([
                'error' => 'Error: the file must contain "id" and "vat_number" columns.'
            ], 400);
        }

        $valid = [];
        $corrected = [];
        $invalid = [];

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $id = trim($row[$headerMap['id']] ?? '');
            $vat = strtoupper(trim($row[$headerMap['vat_number']] ?? ''));

            if (!$vat) continue;

            $result = $this->checkSingle($vat);

            if ($result['status'] === 'valid') {
                $valid[] = ['id' => $id, 'vat' => $vat];
            } elseif ($result['status'] === 'corrected') {
                $corrected[] = [
                    'id' => $id,
                    'original' => $result['original'],
                    'corrected' => $result['corrected'],
                    'note' => $result['note']
                ];
            } else {
                $invalid[] = [
                    'id' => $id,
                    'original' => $vat,
                    'reason' => $result['reason']
                ];
            }
        }

        fclose($handle);

        $uuid = (string) Str::uuid();
        $dir = "vat_results/{$uuid}";
        Storage::makeDirectory($dir);

        $this->writeCsv("{$dir}/valid.csv", $valid, ['id', 'vat']);
        $this->writeCsv("{$dir}/corrected.csv", $corrected, ['id', 'original', 'corrected', 'note']);
        $this->writeCsv("{$dir}/invalid.csv", $invalid, ['id', 'original', 'reason']);

        return response()->json([
            'valid' => $valid,
            'corrected' => $corrected,
            'invalid' => $invalid,
            'uuid' => $uuid,
        ]);
    }

    public function download(Request $request, string $uuid, string $type): StreamedResponse
    {
        $allowed = ['valid', 'corrected', 'invalid'];
        if (!in_array($type, $allowed)) {
            abort(404, 'Invalid type');
        }

        $path = "vat_results/{$uuid}/{$type}.csv";

        if (!Storage::exists($path)) {
            abort(404, 'File not found');
        }

        return Storage::download($path, "{$type}_vat.csv");
    }

    private function checkSingle(string $vat): array
    {
        // Case 1: valid
        if (preg_match('/^IT\d{11}$/', $vat)) {
            return [
                'status' => 'valid',
                'vat' => $vat
            ];
        }

        // Case 2: missing  "IT"
        if (preg_match('/^\d{11}$/', $vat)) {
            return [
                'status' => 'corrected',
                'original' => $vat,
                'corrected' => 'IT' . $vat,
                'note' => 'Added missing "IT" prefix'
            ];
        }

        // Case 3: Not Valid
        return [
            'status' => 'invalid',
            'reason' => 'The VAT number must start with "IT" followed by 11 digits, or be 11 digits to auto-correct.'
        ];
    }


    private function writeCsv(string $path, array $data, array $headers)
    {
        $fullPath = Storage::path($path); // <-- FIX QUI
        $directory = dirname($fullPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true); // crea ricorsivamente
        }

        $handle = fopen($fullPath, 'w');
        fputcsv($handle, $headers);

        foreach ($data as $row) {
            $line = [];
            foreach ($headers as $key) {
                $line[] = $row[$key] ?? '';
            }
            fputcsv($handle, $line);
        }

        fclose($handle);
    }
}
