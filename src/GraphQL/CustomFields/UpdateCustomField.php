<?php
declare(strict_types=1);

namespace App\GraphQL\CustomFields;

final class UpdateCustomField
{
    public const MUTATION = <<<'GRAPHQL'
mutation UpdateCustomFieldDefinition($input: AppFoundations_CustomFieldDefinitionUpdateInput!) {
  appFoundationsUpdateCustomFieldDefinition(input: $input) {
    id
    legacyIDV2
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
  }
}
GRAPHQL;

    public static function buildVariables(string $id, array $body): array
    {
        $input = [
            'id'              => $id,
            'label'           => $body['label']           ?? null,
            'dataType'        => $body['dataType']        ?? null,
            'active'          => $body['active']          ?? null,
            'associations'    => $body['associations']    ?? null,
            'dropDownOptions' => $body['dropDownOptions'] ?? null,
            'description'     => $body['description']     ?? null,
            'legacyIDV2'      => $body['legacyIDV2']      ?? null,
        ];

        $input = array_filter(
            $input,
            static fn($v) => $v !== null
        );

        return ['input' => $input];
    }
}
