<?php

use App\Services\PdfReadService;

beforeEach(function () {
    $this->service = app(PdfReadService::class);
});

// Helper to generate complete SEP text with custom dynamic parts
function makeSepText(array $replacements = []): string
{
    $defaults = [
        'sep_number' => '0069S0020126V000295',
        'sep_date' => '2026-01-19',
        'bpjs_number' => '0002138667153',
        'medical_record_number' => '238136',
        'patient_name' => 'PARSAULIAN SILALAHI',
        'jenis_rawatan' => 'R.Jalan',
        'patient_class' => 'KELAS 1',
    ];

    $data = array_merge($defaults, $replacements);

    // If a custom full raw text is provided, use that instead
    if (isset($data['raw_text'])) {
        return $data['raw_text'];
    }

    return "No.SEP : {$data['sep_number']}\n".
           "Tgl.SEP : {$data['sep_date']}\n".
           "No.Kartu : {$data['bpjs_number']} ( {$data['medical_record_number']} )\n".
           "Nama Peserta : {$data['patient_name']}\n".
           "Jns.Rawat : {$data['jenis_rawatan']}\n".
           "Kls.Rawat : {$data['patient_class']}";
}

// === Jenis Rawatan Tests ===

it('extracts R.Jalan correctly with normal format', function () {
    $text = makeSepText(['jenis_rawatan' => 'R.Jalan']);
    $result = $this->service->extractPdfAssist($text);
    expect($result['jenis_rawatan'])->toBe('RJ');
});

it('extracts R.Inap correctly with normal format', function () {
    $text = makeSepText(['jenis_rawatan' => 'R.Inap']);
    $result = $this->service->extractPdfAssist($text);
    expect($result['jenis_rawatan'])->toBe('RI');
});

it('extracts jenis rawatan with broken line breaks', function () {
    $text = "No.SEP : 0069S0020126V000295\n".
            "Tgl.SEP : 2026-01-19\n".
            "No.Kartu : 0002138667153 ( 238136 )\n".
            "Nama Peserta : PARSAULIAN SILALAHI\n".
            "Jns.Rawat\n:\nR.Jalan\n".
            "Kls.Rawat\n:\nKELAS 1";
    $result = $this->service->extractPdfAssist($text);
    expect($result['jenis_rawatan'])->toBe('RJ');
});

it('extracts jenis rawatan from multi-column layout', function () {
    $text = "No.SEP : 0069S0020126V000295\n".
            "Tgl.SEP : 2026-01-19\n".
            "No.Kartu : 0002138667153 ( 238136 )\n".
            "Nama Peserta : PARSAULIAN SILALAHI\n".
            "Jns.Rawat Jns.Kunjungan Kls.Hak Kls.Rawat Penjamin\n: R.Jalan :: KELAS 1 ::";
    $result = $this->service->extractPdfAssist($text);
    expect($result['jenis_rawatan'])->toBe('RJ');
});

it('extracts jenis rawatan from bottom of text', function () {
    $text = "No.SEP : 0069S0020126V000295\n".
            "Tgl.SEP : 2026-01-19\n".
            "No.Kartu : 0002138667153 ( 238136 )\n".
            "Nama Peserta : PARSAULIAN SILALAHI\n".
            "R.Inap\n".
            'KELAS 2';
    $result = $this->service->extractPdfAssist($text);
    expect($result['jenis_rawatan'])->toBe('RI');
});

it('throws exception when jenis rawatan not found', function () {
    $text = "No.SEP : 0069S0020126V000295\nTgl.SEP : 2026-01-19\nNama Peserta : JOHN DOE\nNo.Kartu : 0002138667153 ( 238136 )\nKelas Rawatan : KELAS 1";
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Jenis Rawatan (R.Jalan/R.Inap) tidak ditemukan.');
    $this->service->extractPdfAssist($text);
});

// === Kelas Rawatan Tests ===

it('extracts KELAS 1 correctly', function () {
    $text = makeSepText(['patient_class' => 'KELAS 1']);
    $result = $this->service->extractPdfAssist($text);
    expect($result['patient_class'])->toBe('1');
});

