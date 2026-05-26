<?php

declare(strict_types=1);

use App\Livewire\ClaimFormManual;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('mounts with current month and year and sets sep_date to 1st of month', function () {
    Livewire::test(ClaimFormManual::class)
        ->assertSet('selected_month', date('m'))
        ->assertSet('selected_year', date('Y'))
        ->assertSet('sep_date', sprintf('%s-%s-01', date('Y'), date('m')));
});

it('updates sep_date to 1st of month when month or year changes', function () {
    Livewire::test(ClaimFormManual::class)
        ->set('selected_month', '05')
        ->set('selected_year', '2026')
        ->assertSet('sep_date', '2026-05-01')
        ->set('selected_month', '12')
        ->assertSet('sep_date', '2026-12-01');
});

it('validates required fields for manual entry in ClaimFormManual', function () {
    $sepFile = UploadedFile::fake()->create('sep.pdf', 100);
    $resumeFile = UploadedFile::fake()->create('resume.pdf', 200);
    $billingFile = UploadedFile::fake()->create('billing.pdf', 150);

    Livewire::test(ClaimFormManual::class)
        ->set('sepFile', $sepFile)
        ->set('showUploadedData', true)
        ->set('patient_name', '') // Empty - should fail
        ->set('sep_number', '1234567890')
        ->set('patient_class', '1')
        ->set('resumeFile', $resumeFile)
        ->set('billingFile', $billingFile)
        ->call('submit')
        ->assertHasErrors(['patient_name']);
});
