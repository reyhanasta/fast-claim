<?php

use App\Livewire\ClaimForm;
use App\Models\BpjsClaim;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('shared');

    $this->user = User::factory()->create();
    actingAs($this->user);
});

test('user can view bpjs rawat jalan form', function () {
    $response = $this->get(route('claim-form'));

    $response->assertStatus(200);
    $response->assertSeeLivewire(ClaimForm::class);
});

test('bpjs claim can be filtered by month', function () {
    BpjsClaim::create([
        'no_rkm_medis' => 'RM123',
        'no_kartu_bpjs' => '0001234567890',
        'no_sep' => 'SEP123',
        'jenis_rawatan' => 'R.Jalan',
        'tanggal_rawatan' => now(),
        'nama_pasien' => 'Test Patient 1',
        'kelas_rawat' => '1',
        'file_path' => 'shared/2025/11/RM123_SEP123.pdf',
    ]);

    BpjsClaim::create([
        'no_rkm_medis' => 'RM124',
        'no_kartu_bpjs' => '0001234567891',
        'no_sep' => 'SEP124',
        'jenis_rawatan' => 'R.Inap',
        'tanggal_rawatan' => now()->subMonth(),
        'nama_pasien' => 'Test Patient 2',
        'kelas_rawat' => '2',
        'file_path' => 'shared/2025/10/RM124_SEP124.pdf',
    ]);

    $currentMonthClaims = BpjsClaim::forMonth(now()->month, now()->year)->get();

    expect($currentMonthClaims)->toHaveCount(1);
    expect($currentMonthClaims->first()->no_sep)->toBe('SEP123');
});

test('bpjs claim can be filtered by rawat jalan', function () {
    BpjsClaim::create([
        'no_rkm_medis' => 'RM123',
        'no_kartu_bpjs' => '0001234567890',
        'no_sep' => 'SEP123',
        'jenis_rawatan' => 'R.Jalan',
        'tanggal_rawatan' => now(),
        'nama_pasien' => 'RJ Patient',
        'kelas_rawat' => '1',
        'file_path' => 'shared/2025/11/RM123_SEP123.pdf',
    ]);

    BpjsClaim::create([
        'no_rkm_medis' => 'RM124',
        'no_kartu_bpjs' => '0001234567891',
        'no_sep' => 'SEP124',
        'jenis_rawatan' => 'R.Inap',
        'tanggal_rawatan' => now(),
        'nama_pasien' => 'RI Patient',
        'kelas_rawat' => '2',
        'file_path' => 'shared/2025/11/RM124_SEP124.pdf',
    ]);

    $rjClaims = BpjsClaim::rawatJalan()->get();

    expect($rjClaims)->toHaveCount(1);
    expect($rjClaims->first()->jenis_rawatan)->toBe('R.Jalan');
});