it('extracts KELAS 2 with broken lines', function () {
    $text = "No.SEP : 0069S0020126V000295\n".
            "Tgl.SEP : 2026-01-19\n".
            "No.Kartu : 0002138667153 ( 238136 )\n".
            "Nama Peserta : PARSAULIAN SILALAHI\n".
            "Kls.Rawat\n:\nKELAS 2\n".
            "Jns.Rawat\n:\nR.Inap";
    $result = $this->service->extractPdfAssist($text);
    expect($result['patient_class'])->toBe('2');
});

it('extracts kelas from multi-column layout', function () {
    $text = "No.SEP : 0069S0020126V000295\n".
            "Tgl.SEP : 2026-01-19\n".
            "No.Kartu : 0002138667153 ( 238136 )\n".
            "Nama Peserta : PARSAULIAN SILALAHI\n".
            "Jns.Rawat Jns.Kunjungan Kls.Hak Kls.Rawat Penjamin\n: R.Inap :: KELAS 3 ::";
    $result = $this->service->extractPdfAssist($text);
    expect($result['patient_class'])->toBe('3');
});

it('extracts kelas from bottom of text', function () {
    $text = "No.SEP : 0069S0020126V000295\n".
            "Tgl.SEP : 2026-01-19\n".
            "No.Kartu : 0002138667153 ( 238136 )\n".
            "Nama Peserta : PARSAULIAN SILALAHI\n".
            "R.Jalan\n".
            'KELAS 1';
    $result = $this->service->extractPdfAssist($text);
    expect($result['patient_class'])->toBe('1');
});

it('throws exception when kelas rawatan not found', function () {
    $text = "No.SEP : 0069S0020126V000295\nTgl.SEP : 2026-01-19\nNama Peserta : JOHN DOE\nNo.Kartu : 0002138667153 ( 238136 )\nJns.Rawat : R.Jalan";
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Kelas Rawatan (KELAS 1/2/3) tidak ditemukan.');
    $this->service->extractPdfAssist($text);
});

// === Kartu/MR Number Tests ===

it('extracts BPJS number and MR from No.Kartu line', function () {
    $text = makeSepText([
        'bpjs_number' => '0002138667153',
        'medical_record_number' => '238136',
    ]);
    $result = $this->service->extractPdfAssist($text);
    expect($result['bpjs_number'])->toBe('0002138667153');
    expect($result['medical_record_number'])->toBe('238136');
});

it('extracts BPJS number and MR with different spacing', function () {
    $text = "No.SEP : 0069S0020126V000295\n".
            "Tgl.SEP : 2026-01-19\n".
            "Nama Peserta : PARSAULIAN SILALAHI\n".
            "Jns.Rawat : R.Jalan\n".
            "Kls.Rawat : KELAS 1\n".
            'No.Kartu: 0002138667153(238136)';
    $result = $this->service->extractPdfAssist($text);
    expect($result['bpjs_number'])->toBe('0002138667153');
    expect($result['medical_record_number'])->toBe('238136');
});

it('extracts MR number with broken lines', function () {
    $text = "No.SEP : 0069S0020126V000295\n".
            "Tgl.SEP : 2026-01-19\n".
            "Nama Peserta : PARSAULIAN SILALAHI\n".
            "Jns.Rawat : R.Jalan\n".
            "Kls.Rawat : KELAS 1\n".
            "No.Kartu\n:\n0002138667153\n(\n238136\n)";
    $result = $this->service->extractPdfAssist($text);
    expect($result['medical_record_number'])->toBe('238136');
});

it('throws exception when BPJS number not found near No.Kartu', function () {
    $text = "No.SEP : 0069S0020126V000295\n".
            "Tgl.SEP : 2026-01-19\n".
            "Nama Peserta : PARSAULIAN SILALAHI\n".
            "Jns.Rawat : R.Jalan\n".
            "Kls.Rawat : KELAS 1\n".
            "No.Kartu : (238136)\n".str_repeat('a', 200).'1234567890123';
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('No. Kartu BPJS (13 digit) tidak ditemukan dekat label No.Kartu.');
    $this->service->extractPdfAssist($text);
});

