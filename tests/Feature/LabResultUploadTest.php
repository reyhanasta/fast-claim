<?php

declare(strict_types=1);

use App\Livewire\ClaimForm;
use App\Models\User;
use App\Services\GenerateFolderService;
use App\Services\PdfMergerService;
use App\Services\PdfReadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

use function Pest\Laravel\mock;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('shared');
});

it('rejects non-pdf for lab result file', function () {
    $user = User::factory()->create();

    // Minimal mocks for SEP processing so component doesn't fail earlier
    $this->mock(PdfReadService::class, function ($mock) {
        $mock->shouldReceive('ensureSinglePage')->andReturnNull();
        $mock->shouldReceive('getPdfTextwithSpatie')->andReturn('X');
        $mock->shouldReceive('extractPdf')->andReturn([
            'patient_class' => '1',
            'medical_record_number' => 'RM001',
            'patient_name' => 'John Doe',
            'sep_number' => 'SEP123',
            'bpjs_number' => '1234567890',
            'jenis_rawatan' => 'RJ',
            'sep_date' => '2025-11-10',
        ]);
    });

    Livewire::actingAs($user)
        ->test(ClaimForm::class)
        ->set('sep_number', 'SEP123')
        ->set('sep_date', '2025-11-10')
        ->set('medical_record_number', 'RM001')
        ->set('patient_name', 'John Doe')
        ->set('bpjs_number', '1234567890')
        ->set('patient_class', '1')
        ->set('sepFile', UploadedFile::fake()->create('sep.pdf', 100))
        ->set('resumeFile', UploadedFile::fake()->create('resume.pdf', 100))
        ->set('billingFile', UploadedFile::fake()->create('billing.pdf', 100))
        ->set('labResultFile', UploadedFile::fake()->create('lab.jpg', 100))
        ->set('labResultFile2', UploadedFile::fake()->create('lab2.jpg', 100))
        ->set('labResultFile3', UploadedFile::fake()->create('lab3.jpg', 100))
        ->set('labResultFile4', UploadedFile::fake()->create('lab4.jpg', 100))
        ->call('submit')
        ->assertHasErrors(['labResultFile']);
});

it('includes lab result in merge order after resume when provided', function () {
    $user = User::factory()->create();

    // Mock services
    $outputDir = 'claims/2025-11-10/SEP123_RJ/';

    $mergedOutput = Storage::disk('shared')->path($outputDir.'MERGED.pdf');

    $calledWithFiles = null;

    $this->mock(GenerateFolderService::class, function ($mock) use ($outputDir) {
        $mock->shouldReceive('generateOutputPath')
            ->andReturn($outputDir);
    });

    $this->mock(PdfMergerService::class, function ($mock) use (&$calledWithFiles, $mergedOutput) {
        $mock->shouldReceive('mergePdfs')
            ->once()
            ->andReturnUsing(function (array $files) use (&$calledWithFiles, $mergedOutput) {
                $calledWithFiles = $files;

                return $mergedOutput;
            });
        // Cleanup call can be ignored in this test
        $mock->shouldReceive('cleanupTempFiles')->andReturnTrue();
    });

    // Mock PDF read service so updatedSepFile succeeds
    $this->mock(PdfReadService::class, function ($mock) {
        $mock->shouldReceive('ensureSinglePage')->andReturnNull();
        $mock->shouldReceive('getPdfTextwithSpatie')
            ->andReturn('FAKE_PDF_TEXT');
        $mock->shouldReceive('extractPdf')
            ->andReturn([
                'patient_class' => 'Kelas 1',
                'medical_record_number' => 'RM001',
                'patient_name' => 'John Doe',
                'sep_number' => 'SEP123',
                'bpjs_number' => '1234567890',
                'jenis_rawatan' => 'RJ',
                'sep_date' => '2025-11-10',
            ]);
    });

    $component = Livewire::actingAs($user)
        ->test(ClaimForm::class)
        // set minimal required fields
        ->set('sep_number', 'SEP123')
        ->set('sep_date', '2025-11-10')
        ->set('medical_record_number', 'RM001')
        ->set('patient_name', 'John Doe')
        ->set('bpjs_number', '1234567890')
        ->set('patient_class', '1')
        // upload required files
        ->set('sepFile', UploadedFile::fake()->create('sep.pdf', 100))
        ->set('resumeFile', UploadedFile::fake()->create('resume.pdf', 100))
        ->set('billingFile', UploadedFile::fake()->create('billing.pdf', 100))
        // upload optional lab result
        ->set('labResultFile', UploadedFile::fake()->create('lab.pdf', 100))
        ->set('labResultFile2', UploadedFile::fake()->create('lab2.pdf', 100))
        ->set('labResultFile3', UploadedFile::fake()->create('lab3.pdf', 100))
        ->set('labResultFile4', UploadedFile::fake()->create('lab4.pdf', 100))
        ->call('submit')
        ->assertHasNoErrors();

    // Ensure merger was called and files are in correct order
    // Order: SEP → Resume → Lab → Billing
    expect($calledWithFiles)->not->toBeNull();
    expect($calledWithFiles)->toBeArray();
    expect(count($calledWithFiles ?? []))->toBeGreaterThanOrEqual(4);

    // Check ordering: first is SEP, last is Billing
    expect(Str::endsWith($calledWithFiles[0], 'sep.pdf'))->toBeTrue();
    $array = $calledWithFiles ?? [];
    $last = $array ? $array[count($array) - 1] : '';
    expect(Str::endsWith($last, 'billing.pdf'))->toBeTrue();
});

