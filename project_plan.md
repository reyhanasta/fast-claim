# FastClaim (Kubr-Claim) — Project Overview

## 1. What Is FastClaim?

**FastClaim** is a web-based document management system purpose-built for Indonesian healthcare facilities (clinics & hospitals) to streamline the **BPJS Kesehatan (National Health Insurance) claim workflow**. It automates the tedious, error-prone process of collecting, extracting, merging, organizing, and backing up the various PDF documents required for each patient insurance claim.

---

## 2. The Problem It Solves

When a healthcare facility files a BPJS claim, staff must manually:

1. **Gather 5+ documents** per claim — SEP (eligibility letter), medical resume, billing, lab results, LIP (service information sheet).
2. **Re-type data** (patient name, SEP number, dates) from the PDF into tracking spreadsheets — a process riddled with typos.
3. **Merge PDFs** one-by-one using desktop tools (Adobe Acrobat, etc.).
4. **Name & file** the merged result into a consistent folder structure that BPJS auditors expect.
5. **Back up** everything to a secondary drive — often forgotten under time pressure.

This takes **5–10 minutes per claim** and scales poorly when a facility processes hundreds of claims per month.

---

## 3. How FastClaim Solves It

### 3.1 Smart Upload & Auto-Extract (OCR)
Upload a single **SEP PDF**, and the system automatically reads the document via **Poppler (`pdftotext`)** to extract:
- SEP Number
- Patient Name
- SEP Date
- Care Class (Kelas 1/2/3)
- Care Type (Outpatient / Inpatient)

The extracted data auto-fills the claim form — no manual typing.

### 3.2 One-Click PDF Merge
All supporting documents (Resume, Billing, Lab, LIP) are uploaded alongside the SEP. With one click, they are merged into a **single standardized PDF** named after the patient.

### 3.3 Structured File Organization
The merged file is automatically stored in a BPJS-standard folder structure:
```
FOLDER_SHARED/
└── 2025/
    └── 12_DESEMBER REGULER 2025/
        ├── R.JALAN/          ← Outpatient
        │   └── 01/           ← Day of month
        │       └── SEP_NUMBER/
        │           ├── PATIENT_NAME.pdf   (merged)
        │           └── LIP.pdf            (separate)
        └── R.INAP/           ← Inpatient
            └── ...
```

### 3.4 Automated Backup
A **Laravel Queue job** (`BackupFileJob`) copies every file to a secondary location (network drive, NAS, external HDD) in the background — without slowing down the operator.

### 3.5 Dashboard & Analytics
Real-time dashboard showing:
- Total claims this period
- Claims by care type (Outpatient vs Inpatient)
- Backup health & status
- Monthly trend charts

### 3.6 Multi-User & Roles
| Role       | Permissions                              |
|------------|------------------------------------------|
| **Admin**  | Full access: user management, settings   |
| **Operator** | Upload claims, view dashboard          |

### 3.7 Dark Mode & Modern UI
Built with **Flux UI** + **Tailwind CSS 4** for a clean, responsive interface with dark mode support.

---

## 4. Technology Stack

| Layer        | Technology                                                                 |
|--------------|---------------------------------------------------------------------------|
| **Backend**  | Laravel 12 / PHP 8.2+                                                     |
| **Frontend** | Livewire 3 / Flux UI / Tailwind CSS 4 / Vite                             |
| **Database** | MySQL or SQLite                                                           |
| **PDF OCR**  | Poppler Utils (`pdftotext`) via `spatie/pdf-to-text`                      |
| **PDF Merge**| `setasign/fpdi` + `setasign/fpdf`                                         |
| **Queue**    | Database Queue Driver (for backup jobs)                                   |
| **Testing**  | Pest v3 / PHPUnit v11                                                     |
| **Linting**  | Laravel Pint                                                              |

---

## 5. Application Architecture

