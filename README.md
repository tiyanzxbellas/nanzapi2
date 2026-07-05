# NanzzAPI — Vercel Deployment

Project ini sudah direstrukturisasi agar bisa dijalankan di Vercel menggunakan
community PHP runtime ([vercel-community/php](https://github.com/vercel-community/php)).

## Struktur

```
.
├── vercel.json          # konfigurasi runtime PHP + CORS headers + rewrite "/"
├── 404.html
├── Mt.html
└── api/
    ├── index.php         # halaman dokumentasi API (auto-generate dari isi /api)
    ├── ai/
    ├── ai-image/
    ├── downloader/
    ├── game/
    ├── informasi/
    ├── maker/
    ├── random/
    ├── search/
    ├── tools/
    ├── uploader/
    └── videystream/      # (sebelumnya folder "source": index.php & list.php)
```

**Penting:** `index.php` (halaman dokumentasi) HARUS berada di dalam folder
`api/`, karena Vercel hanya mengenali file sebagai Serverless Function kalau
letaknya di dalam `api/`. `vercel.json` sudah diset supaya path `/` otomatis
di-rewrite ke `/api/index.php`, jadi pengunjung tetap buka domain utama
seperti biasa.

## Cara Deploy

1. Push folder ini ke repo GitHub.
2. Buka [vercel.com](https://vercel.com) → **Add New → Project** → import repo tersebut.
3. Vercel otomatis membaca `vercel.json` dan menjalankan setiap file di `api/**/*.php`
   dengan runtime `vercel-php@0.9.0`.
4. Klik **Deploy**. Setelah selesai, endpoint bisa diakses di:
   `https://<project-kamu>.vercel.app/api/<folder>/<file>.php`
5. Untuk menambah endpoint baru ke depannya: cukup tambah file `.php` baru
   di dalam `api/`, commit, push ke GitHub — Vercel akan auto redeploy.

## File yang SENGAJA tidak diikutkan

File-file berikut dikeluarkan dari project karena berpotensi disalahgunakan
untuk penipuan atau pelanggaran privasi, dan tidak diikutkan di paket ini:

- `source/cache.php` — berisi mekanisme bypass WAF/ModSecurity (ciri backdoor/webshell)
- `maker/fake-dana.php`, `maker/fake-ovo.php` — generator bukti transfer e-wallet palsu
- `ai/worm-gpt.php`, `ai/uncensored-ai.php` — akses ke AI yang dipasarkan untuk bypass filter keamanan
- `stalker/` (seluruh folder) — tool mengintai akun IG/TikTok/FF/Roblox orang lain
- `tools/nik.php` — lookup data pribadi berdasarkan NIK tanpa persetujuan

Jika kamu butuh fitur "tambah endpoint" yang aman, gunakan alur Git + Vercel
auto-deploy di atas — tidak perlu file manager tersembunyi di server.

## Catatan

- Beberapa endpoint memanggil layanan pihak ketiga (scraping) — pastikan
  domain-domain tersebut masih aktif dan tidak melanggar ToS mereka.
- `maxDuration` di `vercel.json` diset 30 detik; naikkan jika perlu (tergantung plan Vercel).
