<?php

require __DIR__.'/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options;
$options->set('isRemoteEnabled', true);
$options->set('chroot', __DIR__.'/public');
$dompdf = new Dompdf($options);

$imagePath = __DIR__.'/public/images/WA_1773771525722.jpeg';
if (file_exists($imagePath)) {
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrc = 'data:image/jpeg;base64,'.$imageData;
} else {
    $imageSrc = '';
}

$html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        @page {
            margin: 0px;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0px;
            padding: 0px;
            height: 100vh;
            width: 100%;
        }
        /* Dompdf uses default <p> margins; remove so spacing is controlled */
        p {
            margin: 0 0 12px 0;
        }
        .background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        .container {
            position: relative;
            padding: 240px 60px 40px 60px;
            color: #000;
            line-height: 1.6;
            z-index: 1;
        }
        .header {
            margin-bottom: 30px;
        }
        .content {
            margin-bottom: 30px;
        }
        .details {
            margin-bottom: 20px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
        }
        .details-table td {
            width: 50%;
            vertical-align: top;
            padding-right: 20px;
        }
        .details-table td:last-child {
            padding-right: 0;
            padding-left: 20px;
        }
        .footer {
            margin-top: 40px;
        }
        .addressed-to {
            margin-top: 50px;
            font-size: 14px;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-table td {
            width: 50%;
            vertical-align: top;
            padding-right: 20px;
        }
        .footer-table td:last-child {
            padding-right: 0;
            padding-left: 20px;
        }
        strong {
            color: #000;
        }
    </style>
</head>
<body>
    <img src="'.$imageSrc.'" class="background" />
    <div class="container">
        <div class="header">
            <p>'.date('F j, Y').'</p>
            <p>Dear Sir/Madam,</p>
        </div>

        <div class="content">
            <p>I hereby give full authorization for the registration of an <strong>edu.ng domain</strong> on behalf of our institution.</p>
        </div>

        <div class="details">
            <table class="details-table">
                <tr>
                    <td>
                        <p><strong>Developer Details:</strong><br>
                        Name: Joseph Ugbeva<br>
                        Email: josephugbeva@gmail.com<br>
                        Phone: 0903 444 3250</p>
                    </td>
                    <td>
                        <p><strong>School Owner Details:</strong><br>
                        Name: Awuto Peace Titilayo<br>
                        Email: optimalacademy06@gmail.com<br>
                        Phone: 07038448877</p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="content">
            <p>Joseph Ugbeva is authorized to act on our behalf in processing and completing the domain registration, including submission of all required documents and information.</p>
            <p>Please proceed with the necessary steps to register the domain <strong>Plusoptimalacademy.edu.ng</strong> under these provided details.</p>
            <p>Thank you for your assistance.</p>
        </div>

        <div class="footer">
            <table class="footer-table">
                <tr>
                    <td>
                        <p>Yours faithfully,<br>
                        <strong>Awuto Peace Titilayo</strong><br>
                        For: Plus Optimal Academy<br>
                        Email: optimalacademy06@gmail.com<br>
                        Phone: 07038448877</p>
                    </td>
                    <td>
                        <p><strong>Addressed to:</strong><br>
                        The Chief Operating Officer,<br>
                        Nigeria Internet Registration Association (NiRA)<br>
                        8, Funsho Williams Avenue,<br>
                        Iponri, Surulere,<br>
                        Lagos, Nigeria.</p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$output = $dompdf->output();
$filePath = __DIR__.'/public/authorization_letter.pdf';
file_put_contents($filePath, $output);

echo 'PDF generated successfully at: '.$filePath."\n";
