# FastClaim Backend Remake: Laravel → Node.js (Express)

## Tujuan

Membuat ulang backend FastClaim — dari Laravel/PHP menjadi **Node.js (Express)** — sebagai REST API server. Database MySQL **tetap sama persis** (tabel & kolom tidak diubah). Frontend React akan menyusul nanti.

---

## Keputusan Yang Sudah Disetujui

| Pertanyaan                 | Jawaban                                                    |
|----------------------------|------------------------------------------------------------|
| Background Job (Redis?)    | **Tidak pakai Redis**. Pakai simple async function.        |
| ORM                        | **Prisma** (`db pull` dari MySQL yang sudah ada).          |
| Testing                    | **Jest + Supertest** untuk API testing.                    |
| PDF Text Extraction        | Coba **`pdf-parse`** dulu, fallback ke Poppler kalau kurang akurat. |

---

## Gambaran Besar

Project lama (Laravel) = backend + frontend jadi satu.
Project baru = **dipecah dua**: Express REST API + React (nanti).

```
┌──────────────────┐         ┌─────────────────────┐
│  React Frontend  │ ──API── │  Express Backend     │
│  (nanti)         │         │  (ini yang kita buat)│
└──────────────────┘         └────────┬────────────┘
                                      │
                              ┌───────▼───────┐
                              │   MySQL DB    │
                              │  (sama persis)│
                              └───────────────┘
```

Backend Express cuma terima request (HTTP) → proses → kirim response (JSON). Tidak render HTML.

---

## Database Yang Sudah Ada (TIDAK DIUBAH)

### Tabel `users`
| Kolom              | Tipe       | Keterangan                     |
|--------------------|------------|--------------------------------|
| id                 | bigint PK  | Auto increment                 |
| name               | varchar    | Nama user                      |
| email              | varchar    | Unik, untuk login              |
| email_verified_at  | timestamp  | Nullable                       |
| role               | varchar    | `admin` atau `operator`        |
| password           | varchar    | Hash bcrypt                    |
| remember_token     | varchar    | Nullable (tidak dipakai di API)|
| created_at         | timestamp  |                                |
| updated_at         | timestamp  |                                |

### Tabel `bpjs_claims`
| Kolom            | Tipe       | Keterangan                     |
|------------------|------------|--------------------------------|
| id               | bigint PK  | Auto increment                 |
| no_rm            | varchar    | Nomor Rekam Medis (nullable)   |
| nama_pasien      | varchar    | Nama pasien                    |
| no_kartu_bpjs    | varchar    | Nomor kartu BPJS (nullable)    |
| no_sep           | varchar    | Nomor SEP (unik)               |
| jenis_rawatan    | varchar    | `RJ` (Rawat Jalan) / `RI` (Rawat Inap) |
| kelas_rawatan    | varchar    | Kelas 1/2/3 (nullable)         |
| file_path        | varchar    | Path file merged PDF (nullable)|
| lip_file_path    | varchar    | Path file LIP (nullable)       |
| tanggal_rawatan  | date       | Tanggal rawatan (nullable)     |
| created_at       | timestamp  |                                |
| updated_at       | timestamp  |                                |

### Tabel `backup_logs`
| Kolom            | Tipe       | Keterangan                     |
|------------------|------------|--------------------------------|
| id               | bigint PK  | Auto increment                 |
| bpjs_claims_id   | bigint FK  | Relasi ke bpjs_claims (nullable)|
| source_path      | varchar    | Path file sumber               |
| backup_path      | varchar    | Path file backup (nullable)    |
| file_type        | varchar    | `merged`, `lip`, `raw`         |
| file_size        | bigint     | Ukuran file (nullable)         |
| status           | enum       | `pending`, `success`, `failed` |
| error_message    | text       | Pesan error (nullable)         |
| retry_count      | int        | Default 0                      |
| completed_at     | timestamp  | Nullable                       |
| created_at       | timestamp  |                                |
| updated_at       | timestamp  |                                |

### Tabel `app_settings`
| Kolom            | Tipe       | Keterangan                     |
|------------------|------------|--------------------------------|
| id               | bigint PK  | Auto increment                 |
| key              | varchar    | Unik, contoh: `clinic_name`   |
| value            | text       | Nullable                       |
| type             | varchar    | `string`, `boolean`, `integer`, `json` |
| group            | varchar    | `general`, `clinic`, `storage` |
| description      | text       | Nullable                       |
| created_at       | timestamp  |                                |
| updated_at       | timestamp  |                                |