```
app/
├── Enums/
│   └── UserRole.php                  # Admin / Operator role enum
├── Helpers/                          # Utility functions
├── Http/                             # Controllers & middleware
├── Jobs/
│   └── BackupFileJob.php             # Async file backup to secondary storage
├── Livewire/
│   ├── ClaimForm.php                 # Main claim upload + merge form
│   ├── ClaimFormAssist.php           # Assisted claim workflow (auto-discovery)
│   ├── ClaimFormManual.php           # Manual claim entry mode
│   ├── ClaimsList.php                # Paginated list of processed claims
│   ├── BackupDashboard.php           # Backup monitoring dashboard
│   ├── Dashboard/                    # Dashboard widgets & pages
│   ├── Settings/                     # App settings (storage paths, profile)
│   ├── Auth/                         # Login, registration, password reset
│   └── Actions/                      # Reusable Livewire actions
├── Models/
│   ├── BpjsClaim.php                 # Core claim record
│   ├── BackupLog.php                 # Backup job audit trail
│   ├── AppSetting.php                # System configuration storage
│   └── User.php                      # User model with roles
├── Policies/                         # Authorization policies
├── Services/
│   ├── PdfReadService.php            # OCR text extraction from SEP PDFs
│   ├── PdfMergerService.php          # Multi-document PDF merge
│   ├── PdfDecompressionService.php   # PDF stream decompression
│   ├── SepDataProcessor.php          # Parse extracted text → structured data
│   ├── FileUploadService.php         # Handle file uploads to storage
│   └── GenerateFolderService.php     # Build BPJS-standard directory paths
└── Traits/                           # Shared model/component traits
```

---

## 6. Key Workflows

### Claim Processing Flow
```
Operator uploads SEP PDF
        │
        ▼
PdfReadService extracts text via Poppler
        │
        ▼
SepDataProcessor parses: SEP#, Name, Date, Class
        │
        ▼
Auto-fills form → Operator uploads supporting docs
        │
        ▼
PdfMergerService merges all into one PDF
        │
        ▼
GenerateFolderService creates target directory
        │
        ▼
FileUploadService saves to FOLDER_SHARED
        │
        ▼
BackupFileJob (queued) copies to FOLDER_BACKUP
```

---

## 7. Future Roadmap: Automated Document Discovery

The next major feature (**Phase 2**) is an **Automated Discovery Service** that eliminates the need to manually upload supporting documents:

1. **`DocumentDiscoveryService`** — Given a patient name + date (from SEP OCR), it scans a shared drive with a known folder structure to auto-locate matching Billing, Lab, and Resume PDFs.
2. **Fuzzy filename matching** — Searches for `*PatientName*.pdf` in the appropriate date/department folder.
3. **OCR fallback** — If filenames are generic (e.g., `scan1.pdf`), falls back to reading PDF content via Poppler to find the patient's name inside the document.
4. **UI integration** — A "Found Documents" checklist with status indicators:
   - ✅ **Match Found** (filename match)
   - 🔍 **Suggested** (content match via OCR)
   - ❌ **Missing** (not found)
5. **One-Click Claim** — Merges SEP + auto-discovered files and saves to final storage.

### Discovery Roadmap Checklist
- [ ] Configure `SHARED_SOURCE_PATH` in `.env` and `config/filesystems.php`
- [ ] Create `App\Services\DocumentDiscoveryService`
- [ ] Create Discovery Result DTO
- [ ] Implement `pdftotext` fallback for generic filenames
- [ ] Update Livewire frontend for discovery status display
- [ ] Test edge cases (e.g., lab done on a different day than SEP)

---

## 8. Target Users

- **Klinik Pratama & Utama** (primary/specialized clinics) partnered with BPJS
- **Type D/C Hospitals** with moderate claim volume
- **Puskesmas** (community health centers)
- **Healthcare admin teams** managing claim documentation
- **BPJS verifiers** who need quick access to structured documents

---

## 9. Impact Summary

| Before FastClaim                     | After FastClaim                       |
|--------------------------------------|---------------------------------------|
| ⏱️ 5–10 min/claim (manual)           | ⚡ 1–2 min/claim (automated)          |
| 📝 Manual data entry → error-prone   | 🤖 Auto-extract → accurate & fast    |
| 📂 Inconsistent folder structure     | 🗂️ BPJS-standard organization        |
| 💾 Manual backup (often forgotten)   | ☁️ Automatic backup on every upload   |
| ❓ No visibility into progress       | 📊 Real-time dashboard               |
| 🔍 Hard to find old files            | 🎯 Search by SEP number/patient name |
