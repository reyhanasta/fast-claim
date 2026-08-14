<?php

use App\Services\GenerateFolderService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('shared');
    $this->service = new GenerateFolderService;
});

it('creates correct folder structure based on date and type', function () {
    $date = '2025-10-06';

    $result = $this->service->generateOutputPath($date, 'RJ');

    // Folder hasil: 2025/10_OKTOBER REGULER 2025/R.JALAN/
    expect($result)->toBe('2025/10_OKTOBER REGULER 2025/R.JALAN/');
    expect($result)->toContain('2025');
    expect($result)->toContain('R.JALAN');

    // Simulasikan bahwa folder benar-benar dibuat di storage fake
    Storage::disk('shared')->makeDirectory($result);
    expect(Storage::disk('shared')->exists($result))->toBeTrue();
});

it('maps RI to R.INAP folder', function () {
    $result = $this->service->generateOutputPath('2025-10-06', 'RI');

    expect($result)->toBe('2025/10_OKTOBER REGULER 2025/R.INAP/');
});

it('throws exception for invalid date format', function () {
    $this->service->generateOutputPath('invalid-date', 'RJ');
})->throws(Exception::class);
