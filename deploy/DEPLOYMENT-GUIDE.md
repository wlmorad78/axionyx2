# دليل نشر Axionyx ERP على Ubuntu Server

## المتطلبات
- سيرفر Ubuntu 22.04/24.04 (عندك: `207.231.110.79`)
- Domain: `vps-4725-2d60766e.wpressly.com`
- SSH access للسيرفر

---

## الخطوة 1: ربط الـ Domain بالسيرفر

اذهب إلى **DNS settings** للـ Domain وأضف:

```
Type: A
Name: @ (or vps-4725-2d60766e)
Value: 207.231.110.79
TTL: 3600
```

---

## الخطوة 2: الاتصال بالسيرفر

```bash
ssh root@207.231.110.79
# أو
ssh ubuntu@207.231.110.79
```

---

## الخطوة 3: تثبيت البرامج

```bash
# شغّل السكربت ده على السيرفر
bash deploy/server-setup.sh
```

**اللي بيعمله:**
- PHP 8.3 + الـ extensions المطلوبة
- Nginx (ويب سيرفر)
- Composer
- Node.js 20
- Supervisor (لتشغيل الـ queue)
- SQLite
- Git

---

## الخطوة 4: رفع المشروع على السيرفر

### من جهازك المحلي (Windows):

```bash
# انسخ المشروع للسيرفر
scp -r ./* root@207.231.110.79:/var/www/axionyx/
```

**ملاحظة:** استبعد الـ `vendor/` و `node_modules/` عشان تقلل الحجم:
```bash
scp -r --exclude='vendor' --exclude='node_modules' --exclude='.git' ./* root@207.231.110.79:/var/www/axionyx/
```

### أو استخدم Git:

```bash
# على السيرفر
cd /var/www/axionyx
git clone https://github.com/yourusername/axionyx-erp.git .
```

---

## الخطوة 5: الإعداد على السيرفر

```bash
ssh root@207.231.110.79

# ادخل على المجلد
cd /var/www/axionyx

# شغّل سكربت النشر
bash deploy/deploy-app.sh
```

---

## الخطوة 6: تعديل ملف .env

```bash
nano /var/www/axionyx/.env
```

**غيّر القيم دي:**

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://vps-4725-2d60766e.wpressly.com
APP_KEY=base64:...  # يتم إنشاؤه تلقائياً
```

**النافذة:** اضغط `Ctrl+X` ثم `Y` ثم `Enter` للحفظ

---

## الخطوة 7: إعداد Nginx

```bash
# انسخ الـ config
sudo cp deploy/nginx.conf /etc/nginx/sites-available/axionyx

# فعّل الـ site
sudo ln -s /etc/nginx/sites-available/axionyx /etc/nginx/sites-enabled/

# احذف الـ default
sudo rm /etc/nginx/sites-enabled/default

# أعد تشغيل Nginx
sudo nginx -t
sudo systemctl restart nginx
```

---

## الخطوة 8: إعداد Queue Worker

```bash
# انسخ ملف Supervisor
sudo cp deploy/supervisor.conf /etc/supervisor/conf.d/axionyx-queue.conf

# أعد تحميل Supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start axionyx-queue:*
```

---

## الخطوة 9: إعداد SSL (HTTPS)

```bash
bash deploy/ssl-setup.sh
```

**ده هيربط شهادة SSL مجانية (Let's Encrypt) بموقعك**

---

## الخطوة 10: التحقق النهائي

### تحقق من Nginx:
```bash
sudo nginx -t
curl -I http://vps-4725-2d60766e.wpressly.com
```

### تحقق من الـ Queue:
```bash
sudo supervisorctl status
```

### تحقق من الأذونات:
```bash
ls -la /var/www/axionyx/storage/
ls -la /var/www/axionyx/bootstrap/cache/
```

---

## أوامر مهمة بعد النشر

```bash
# مسح الكاش
php artisan config:cache
php artisan route:cache
php artisan view:cache

# عرض الـ logs
tail -f storage/logs/laravel.log

# إعادة تشغيل Queue
sudo supervisorctl restart axionyx-queue:*

# تحديث المشروع
cd /var/www/axionyx
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache
sudo supervisorctl restart axionyx-queue:*
```

---

## حل المشاكل الشائعة

### 502 Bad Gateway:
```bash
# تأكد إن PHP-FPM شغال
sudo systemctl status php8.3-fpm
sudo systemctl restart php8.3-fpm
```

### Permission Denied:
```bash
sudo chown -R www-data:www-data /var/www/axionyx
sudo chmod -R 775 /var/www/axionyx/storage
sudo chmod -R 775 /var/www/axionyx/bootstrap/cache
```

### SQLite Permission Error:
```bash
touch /var/www/axionyx/database/database.sqlite
chmod 664 /var/www/axionyx/database/database.sqlite
chown www-data:www-data /var/www/axionyx/database/database.sqlite
```

---

## ملخص الأوامر السريعة

```bash
# 1. تثبيت البرامج
bash deploy/server-setup.sh

# 2. رفع المشروع
scp -r --exclude='vendor' --exclude='node_modules' ./* root@207.231.110.79:/var/www/axionyx/

# 3. الاتصال والإعداد
ssh root@207.231.110.79
cd /var/www/axionyx
bash deploy/deploy-app.sh

# 4. إعداد Nginx
sudo cp deploy/nginx.conf /etc/nginx/sites-available/axionyx
sudo ln -s /etc/nginx/sites-available/axionyx /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl restart nginx

# 5. إعداد Queue
sudo cp deploy/supervisor.conf /etc/supervisor/conf.d/axionyx-queue.conf
sudo supervisorctl reread && sudo supervisorctl update

# 6. SSL
bash deploy/ssl-setup.sh
```
