<?php

use App\Livewire\BackupDashboard;
use App\Livewire\ClaimForm;
use App\Livewire\ClaimFormManual;
use App\Livewire\ClaimFormAssist;
use App\Livewire\ClaimsList;
use App\Livewire\Dashboard\BpjsClaimDashboard;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Clinic;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Storage;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', BpjsClaimDashboard::class)
        ->name('dashboard');

    Route::get('/claims', ClaimsList::class)
        ->name('claims.list');

    Route::get('claim-form', ClaimForm::class)->name('claim-form');

    // Download routes
    Route::get('/claims/{claim}/download', function (App\Models\BpjsClaim $claim) {
        $disk = Illuminate\Support\Facades\Storage::disk('shared');

        if (! $disk->exists($claim->file_path)) {
            abort(404, 'File tidak ditemukan');
        }

        return response()->download(
            $disk->path($claim->file_path),
            basename($claim->file_path)
        );
    })->name('claims.download');

    Route::get('/claims/{claim}/download-lip', function (App\Models\BpjsClaim $claim) {
        if (! $claim->lip_file_path) {
            abort(404, 'File LIP tidak ada');
        }

        $disk = Illuminate\Support\Facades\Storage::disk('shared');

        if (! $disk->exists($claim->lip_file_path)) {
            abort(404, 'File LIP tidak ditemukan');
        }

        return response()->download(
            $disk->path($claim->lip_file_path),
            basename($claim->lip_file_path)
        );
    })->name('claims.download-lip');

    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');

    // Admin Only Routes
    Route::middleware(['admin'])->group(function () {
        Route::get('backup', BackupDashboard::class)->name('backup.dashboard');
        Route::get('settings/clinic', Clinic::class)->name('settings.clinic');
        Route::get('settings/storage', Storage::class)->name('settings.storage');
    });
});

// Route::get('bpjs-rajal-form', \App\Livewire\BpjsRawatJalanForm::class)->middleware(['auth', 'verified'])->name('bpjs-rajal-form');
Route::get('claim-form-assist', ClaimFormAssist::class)->name('claim-form-assist');
Route::get('claim-form-manual', ClaimFormManual::class)->name('claim-form-manual');

require __DIR__.'/auth.php';
