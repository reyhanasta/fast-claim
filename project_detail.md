# Project Detail: FastClaim (Kubr Claim)

## 1. Project Overview

**FastClaim** is a modern web application designed to streamline and automate the management of BPJS Health claim documents for advanced healthcare facilities (clinics and hospitals). It addresses the challenges of manual document collection, data entry errors, and inconsistent file storage by providing automated tools for extraction, merging, and organization.

## 2. Core Features

### 📄 Document Processing

-   **Smart Upload & Auto Extract**: Automatically extracts key data (SEP Number, Patient Name, Date, Class) from uploaded SEP PDFs using OCR (Poppler Utils).
-   **One-Click PDF Merge**: Combines multiple documents (SEP, Medical Resume, Billing, Lab Results, LIP) into a single, standardized PDF file.
-   **PDF Manipulation**: Uses `setasign/fpdi` and `setasign/fpdf` for merging and `spatie/pdf-to-text` for text extraction.

### 🗂️ File Management

-   **Structured Organization**: Automatically organizes files into a consistent directory structure based on year, month/period, care type (Outpatient/Inpatient), and dates.
    -   Example: `2025/12_DESEMBER/R.JALAN/01/SEP_NUMBER/PATIENT_NAME.pdf`
-   **Automated Backup**: Uses Laravel Queues to automatically backup files to a secondary location (e.g., network drive, NAS) in the background without blocking the user interface.

### 📊 Dashboard & Analytics

-   **Real-time Stats**: Displays total claims, claims per care type, and specific time periods.
-   **Monitoring**: Tracks backup status and system health.

### 🛡️ Access Control & UI

-   **Role-Based Access**:
    -   **Admin**: Full system control, user management, configuration.
    -   **Operator**: Claim processing and dashboard viewing.
-   **Modern Interface**: Built with **Flux UI** and **Tailwind CSS 4**, featuring a responsive design and dark mode support.

## 3. Technology Stack

### Backend

-   **Framework**: Laravel 12.x
-   **Language**: PHP 8.2+
-   **Database**: MySQL / SQLite
-   **Queueing**: Database Queue Driver (for backups and heavy processing)

### Frontend

-   **Framework**: Livewire 3.x
-   **UI Toolkit**: Flux UI
-   **Styling**: Tailwind CSS 4.0
-   **Build Tool**: Vite

### External Tools & Libraries

-   **Poppler Utils** (`pdftotext`): Required for reading text from PDFs.
-   **PDF PHP Libraries**:
    -   `setasign/fpdf`: PDF generation.
    -   `setasign/fpdi`: Importing existing PDF pages.
    -   `spatie/pdf-to-text`: Wrapper for pdftotext.

## 4. Module Structure (`app/`)

The application follows a domain-driven or functional structure within standard Laravel conventions:

-   **Livewire (`app/Livewire`)**: Contains the frontend components and page logic (likely `Pages/Auth`, `Pages/Dashboard`, `Forms/ClaimForm` etc.).
-   **Services (`app/Services`)**: Encapsulates business logic, particularly for PDF processing (`PdfMergeService`, `OcrService` likely reside here).
-   **Jobs (`app/Jobs`)**: Handles background tasks, primarily the automated file backup process.
-   **Enums (`app/Enums`)**: Defines standardized values for care types, claim statuses, etc.
-   **Models (`app/Models`)**: Eloquent models representing database tables (likely `Claim`, `Patient`, `Document`, `User`).

## 5. System Requirements

-   **OS**: Windows (with poppler-windows) or Linux (with poppler-utils package).
-   **PHP Extensions**: `ctype`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`.
-   **Hardware**: Min. 2 Core CPU, 4GB RAM recommended for PDF processing.
