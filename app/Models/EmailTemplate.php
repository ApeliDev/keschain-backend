<?php
namespace App\Models;

use App\Config\Database;

class EmailTemplate extends BaseModel {
    protected $table = 'pyr_email_templates';
    
    public function getTemplateByName(string $name) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM {$this->table} WHERE name = ? AND is_active = TRUE");
        $stmt->execute([$name]);
        return $stmt->fetch();
    }
    
    public function renderTemplate(string $name, array $variables = []): ?array {
        $template = $this->getTemplateByName($name);
        if (!$template) {
            return null;
        }
        
        $subject = $this->replaceVariables($template['subject'], $variables);
        $content = $this->replaceVariables($template['content'], $variables);
        
        return [
            'subject' => $subject,
            'content' => $content
        ];
    }
    
    private function replaceVariables(string $text, array $variables): string {
        foreach ($variables as $key => $value) {
            $text = str_replace("{{$key}}", $value, $text);
        }
        return $text;
    }
}