it('throws exception when MR number not found in parentheses', function () {
    $text = "No.SEP : 0069S0020126V000295\n".
            "Tgl.SEP : 2026-01-19\n".
            "Nama Peserta : PARSAULIAN SILALAHI\n".
            "Jns.Rawat : R.Jalan\n".
            "Kls.Rawat : KELAS 1\n".
            'No.Kartu : 0002138667153 ( )';
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('No. RM (6 digit dalam kurung) tidak ditemukan.');
    $this->service->extractPdfAssist($text);
});

// === Full SEP Text Tests ===

it('extracts all fields from complete SEP text', function () {
    $text = <<<'TEXT'
Surat Eligibilitas Peserta

No.SEP
: 0069S0020126V000295

Tgl.SEP
: 2026-01-19

No.Kartu
: 0002138667153 ( 238136 )

Nama Peserta : PARSAULIAN SILALAHI

Jns.Rawat Jns.Kunjungan Kls.Hak Kls.Rawat Penjamin
: R.Jalan :: KELAS 1 ::
TEXT;

    $result = $this->service->extractPdfAssist($text);

    expect($result['sep_number'])->toBe('0069S0020126V000295');
    expect($result['sep_date'])->toBe('2026-01-19');
    expect($result['bpjs_number'])->toBe('0002138667153');
    expect($result['medical_record_number'])->toBe('238136');
    expect($result['patient_name'])->toBe('PARSAULIAN SILALAHI');
    expect($result['jenis_rawatan'])->toBe('RJ');
    expect($result['patient_class'])->toBe('1');
});

it('handles SEP text with different formats', function () {
    $text = <<<'TEXT'
Surat Eligibilitas Peserta

No.SEP: 0069S0020126V000295
Tgl.SEP: 2026-01-19
No.Kartu: 0002138667153 (238136)
Nama Peserta: PARSAULIAN SILALAHI

Jns.Rawat Jns.Kunjungan Kls.Hak Kls.Rawat Penjamin
: R.Inap :: KELAS 2 ::
TEXT;

    $result = $this->service->extractPdfAssist($text);

    expect($result['sep_number'])->toBe('0069S0020126V000295');
    expect($result['sep_date'])->toBe('2026-01-19');
    expect($result['bpjs_number'])->toBe('0002138667153');
    expect($result['medical_record_number'])->toBe('238136');
    expect($result['patient_name'])->toBe('PARSAULIAN SILALAHI');
    expect($result['jenis_rawatan'])->toBe('RI');
    expect($result['patient_class'])->toBe('2');
});

it('handles SEP text with values at bottom', function () {
    $text = <<<'TEXT'
Surat Eligibilitas Peserta

No.SEP
: 0069S0020126V000295

Tgl.SEP
: 2026-01-19

No.Kartu
: 0002138667153 ( 238136 )

Nama Peserta : PARSAULIAN SILALAHI

R.Jalan
KELAS 1
TEXT;

    $result = $this->service->extractPdfAssist($text);

    expect($result['jenis_rawatan'])->toBe('RJ');
    expect($result['patient_class'])->toBe('1');
});

// === Edge Case Tests ===

it('handles mixed case in jenis rawatan', function () {
    $text = makeSepText([
        'jenis_rawatan' => 'r.jalan',
    ]);
    $result = $this->service->extractPdfAssist($text);
    expect($result['jenis_rawatan'])->toBe('RJ');
});

it('handles spaces around jenis rawatan', function () {
    $text = makeSepText([
        'jenis_rawatan' => 'R.  Inap  ',
    ]);
    $result = $this->service->extractPdfAssist($text);
    expect($result['jenis_rawatan'])->toBe('RI');
});

it('handles kelas with different spacing', function () {
    $text = makeSepText([
        'patient_class' => 'KELAS  1',
    ]);
    $result = $this->service->extractPdfAssist($text);
    expect($result['patient_class'])->toBe('1');
});
