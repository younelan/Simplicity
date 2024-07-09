<?php
$vendor_dir = dirname(dirname(__DIR__)) . "/vendor";
$simplicity_dir = dirname(dirname(__DIR__)) . "/src";
require_once("$vendor_dir/autoload.php");
$csrf_token = \Opensitez\Simplicity\CSRF::generate_token();
$output = "";
// Include $csrf_token in your form as a hidden field or in a header for AJAX requests

function generate_qr_code($url) 
{
        $options = ["s" => "qr", "w" => 200, "h" => 200, "p" => 5];
        $generator = new \Opensitez\Simplicity\SimpleQRCode($url, $options);
        $image = $generator->render_image();
        ob_start();
        imagepng($image);
        $outputimage = ob_get_clean();
        imagedestroy($image);
        return $outputimage;
}
if($_SERVER['REQUEST_METHOD']=='POST') {
        // On form submission or request handling:
        if (isset($_POST['csrf_token'])) {
                //print "<div>" . $_POST['code'] . "</div>" . "<div>" . $_POST['csrf_token'] . "</div>";
                $submitted_token = $_POST['csrf_token'];
                if (\Opensitez\Simplicity\CSRF::validate_token($submitted_token)) {
                // Proceed with processing the form or request
                // CSRF token is valid
                        $url = $_POST['code']??"";
                        if($url) {
                                $outputimage = generate_qr_code($url);
                                $output .= "<div class=img><img src='data:image/png;base64," .
                                base64_encode($outputimage) . "'></div>";        
                        } else {
                                $output .= "<div class=img>No url provided</div>";
                        }

                } else {
                        $output .= "<div class=img>No valid csrf code provided</div>";
                // Handle invalid CSRF token
                }
        } else {
                echo "<div>Invalid CSRF Token</div>";
        }
        $csrf_token = \Opensitez\Simplicity\CSRF::reset_token();
        
}
?>

<html>
<head>
    <title>QR Code Generator</title>
</head>
<body>
<form method=post>
    <input type="text" name="code">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="submit" value="Generate">
</form>
</body>
</html> 

<?php
        print $output;