it('rejects non-pdf for lab result file 2', function () {
    $user = User::factory()->create();

    $this->mock(PdfReadService::class, function ($mock) {
        $mock->shouldReceive('ensureSinglePage')->andReturnNull();
        $mock->shouldReceive('getPdfTextwithSpatie')->andReturn('X');
        $mock->shouldReceive('extractPdf')->andReturn([
            'patient_class' => '1',
            'medical_record_number' => 'RM001',
            'patient_name' => 'John Doe',
            'sep_number' => 'SEP456',
            'bpjs_number' => '1234567890',
            'jenis_rawatan' => 'RJ',
            'sep_date' => '2025-11-10',
        ]);
    });

    Livewire::actingAs($user)
        ->test(ClaimForm::class)
        ->set('sep_number', 'SEP456')
        ->set('sep_date', '2025-11-10')
        ->set('medical_record_number', 'RM001')
        ->set('patient_name', 'John Doe')
        ->set('bpjs_number', '1234567890')
        ->set('patient_class', '1')
        ->set('sepFile', UploadedFile::fake()->create('sep.pdf', 100))
        ->set('resumeFile', UploadedFile::fake()->create('resume.pdf', 100))
        ->set('billingFile', UploadedFile::fake()->create('billing.pdf', 100))
        ->set('labResultFile2', UploadedFile::fake()->create('lab2.jpg', 100))
        ->set('labResultFile3', UploadedFile::fake()->create('lab3.pdf', 100))
        ->set('labResultFile4', UploadedFile::fake()->create('lab4.pdf', 100))
        ->call('submit')
        ->assertHasErrors(['labResultFile2']);
});

it('includes both lab result files in merge order before billing', function () {
    $user = User::factory()->create();

    $outputDir = 'claims/2025-11-10/SEP789_RJ/';
    $mergedOutput = Storage::disk('shared')->path($outputDir.'MERGED.pdf');
    $calledWithFiles = null;

    $this->mock(GenerateFolderService::class, function ($mock) use ($outputDir) {
        $mock->shouldReceive('generateOutputPath')->andReturn($outputDir);
    });

    $this->mock(PdfMergerService::class, function ($mock) use (&$calledWithFiles, $mergedOutput) {
        $mock->shouldReceive('mergePdfs')
            ->once()
            ->andReturnUsing(function (array $files) use (&$calledWithFiles, $mergedOutput) {
                $calledWithFiles = $files;

                return $mergedOutput;
            });
        $mock->shouldReceive('cleanupTempFiles')->andReturnTrue();
    });

    $this->mock(PdfReadService::class, function ($mock) {
        $mock->shouldReceive('ensureSinglePage')->andReturnNull();
        $mock->shouldReceive('getPdfTextwithSpatie')->andReturn('FAKE_PDF_TEXT');
        $mock->shouldReceive('extractPdf')->andReturn([
            'patient_class' => 'Kelas 2',
            'medical_record_number' => 'RM002',
            'patient_name' => 'Jane Doe',
            'sep_number' => 'SEP789',
            'bpjs_number' => '9876543210',
            'jenis_rawatan' => 'RJ',
            'sep_date' => '2025-11-10',
        ]);
    });

    Livewire::actingAs($user)
        ->test(ClaimForm::class)
        ->set('sep_number', 'SEP789')
        ->set('sep_date', '2025-11-10')
        ->set('medical_record_number', 'RM002')
        ->set('patient_name', 'Jane Doe')
        ->set('bpjs_number', '9876543210')
        ->set('patient_class', '2')
        ->set('sepFile', UploadedFile::fake()->create('sep.pdf', 100))
        ->set('resumeFile', UploadedFile::fake()->create('resume.pdf', 100))
        ->set('billingFile', UploadedFile::fake()->create('billing.pdf', 100))
        ->set('labResultFile', UploadedFile::fake()->create('lab1.pdf', 100))
        ->set('labResultFile2', UploadedFile::fake()->create('lab2.pdf', 100))
        ->set('labResultFile3', UploadedFile::fake()->create('lab3.pdf', 100))
        ->set('labResultFile4', UploadedFile::fake()->create('lab4.pdf', 100))
        ->call('submit')
        ->assertHasNoErrors();

    // Verify merge order: SEP → Resume → Lab1 → Lab2 → Billing
    expect($calledWithFiles)->not->toBeNull();

    expect($calledWithFiles)->toBeArray();
    expect(count($calledWithFiles ?? []))->toBe(7);
    expect(Str::endsWith($calledWithFiles[0], 'sep.pdf'))->toBeTrue();
    expect(Str::endsWith($calledWithFiles[1], 'resume.pdf'))->toBeTrue();
    expect(Str::endsWith($calledWithFiles[2], 'lab1.pdf'))->toBeTrue();
    expect(Str::endsWith($calledWithFiles[3], 'lab2.pdf'))->toBeTrue();
    expect(Str::endsWith($calledWithFiles[4], 'lab3.pdf'))->toBeTrue();
    expect(Str::endsWith($calledWithFiles[5], 'lab4.pdf'))->toBeTrue();
    expect(Str::endsWith($calledWithFiles[6], 'billing.pdf'))->toBeTrue();
});
