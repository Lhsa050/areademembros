<?php

namespace App\Core;

/**
 * Classe de Validação
 */
class Validator
{
    private array $errors = [];
    private array $data;
    private array $rules;

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    /**
     * Executa validação
     */
    public function validate(): bool
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }

        return empty($this->errors);
    }

    /**
     * Aplica uma regra
     */
    private function applyRule(string $field, $value, string $rule): void
    {
        $params = [];
        if (str_contains($rule, ':')) {
            [$rule, $paramString] = explode(':', $rule, 2);
            $params = explode(',', $paramString);
        }

        $label = ucfirst(str_replace('_', ' ', $field));

        switch ($rule) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->errors[$field][] = "{$label} é obrigatório.";
                }
                break;

            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field][] = "{$label} não é um email válido.";
                }
                break;

            case 'min':
                if (!empty($value) && strlen($value) < (int) $params[0]) {
                    $this->errors[$field][] = "{$label} deve ter pelo menos {$params[0]} caracteres.";
                }
                break;

            case 'max':
                if (!empty($value) && strlen($value) > (int) $params[0]) {
                    $this->errors[$field][] = "{$label} deve ter no máximo {$params[0]} caracteres.";
                }
                break;

            case 'confirmed':
                $confirmField = $field . '_confirmation';
                $confirmValue = $this->data[$confirmField] ?? null;
                if ($value !== $confirmValue) {
                    $this->errors[$field][] = "A confirmação de {$label} não confere.";
                }
                break;

            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->errors[$field][] = "{$label} deve ser numérico.";
                }
                break;

            case 'url':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->errors[$field][] = "{$label} não é uma URL válida.";
                }
                break;

            case 'unique':
                if (!empty($value)) {
                    $table = $params[0] ?? '';
                    $column = $params[1] ?? $field;
                    $exceptId = $params[2] ?? null;

                    $sql = "SELECT id FROM {$table} WHERE {$column} = ?";
                    $bindings = [$value];

                    if ($exceptId) {
                        $sql .= " AND id != ?";
                        $bindings[] = $exceptId;
                    }

                    $exists = Database::fetch($sql, $bindings);
                    if ($exists) {
                        $this->errors[$field][] = "{$label} já está em uso.";
                    }
                }
                break;

            case 'in':
                if (!empty($value) && !in_array($value, $params)) {
                    $this->errors[$field][] = "{$label} não é válido.";
                }
                break;
        }
    }

    /**
     * Retorna erros
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Retorna primeira mensagem de erro de um campo
     */
    public function first(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Verifica se campo tem erro
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /**
     * Valida e redireciona com erros se falhar
     */
    public static function make(array $data, array $rules): bool
    {
        $validator = new self($data, $rules);
        
        if (!$validator->validate()) {
            $_SESSION['_validation_errors'] = $validator->errors();
            save_old_input();
            return false;
        }

        return true;
    }

    /**
     * Obtém erros de validação da sessão
     */
    public static function getErrors(): array
    {
        $errors = $_SESSION['_validation_errors'] ?? [];
        unset($_SESSION['_validation_errors']);
        return $errors;
    }
}