---

## Tech Stack

| Kebutuhan           | Package                             | Kenapa dipilih                              |
|---------------------|-------------------------------------|---------------------------------------------|
| Web framework       | `express`                           | Paling populer, simple, banyak tutorial     |
| Database ORM        | `prisma`                            | Type-safe, auto-generate dari DB yang ada   |
| Auth (JWT)          | `jsonwebtoken` + `bcryptjs`         | Standard industri untuk REST API            |
| File upload         | `multer`                            | Middleware upload file untuk Express         |
| PDF merge           | `pdf-lib`                           | Pure JS, tidak perlu install software lain  |
| PDF text extract    | `pdf-parse`                         | Baca teks dari PDF, pure JS                 |
| Background job      | **Simple async** (tanpa Redis)      | Cukup untuk volume klaim klinik             |
| Validasi input      | `zod`                               | Validasi schema yang simple & type-safe     |
| Environment         | `dotenv`                            | Baca file `.env`                            |
| Logging             | `winston`                           | Logger yang kuat, bisa ke file/console      |
| CORS                | `cors`                              | Izinkan React frontend akses API            |
| Date handling       | `dayjs`                             | Ringan, mirip Carbon di Laravel             |
| Testing             | `jest` + `supertest`                | Standard testing untuk Express API          |

---

## Struktur Folder Project

```
fast-claim-backend/
├── prisma/
│   ├── schema.prisma          ← Schema database (auto-generate dari MySQL)
│   └── seed.js                ← Seeder: insert user admin & app settings
├── src/
│   ├── index.js               ← Entry point: start Express server
│   ├── app.js                 ← Express app setup (middleware, routes)
│   ├── config/
│   │   ├── database.js        ← Prisma client setup
│   │   ├── storage.js         ← Path folder shared & backup
│   │   └── auth.js            ← JWT secret, token expiry
│   ├── middleware/
│   │   ├── auth.js            ← Cek JWT token valid
│   │   ├── adminOnly.js       ← Cek user role = admin
│   │   ├── upload.js          ← Multer config untuk upload PDF
│   │   └── errorHandler.js    ← Global error handler
│   ├── routes/
│   │   ├── auth.routes.js     ← Login, register, logout
│   │   ├── claims.routes.js   ← CRUD klaim + upload + merge
│   │   ├── dashboard.routes.js← Statistik dashboard
│   │   ├── backup.routes.js   ← Status backup (admin only)
│   │   └── settings.routes.js ← App settings (admin only)
│   ├── controllers/
│   │   ├── auth.controller.js
│   │   ├── claims.controller.js
│   │   ├── dashboard.controller.js
│   │   ├── backup.controller.js
│   │   └── settings.controller.js
│   ├── services/
│   │   ├── pdfRead.service.js       ← Baca teks dari PDF
│   │   ├── pdfMerger.service.js     ← Gabung banyak PDF jadi satu
│   │   ├── sepDataProcessor.service.js ← Parse teks SEP → data terstruktur
│   │   ├── fileUpload.service.js    ← Simpan file upload ke disk
│   │   ├── folderGenerator.service.js ← Bikin path folder sesuai format BPJS
│   │   └── backup.service.js        ← Copy file ke folder backup (async)
│   ├── validators/
│   │   ├── auth.validator.js  ← Validasi login/register input
│   │   ├── claims.validator.js← Validasi data klaim
│   │   └── settings.validator.js
│   └── utils/
│       ├── response.js        ← Helper format response JSON
│       └── logger.js          ← Winston logger setup
├── uploads/                   ← Folder temp untuk file yang diupload
├── tests/
│   ├── auth.test.js           ← Test login, register, token
│   ├── claims.test.js         ← Test CRUD klaim
│   ├── dashboard.test.js      ← Test statistik
│   └── setup.js               ← Setup test database
├── .env                       ← Environment variables
├── .env.example               ← Contoh env
├── package.json
├── jest.config.js             ← Jest configuration
└── README.md
```

---

## Tahapan Implementasi (9 Fase)

