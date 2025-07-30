# Changelog

## main-dev (2025-07-30)

- Removed strict_types from Model\Attribute\Field\TypeEnum as backed enum can be int or string. Maybe check that later and implement correct cast
- Correctly return NULL for related object if not set

## main-dev (...)

- Attribute\Relation will not return rfield in json_encode() as it causes circular loop
- Fix Finder::find() to accept arguments[0][FindArguments]
- Added QuoteIdentifier attribute to support reserved database keywords for column names
- Improved error message in Model
- Correctly recognize nullable enums
- QueryBuilder::condition()
- Hydrator::toArray() will decast values (because of enums)

## main-dev (2024-04-02)

- Added DateTime controlling methods to Factory
- Added Enum type

## main-dev (2024-03-17)

- Initial commit

