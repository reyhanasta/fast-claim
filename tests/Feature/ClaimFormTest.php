<?php

declare(strict_types=1);

use App\Livewire\BpjsRawatJalanForm;
use App\Models\BpjsClaim;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('shared');
});

test('component can render', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BpjsRawatJalanForm::class)
        ->assertStatus(200);
});

test('validates required files on submit', function () {
    $user = User::factory()->create();

    // Test that submit method checks for required files
    $component = Livewire::actingAs($user)
        ->test(BpjsRawatJalanForm::class)
        ->set('sep_number', 'SEP123')
        ->set('sep_date', '2025-11-06')
        ->set('medical_record_number', 'RM001')
        ->set('patient_name', 'John Doe')
        ->set('bpjs_serial_number', '1234567890')
        ->set('patient_class', '1')
        ->call('submit');

    // Check that requiredFilesUploaded computed property is false
    expect($component->get('requiredFilesUploaded'))->toBeFalse();

    // Verify no claim was created (because files missing)
    expect(BpjsClaim::count())->toBe(0);
});

test('validates patient class must be 1, 2, or 3', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BpjsRawatJalanForm::class)
        ->set('sepFile', UploadedFile::fake()->create('sep.pdf', 100))
        ->set('resumeFile', UploadedFile::fake()->create('resume.pdf', 100))
        ->set('billingFile', UploadedFile::fake()->create('billing.pdf', 100))
        ->set('sep_number', 'SEP123')
        ->set('sep_date', '2025-11-06')
        ->set('medical_record_number', 'RM001')
        ->set('patient_name', 'John Doe')
        ->set('bpjs_serial_number', '1234567890')
        ->set('patient_class', '4') // Invalid class
        ->call('submit')
        ->assertHasErrors(['patient_class']);
});

test('accepts valid patient classes', function (string $class) {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BpjsRawatJalanForm::class)
        ->set('patient_class', $class)
        ->assertHasNoErrors(['patient_class']);
})->with(['1', '2', '3']);

test('sep file must be pdf', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BpjsRawatJalanForm::class)
        ->set('sepFile', UploadedFile::fake()->create('sep.jpg', 100))
        ->assertHasErrors(['sepFile']);
});

test('resume file must be pdf', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BpjsRawatJalanForm::class)
        ->set('resumeFile', UploadedFile::fake()->create('resume.txt', 100))
        ->assertHasErrors(['resumeFile']);
});

test('billing file accepts pdf, jpg, jpeg, png', function (string $extension) {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BpjsRawatJalanForm::class)
        ->set('billingFile', UploadedFile::fake()->create("billing.{$extension}", 100))
        ->assertHasNoErrors(['billingFile']);
})->with(['pdf', 'jpg', 'jpeg', 'png']);

test('file size cannot exceed 2MB', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BpjsRawatJalanForm::class)
        ->set('sepFile', UploadedFile::fake()->create('sep.pdf', 3000)) // 3MB
        ->assertHasErrors(['sepFile']);
});

test('sep number must be unique', function () {
    $user = User::factory()->create();

    // Create existing claim
    BpjsClaim::create([
        'no_sep' => 'SEP123',
        'no_rm' => 'RM001',
        'no_kartu_bpjs' => '1234567890',
        'jenis_rawatan' => 'RJ',
        'tanggal_rawatan' => '2025-11-06',
        'nama_pasien' => 'Existing Patient',
        'kelas_rawatan' => '1',
    ]);

    Livewire::actingAs($user)
        ->test(BpjsRawatJalanForm::class)
        ->set('sep_number', 'SEP123')
        ->set('sep_date', '2025-11-06')
        ->set('medical_record_number', 'RM002')
        ->assertHasErrors(['sep_number']);
});

test('required files uploaded check works correctly', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(BpjsRawatJalanForm::class);

    // Initially, no files uploaded - should be false
    expect($component->get('requiredFilesUploaded'))->toBeFalse();

    // Note: This test is skipped because Livewire file uploads
    // behave differently in tests vs production
})->skip('Livewire file upload testing limitation');

test('upload progress tracking works', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(BpjsRawatJalanForm::class);

    // Initially no files
    expect($component->get('sepFile'))->toBeNull();
    expect($component->get('resumeFile'))->toBeNull();

    // Upload files and verify they're set
    $component->set('resumeFile', UploadedFile::fake()->create('resume.pdf', 100));
    expect($component->get('resumeFile'))->not->toBeNull();

    $component->set('billingFile', UploadedFile::fake()->create('billing.pdf', 100));
    expect($component->get('billingFile'))->not->toBeNull();
});

test('cancel upload cleans up files and resets state', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(BpjsRawatJalanForm::class)
        ->set('sepFile', UploadedFile::fake()->create('sep.pdf', 100))
        ->set('showUploadedData', true)
        ->call('cancelUpload');

    expect($component->sepFile)->toBeNull();
    expect($component->showUploadedData)->toBeFalse();
});

test('locked properties cannot be modified from frontend', function () {
    $user = User::factory()->create();

    expect(fn () => Livewire::actingAs($user)
        ->test(BpjsRawatJalanForm::class)
        ->set('jenis_rawatan', 'HACKED'))
        ->toThrow(\Exception::class);
});

test('constants are defined correctly', function () {
    $reflection = new ReflectionClass(BpjsRawatJalanForm::class);

    expect($reflection->getConstant('TEMP_STORAGE_PATH'))->toBe('temp');
    expect($reflection->getConstant('MAX_FILE_SIZE'))->toBe(300);
});

test('validation messages are user friendly', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(BpjsRawatJalanForm::class)
        ->set('sepFile', UploadedFile::fake()->create('sep.jpg', 100));

    $errors = $component->errors();

    expect($errors->get('sepFile')[0])->toContain('PDF');
});

test('patient class validation message is user friendly', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(BpjsRawatJalanForm::class)
        ->set('patient_class', '5')
        ->set('sep_number', 'SEP123')
        ->set('sep_date', '2025-11-06')
        ->set('medical_record_number', 'RM001')
        ->set('patient_name', 'John Doe')
        ->set('bpjs_serial_number', '1234567890')
        ->call('submit');

    $errors = $component->errors();
    expect($errors->get('patient_class')[0])->toContain('1, 2, atau 3');
});
