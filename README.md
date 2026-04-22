# web_gamon (Waste Management and Reporting System)

Frameworksüz (vanilla) bir **Waste Management and Reporting System** için temiz, modüler başlangıç iskeleti:

- **Citizens**: çöp birikimi raporlar
- **Personnel**: temizlik işlerini yürütür
- **Admins**: analitikleri izler, sistemi yönetir

Mimari **API-first**: sayfalar backend’e **JSON dönen web servisleri** üzerinden `fetch()` ile konuşur.

## Klasör Yapısı

```
web_gamon/
├── index.php
├── login.php
├── register.php
├── logout.php
├── dashboard.php
├── admin/
├── citizen/
├── personnel/
├── api/
├── assets/
├── config/
├── core/
├── includes/
├── database/
├── docs/
└── uploads/
```

## Kurulum (lokal)

1) PHP 8+ kurulu olsun.

2) Proje kökünde çalıştır:

```bash
php -S localhost:8000
```

3) Tarayıcı:

- `http://localhost:8000/register.php` ile kullanıcı oluştur
- `http://localhost:8000/login.php` ile giriş yap

> Veritabanı: `database/waste.db` (SQLite). Dosya yoksa otomatik oluşur, şema: `database/schema.sql`.

