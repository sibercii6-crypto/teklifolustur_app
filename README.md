# Teklif Yönetim Sistemi

Bu proje, kullanıcıların müşterileri için teklifler oluşturmasını, yönetmesini ve takip etmesini sağlayan basit bir web tabanlı PHP uygulamasıdır.

## Özellikler

- **Kullanıcı Yönetimi**:
  - Güvenli parola saklama (`password_hash`) ile kullanıcı kaydı.
  - `password_verify` ile güvenli kullanıcı girişi.
  - Kullanıcıların kendi parolalarını güncelleyebileceği profil yönetimi.

- **Teklif Yönetimi (CRUD)**:
  - Teklif oluşturma, düzenleme, silme ve görüntüleme.
  - Teklif paneli üzerinden tüm teklifleri listeleme ve arama.

- **Çoklu Ürün Desteği**:
  - Her bir teklife birden fazla ürün veya hizmet kalemi ekleyebilme.
  - Dinamik olarak yeni kalemler ekleme ve mevcutları silme.

- **Ürün Şablonları**:
  - Sık kullanılan ürün/hizmetleri sisteme kaydetme.
  - Teklif oluştururken bu hazır ürünleri seçerek açıklama ve fiyat alanlarını otomatik doldurma.
  - Teklif oluşturma ekranından hızlıca yeni ürün ekleyebilme (AJAX ile).

- **PDF Oluşturma**:
  - Oluşturulan teklifleri profesyonel görünümlü bir PDF formatında indirme (`FPDF` kütüphanesi kullanılmıştır).

- **Arama ve Filtreleme**:
  - Teklif panelinde müşteri adına göre hızlı arama yapma.

## Kullanılan Teknolojiler

- **Backend**: PHP
- **Veritabanı**: MySQL
- **PDF Kütüphanesi**: FPDF

## Kurulum Talimatları

Projeyi yerel makinenizde çalıştırmak için aşağıdaki adımları izleyin.

### Gereksinimler

- [XAMPP](https://www.apachefriends.org/tr/index.html), WAMP veya benzeri bir PHP/MySQL sunucu ortamı.
- Web tarayıcısı (Chrome, Firefox, vb.).

### Adımlar

1.  **Projeyi Klonlayın veya İndirin**:
    ```bash
    git clone [bu-repository-adresi]
    ```
    Veya dosyaları ZIP olarak indirip `xampp/htdocs/` altında bir klasöre (örn: `order_app`) çıkarın.

2.  **Veritabanını Kurun**:
    - XAMPP kontrol panelinden Apache ve MySQL'i başlatın.
    - Tarayıcınızdan `http://localhost/phpmyadmin` adresine gidin.
    - Yeni bir veritabanı oluşturun ve adını `order_db` olarak belirleyin.
    - `order_db` veritabanını seçin ve üst menüden **"İçe Aktar" (Import)** sekmesine tıklayın.
    - **"Dosya Seç"** butonuna basarak proje klasörünün içindeki `database.sql` dosyasını seçin.
    - Sayfanın altındaki **"Git" (Go)** butonuna basarak veritabanı şemasını ve örnek verileri içe aktarın.

3.  **Uygulamayı Çalıştırın**:
    - Tarayıcınızda projenin bulunduğu adresi açın. (Örn: `http://localhost/order_app/login.php`)

4.  **Giriş Bilgileri**:
    - Başlamak için kendi kullanıcınızı ekleyebilirsiniz.


---


