<?php

namespace App\Helpers;

class PdfReadHelper
{
    public static function extractPdf($pdf)
    {
        $pdf = new Pdf($pdf);
        return $pdf->text();
    }
}
