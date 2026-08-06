# Klarifikasi Implementasi: Status `calculated` dan Kriteria 18.3.5

**Tanggal:** 6 Agustus 2026
**Sumber:** Audit implementasi terhadap `2026-08-04-ueq-saw-ta-mvp-design.md` (kode pada branch `master`).
**Sifat:** Klarifikasi dokumentasi — TIDAK mengubah kebutuhan fungsional, aturan bisnis, atau spesifikasi perhitungan.

## 1. Status `calculated` pada bagian 9.1

Desain mendefinisikan status periode `draft`, `active`, `closed`, `calculated`, dan `locked`, dengan catatan "Periode calculated dapat mempunyai beberapa calculation run."

**Implementasi aktual:** preview calculation run dibuat ketika periode berstatus `closed`; periode berpindah langsung dari `closed` ke `locked` ketika satu run ditetapkan sebagai hasil resmi (`CalculationRunService::lockAsOfficial`). Nilai `calculated` tetap ada pada enum `App\Domain\Study\PeriodStatus` untuk kompatibilitas, tetapi tidak pernah di-assign.

**Justifikasi:** perilaku fungsional identik dengan desain — beberapa preview run tetap dimungkinkan (pada status `closed`), run lama ditandai stale oleh perubahan input, dan kelayakan penguncian ditegakkan oleh `OfficialRunEligibility` yang mensyaratkan periode `closed`. Menghapus transisi terpisah ke `calculated` menyederhanakan state machine tanpa mengurangi jejak audit.

**Keputusan:** diterima sebagai deviasi kosmetik. Apabila Bab IV mengutip diagram status dari desain, gunakan penjelasan ini bahwa `calculated` direpresentasikan oleh periode `closed` yang telah memiliki minimal satu calculation run.

## 2. Kriteria penerimaan 18.3.5: "dikunci dan dibuka kembali"

Kriteria 18.3.5 berbunyi: "Satu run resmi dapat dikunci dan dibuka kembali tanpa perubahan angka."

**Implementasi aktual:** frasa "dibuka kembali" diinterpretasikan sebagai *dibuka kembali untuk ditinjau* (read), bukan *unlock* (membatalkan status resmi):

- Satu run resmi dapat dikunci; hasilnya immutable — mengubah `saw_results`, `ueq_results`, `ueq_pooled_results`, `sensitivity_results`, atau `expert_judgments` pada run official melempar `LogicException` (`CalculationResultImmutabilityTest`).
- Run official dapat dibuka kembali untuk ditinjau kapan pun melalui pemilihan run di halaman Kalkulasi, dan angkanya dijamin tidak berubah karena immutability di atas.
- Tidak ada fitur unlock. Hal ini konsisten dengan aturan bisnis 10.13 ("Run locked tidak dapat ditimpa; koreksi menghasilkan run baru") — koreksi dilakukan dengan membuat calculation run baru, bukan dengan membuka kunci run resmi.

**Keputusan:** interpretasi ini selaras dengan aturan bisnis 10.13; interpretasi alternatif (unlock lalu lock ulang) akan melanggar immutability hasil resmi. Jika pembimbing menghendaki fitur unlock eksplisit, itu adalah perubahan kebutuhan baru di luar MVP dan harus dicatat sebagai pekerjaan pasca-TA bersama mekanisme auditnya.
