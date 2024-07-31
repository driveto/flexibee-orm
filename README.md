# ORM layer for Flexibee API
## Installation
```bash
$ composer require driveto/flexibee-orm
```

Register the bundle in your `bundles.php` file

```php
// config/bundles.php
return [
    // ...
    Driveto\FlexibeeOrmBundle\FlexibeeOrmBundle::class => ['all' => true],
];
```

## How to use it
### Record
Create a model entity representing a record from Flexibee and mark it with attribute `#[AsFlexibeeRecord]`.
This attribute needs to know from what agenda the data will be read. In this case we're reading issued invoices (eg: `faktura-vydana`).

Every property you want to map must be declared in the entity. This is used to read only necessary data.
If property's name in the entity is different from the name in the Flexibee API, you have to use Symfony's `#[SerializedName('property_name_in_api')]` attribute to properly map it.
And if you have your custom properties in a record entity you must add the `#[Ignore]` attribute to them.

```php
#[AsFlexibeeRecord(agenda: 'faktura-vydana')]
class FlexibeeInvoiceRecord
{
    public string $id;
    public string $uuid;

    #[SerializedName('kod')]
    public string $invoiceNumber;

    #[SerializedName('varSym')]
    public string $variableSymbol;

    #[SerializedName('cisSml')]
    public string $contractNumber;

    #[SerializedName('typDokl')]
    public FlexibeeInvoiceRecordType $type;

    #[Ignore]
    public string $typeCode;

    #[SerializedName('typDokl@ref')]
    public string $typeRef;

    // ...
}
```

### Relations
Record can have relations and if you want to map them as well you need to tell the ORM to do so with an attribute `#[AsFlexibeeRelation]`.
This attribute needs to know a `relationName` and a `class` which will hold the data.

```php
#[AsFlexibeeRecord(agenda: 'faktura-vydana')]
class FlexibeeInvoiceRecord

    // ...
    
    #[AsFlexibeeRelation(relationName: 'prilohy', class: FlexibeeAttachmentRecord::class)]
    public array $attachments;
    
    // ...
}
```

### Repository
#### Reading
Every Flexibee repository must extend the `AbstractFlexibeeRepository` and FQN of a record class must be passed to it. 

```php
/**
 * @extends AbstractFlexibeeRepository<FlexibeeInvoiceRecord>
 */
class FlexibeeInvoiceRecordRepository extends AbstractFlexibeeRepository
{
    public function __construct()
    {
        parent::__construct(FlexibeeInvoiceRecord::class);
    }
}
```

API of the base repository is similar to the Doctrine. So to find a record by its UUID all you have to do is:

```php
$invoice = $this->findOneBy(['uuid' => $uuid]);
```

If the record is found then an object is returned, otherwise you'll get `NULL`.\
To find multiple records you can use `AbstractFlexibeeRepository::findBy(criteria: [...], orderBy: [...])` method.\
And if you want to count the records in an agenda just call `AbstractFlexibeeRepository::count([...])`.\
A record can be also read by its unique identifier with `AbstractFlexibeeRepository::find($id)`. 

#### Inserting & updating
It's same as in the Doctrine. Just pass a record entity into `AbstractFlexibeeRepository::persist(...)` method.
**But be aware!** There's no such thing as the `UnitOfWork` known from Doctrine! So when you call the `persist` method the insert/update operation is processed immediately.

#### Removing
To remove a record from Flexibee call `AbstractFlexibeeRepository::remove(...)` method. The record entity passed to this method must contain an accessible `$id` property.

### Expressions & operators
Sometimes you need to do more sophisticated queries than those mentioned above. For this situation the library contains a query builder and expression "language".
Let's take a look to the examples:

```php
$queryBuilder = $this->createQueryBuilder()
    ->where(['cisSml' => '12345'])
    ->andWhere(['datVyst' => Operator::greaterThanOrEqual('2024-01-01')])
    ->andWhere(['datVyst' => Operator::lessThan('2025-01-01')])
    ->andWhere(['varSym' => Operator::notEqual(null)])
    ->orderBy('datVyst', Operator::DESC)
;
```

... and thanks to expressions you can do subqueries:

```php
$queryBuilder = $this->createQueryBuilder()
    ->where(
        Expression::or(
            ['foo' => 'bar', 'bar' => 'foo'],
            ['bar' => Operator::notEqual(null)],
        ),
    )
    ->andWhere(
        [
            Expression::or(
                ['name' => Operator::notEqual('Jane')],
                ['name' => Operator::notEqual('John')],
                [Expression::and(['text' => 'spa ce!'], ['text' => Operator::notEqual('???')])],
            ),
            'A' => 'B',
        ],
    )
;

// The resulting query will look like: 
// agenda/(
//     foo = "bar" or bar = "foo" or bar != ""
//     and ((name != "Jane" or name != "John" or (text = "spa%20ce%21" and text != "%3F%3F%3F")) and A = "B")'
// )
```

Once you have built your query just call:

```php
$records = $this->runCustomQuery($queryBuilder->getQuery());
```


## Q&A
Run tests:
```bash
$ ./vendor/bin/phpunit ./tests
```

Run static analysis:
```bash
$ ./vendor/bin/phpstan analyse -c phpstan.neon ./src ./tests
```

Run code sniffer:
```bash
$ vendor/bin/phpcs --standard=phpcs.xml --extensions=php --encoding=utf-8 -sp ./src ./tests
```

If you use Symfony Profiler then all run queries are visible there including their execution time and the links to their JSON/XML result.
