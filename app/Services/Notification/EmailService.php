<?php
namespace App\Services\Notification;

use App\Models\EmailTemplate;
use App\Helpers\FormatHelper;
use PHPMailer\PHPMailer\PHPMailer;

class EmailService {
    public function sendEmail(
        string $to,
        string $subject,
        string $body,
        array $context = [],
        bool $isHtml = true
    ): bool {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = getenv('MAIL_HOST');
            $mail->SMTPAuth = true;
            $mail->Username = getenv('MAIL_USERNAME');
            $mail->Password = getenv('MAIL_PASSWORD');
            $mail->SMTPSecure = getenv('MAIL_ENCRYPTION') ?: 'tls';
            $mail->Port = getenv('MAIL_PORT') ?: 587;
            
            // Recipients
            $mail->setFrom(getenv('MAIL_FROM_ADDRESS'), getenv('MAIL_FROM_NAME'));
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            if (!$isHtml) {
                $mail->AltBody = FormatHelper::htmlToText($body);
            }
            
            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendTemplateEmail(
        string $to,
        string $templateName,
        array $variables = []
    ): bool {
        $template = (new EmailTemplate())->renderTemplate($templateName, $variables);
        
        if (!$template) {
            throw new \Exception("Email template '$templateName' not found");
        }
        
        return $this->sendEmail(
            $to,
            $template['subject'],
            $template['content'],
            $variables
        );
    }
}