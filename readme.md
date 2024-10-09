## Kurulum
Projeyi klonlayın

```bash
  git clone https://github.com/haliltuksal/to_do_c.git
```

to_do_c dizinine gidin

```bash
  cd to_do_c
```

Bağımlılıkları yükleyin

```bash
  composer install
```

**Verileri Veritabanına Aktarın**

```bash
  php bin/console doctrine:fixtures:load
```
## Kullanım

API istekleri için http://localhost:8001 adresini kullanın.

```bash
php -S localhost:8001 -t public
```

## Komutlar

```bash
php bin/console app:assign-tasks
#- Mock dosyalarından görevleri yükler.
#- Planlama algoritmasını çalıştırır ve görevleri geliştiricilere atar.
```