---

### FASE 1: Setup Project & Database Connection

**Tujuan**: Buat project Node.js kosong, install semua package, dan hubungkan ke database MySQL yang sudah ada.

**Langkah-langkah**:
1. `npm init -y` → buat project
2. Install semua dependencies (express, prisma, dll.)
3. Buat file `.env` dengan konfigurasi database
4. Jalankan `npx prisma db pull` → Prisma baca tabel MySQL dan bikin schema otomatis
5. Jalankan `npx prisma generate` → Prisma bikin client untuk query
6. Buat Express server basic yang jalan di port 3000
7. Test: `GET /api/health` → return `{ status: "ok" }`

**File yang dibuat**:
- `package.json`
- `.env` & `.env.example`
- `prisma/schema.prisma` (auto-generated)
- `src/index.js` — start server
- `src/app.js` — Express app config
- `src/config/database.js` — Prisma client
- `src/config/storage.js` — folder paths
- `src/config/auth.js` — JWT config
- `src/utils/logger.js` — Winston logger
- `src/utils/response.js` — response helpers

**Cara verifikasi**: Server start tanpa error, `GET /api/health` return 200.

---

### FASE 2: Authentication (Login & Register)

**Tujuan**: User bisa login pakai email + password dan dapat token JWT.

**Alur login**:
```
User kirim POST /api/auth/login { email, password }
       ↓
Cari user di tabel `users` berdasarkan email
       ↓
Bandingkan password pakai bcrypt.compare()
       ↓
Cocok → buat JWT token → kirim ke user
Tidak cocok → kirim error 401 Unauthorized
```

**File yang dibuat**:
- `src/routes/auth.routes.js`
- `src/controllers/auth.controller.js`
- `src/middleware/auth.js` — cek JWT di header `Authorization: Bearer <token>`
- `src/middleware/adminOnly.js` — cek `user.role === 'admin'`
- `src/validators/auth.validator.js` — validasi input dengan Zod

**API Endpoints**:
| Method | URL                | Body                          | Response                   |
|--------|--------------------|-------------------------------|----------------------------|
| POST   | `/api/auth/login`  | `{ email, password }`         | `{ token, user }`          |
| POST   | `/api/auth/register`| `{ name, email, password }`  | `{ token, user }`          |
| GET    | `/api/auth/me`     | — (pakai token di header)     | `{ user }`                 |
| POST   | `/api/auth/logout` | — (pakai token di header)     | `{ message }`              |

**Penting**: Password di database lama pakai bcrypt dari Laravel. Package `bcryptjs` **kompatibel**, jadi user lama bisa login tanpa reset password.

**Cara verifikasi**: Login dengan user dari database lama → dapat token → pakai token di `/api/auth/me` → dapat data user.

---

### FASE 3: Claims CRUD (Tanpa Upload Dulu)

**Tujuan**: API untuk lihat daftar klaim, detail klaim, dan hapus klaim. Belum termasuk upload file.

**File yang dibuat**:
- `src/routes/claims.routes.js`
- `src/controllers/claims.controller.js`
- `src/validators/claims.validator.js`

**API Endpoints**:
| Method | URL                            | Keterangan                                       |
|--------|--------------------------------|--------------------------------------------------|
| GET    | `/api/claims`                  | Daftar klaim (paginasi, filter jenis/bulan/tahun)|
| GET    | `/api/claims/:id`              | Detail satu klaim                                |
| DELETE | `/api/claims/:id`              | Hapus klaim (admin only)                         |
| GET    | `/api/claims/:id/download`     | Download file merged PDF                         |
| GET    | `/api/claims/:id/download-lip` | Download file LIP                                |

**Query filter** (via query string):
- `?page=1&limit=10` — paginasi
- `?jenis_rawatan=RJ` — filter rawat jalan/inap
- `?month=12&year=2025` — filter bulan/tahun
- `?search=nama_pasien` — cari berdasarkan nama atau no_sep

**Cara verifikasi**: `GET /api/claims` return list klaim dari database yang sudah ada.

---

### FASE 4: PDF Processing (Upload, Extract, Merge)

**Tujuan**: Upload file SEP → baca datanya otomatis → upload dokumen lain → gabung jadi satu PDF.

