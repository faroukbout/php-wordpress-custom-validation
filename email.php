<?php

require 'db.php'; 
require 'libs/phpqrcode/qrlib.php'; // Include PHP QR Code library
require 'libs/tcpdf/tcpdf.php';    // Include TCPDF library

// Sanitize email and generate unique filename
function sanitizeFileName($email) {
    $sanitizedEmail = preg_replace('/[^a-zA-Z0-9]/', '', $email); // Remove special characters
    $timestamp = date('YmdHis'); // Current date and time
    return "{$timestamp}_{$sanitizedEmail}";
}

function generateQrCode($name, $surname, $id) {
    $fileName = sanitizeFileName($id) . '.png';
    $qrCodePath = __DIR__ . "/qrcodes/$fileName";
    $qrContent = "Nom: $name\nPrénom: $surname\nInscription: $id"; // Updated QR code content
    QRcode::png($qrContent, $qrCodePath, QR_ECLEVEL_L, 4);

    return $qrCodePath;
}

function generatePdf($userInfo, $type) {
    $qrCodePath = generateQrCode($userInfo['name'], $userInfo['surname'], $userInfo['id']);

    $fileName = sanitizeFileName($userInfo['email']) . '.pdf';
    $outputPath = __DIR__ . "/generated_pdfs/$fileName";

    // Create PDF
    $pdf = new TCPDF();
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('');
    $pdf->SetTitle('');
    $pdf->SetMargins(20, 40, 20); // Adjusted margins
    $pdf->SetAutoPageBreak(TRUE, 20);

    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);

    // Add header image
    $pdf->Image('', 15, 10, 180, '', '', '', '', false, 300);

    // Define title and message
    $title = $type === 'paid' ? "Confirmation d'inscription" : "Rappel d'inscription";
    $message = $type === 'paid' 
        ? "Votre inscription est validée."
        : "Votre inscription n'est pas encore finalisée";

    // Add content below header
    $pdf->SetY(65); // Position content further down
    $html = <<<EOD
...
EOD;

    $pdf->writeHTML($html, true, false, true, false, '');

    // Add QR Code
    $pdf->Image($qrCodePath, 85, 220, 40, 40, '', '', '', false, 300);

    $pdf->Output($outputPath, 'F');

    return $outputPath;
}

if (isset($_POST['email'], $_POST['entry_id'], $_POST['type'])) {
    $email = $_POST['email'];
    $entry_id = intval($_POST['entry_id']);
    $type = $_POST['type'];

    // Get user details
    $query = "
        SELECT 
            e.entry_id AS id,
            n.meta_value AS name,
            t.meta_value AS surname,
            m.meta_value AS email,
            p.meta_value AS telephone,
            a.meta_value AS address
        FROM wp_frmt_form_entry_meta e
        LEFT JOIN wp_frmt_form_entry_meta n ON e.entry_id = n.entry_id AND n.meta_key = 'name-1'
        LEFT JOIN wp_frmt_form_entry_meta t ON e.entry_id = t.entry_id AND t.meta_key = 'text-1'
        LEFT JOIN wp_frmt_form_entry_meta m ON e.entry_id = m.entry_id AND m.meta_key = 'email-1'
        LEFT JOIN wp_frmt_form_entry_meta p ON e.entry_id = p.entry_id AND p.meta_key = 'phone-1'
        LEFT JOIN wp_frmt_form_entry_meta a ON e.entry_id = a.entry_id AND a.meta_key = 'address-1'
        WHERE e.entry_id = ?
    ";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $entry_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $userInfo = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    // Extract Wilaya from address
    $address = $userInfo['address'];
    $userInfo['wilaya'] = 'N/A';
    if (!empty($address)) {
        if (strpos($address, 'a:') === 0) {
            $addressData = unserialize($address);
            $userInfo['wilaya'] = $addressData['city'] ?? 'N/A';
        } elseif ($decoded = json_decode($address, true)) {
            $userInfo['wilaya'] = $decoded['city'] ?? 'N/A';
        }
    }

    // Generate the PDF
    $pdfPath = generatePdf($userInfo, $type);

    // Email content
    $subject = $type === 'paid' ? 
        "Validation de votre inscription " : 
        "Rappel pour compléter votre inscription ..";
    
    $message = $type === 'paid' ? "
        <html>
        <body>
            <p>Votre inscription est validée.</p>
        </body>
        </html>
    " : "
        <html>
        <body>
            <p>Votre inscription n'est pas finalisée</p>
        </body>
        </html>
    ";

    // Attach PDF and send email
    $boundary = md5(time());
    $headers = "From: ";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

    $body = "--$boundary\r\n";
    $body .= "Content-Type: text/html; charset=\"UTF-8\"\r\n\r\n";
    $body .= $message . "\r\n";
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: application/pdf; name=\"" . basename($pdfPath) . "\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment; filename=\"" . basename($pdfPath) . "\"\r\n\r\n";
    $body .= chunk_split(base64_encode(file_get_contents($pdfPath))) . "\r\n";
    $body .= "--$boundary--";

    if (mail($email, $subject, $body, $headers)) {
        // Update `_forminator_user_ip` to `0` in the database
        $updateQuery = "
            UPDATE wp_frmt_form_entry_meta
            SET meta_value = '0'
            WHERE entry_id = ? AND meta_key = '_forminator_user_ip'
        ";
        $updateStmt = mysqli_prepare($conn, $updateQuery);
        mysqli_stmt_bind_param($updateStmt, 'i', $entry_id);

        if (mysqli_stmt_execute($updateStmt)) {
            echo json_encode([
                'status' => 'success',
                'message' => "E-mail de type '$type' envoyé avec succès!",
                'type' => $type
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => "E-mail de type '$type' envoyé, mais échec de la mise à jour de la base de données.",
                'type' => $type
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => "Échec de l'envoi de l'e-mail de type '$type'.",
            'type' => $type
        ]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Paramètres manquants.']);
}

?>
