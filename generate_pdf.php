<?php
require('fpdf/fpdf.php');
include "db.php";

// Kullanıcı girişi kontrolü
if (!isset($_SESSION['user_id'])) {
    die("Yetkisiz erişim!");
}

$user_id = $_SESSION['user_id'];
$offer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($offer_id <= 0) {
    die("Geçersiz teklif ID'si.");
}

// Teklif bilgilerini veritabanından çek
$stmt = mysqli_prepare($conn, "SELECT o.*, u.email FROM offers o JOIN users u ON o.user_id = u.id WHERE o.id = ? AND o.user_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $offer_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) != 1) {
    die("Teklif bulunamadı veya bu teklifi görüntüleme yetkiniz yok.");
}
$offer = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// --- PDF Oluşturma Başlangıcı ---

class PDF extends FPDF
{
    // Sayfa başlığı
    function Header()
    {
        // Arial bold 15
        $this->SetFont('Arial','B',15);
        // Sayfa ortasına başlık
        $this->Cell(80);
        $this->Cell(30,10,'Teklif Formu',0,0,'C');
        // Satır sonu
        $this->Ln(20);
    }

    // Sayfa altbilgisi
    function Footer()
    {
        // Sayfanın 1.5 cm altından pozisyon ayarla
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial','I',8);
        // Sayfa numarası
        $this->Cell(0,10,'Sayfa '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

// PDF objesi oluştur
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',12);

// Karakter encoding problemi için iconv kullanımı
function to_utf8($string) {
    return iconv('UTF-8', 'ISO-8859-9//TRANSLIT', $string);
}

// Müşteri Bilgileri
$pdf->SetFont('Arial','B',12);
$pdf->Cell(40,10, to_utf8('Müşteri:'));
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10, to_utf8($offer['customer_name']));
$pdf->Ln();

$pdf->SetFont('Arial','B',12);
$pdf->Cell(40,10, to_utf8('Tarih:'));
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10, date("d.m.Y", strtotime($offer['created_at'])));
$pdf->Ln();
$pdf->Ln(10); // Boşluk

// Teklif Detayları Tablosu
$pdf->SetFont('Arial','B',12);
$pdf->Cell(130,10, to_utf8('Açıklama'),1);
$pdf->Cell(20,10, to_utf8('Adet'),1);
$pdf->Cell(40,10, to_utf8('Birim Fiyat'),1);
$pdf->Ln();

$pdf->SetFont('Arial','',12);
$pdf->MultiCell(130,10, to_utf8($offer['description']),1);
$x = $pdf->GetX();
$y = $pdf->GetY();
$pdf->SetXY($x + 130, $y - 10); // MultiCell sonrası pozisyonu ayarla
$pdf->Cell(20,10, $offer['quantity'],1);
$pdf->Cell(40,10, number_format($offer['unit_price'], 2, ',', '.') . ' TL',1,0,'R');
$pdf->Ln();

// Toplamlar
$subtotal = $offer['quantity'] * $offer['unit_price'];
$tax_amount = $subtotal * ($offer['tax_rate'] / 100);

$pdf->Ln(10);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(150,10, to_utf8('Ara Toplam:'),0,0,'R');
$pdf->SetFont('Arial','',12);
$pdf->Cell(40,10, number_format($subtotal, 2, ',', '.') . ' TL',0,1,'R');

$pdf->SetFont('Arial','B',12);
$pdf->Cell(150,10, to_utf8('Vergi (%' . $offer['tax_rate'] . '):'),0,0,'R');
$pdf->SetFont('Arial','',12);
$pdf->Cell(40,10, number_format($tax_amount, 2, ',', '.') . ' TL',0,1,'R');

$pdf->SetFont('Arial','B',12);
$pdf->Cell(150,10, to_utf8('İskonto:'),0,0,'R');
$pdf->SetFont('Arial','',12);
$pdf->Cell(40,10, number_format($offer['discount'], 2, ',', '.') . ' TL',0,1,'R');

$pdf->SetFont('Arial','B',14);
$pdf->Cell(150,10, to_utf8('Genel Toplam:'),0,0,'R');
$pdf->Cell(40,10, number_format($offer['total_amount'], 2, ',', '.') . ' TL',0,1,'R');

// PDF Çıktısı
$pdf->Output('D', 'teklif-'.$offer_id.'.pdf'); // 'D' -> Tarayıcıda indirmeyi zorlar

?>
