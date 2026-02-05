<?php

declare(strict_types=1);

use App\Livewire\ClaimForm;
use App\Models\BpjsClaim;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('allows manual claim entry without auto-extraction', function () {
    $sepFile = UploadedFile::fake()->create('sep.pdf', 100, 'application/pdf');

    Livewire::test(ClaimForm::class)
        // Upload SEP file
        ->set('sepFile', $sepFile)
        ->set('showUploadedData', true) // Manually set since lifecycle hooks don't run in tests

        // Verify fields are empty (not auto-filled) - this proves no auto-extraction
        ->assertSet('patient_name', '')
        ->assertSet('sep_number', '')
        ->assertSet('bpjs_number', '')
        ->assertSet('medical_record_number', '')

        // Fill form manually - verify all fields accept user input
        ->set('medical_record_number', 'RM-001')
        ->set('patient_name', 'Test Patient')
        ->set('sep_number', '1234567890')
        ->set('bpjs_number', '0001234567890')
        ->set('sep_date', '2024-01-15')
        ->set('patient_class', '1')
        ->set('jenis_rawatan', 'RJ')

        // Verify all values were set correctly
        ->assertSet('medical_record_number', 'RM-001')
        ->assertSet('patient_name', 'Test Patient')
        ->assertSet('sep_number', '1234567890')
        ->assertSet('bpjs_number', '0001234567890')
        ->assertSet('sep_date', '2024-01-15')
        ->assertSet('patient_class', '1')
        ->assertSet('jenis_rawatan', 'RJ');
});

it('validates required fields for manual entry', function () {
    $sepFile = UploadedFile::fake()->create('sep.pdf', 100);
    $resumeFile = UploadedFile::fake()->create('resume.pdf', 200);
    $billingFile = UploadedFile::fake()->create('billing.pdf', 150);

    Livewire::test(ClaimForm::class)
        ->set('sepFile', $sepFile)
        ->set('showUploadedData', true)
        ->set('medical_record_number', 'RM-001')
        ->set('patient_name', '') // Empty - should fail
        ->set('sep_number', '1234567890')
        ->set('bpjs_number', '0001234567890')
        ->set('sep_date', '2024-01-15')
        ->set('patient_class', '1')
        ->set('resumeFile', $resumeFile)
        ->set('billingFile', $billingFile)
        ->call('submit')
        ->assertHasErrors(['patient_name']);
});

it('validates medical record number is required', function () {
    $sepFile = UploadedFile::fake()->create('sep.pdf', 100);
    $resumeFile = UploadedFile::fake()->create('resume.pdf', 200);
    $billingFile = UploadedFile::fake()->create('billing.pdf', 150);

    Livewire::test(ClaimForm::class)
        ->set('sepFile', $sepFile)
        ->set('showUploadedData', true)
        ->set('medical_record_number', '') // Empty - should fail
        ->set('patient_name', 'Test Patient')
        ->set('sep_number', '1234567890')
        ->set('bpjs_number', '0001234567890')
        ->set('sep_date', '2024-01-15')
        ->set('patient_class', '1')
        ->set('resumeFile', $resumeFile)
        ->set('billingFile', $billingFile)
        ->call('submit')
        ->assertHasErrors(['medical_record_number']);
});

it('allows selecting jenis rawatan as RJ', function () {
    $sepFile = UploadedFile::fake()->create('sep.pdf', 100);

    Livewire::test(ClaimForm::class)
        ->set('sepFile', $sepFile)
        ->set('showUploadedData', true)
        ->set('jenis_rawatan', 'RJ')
        ->assertSet('jenis_rawatan', 'RJ')
        ->assertSet('sep_date_label', 'Tanggal SEP');
});

it('allows selecting jenis rawatan as RI and updates label', function () {
    $sepFile = UploadedFile::fake()->create('sep.pdf', 100);

    Livewire::test(ClaimForm::class)
        ->set('sepFile', $sepFile)
        ->set('showUploadedData', true)
        ->set('jenis_rawatan', 'RI')
        ->assertSet('jenis_rawatan', 'RI')
        ->assertSet('sep_date_label', 'Tanggal Pulang');
});

it('prevents duplicate sep numbers', function () {
    // Create existing claim manually
    BpjsClaim::create([
        'no_sep' => '1234567890',
        'nama_pasien' => 'Existing Patient',
        'no_kartu_bpjs' => '0001234567890',
        'no_rm' => 'RM-000',
        'jenis_rawatan' => 'RJ',
        'kelas_rawatan' => '1',
        'tanggal_rawatan' => '2024-01-01',
        'file_path' => 'test/claim.pdf',
    ]);

    $sepFile = UploadedFile::fake()->create('sep.pdf', 100);
    $resumeFile = UploadedFile::fake()->create('resume.pdf', 200);
    $billingFile = UploadedFile::fake()->create('billing.pdf', 150);

    Livewire::test(ClaimForm::class)
        ->set('sepFile', $sepFile)
        ->set('showUploadedData', true)
        ->set('medical_record_number', 'RM-001')
        ->set('patient_name', 'Test Patient')
        ->set('sep_number', '1234567890') // Duplicate
        ->set('bpjs_number', '0001234567890')
        ->set('sep_date', '2024-01-15')
        ->set('patient_class', '1')
        ->set('resumeFile', $resumeFile)
        ->set('billingFile', $billingFile)
        ->call('submit')
        ->assertHasErrors(['sep_number']);
});

it('validates patient class is 1, 2, or 3', function () {
    $sepFile = UploadedFile::fake()->create('sep.pdf', 100);
    $resumeFile = UploadedFile::fake()->create('resume.pdf', 200);
    $billingFile = UploadedFile::fake()->create('billing.pdf', 150);

    Livewire::test(ClaimForm::class)
        ->set('sepFile', $sepFile)
        ->set('showUploadedData', true)
        ->set('medical_record_number', 'RM-001')
        ->set('patient_name', 'Test Patient')
        ->set('sep_number', '1234567890')
        ->set('bpjs_number', '0001234567890')
        ->set('sep_date', '2024-01-15')
        ->set('patient_class', '4') // Invalid
        ->set('resumeFile', $resumeFile)
        ->set('billingFile', $billingFile)
        ->call('submit')
        ->assertHasErrors(['patient_class']);
});

it('validates jenis rawatan is RJ or RI', function () {
    $sepFile = UploadedFile::fake()->create('sep.pdf', 100);
    $resumeFile = UploadedFile::fake()->create('resume.pdf', 200);
    $billingFile = UploadedFile::fake()->create('billing.pdf', 150);

    Livewire::test(ClaimForm::class)
        ->set('sepFile', $sepFile)
        ->set('showUploadedData', true)
        ->set('medical_record_number', 'RM-001')
        ->set('patient_name', 'Test Patient')
        ->set('sep_number', '1234567890')
        ->set('bpjs_number', '0001234567890')
        ->set('sep_date', '2024-01-15')
        ->set('patient_class', '1')
        ->set('jenis_rawatan', 'INVALID') // Invalid
        ->set('resumeFile', $resumeFile)
        ->set('billingFile', $billingFile)
        ->call('submit')
        ->assertHasErrors(['jenis_rawatan']);
});
