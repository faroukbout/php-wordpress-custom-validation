<?php
require_once 'libs/phpqrcode/qrlib.php'; // Include PHP QR Code library
require_once 'libs/tcpdf/tcpdf.php';   // Include TCPDF library

function generateQrCode($name, $surname, $email) {
    // Generate QR Code
    $qrCodePath = __DIR__ . "/qrcodes/qr_$email.png";
    $qrContent = "Nom: $name\nPrénom: $surname\nEmail: $email";
    QRcode::png($qrContent, $qrCodePath, QR_ECLEVEL_L, 4);

    return $qrCodePath;
}

function generatePdf($name, $surname, $email) {
    $qrCodePath = generateQrCode($name, $surname, $email);

    // Create PDF
    $pdf = new TCPDF();
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor(''); //header herer
    $pdf->SetTitle(''); // title here
    $pdf->SetMargins(20, 20, 20);
    $pdf->SetAutoPageBreak(TRUE, 20);

    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);

    // Add header image
    $pdf->Image('', 15, 10, 180, '', '', '', '', false, 300);

    // Add content of ur  pdf here
    $html = <<<EOD
.............
EOD;

    $pdf->writeHTML($html, true, false, true, false, '');

    // Add QR Code
    $pdf->Image($qrCodePath, 80, 150, 50, 50, '', '', '', false, 300);

    $outputPath = __DIR__ . "/generated_pdfs/confirmation_$email.pdf";
    $pdf->Output($outputPath, 'F');

    return $outputPath;
}
?>
