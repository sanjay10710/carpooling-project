<?php

class Validator
{
    private array $errors = [];

    public function required(array $data, string $field, string $label): self
    {
        if (trim((string)($data[$field] ?? '')) === '') {
            $this->errors[$field] = "$label is required.";
        }
        return $this;
    }

    public function email(array $data, string $field, string $label): self
    {
        if (!empty($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "$label must be a valid email address.";
        }
        return $this;
    }

    public function minLength(array $data, string $field, int $min, string $label): self
    {
        if (!empty($data[$field]) && strlen((string)$data[$field]) < $min) {
            $this->errors[$field] = "$label must be at least $min characters.";
        }
        return $this;
    }

    public function maxLength(array $data, string $field, int $max, string $label): self
    {
        if (!empty($data[$field]) && strlen((string)$data[$field]) > $max) {
            $this->errors[$field] = "$label must be at most $max characters.";
        }
        return $this;
    }

    public function inArray(array $data, string $field, array $allowed, string $label): self
    {
        if (isset($data[$field]) && !in_array($data[$field], $allowed, true)) {
            $this->errors[$field] = "$label is invalid.";
        }
        return $this;
    }

    public function datetime(array $data, string $field, string $label): self
    {
        if (!empty($data[$field]) && strtotime((string)$data[$field]) === false) {
            $this->errors[$field] = "$label must be a valid date/time.";
        }
        return $this;
    }

    public function futureDatetime(array $data, string $field, string $label): self
    {
        if (!empty($data[$field])) {
            $ts = strtotime((string)$data[$field]);
            if ($ts === false || $ts <= time()) {
                $this->errors[$field] = "$label must be a future date/time.";
            }
        }
        return $this;
    }

    public function integerMin(array $data, string $field, int $min, string $label): self
    {
        if (isset($data[$field]) && trim((string)$data[$field]) !== '') {
            if (!ctype_digit((string)$data[$field]) || (int)$data[$field] < $min) {
                $this->errors[$field] = "$label must be a whole number of at least $min.";
            }
        }
        return $this;
    }

    public function integerBetween(array $data, string $field, int $min, int $max, string $label): self
    {
        if (isset($data[$field]) && trim((string)$data[$field]) !== '') {
            $val = (string)$data[$field];
            if (!ctype_digit($val) || (int)$val < $min || (int)$val > $max) {
                $this->errors[$field] = "$label must be a whole number between $min and $max.";
            }
        }
        return $this;
    }

    public function decimalMin(array $data, string $field, float $min, string $label): self
    {
        if (isset($data[$field]) && trim((string)$data[$field]) !== '') {
            $val = (string)$data[$field];
            if (!is_numeric($val) || (float)$val < $min) {
                $this->errors[$field] = "$label must be a number of at least $min.";
            }
        }
        return $this;
    }

    public function fails(): bool
    {
        return count($this->errors) > 0;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