**Ini inti dari seluruh aplikasi.**

**File yang dibuat**:
- `src/middleware/upload.js` — Multer config
- `src/services/pdfRead.service.js` — baca teks dari PDF pakai `pdf-parse`
- `src/services/sepDataProcessor.service.js` — parse teks SEP → data terstruktur
- `src/services/pdfMerger.service.js` — gabung banyak PDF pakai `pdf-lib`
- `src/services/fileUpload.service.js` — simpan file ke disk
- `src/services/folderGenerator.service.js` — bikin path folder sesuai format BPJS

**Alur lengkap**:
```
STEP 1: Upload SEP
──────────────────
POST /api/claims/upload-sep (kirim file PDF)
  → Multer simpan file ke folder /uploads/temp/
  → pdfRead.service baca teks dari PDF
  → sepDataProcessor parse teks → { no_sep, nama, tanggal, kelas, jenis }
  → Kirim data hasil extract ke frontend (belum simpan ke DB)

STEP 2: Process & Merge
────────────────────────
POST /api/claims/process (kirim data + semua file PDF)
  → Terima: data SEP + file resume + billing + lab + LIP
  → Cek duplikat no_sep di database
  → folderGenerator bikin path output folder
  → pdfMerger gabung semua PDF (kecuali LIP) jadi satu
  → Simpan merged PDF ke folder shared
  → Simpan LIP terpisah ke folder yang sama
  → Simpan record ke tabel bpjs_claims
  → Trigger backup async (fase 5)
  → Bersihkan file temp
  → Kirim response sukses
```

**API Endpoints baru**:
| Method | URL                        | Body                    | Response                |
|--------|----------------------------|-------------------------|-------------------------|
| POST   | `/api/claims/upload-sep`   | File SEP (multipart)    | `{ extractedData }`    |
| POST   | `/api/claims/process`      | Data + semua file PDF   | `{ claim, message }`   |

**Regex SEP yang dipakai** (dipindahkan dari Laravel):
- `No.SEP` → `/No\.SEP\s*:\s*([\w\/\.-]+)/i`
- `Tgl.SEP` → `/Tgl\.SEP\s*:\s*([0-9]{4}-[0-9]{2}-[0-9]{2})/i`
- `No.Kartu` → `/No\.Kartu\s*:\s*([0-9]+)/i`
- `Nama Peserta` → `/Nama\s*Peserta\s*:\s*([^\n]+)/i`
- `Kelas Rawat` → `/\bKELAS\s*([1-3])\b/i`
- `Jenis Rawat` → `/\bR\.(?:Jalan|Inap)\b/i`

**Cara verifikasi**: Upload `SEP-DUMMY.pdf` → data ter-extract sama persis seperti di Laravel. Merge → file ada di folder shared.

---

### FASE 5: Backup Service (Simple Async)

**Tujuan**: Setiap klaim disimpan → file otomatis dicopy ke folder backup di background.

**File yang dibuat**:
- `src/services/backup.service.js`

**Cara kerja** (tanpa Redis, pakai async biasa):
```javascript
// Di claims.controller.js, setelah klaim berhasil disimpan:
// Jalankan backup di background (TIDAK ditunggu)
backupService.backupFile({
  sourcePath: finalPath,
  lipPath: lipPath,
  claimId: claim.id
});
// Response langsung dikirim ke user tanpa menunggu backup selesai
```

**Backup service logic**:
1. Buat record di `backup_logs` dengan status `pending`
2. Copy file dari folder shared ke folder backup (pakai `fs.copyFile`)
3. Kalau sukses → update status jadi `success`, catat file_size & completed_at
4. Kalau gagal → update status jadi `failed`, catat error_message, increment retry_count
5. Kalau gagal dan retry_count < 3 → coba lagi setelah 1 detik

**Cara verifikasi**: Simpan klaim → cek `backup_logs` → harus ada entry dengan status `success`.

---

### FASE 6: Dashboard API

**Tujuan**: API untuk data statistik yang ditampilkan di dashboard.

**File yang dibuat**:
- `src/routes/dashboard.routes.js`
- `src/controllers/dashboard.controller.js`

**API Endpoint**:
| Method | URL                  | Response                                    |
|--------|----------------------|---------------------------------------------|
| GET    | `/api/dashboard`     | Statistik klaim + backup bulan ini          |

