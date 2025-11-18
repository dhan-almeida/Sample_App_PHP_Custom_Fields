<?php
declare(strict_types=1);

namespace App\GraphQL\CustomFields;

final class GetAllCustomFields
{
    public const QUERY = <<<'GRAPHQL'
query GetCustomFieldDefinitions {
  appFoundationsCustomFieldDefinitions {
    edges {
      node {
        id
        legacyIDV2
        label
        associations {
          associatedEntity
          active
          validationOptions {
            required
          }
          allowedOperations
          associationCondition
        }
        dataType
        description
        dropDownOptions {
          id
          value
          active
          order
        }
        active
        customFieldDefinitionMetaModel {
          suggested
        }
      }
    }
  }
}
GRAPHQL;
}
