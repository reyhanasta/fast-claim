To implement this **Advanced Document Intelligence** service, we will transition FastClaim from a manual upload tool into an automated correlation engine. Below is the project plan in Markdown format, designed for you to use as a prompt or roadmap for AI-assisted development.

# Project Plan: FastClaim Automated Discovery Service

## 1. Objective

To minimize manual document handling by allowing users to upload a single **SEP** file, while the system automatically retrieves supporting documents (Billing, Lab, Resume) from a structured shared folder system based on patient identity and date.

## 2. Infrastructure Requirements

-   **Shared Drive Access**: Ensure the Laravel server has a persistent connection to the central shared folder (e.g., via `net use` or local path).
-   **Directory Standard**:
-   `ROOT/{TYPE}/{YEAR}/{MONTH_NAME}/{DAY}/{FILENAME}.pdf`
-   Example: `Z:/SOURCE_DATA/02_LAB/2026/09_SEPTEMBER/9/Reyhan.pdf`

## 3. Phase 1: Service Layer Development

### 3.1. `DocumentDiscoveryService`

-   **Input**: Patient Name, Date (from SEP OCR).
-   **Logic**:

1. Parse the date using `Carbon` to match the folder structure (handling no-leading-zero days).
2. Construct search paths for each department (Billing, Lab, Resume).
3. Perform a fuzzy filename search (e.g., `*Name*.pdf`).

-   **Fallback**: If no filename match is found, use `pdftotext` (Poppler) to scan contents of all files in that specific day's folder for the patient's name.

## 4. Phase 2: Integration & UI (Livewire)

### 4.1. Triggering Discovery

-   Modify `ClaimForm` to trigger the `DocumentDiscoveryService` immediately after the SEP OCR extraction is complete.
-   Use Laravel Queues for the "Internal PDF Verification" to ensure the UI doesn't freeze during heavy scans.

### 4.2. Flux UI Enhancement

-   Create a "Found Documents" checklist component using **Flux UI**.
-   Display status indicators:
-   ✅ **Match Found**: Filename matches name + date.
-   🔍 **Suggested**: Name found inside PDF content via OCR.
-   ❌ **Missing**: No record found in the specific folder.

## 5. Phase 3: Automated Merging

-   Update `PdfMergeService` to accept paths discovered by the service instead of only `TemporaryUploadedFile` objects.
-   Implement a "One-Click Claim" button that merges the uploaded SEP with the auto-discovered files and moves the result to the final structured storage.

## 6. Development Roadmap (Checklist)

-   [ ] **Task 1**: Update `.env` and `config/filesystems.php` to include the `SHARED_SOURCE_PATH`.
-   [ ] **Task 2**: Create `App\Services\DocumentDiscoveryService`.
-   [ ] **Task 3**: Create a "Discovery Result" Model or DTO (Data Transfer Object) to store metadata.
-   [ ] **Task 4**: Implement the `pdftotext` fallback logic to handle generic filenames (e.g., `scan1.pdf`).
-   [ ] **Task 5**: Update the Livewire frontend to display the discovery status.
-   [ ] **Task 6**: Test with "Edge Cases" (e.g., patient visits Lab a day before SEP is issued).

## 7. Technical Stack Reference

-   **Backend**: Laravel 12 / PHP 8.2.
-   **OCR/Text Extraction**: Poppler-utils (`pdftotext`) via `spatie/pdf-to-text`.
-   **PDF Logic**: `setasign/fpdi` for merging discovered files.
-   **Frontend**: Livewire 3 / Flux UI / Tailwind 4.