**Data yang dikembalikan**:
```json
{
  "totalClaimsThisMonth": 150,
  "totalRawatJalan": 120,
  "totalRawatInap": 30,
  "todaysClaims": 5,
  "backup": {
    "totalSuccess": 145,
    "totalFailed": 3,
    "totalPending": 2,
    "lastBackupAt": "2025-12-15T10:30:00Z"
  },
  "monthlyTrend": [
    { "month": "Jan", "count": 80 },
    { "month": "Feb", "count": 95 }
  ]
}
```

**Cara verifikasi**: `GET /api/dashboard` → angka-angka cocok dengan yang ada di database.

---

### FASE 7: Settings & Backup Dashboard API (Admin Only)

**Tujuan**: API untuk admin mengelola pengaturan dan melihat status backup.

**File yang dibuat**:
- `src/routes/settings.routes.js`
- `src/controllers/settings.controller.js`
- `src/routes/backup.routes.js`
- `src/controllers/backup.controller.js`
- `src/validators/settings.validator.js`

**API Endpoints**:
| Method | URL                        | Keterangan                        | Akses      |
|--------|----------------------------|-----------------------------------|------------|
| GET    | `/api/settings/:group`     | Ambil settings by group           | Admin only |
| PUT    | `/api/settings`            | Update satu/banyak setting        | Admin only |
| GET    | `/api/backup/logs`         | Daftar backup logs (paginasi)     | Admin only |
| POST   | `/api/backup/retry/:id`    | Retry backup yang gagal           | Admin only |

**Cara verifikasi**: Login sebagai admin → akses settings → update → baca lagi → nilai berubah. Login sebagai operator → akses settings → ditolak (403).

---

### FASE 8: Error Handling, Logging & Polish

**Tujuan**: Rapikan error handling supaya setiap error punya format konsisten. Tambahkan logging ke file.

**File yang dibuat/diupdate**:
- `src/middleware/errorHandler.js` — global error handler
- Update `src/utils/response.js` — helper response
- Update `src/utils/logger.js` — log ke console + file

**Format response sukses**:
```json
{
  "success": true,
  "data": { ... },
  "message": "Klaim berhasil disimpan"
}
```

**Format response error**:
```json
{
  "success": false,
  "error": "Nomor SEP sudah terdaftar",
  "code": "DUPLICATE_SEP"
}
```

**Error codes yang dipakai**:
| Code                | HTTP Status | Kapan terjadi                     |
|---------------------|-------------|-----------------------------------|
| `VALIDATION_ERROR`  | 400         | Input tidak valid                 |
| `UNAUTHORIZED`      | 401         | Belum login / token expired       |
| `FORBIDDEN`         | 403         | Role tidak punya akses            |
| `NOT_FOUND`         | 404         | Data tidak ditemukan              |
| `DUPLICATE_SEP`     | 409         | No SEP sudah ada di database      |
| `PDF_EXTRACT_FAILED`| 422         | Gagal baca data dari PDF          |
| `PDF_MERGE_FAILED`  | 500         | Gagal merge PDF                   |
| `BACKUP_FAILED`     | 500         | Gagal backup file                 |
| `INTERNAL_ERROR`    | 500         | Error yang tidak terduga          |

**Cara verifikasi**: Kirim request yang salah → format error konsisten. Cek file log → semua aktivitas tercatat.

---

### FASE 9: Seeder Script

**Tujuan**: Script untuk insert data awal ke database — user admin, operator, dan app settings.

**File yang dibuat**:
- `prisma/seed.js`

**Data yang di-seed** (sama persis dengan Laravel):

**Users**:
| Name            | Email                   | Password       | Role     |
|-----------------|-------------------------|----------------|----------|
| Asta            | astareyhan@gmail.com    | admin1234      | admin    |
| Admin Dummy     | test@example.com        | password        | admin    |
| Operator        | operator@kubr.local     | operator123    | operator |
| Syafrul Andri   | syafrulandri@gmail.com  | Almonda70@#    | operator |

