<?php

use App\Services\PdfReadService;
use Tests\TestCase;

beforeEach(function () {
    $this->service = app(PdfReadService::class);
});

// === Jenis Rawatan Tests ===

it('extracts R.Jalan correctly with normal format', function () {
    $text = "Jns.Rawat : R.Jalan\nKls.Rawat : KELAS 1\nNo.Kartu : 0002138667153 (238136)";
    $result = $this->service->extractPdfAssist($text);
    expect($result['jenis_rawatan'])->toBe('RJ');
});

it('extracts R.Inap correctly with normal format', function () {
    $text = "Jns.Rawat : R.Inap\nKls.Rawat : KELAS 2\nNo.Kartu : 0002138667153 (238136)";
    $result = $this->service->extractPdfAssist($text);
    expect($result['jenis_rawatan'])->toBe('RI');
});

it('extracts jenis rawatan with broken line breaks', function () {
    $text = "Jns.Rawat\n:\nR.Jalan\nKls.Rawat\n:\nKELAS 1\nNo.Kartu\n:\n0002138667153\n(\n238136\n)";
    $result = $this->service->extractPdfAssist($text);
    expect($result['jenis_rawatan'])->toBe('RJ');
});

it('extracts jenis rawatan from multi-column layout', function () {
    $text = "Jns.Rawat Jns.Kunjungan Kls.Hak Kls.Rawat Penjamin\n: R.Jalan :: KELAS 1 ::\nNo.Kartu : 0002138667153 (238136)";
    $result = $this->service->extractPdfAssist($text);
    expect($result['jenis_rawatan'])->toBe('RJ');
});

it('extracts jenis rawatan from bottom of text', function () {
    $text = "No.SEP : 0069S0020126V000295\nNama Peserta : JOHN DOE\nR.Inap\nKELAS 2\nNo.Kartu : 0002138667153 (238136)";
    $result = $this->service->extractPdfAssist($text);
    expect($result['jenis_rawatan'])->toBe('RI');
});

it('throws exception when jenis rawatan not found', function () {
    $text = "No.SEP : 0069S0020126V000295\nNama Peserta : JOHN DOE\nKelas Rawatan : KELAS 1";
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Jenis Rawatan (R.Jalan/R.Inap) tidak ditemukan.');
    $this->service->extractPdfAssist($text);
});

// === Kelas Rawatan Tests ===

it('extracts KELAS 1 correctly', function () {
    $text = "Kls.Rawat : KELAS 1\nJns.Rawat : R.Jalan";
    $result = $this->service->extractPdfAssist($text);
    expect($result['patient_class'])->toBe('1');
});

it('extracts KELAS 2 with broken lines', function () {
    $text = "Kls.Rawat\n:\nKELAS 2\nJns.Rawat\n:\nR.Inap";
    $result = $this->service->extractPdfAssist($text);
    expect($result['patient_class'])->toBe('2');
});

it('extracts kelas from multi-column layout', function () {
    $text = "Jns.Rawat Jns.Kunjungan Kls.Hak Kls.Rawat Penjamin\n: R.Inap :: KELAS 3 ::";
    $result = $this->service->extractPdfAssist($text);
    expect($result['patient_class'])->toBe('3');
});

it('extracts kelas from bottom of text', function () {
    $text = "No.SEP : 0069S0020126V000295\nNama Peserta : JOHN DOE\nR.Jalan\nKELAS 1";
    $result = $this->service->extractPdfAssist($text);
    expect($result['patient_class'])->toBe('1');
});

it('throws exception when kelas rawatan not found', function () {
    $text = "No.SEP : 0069S0020126V000295\nNama Peserta : JOHN DOE";
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Kelas Rawatan (KELAS 1/2/3) tidak ditemukan.');
    $this->service->extractPdfAssist($text);
});

// === Kartu/MR Number Tests ===

it('extracts BPJS number and MR from No.Kartu line', function () {
    $text = "No.Kartu : 0002138667153 ( 238136 )";
    $result = $this->service->extractPdfAssist($text);
    expect($result['bpjs_number'])->toBe('0002138667153');
    expect($result['medical_record_number'])->toBe('238136');
});

it('extracts BPJS number and MR with different spacing', function () {
    $text = "No.Kartu: 0002138667153(238136)";
    $result = $this->service->extractPdfAssist($text);
    expect($result['bpjs_number'])->toBe('0002138667153');
    expect($result['medical_record_number'])->toBe('238136');
});

it('extracts MR number with broken lines', function () {
    $text = "No.Kartu\n:\n0002138667153\n(\n238136\n)";
    $result = $this->service->extractPdfAssist($text);
    expect($result['medical_record_number'])->toBe('238136');
});

it('throws exception when BPJS number not found near No.Kartu', function () {
    $text = "No.Kartu : \nSome random text with 1234567890123 but not near No.Kartu";
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('No. Kartu BPJS (13 digit) tidak ditemukan dekat label No.Kartu.');
    $this->service->extractPdfAssist($text);
});

it('throws exception when MR number not found in parentheses', function () {
    $text = "No.Kartu : 0002138667153 ( )";
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('No. RM (6 digit dalam kurung) tidak ditemukan.');
    $this->service->extractPdfAssist($text);
});

// === Full SEP Text Tests ===

it('extracts all fields from complete SEP text', function () {
    $text = <<<TEXT
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
    $text = <<<TEXT
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
    $text = <<<TEXT
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
    $text = "r.jalan\nKELAS 1";
    $result = $this->service->extractPdfAssist($text);
    expect($result['jenis_rawatan'])->toBe('RJ');
});

it('handles spaces around jenis rawatan', function () {
    $text = "R.  Inap  \nKELAS 2";
    $result = $this->service->extractPdfAssist($text);
    expect($result['jenis_rawatan'])->toBe('RI');
});

it('handles kelas with different spacing', function () {
    $text = "R.Jalan\nKELAS  1";
    $result = $this->service->extractPdfAssist($text);
    expect($result['patient_class'])->toBe('1');
});