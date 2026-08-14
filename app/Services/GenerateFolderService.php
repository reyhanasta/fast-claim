<?php

namespace App\Services;

use Carbon\Carbon;

class GenerateFolderService
{
    /**
     * Generate output folder path for claim files
     *
     * @param  string  $sep_date  Tanggal SEP (format bebas, akan diparse Carbon)
     * @param  string  $jenis_rawatan  Jenis rawatan (default: RJ)
     * @return string Path folder relatif
     */
    public function generateOutputPath(string $sep_date, string $jenis_rawatan = 'RJ'): string
    {
        $date = Carbon::parse($sep_date);
        $month = $date->format('m').'_'.strtoupper($date->translatedFormat('F'));
        $year = $date->format('Y');

        $jenisRawatan = strtoupper($jenis_rawatan) === 'RI' ? 'R.INAP' : 'R.JALAN';

        return sprintf('%s/%s REGULER %s/%s/',
            $year, $month, $year, $jenisRawatan
        );
    }
}
