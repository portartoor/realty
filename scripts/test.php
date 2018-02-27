<?
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/phpmailer/PHPMailerAutoload.php');
$mail = new PHPMailer;
$mail->isSMTP();
$mail->SMTPDebug = 0;
$mail->Debugoutput = 'html';
$mail->Host = 'smtp.gmail.com';
$mail->Port = 587;
$mail->SMTPSecure = 'tls';
$mail->SMTPAuth = true;
$mail->Username = "inventrealty@gmail.com";
$mail->Password = "333741marlen";
$mail->setFrom('inventrealty@gmail.com', 'First Last');
$mail->addReplyTo('mail@invent-realty.ru', 'First Last');
$mail->addAddress('wasilevskaja@gmail.com', 'John Doe');
$mail->Subject = 'PHPMailer GMail SMTP test';
$mail->msgHTML("test");
$mail->AltBody = 'This is a plain-text message body';
if (!$mail->send()) {
    echo "Mailer Error: " . $mail->ErrorInfo;
} else {
    echo "Message sent!";
}
?>