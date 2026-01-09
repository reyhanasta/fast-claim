<?php

namespace App\Services;

use Carbon\Carbon;

class GenerateFolderService
{
    /**
     * Generate output folder path for claim files
     *
     * @param  string  $sep_date  Tanggal SEP (format bebas, akan diparse Carbon)
     * @param  string  $sep_number  Nomor SEP
     * @param  string  $jenisRawatan  Jenis rawatan (default: RJ)
     * @return string Path folder relatif
     */
    public function generateOutputPath(string $sep_date, string $sep_number, string $jenis_rawatan = 'RJ'): string
    {
        $date = Carbon::parse($sep_date);
        $month = $date->format('m').'_'.strtoupper($date->translatedFormat('F'));
        $year = $date->format('Y');
        // $day = $date->format('d');

        $jenisRawatan = strtoupper($jenis_rawatan) === 'RI' ? 'R.INAP' : 'R.JALAN';

        // Sanitasi sep_number agar tidak menyebabkan error path
        $safeSepNumber = preg_replace('/[^A-Za-z0-9_\-]/', '_', $sep_number);

        return sprintf('%s/%s REGULER %s/%s/%s/',
            $year, $month, $year, $jenisRawatan, $safeSepNumber
        );
    }
}
