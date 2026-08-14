# FastClaim

Aplikasi pengelolaan berkas klaim BPJS: mengekstrak data dari SEP, menggabungkan dokumen pendukung menjadi satu PDF, menyimpan dengan struktur folder standar BPJS, dan mem-backup otomatis.

## Language

**SEP**:
Surat Eligibilitas Peserta; dokumen eligibilitas BPJS yang menjadi sumber data klaim (Nomor SEP, Tanggal SEP, Nama Peserta, Kelas).
_Avoid_: Surat kelayakan

**LIP**:
Lembar Informasi Pelayanan; dokumen info detail pelayanan per klaim, digabung di halaman akhir berkas klaim.
_Avoid_: Lembar informasi pasien

**Nomor SEP**:
Identitas unik satu klaim; juga menjadi nama file berkas klaim (`{NO_SEP}.pdf`).
_Avoid_: no_sep, kode SEP

**Jenis Rawatan**:
Tipe pelayanan klaim: `R.JALAN` (RJ, rawat jalan) atau `R.INAP` (RI, rawat inap).
_Avoid_: tipe rawatan, jenis perawatan

**Periode Klaim**:
Folder periode penagihan klaim, format `MM_BULAN REGULER YYYY` (mis. `07_JULI REGULER 2026`).
_Avoid_: periode billing, bulan klaim

**Berkas Klaim**:
Satu file PDF gabungan untuk satu klaim (SEP, resume medis, hasil lab, billing, LIP), tersimpan dengan struktur `{Periode Klaim}/{Jenis Rawatan}/{Nomor SEP}.pdf`.
_Avoid_: dokumen klaim, file klaim
