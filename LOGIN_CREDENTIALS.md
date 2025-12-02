# Login Credentials untuk Testing

Berikut adalah kredensial login yang valid untuk testing aplikasi HelpDesk Kemlu:

## 1. Admin Helpdesk
- **NIP**: 198001012005011001
- **Password**: admin123
- **Nama**: Dr. Ahmad Surya Wijaya, M.T.
- **Email**: ahmad.wijaya@kemlu.go.id
- **Role**: Admin Helpdesk (Kepala Bagian Helpdesk)

## 2. Admin Aplikasi
- **NIP**: 197501012000011001
- **Password**: admin123
- **Nama**: Dr. Ir. Hendro Wicaksono, M.T., Ph.D.
- **Email**: hendro.wicaksono@kemlu.go.id
- **Role**: Admin Aplikasi (Kepala Pusat Data dan Informasi)

## 3. Teknisi
- **NIP**: 199001012015011001
- **Password**: password123
- **Nama**: Andi Wijaya, S.Kom.
- **Email**: andi.wijaya@kemlu.go.id
- **Role**: Teknisi (Senior IT Support Specialist)

## 4. User (Pegawai)
- **NIP**: 198501012010011001
- **Password**: password123
- **Nama**: Budi Santoso
- **Email**: budi.santoso@kemlu.go.id
- **Role**: User/Pegawai (Diplomat Madya)

---

## Kredensial Tambahan

### Admin Helpdesk Lainnya:
1. NIP: 198102152005012002 | Password: admin123 (Ir. Siti Rahayu, M.Kom.)
2. NIP: 198203202005013003 | Password: admin123 (Drs. Budi Hermawan, M.Si.)
3. NIP: 198304252005014004 | Password: admin123 (Maya Fitriani, S.Kom., M.T.)
4. NIP: 198405102005015005 | Password: admin123 (Agus Santoso, S.T., M.T.)

### Teknisi Lainnya:
1. NIP: 199102152015012002 | Password: password123 (Budi Santoso, S.T.)
2. NIP: 199203202015013003 | Password: password123 (Citra Kirana, S.Kom.)
3. NIP: 199304252015014004 | Password: password123 (Doni Ramadhan, S.T.)
4. NIP: 199405102015015005 | Password: password123 (Eva Sari Dewi, S.Kom.)

### User/Pegawai Lainnya:
1. NIP: 198602152010012002 | Password: password123 (Siti Nurhaliza)
2. NIP: 198703202010013003 | Password: password123 (Agus Setiawan)
3. NIP: 198804252010014004 | Password: password123 (Dewi Sartika)
4. NIP: 198905102010015005 | Password: password123 (Rizki Ramadhan)

---

## Fitur Keamanan

### Rate Limiting:
- Maximum 5 login attempts per 15 minutes
- Account will be locked for 30 minutes after exceeding limit

### Session Management:
- Session timeout: 120 minutes (2 hours)
- Session warning: 10 minutes before expiry
- Maximum concurrent sessions: 3 per user

---

## Cara Login

1. Buka browser dan akses: `http://localhost:8000/login`
2. Masukkan NIP (tanpa spasi)
3. Masukkan password sesuai role
4. Klik tombol "Masuk"

## Troubleshooting

### Jika login gagal:
1. **Pastikan NIP dan password benar** (case-sensitive)
2. **Cek status akun** - harus `active`
3. **Clear browser cache dan cookies**
4. **Cek database** dengan menjalankan: `php test_login.php`
5. **Re-seed database** jika diperlukan:
   ```bash
   php artisan db:seed --class=UserSeeder
   php artisan db:seed --class=AdminHelpdeskSeeder
   php artisan db:seed --class=TeknisiSeeder
   ```

### Jika akun terkunci:
Tunggu 30 menit atau clear rate limiting cache:
```bash
php artisan cache:clear
```

---

## Development Notes

- All passwords are hashed using bcrypt
- NIP is used as username (primary key)
- Multi-table authentication (users, admin_helpdesks, admin_aplikasis, teknisis)
- Role-based access control (RBAC)
