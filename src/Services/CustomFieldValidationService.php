<?php
declare(strict_types=1);

namespace App\Services;

class CustomFieldValidationService
{
    private static ?array $cachedDefinitions = null;

    /**
     * Get all custom field definitions and cache them
     *
     * @return array Map of definitionId => definition data
     */
    private static function getDefinitions(): array
    {
        if (self::$cachedDefinitions !== null) {
            return self::$cachedDefinitions;
        }

        try {
            $response = CustomFieldsService::getCustomFields();
            $edges = $response['appFoundationsCustomFieldDefinitions']['edges'] ?? [];

            $definitions = [];
            foreach ($edges as $edge) {
                $node = $edge['node'] ?? [];
                $legacyId = $node['legacyIDV2'] ?? null;

                if ($legacyId) {
                    $definitions[$legacyId] = $node;
                }
            }

            self::$cachedDefinitions = $definitions;
            return $definitions;
        } catch (\Throwable $e) {
            // If we can't fetch definitions, return empty array
            // This allows the app to continue working without validation
            return [];
        }
    }

    /**
     * Clear the cached definitions (useful after creating/updating definitions)
     */
    public static function clearCache(): void
    {
        self::$cachedDefinitions = null;
    }

    /**
     * Validate a single custom field value against its definition
     *
     * @param string $definitionId The custom field definition ID
     * @param mixed $value The value to validate
     * @param string|null $providedType Optional type hint from the caller
     * @return array ['valid' => bool, 'error' => string|null, 'expectedType' => string|null]
     */
    public static function validateField(string $definitionId, $value, ?string $providedType = null): array
    {
        $definitions = self::getDefinitions();

        // If definition not found, this is a validation error
        if (!isset($definitions[$definitionId])) {
            return [
                'valid' => false,
                'error' => "Custom field definition not found. Please ensure the field exists in QuickBooks.",
                'expectedType' => null,
            ];
        }

        $definition = $definitions[$definitionId];
        $expectedType = strtoupper($definition['dataType'] ?? 'STRING');
        $isActive = $definition['active'] ?? false;

        // Check if the field is active
        if (!$isActive) {
            return [
                'valid' => false,
                'error' => "Custom field definition {$definitionId} is not active",
                'expectedType' => $expectedType,
            ];
        }

        // If caller provided a type, check if it matches
        if ($providedType !== null && strtoupper($providedType) !== $expectedType) {
            return [
                'valid' => false,
                'error' => "Type mismatch: provided '{$providedType}' but definition expects '{$expectedType}'",
                'expectedType' => $expectedType,
            ];
        }

        // Validate value based on type
        switch ($expectedType) {
            case 'NUMBER':
                if (!is_numeric($value)) {
                    return [
                        'valid' => false,
                        'error' => "Value must be numeric for NUMBER field (got: " . gettype($value) . ")",
                        'expectedType' => $expectedType,
                    ];
                }
                break;

            case 'STRING':
                // Strings are generally flexible, but check if it's convertible
                if (is_array($value) || is_object($value)) {
                    return [
                        'valid' => false,
                        'error' => "Value cannot be converted to string (got: " . gettype($value) . ")",
                        'expectedType' => $expectedType,
                    ];
                }
                break;

            case 'DROPDOWN':
                // For dropdown, validate against allowed options if available
                $dropDownOptions = $definition['dropDownOptions'] ?? [];
                if (!empty($dropDownOptions)) {
                    $validValues = array_column($dropDownOptions, 'value');
                    if (!in_array((string) $value, $validValues, true)) {
                        return [
                            'valid' => false,
                            'error' => "Value '{$value}' is not a valid dropdown option. Valid options: " . implode(', ', $validValues),
                            'expectedType' => $expectedType,
                        ];
                    }
                }
                break;
        }

        return [
            'valid' => true,
            'error' => null,
            'expectedType' => $expectedType,
        ];
    }

    /**
     * Validate multiple custom fields at once
     *
     * @param array $customFields Array of custom fields with 'definitionId', 'value', and optional 'type'
     * @return array ['valid' => bool, 'errors' => array, 'warnings' => array]
     */
    public static function validateFields(array $customFields): array
    {
        $errors = [];
        $warnings = [];

        foreach ($customFields as $index => $field) {
            $definitionId = (string) ($field['definitionId'] ?? '');
            $value = $field['value'] ?? '';
            $type = $field['type'] ?? null;

            if (!$definitionId) {
                $errors[] = "Field at index {$index}: definitionId is required";
                continue;
            }

            $result = self::validateField($definitionId, $value, $type);

            if (!$result['valid']) {
                $errors[] = "Field {$definitionId}: {$result['error']}";
            }

            if (isset($result['warning'])) {
                $warnings[] = $result['warning'];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Validate and auto-correct custom field types
     * This will update the 'type' field in each custom field to match the definition
     *
     * @param array $customFields Array of custom fields (will be modified in place)
     * @return array ['valid' => bool, 'errors' => array, 'corrected' => array]
     */
    public static function validateAndCorrectTypes(array &$customFields): array
    {
        $errors = [];
        $corrected = [];

        foreach ($customFields as $index => &$field) {
            $definitionId = (string) ($field['definitionId'] ?? '');
            $value = $field['value'] ?? '';
            $providedType = $field['type'] ?? null;

            if (!$definitionId) {
                $errors[] = "Field at index {$index}: definitionId is required";
                continue;
            }

            // First, get the expected type from the definition (without type validation)
            // This allows us to auto-correct the type before validating the value
            $definitions = self::getDefinitions();
            
            if (!isset($definitions[$definitionId])) {
                // Definition not found - this is a validation error
                $errors[] = "Field {$definitionId}: Custom field definition not found. Please ensure the field exists in QuickBooks.";
                continue;
            }

            $definition = $definitions[$definitionId];
            $expectedType = strtoupper($definition['dataType'] ?? 'STRING');
            $isActive = $definition['active'] ?? false;

            // Check if the field is active
            if (!$isActive) {
                $errors[] = "Field {$definitionId}: Custom field definition is not active";
                continue;
            }

            // Auto-correct the type if needed (before validation)
            if ($expectedType && strtoupper($providedType ?? '') !== $expectedType) {
                $field['type'] = $expectedType;
                
                // Create a clear correction message
                $fromType = $providedType === null ? '(not provided)' : "'{$providedType}'";
                $corrected[] = "Field {$definitionId}: type corrected from {$fromType} to '{$expectedType}'";
            }

            // Now validate the value with the corrected type
            $result = self::validateField($definitionId, $value, $field['type']);

            if (!$result['valid']) {
                $errors[] = "Field {$definitionId}: {$result['error']}";
            }
        }
        
        // Unset the reference to avoid potential side effects
        unset($field);

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'corrected' => $corrected,
        ];
    }
}
