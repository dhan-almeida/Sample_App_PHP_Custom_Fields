<?php
declare(strict_types=1);

namespace App\GraphQL\CustomFields;

final class CreateCustomField
{
    public const MUTATION = <<<'GRAPHQL'
mutation CreateCustomFieldDefinition($input: AppFoundations_CustomFieldDefinitionCreateInput!) {
  appFoundationsCreateCustomFieldDefinition(input: $input) {
    id
    label
    dataType
    active
    associations {
      associatedEntity
      active
      validationOptions {
        required
      }
      allowedOperations
      associationCondition
    }
    dropDownOptions {
      id
      value
      active
      order
    }
    legacyIDV2
  }
}
GRAPHQL;

    public static function buildVariables(array $body): array
    {
        $input = [
            'label'           => $body['label'] ?? '',
            'dataType'        => $body['dataType'] ?? 'STRING',
            'active'          => $body['active'] ?? true,
            'associations'    => $body['associations'] ?? [],
            'dropDownOptions' => $body['dropDownOptions'] ?? [],
            'description'     => $body['description'] ?? null,
        ];

        return ['input' => $input];
    }
}
