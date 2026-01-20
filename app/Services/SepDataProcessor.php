<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BpjsClaim;

class SepDataProcessor
{
    /**
     * Validate that essential data was extracted from SEP document.
     *
     * @throws \RuntimeException if essential data is missing
     */
    public function validateExtractedData(array $data): void
    {
       
        $requiredFields = [
            'sep_number' => 'Nomor SEP',
            'patient_name' => 'Nama Pasien',
            // 'medical_record_number' => 'Nomor Rekam Medis',
            'bpjs_number' => 'Nomor Kartu BPJS',
        ];

        $missingFields = [];

        foreach ($requiredFields as $field => $label) {
            if (empty(trim($data[$field] ?? ''))) {
                $missingFields[] = $label;
            }
        }

        if (! empty($missingFields)) {
            throw new \RuntimeException(
                'Data SEP tidak lengkap. Field berikut tidak dapat dibaca: '.implode(', ', $missingFields).'. Pastikan file SEP yang diupload valid dan dapat dibaca.'
            );
        }
    }

    /**
     * Check if SEP number already exists in database.
     *
     * @throws \RuntimeException if SEP number is duplicate
     */
    public function checkDuplicateSepNumber(string $sepNumber): void
    {
        if (empty($sepNumber)) {
            return;
        }

        $existingClaim = BpjsClaim::where('no_sep', $sepNumber)->first();

        if ($existingClaim) {
            $tanggalRawatan = $existingClaim->tanggal_rawatan
                ? $existingClaim->tanggal_rawatan->format('d/m/Y')
                : '-';
            $jenisRawatan = $existingClaim->jenis_rawatan === 'RI' ? 'Rawat Inap' : 'Rawat Jalan';

            throw new \RuntimeException(
                "Nomor SEP {$sepNumber} sudah terdaftar sebelumnya. ".
                "Data klaim: {$existingClaim->nama_pasien} ({$jenisRawatan}) ".
                "tanggal {$tanggalRawatan}."
            );
        }
    }

    /**
     * Normalize patient class from "Kelas 1" format to numeric.
     */
    public function normalizePatientClass(string $patientClass): string
    {
        if (preg_match('/\d+/', $patientClass, $matches)) {
            return $matches[0];
        }

        return $patientClass;
    }

    /**
     * Prepare data for form filling.
     */
    public function prepareFormData(array $extractedData): array
    {
        return [
            'medical_record_number' => $extractedData['medical_record_number'] ?? '',
            'patient_name' => $extractedData['patient_name'] ?? '',
            'sep_number' => $extractedData['sep_number'] ?? '',
            'bpjs_number' => $extractedData['bpjs_number'] ?? '',
            'patient_class' => $this->normalizePatientClass($extractedData['patient_class'] ?? ''),
            'jenis_rawatan' => $extractedData['jenis_rawatan'] ?? 'RJ',
            'sep_date' => $extractedData['sep_date'] ?? null,
        ];
    }
}
