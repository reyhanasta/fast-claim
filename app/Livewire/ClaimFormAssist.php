<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Jobs\BackupFileJob;
use App\Models\BpjsClaim;
use App\Services\FileUploadService;
use App\Services\GenerateFolderService;
use App\Services\PdfMergerService;
use App\Services\PdfReadService;
use App\Services\SepDataProcessor;
use App\Traits\HasAlerts;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class ClaimFormAssist extends Component
{
    use HasAlerts, WithFileUploads;

    // File uploads
    #[Validate('required|file|mimes:pdf|max:2048')]
    public ?TemporaryUploadedFile $sepFile = null;

    #[Validate('nullable|file|mimes:pdf|max:2048')]
    public ?TemporaryUploadedFile $sepRJFile = null;

    #[Validate('required|file|mimes:pdf|max:2048')]
    public ?TemporaryUploadedFile $resumeFile = null;

    #[Validate('required|file|mimes:pdf,jpg,jpeg,png|max:2048')]
    public ?TemporaryUploadedFile $billingFile = null;

    // Lab result (optional, PDF only) – merged into final combined PDF
    #[Validate('nullable|file|mimes:pdf|max:2048')]
    public ?TemporaryUploadedFile $labResultFile = null;

    // Lab result (optional, PDF only) – merged into final combined PDF
    #[Validate('nullable|file|mimes:pdf|max:2048')]
    public ?TemporaryUploadedFile $labResultFile2 = null;
    // Lab result (optional, PDF only) – merged into final combined PDF
    #[Validate('nullable|file|mimes:pdf|max:2048')]
    public ?TemporaryUploadedFile $labResultFile3 = null;
    // Lab result (optional, PDF only) – merged into final combined PDF
    #[Validate('nullable|file|mimes:pdf|max:2048')]
    public ?TemporaryUploadedFile $labResultFile4 = null;

    #[Validate('nullable|file|mimes:pdf|max:2048')]
    public ?TemporaryUploadedFile $fileLIP = null;

    // Patient data
    #[Validate('string|max:50')]
    public string $medical_record_number = '';

    #[Validate('required|string|max:100')]
    public string $patient_name = '';

    #[Validate('required|string|max:50|unique:bpjs_claims,no_sep', message: 'Nomor SEP sudah terdaftar')]
    public string $sep_number = '';

    #[Validate('required|string|max:20')]
    public string $bpjs_number = '';

    #[Validate('required|date')]
    public ?string $sep_date = null;

    #[Validate('required|string|in:1,2,3', message: 'Kelas rawatan harus berupa 1, 2, atau 3')]
    public string $patient_class = '';

    #[Locked]
    public string $jenis_rawatan = 'RJ';

    #[Locked]
    public string $sep_date_label = 'Tanggal SEP';

    // Internal state
    public array $temporaryPaths = [];

    public array $previewUrls = [];

    public bool $uploading = false;

    public bool $showUploadedData = false;

    // Constants
    private const MAX_FILE_SIZE = 300; // KB

    private const TEMP_STORAGE_PATH = 'temp';

    // File key identifiers
    private const FILE_SEP = 'sepFile';

    private const FILE_SEP_RJ = 'sepRJFile';

    private const FILE_RESUME = 'resumeFile';

    private const FILE_BILLING = 'billingFile';

    private const FILE_LIP = 'fileLIP';

    private const FILE_LAB_RESULT = 'labResultFile';

    private const FILE_LAB_RESULT_2 = 'labResultFile2';

    private const FILE_LAB_RESULT_3 = 'labResultFile3';

    private const FILE_LAB_RESULT_4 = 'labResultFile4';

    protected function messages(): array
    {
        return [
            'sepFile.required' => 'File SEP wajib diunggah',
            'sepFile.mimes' => 'File SEP harus berformat PDF maksimal 2MB',
            'sepFile.max' => 'File SEP maksimal 2MB',
            'resumeFile.required' => 'File Resume Medis wajib diunggah',
            'resumeFile.mimes' => 'File Resume Medis harus berformat PDF maksimal 2MB',
            'resumeFile.max' => 'File Resume Medis maksimal 2MB',
            'billingFile.required' => 'File Billing wajib diunggah',
            'billingFile.mimes' => 'File Billing harus berformat PDF/JPG/PNG maksimal 2MB',
            'billingFile.max' => 'File Billing maksimal 2MB',
            'fileLIP.mimes' => 'File LIP harus berformat PDF maksimal 2MB',
            'fileLIP.max' => 'File LIP maksimal 2MB',
            'sepRJFile.mimes' => 'File SEP RJ harus berformat PDF maksimal 2MB',
            'sepRJFile.max' => 'File SEP RJ maksimal 2MB',
            'labResultFile.mimes' => 'File Hasil Labor harus berformat PDF maksimal 2MB',
            'labResultFile.max' => 'File Hasil Labor maksimal 2MB',
            'labResultFile2.mimes' => 'File Hasil Labor harus berformat PDF maksimal 2MB',
            'labResultFile2.max' => 'File Hasil Labor maksimal 2MB',
            'labResultFile3.mimes' => 'File Hasil Labor harus berformat PDF maksimal 2MB',
            'labResultFile3.max' => 'File Hasil Labor maksimal 2MB',
            'labResultFile4.mimes' => 'File Hasil Labor harus berformat PDF maksimal 2MB',
            'labResultFile4.max' => 'File Hasil Labor maksimal 2MB',
            'sep_number.required' => 'Nomor SEP wajib diisi',
            'sep_number.unique' => 'Nomor SEP sudah terdaftar',
            'sep_date.required' => 'Tanggal SEP wajib diisi',
            'sep_date.date' => 'Tanggal SEP harus berupa format tanggal yang valid',
            'patient_name.required' => 'Nama pasien wajib diisi',
        ];
    }

    #[On('cancelUploadTimeout')]
    public function handleUploadTimeout(): void
    {
        if (! $this->uploading) {
            return;
        }

        $this->cancelUpload();
        $this->showErrorAlert('Waktu pemrosesan terlalu lama!', 'File tidak dapat diproses, silakan coba lagi');
    }

    #[Computed]
    public function requiredFilesUploaded(): bool
    {
        return $this->sepFile !== null
            && $this->resumeFile !== null
            && $this->billingFile !== null;
    }

    #[Computed]
    public function uploadProgress(): array
    {
        return [
            'sep' => $this->sepFile !== null,
            'resume' => $this->resumeFile !== null,
            'billing' => $this->billingFile !== null,
            'sepRJ' => $this->sepRJFile !== null,
            'lip' => $this->fileLIP !== null,
            'labResultFile' => $this->labResultFile !== null,
            'labResultFile2' => $this->labResultFile2 !== null,
            'labResultFile3' => $this->labResultFile3 !== null,
            'labResultFile4' => $this->labResultFile4 !== null,
        ];
    }
    

    /**
     * Check if supporting documents form can be displayed.
     * For Rawat Inap (RI), user must fill discharge date first.
     */
    #[Computed]
    public function canShowSupportingDocuments(): bool
    {
        // For Rawat Jalan, always show after SEP is uploaded
        if ($this->jenis_rawatan === 'RJ') {
            return true;
        }

        // For Rawat Inap, require discharge date to be filled
        return ! empty($this->sep_date);
    }

    public function updatedSepFile(): void
    {
        if (! $this->sepFile) {
            return;
        }

        if ($this->sepFile->getSize() / 1024 > self::MAX_FILE_SIZE) {
            $fileSizeKB = round($this->sepFile->getSize() / 1024, 2);
            $this->showErrorAlert(
                'File SEP terlalu besar',
                "Ukuran file {$fileSizeKB} KB melebihi batas maksimal ".self::MAX_FILE_SIZE.' KB. Silakan kompres file terlebih dahulu atau gunakan PDF yang lebih kecil.'
            );
            $this->sepFile = null;

            return;
        }

        $this->uploading = true;

        try {
            $this->validateOnly('sepFile');
            $this->processSepFile();
        } catch (ValidationException $e) {
            $this->handleValidationErrors($e);
        } catch (\Throwable $e) {
            $this->handleFileProcessingError($e);
        } finally {
            $this->uploading = false;
        }
    }

    public function updatedResumeFile(): void
    {
        $this->processOptionalFile($this->resumeFile, self::FILE_RESUME);
    }

    public function updatedSepRJFile(): void
    {
        $this->processOptionalFile($this->sepRJFile, self::FILE_SEP_RJ);
    }

    public function updatedBillingFile(): void
    {
        $this->processOptionalFile($this->billingFile, self::FILE_BILLING);
    }

    public function updatedFileLIP(): void
    {
        $this->processOptionalFile($this->fileLIP, self::FILE_LIP);
    }

    public function updatedLabResultFile(): void
    {
        $this->processOptionalFile($this->labResultFile, self::FILE_LAB_RESULT);
    }

    public function updatedLabResultFile2(): void
    {
        $this->processOptionalFile($this->labResultFile2, self::FILE_LAB_RESULT_2);
    }
    public function updatedLabResultFile3(): void
    {
        $this->processOptionalFile($this->labResultFile3, self::FILE_LAB_RESULT_3);
    }
    public function updatedLabResultFile4(): void
    {
        $this->processOptionalFile($this->labResultFile4, self::FILE_LAB_RESULT_4);
    }

    public function cancelUpload(): void
    {
        $this->uploading = false;
        $this->cleanupTempFiles();
        $this->resetUploadState();

        $this->showWarningAlert('Upload dibatalkan', 'Proses upload file dibatalkan');
    }

    public function cancelForm()
    {
        $this->cleanupTempFiles();
        $this->resetUploadState();

        $this->redirect(request()->header('Referer') ?? route('dashboard'), navigate: true);
    }

    /**
     * Submit and process the BPJS claim.
     * Merges all uploaded documents, creates database records, and dispatches backup job.
     */
    public function submit(
        PdfMergerService $pdfMergeService,
        GenerateFolderService $generateFolderService
    ): void {
        if (! $this->validateSubmission()) {
            return;
        }

        DB::beginTransaction();

        try {
            // Generate output path
            $outputDir = $generateFolderService->generateOutputPath(
                $this->sep_date,
                $this->sep_number,
                $this->jenis_rawatan
            );

            // Prepare merged PDF path
            $patientNameSafe = $this->sanitizePatientName();
            $pdfOutputPath = $outputDir.$patientNameSafe.'.pdf';

            // Get ordered files for merging
            $orderedFiles = $this->getOrderedFilesForMerge();

            Log::info('Order Files',$orderedFiles);
           

            if (empty($orderedFiles)) {
                throw new \RuntimeException('Tidak ada file yang dapat digabungkan');
            }

            // Merge PDFs
            $finalPath = $pdfMergeService->mergePdfs($orderedFiles, $pdfOutputPath);

            // Create claim record with file path
            $claim = $this->createClaimRecord($finalPath);

            // Handle optional LIP file
            $lipPath = $this->handleLipFile($claim, $outputDir);

            DB::commit();

            // Cleanup and dispatch backup job with claim ID for tracking
            $this->cleanUpAfterSubmit($pdfMergeService);
            BackupFileJob::dispatch($finalPath, $lipPath, $claim->id);

            $this->showSuccessAlert('Klaim berhasil dibuat!', 'Dokumen telah digabung dan disimpan');

            // Redirect to claims list or dashboard
            $this->redirect(route('claim-form-assist'), navigate: true);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('BPJS Claim Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'sep_number' => $this->sep_number,
            ]);

            $errorMessage = 'Terjadi kesalahan saat menyimpan klaim. ';

            if (str_contains($e->getMessage(), 'duplicate')) {
                $errorMessage .= 'Nomor SEP sudah terdaftar dalam sistem.';
            } elseif (str_contains($e->getMessage(), 'PDF')) {
                $errorMessage .= 'File PDF tidak dapat diproses. Pastikan file tidak terenkripsi atau corrupt.';
            } else {
                $errorMessage .= 'Silakan coba lagi atau hubungi administrator jika masalah berlanjut.';
            }

            $this->showErrorAlert('Klaim gagal dibuat', $errorMessage);
        }
    }

    public function render()
    {
        return view('livewire.claim-form-assist');
    }

    // ========================================
    // Validation Methods
    // ========================================

    /**
     * Validate submission requirements.
     */
    private function validateSubmission(): bool
    {
        try {
            $this->validate();
        } catch (ValidationException $e) {
            $this->handleValidationErrors($e);

            return false;
        }

        if (! $this->requiredFilesUploaded) {
            $missingFiles = [];
            if (! $this->sepFile) {
                $missingFiles[] = 'SEP';
            }
            if (! $this->resumeFile) {
                $missingFiles[] = 'Resume Medis';
            }
            if (! $this->billingFile) {
                $missingFiles[] = 'Billing';
            }

            $fileList = implode(', ', $missingFiles);
            $this->showErrorAlert(
                'Dokumen belum lengkap',
                "File berikut masih diperlukan: {$fileList}. Silakan upload semua file wajib terlebih dahulu."
            );

            return false;
        }

        return true;
    }

    // ========================================
    // File Processing Methods
    // ========================================

    /**
     * Process SEP file: extract data and auto-fill form fields.
     *
     * @throws \RuntimeException if SEP is invalid or duplicate
     */
    private function processSepFile(): void
    {
        /** @var PdfReadService $pdfReadService */
        $pdfReadService = app(PdfReadService::class);

        /** @var SepDataProcessor $sepProcessor */
        $sepProcessor = app(SepDataProcessor::class);

        // Validate SEP PDF must be a single page
        $pdfReadService->ensureSinglePage($this->sepFile);

        $pdfText = $pdfReadService->getPdfTextwithSpatie($this->sepFile);
        $extractedData = $pdfReadService->extractPdfAssist($pdfText);

        if (! $extractedData) {            
            throw new \RuntimeException('Format dokumen SEP tidak valid atau tidak dapat dibaca');
        }

        // Validate and check duplicates using SepDataProcessor
        $sepProcessor->validateExtractedData($extractedData);
        $sepProcessor->checkDuplicateSepNumber($extractedData['sep_number'] ?? '');

        // Fill form with extracted data
        $formData = $sepProcessor->prepareFormData($extractedData);
        $this->fill($formData);

        if ($this->jenis_rawatan === 'RI') {
            $this->sep_date_label = 'Tanggal Pulang';
            $this->sep_date = null;
        }

        $this->storeTempFile($this->sepFile, self::FILE_SEP);
        $this->showUploadedData = true;
    }

    private function processOptionalFile(?TemporaryUploadedFile $file, string $key): void
    {
        if (! $file) {
            return;
        }

        try {
            $this->storeTempFile($file, $key);
        } catch (\Throwable $e) {
            Log::warning("Failed to process {$key}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Store uploaded file to temporary storage.
     */
    private function storeTempFile(TemporaryUploadedFile $file, string $key): void
    {
        /** @var FileUploadService $uploadService */
        $uploadService = app(FileUploadService::class);

        $fileInfo = $uploadService->storeTemporary($file, $key);

        $this->temporaryPaths[$key] = $fileInfo['path'];
        $this->previewUrls[$key] = $uploadService->generatePreviewUrl(
            $fileInfo['path'],
            $key === self::FILE_SEP
        );
    }

    // ========================================
    // Utility Methods
    // ========================================

    /**
     * Sanitize patient name for safe filename usage.
     */
    private function sanitizePatientName(): string
    {
        return Str::of($this->patient_name)
            ->upper()
            ->ascii()
            ->replaceMatches('/[^A-Z0-9_\-]/', '_')
            ->toString();
    }

    /**
     * Get ordered file paths for PDF merging.
     * Order: SEP → SEP RJ → Resume → Lab Results → Billing
     */
    private function getOrderedFilesForMerge(): array
    {
        return collect([
            $this->temporaryPaths[self::FILE_SEP] ?? null,
            $this->temporaryPaths[self::FILE_SEP_RJ] ?? null,
            $this->temporaryPaths[self::FILE_RESUME] ?? null,
            $this->temporaryPaths[self::FILE_LAB_RESULT] ?? null,
            $this->temporaryPaths[self::FILE_LAB_RESULT_2] ?? null,
            $this->temporaryPaths[self::FILE_LAB_RESULT_3] ?? null,
            $this->temporaryPaths[self::FILE_LAB_RESULT_4] ?? null,
            $this->temporaryPaths[self::FILE_BILLING] ?? null,
        ])->filter()->values()->all();
    }

    // ========================================
    // Database Operations
    // ========================================

    /**
     * Create BPJS claim record in database.
     */
    private function createClaimRecord(string $finalPath): BpjsClaim
    {
        return BpjsClaim::create([
            'no_rm' => $this->medical_record_number,
            'no_kartu_bpjs' => $this->bpjs_number,
            'no_sep' => $this->sep_number,
            'jenis_rawatan' => $this->jenis_rawatan,
            'tanggal_rawatan' => $this->sep_date,
            'nama_pasien' => $this->patient_name,
            'kelas_rawatan' => $this->patient_class,
            'file_path' => $finalPath,
        ]);
    }

    /**
     * Handle optional LIP file upload and storage.
     */
    private function handleLipFile(BpjsClaim $claim, string $outputDir): ?string
    {
        if (! $this->fileLIP) {
            return null;
        }

        $lipFilename = 'LIP.pdf';
        $lipPath = $outputDir.$lipFilename;

        Storage::disk('shared')->putFileAs($outputDir, $this->fileLIP, $lipFilename);
        $claim->update(['lip_file_path' => $lipPath]);

        Log::info('LIP file saved', compact('lipPath'));

        return $lipPath;
    }

    // ========================================
    // Cleanup Methods
    // ========================================

    /**
     * Remove temporary files from temp storage.
     */
    private function cleanupTempFiles(): void
    {
        /** @var FileUploadService $uploadService */
        $uploadService = app(FileUploadService::class);
        $uploadService->cleanupTempFiles($this->temporaryPaths);
    }

    /**
     * Cleanup all temporary files after successful submission.
     */
    private function cleanUpAfterSubmit(PdfMergerService $pdfMergeService): void
    {
        $this->cleanupTempFiles();
        $pdfMergeService->cleanupTempFiles($this->temporaryPaths);
        $this->resetUploadState();
    }

    /**
     * Reset component state to initial values.
     */
    private function resetUploadState(): void
    {
        $this->reset([
            'sepFile',
            'sepRJFile',
            'resumeFile',
            'billingFile',
            'fileLIP',
            'labResultFile',
            'labResultFile2',
            'labResultFile3',
            'labResultFile4',
            'previewUrls',
            'temporaryPaths',
            'showUploadedData',
        ]);
    }

    private function handleValidationErrors(ValidationException $e): void
    {
        $this->uploading = false;

        foreach ($e->validator->errors()->all() as $error) {
            $this->showErrorAlert('Validasi Gagal', $error);
        }
    }

    private function handleFileProcessingError(\Throwable $e): void
    {
        Log::error('File processing error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $this->cancelUpload();

        $message = $e instanceof \RuntimeException
            ? $e->getMessage()
            : 'Terjadi kesalahan saat memproses file';

        $this->showErrorAlert('Gagal memproses file!', $message);
    }
}
