<?php
$vendor_dir = dirname(dirname(__DIR__)) . "/vendor";
$simplicity_dir = dirname(dirname(__DIR__)) . "/src";
?>
<html>
<head>
    <title>QR Code Generator</title>
</head>
<body>
<form method=post>
    <input type="text" name="code">
    <input type="submit" value="Generate">
</form>
</body>
</html> 
<?php
        require_once("$vendor_dir/autoload.php");
        $qrurl = $_POST['code']??"";
        $options = ["s" => "qr", "w" => 200, "h" => 200, "p" => 5];
        $generator = new \Opensitez\Simplicity\SimpleQRCode($qrurl, $options);
        $image = $generator->render_image();
        ob_start();
        imagepng($image);
        $outputimage = ob_get_clean();
        $output .= "<img src='data:image/png;base64," .
            base64_encode($outputimage) . "'>";

        imagedestroy($image);

        print_r($output);
/*
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
        $generator = new \Opensitez\Simplicity\SimpleQRCode($_REQUEST['code'], $_REQUEST);
        $generator->output_image();
        exit(0);
}
 */