**App Settings**:
| Key             | Value                                          | Type    | Group   |
|-----------------|-------------------------------------------------|---------|---------|
| clinic_name     | Klinik Utama Bukit Raya                         | string  | clinic  |
| clinic_code     | (kosong)                                        | string  | clinic  |
| clinic_address  | (kosong)                                        | string  | clinic  |
| clinic_phone    | (kosong)                                        | string  | clinic  |
| folder_shared   | Z:/FOLDER KLAIM REGULER BPJS SINTA             | string  | storage |
| folder_backup   | D:/Backup Folder Klaim BPJS/Folder Klaim Reguler BPJS | string | storage |
| auto_backup     | 1                                               | boolean | storage |
| app_name        | FastClaim                                       | string  | general |

**Cara verifikasi**: Jalankan seed → cek database → data ada.

---

## Semua API Endpoints (Ringkasan)

### Auth
| Method | URL                  | Body                         | Akses   |
|--------|----------------------|------------------------------|---------|
| POST   | `/api/auth/login`    | `{ email, password }`        | Public  |
| POST   | `/api/auth/register` | `{ name, email, password }`  | Public  |
| GET    | `/api/auth/me`       | —                            | Auth    |
| POST   | `/api/auth/logout`   | —                            | Auth    |

### Claims
| Method | URL                              | Akses   |
|--------|----------------------------------|---------|
| GET    | `/api/claims`                    | Auth    |
| GET    | `/api/claims/:id`                | Auth    |
| POST   | `/api/claims/upload-sep`         | Auth    |
| POST   | `/api/claims/process`            | Auth    |
| DELETE | `/api/claims/:id`                | Admin   |
| GET    | `/api/claims/:id/download`       | Auth    |
| GET    | `/api/claims/:id/download-lip`   | Auth    |

### Dashboard
| Method | URL                  | Akses   |
|--------|----------------------|---------|
| GET    | `/api/dashboard`     | Auth    |

### Settings
| Method | URL                      | Akses   |
|--------|--------------------------|---------|
| GET    | `/api/settings/:group`   | Admin   |
| PUT    | `/api/settings`          | Admin   |

### Backup
| Method | URL                      | Akses   |
|--------|--------------------------|---------|
| GET    | `/api/backup/logs`       | Admin   |
| POST   | `/api/backup/retry/:id`  | Admin   |

---

## Urutan Eksekusi

```
FASE 1 → Setup project, install packages, koneksi database
         ↓
FASE 2 → Auth (login/register/JWT)
         ↓
FASE 3 → Claims CRUD (list/detail/delete/download)
         ↓
FASE 4 → PDF Processing (upload SEP, extract, merge)
         ↓
FASE 5 → Backup Service (async copy file)
         ↓
FASE 6 → Dashboard API (statistik)
         ↓
FASE 7 → Settings & Backup Dashboard API
         ↓
FASE 8 → Error Handling & Logging
         ↓
FASE 9 → Seeder Script
         ↓
       ✅ BACKEND SELESAI → Lanjut ke React Frontend
```

---

## File .env Yang Dibutuhkan

```env
# Database
DATABASE_URL="mysql://root:password@localhost:3306/fast_claim"

# JWT
JWT_SECRET=your-secret-key-here
JWT_EXPIRES_IN=7d

# Server
PORT=3000
NODE_ENV=development

# Storage Paths
FOLDER_SHARED=D:/Folder Klaim BPJS
FOLDER_BACKUP=D:/Backup Klaim BPJS

# CORS
FRONTEND_URL=http://localhost:5173
```

---

## Testing Plan (Jest + Supertest)

Setiap fase punya test sendiri:

| Fase  | Test file            | Yang ditest                                    |
|-------|----------------------|------------------------------------------------|
| 2     | `auth.test.js`       | Login, register, token validation, role check  |
| 3     | `claims.test.js`     | List, detail, delete, download, pagination     |
| 4     | `pdf.test.js`        | Upload SEP, extract data, merge PDF            |
| 5     | `backup.test.js`     | Backup file, retry, log status                 |
| 6     | `dashboard.test.js`  | Statistik klaim, backup summary                |
| 7     | `settings.test.js`   | CRUD settings, admin-only access               |

**Cara jalankan test**:
```bash
# Jalankan semua test
npm test

# Jalankan satu file test
npx jest tests/auth.test.js

# Jalankan test dengan watch mode (auto re-run saat file berubah)
npx jest --watch
```
