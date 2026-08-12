<?php

declare(strict_types=1);

namespace App\AgentIntegration\OpenApi;

use App\AgentIntegration\Catalog\CatalogResourceType;
use App\AgentIntegration\Media\AgentMediaResourceType;
use App\AgentIntegration\OpenApi\Types\ContractObjectType;
use App\AgentIntegration\OpenApi\Types\OneOfType;
use Dedoc\Scramble\Contracts\DocumentTransformer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\RequestBodyObject;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\BooleanType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\MixedType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Generator\Types\Type;
use LogicException;

final readonly class AgentOpenApiDocument implements DocumentTransformer
{
    private const ULID_PATTERN = '^[0-7][0-9A-HJKMNP-TV-Z]{25}$';

    public function handle(OpenApi $document, OpenApiContext $context): void
    {
        $operationSchemas = new AgentOperationOpenApiSchemas();
        $operation = $operationSchemas->register($document);
        $previewDocument = $this->previewRequestSchema($operation);
        $previewDocument->examples($operationSchemas->documentExamples());
        $previewRequest = $this->addSchema($document, 'AgentChangeSetDocument', $previewDocument);
        $applyRequest = $this->addSchema($document, 'ApplyAgentChangeSetDocument', $this->applyRequestSchema());
        $credentialRestrictionDocument = $this->credentialRestrictionRequestSchema();
        $credentialRestrictionDocument->examples([
            ['action' => 'shorten_expiry', 'expires_at' => '2026-08-12T18:00:00Z'],
            ['action' => 'revoke'],
        ]);
        $credentialRestrictionRequest = $this->addSchema(
            $document,
            'AgentCredentialRestrictionDocument',
            $credentialRestrictionDocument,
        );
        $mediaUploadRequest = $this->addSchema(
            $document,
            'AgentMediaUploadDocument',
            $this->mediaUploadRequestSchema(),
        );
        $error = $this->addSchema($document, 'AgentApiError', $this->errorSchema());
        $catalogResource = $this->addSchema($document, 'CatalogResource', $this->catalogResourceSchema());
        $changeSet = $this->addSchema($document, 'AgentChangeSet', $this->changeSetSchema());
        $catalogCollection = $this->addSchema($document, 'CatalogCollection', $this->catalogCollectionSchema($catalogResource));
        $catalogDetail = $this->addSchema($document, 'CatalogDetail', $this->dataEnvelope($catalogResource));
        $changeSetCollection = $this->addSchema($document, 'AgentChangeSetCollection', $this->collectionEnvelope($changeSet));
        $changeSetDetail = $this->addSchema($document, 'AgentChangeSetDetail', $this->dataEnvelope($changeSet));
        $credentialRestrictionDetail = $this->addSchema(
            $document,
            'AgentCredentialRestrictionDetail',
            $this->dataEnvelope($this->credentialRestrictionResponseSchema()),
        );
        $mediaUploadDetail = $this->addSchema(
            $document,
            'AgentMediaUploadDetail',
            $this->dataEnvelope($this->mediaUploadResponseSchema()),
        );

        foreach ($document->paths as $path) {
            $pathName = trim($path->path, '/');
            foreach ($path->operations as $method => $operationBuilder) {
                if ($pathName === 'change-sets' && $method === 'post') {
                    $operationBuilder->addRequestBodyObject($this->requestBody($previewRequest));
                    $operationBuilder->responses = [
                        $this->response(200, 'Existing idempotent preview.', $changeSetDetail),
                        $this->response(201, 'Change Set preview created.', $changeSetDetail),
                    ];
                } elseif ($pathName === 'change-sets/{changeSet}/apply' && $method === 'post') {
                    $operationBuilder->addRequestBodyObject($this->requestBody($applyRequest));
                    $operationBuilder->responses = [$this->response(200, 'Change Set applied or returned idempotently.', $changeSetDetail)];
                } elseif ($pathName === 'change-sets' && $method === 'get') {
                    $operationBuilder->responses = [$this->response(200, 'Family Change Set history.', $changeSetCollection)];
                } elseif ($pathName === 'change-sets/{changeSet}' && $method === 'get') {
                    $operationBuilder->responses = [$this->response(200, 'Family Change Set detail.', $changeSetDetail)];
                } elseif ($pathName === 'catalog' && $method === 'get') {
                    $operationBuilder->responses = [$this->response(200, 'Complete filtered Family catalog.', $catalogCollection)];
                } elseif ($pathName === 'catalog/{resourceType}/{id}' && $method === 'get') {
                    $operationBuilder->responses = [$this->response(200, 'Family catalog resource detail.', $catalogDetail)];
                } elseif ($pathName === 'credential/restrictions' && $method === 'post') {
                    $operationBuilder->summary('Shorten or revoke the current Agent Credential');
                    $operationBuilder->description(
                        'This command can only restrict the authenticated Agent Credential; it cannot extend expiry or target another credential. '
                        . 'When a bearer secret may have been disclosed in plaintext, use shorten_expiry before other work to set the smallest practical future window. '
                        . 'After all requested API work and verification, use revoke as the final API request unless the user explicitly asked to keep the credential active. '
                        . 'After a successful response, report the resulting status and exact expires_at or revoked_at value to the user. '
                        . 'The application does not send a notification. If a revoke response is lost, do not retry with the same secret; tell the user the outcome is uncertain and ask them to confirm the retained status in Agent Access.',
                    );
                    $operationBuilder->addRequestBodyObject($this->requestBody($credentialRestrictionRequest));
                    $operationBuilder->responses = [
                        $this->response(200, 'Current Agent Credential restricted or returned as a monotonic no-op.', $credentialRestrictionDetail),
                    ];
                } elseif ($pathName === 'media/{resourceType}/{id}' && $method === 'post') {
                    foreach ($operationBuilder->parameters as $parameter) {
                        if ($parameter instanceof Parameter && $parameter->name === 'resourceType') {
                            $resourceTypeSchema = Schema::fromType(
                                $this->string(enum: AgentMediaResourceType::values()),
                            );
                            if ( ! $resourceTypeSchema instanceof Schema) {
                                throw new LogicException('Scramble did not create the media resource type parameter schema.');
                            }
                            $parameter->setSchema($resourceTypeSchema);
                        }
                    }
                    $operationBuilder->summary('Upload or replace one Family resource image');
                    $operationBuilder->description(
                        'Requires cookbook:write. Send one JPEG or PNG image of at most 5 MiB as multipart/form-data field image. Source dimensions must not exceed 8192 pixels per side or 25,000,000 total pixels. '
                        . 'Supported resource types are stores, ingredients, and recipes. Store Sections use the predefined icon catalog and do not accept image uploads. '
                        . 'The credential fixes the Family scope; no Family identifier is accepted. '
                        . 'A successful request immediately replaces the entity’s existing image through the same normalization, private storage, and archive rules used by the web interface. '
                        . 'This immediate binary upload is not an Agent Change Set operation.',
                    );
                    $operationBuilder->addRequestBodyObject($this->requestBody($mediaUploadRequest, 'multipart/form-data'));
                    $operationBuilder->responses = [
                        $this->response(200, 'Entity image uploaded and normalized.', $mediaUploadDetail),
                    ];
                }

                foreach ([401, 403, 404, 409, 413, 422, 429] as $status) {
                    $operationBuilder->addResponse($this->response($status, 'Structured Agent API error.', $error));
                }
            }
        }
    }

    private function previewRequestSchema(Reference $operation): ObjectType
    {
        $schema = new ObjectType();
        $schema->addProperty('version', $this->integer(const: 1));
        $schema->addProperty('client_request_id', $this->string(min: 1, max: 255));
        $schema->addProperty('title', $this->string(nullable: true));
        $schema->addProperty('source_urls', $this->arrayOf($this->string(format: 'uri', max: 2048)));
        $schema->addProperty('note', $this->string(nullable: true));
        $schema->addProperty('supersedes_id', $this->ulid(nullable: true));
        $schema->addProperty('operations', $this->arrayOf($operation, min: 1, max: 250));
        $schema->setRequired(['version', 'client_request_id', 'operations']);

        return $schema;
    }

    private function applyRequestSchema(): ObjectType
    {
        $schema = new ObjectType();
        $schema->addProperty('digest', $this->string(min: 64, max: 64, pattern: '^[a-f0-9]{64}$'));
        $schema->addProperty('warning_acknowledgements', $this->arrayOf(new StringType(), unique: true));
        $schema->setRequired(['digest', 'warning_acknowledgements']);

        return $schema;
    }

    private function credentialRestrictionRequestSchema(): OneOfType
    {
        $shorten = new ContractObjectType();
        $shorten->addProperty('action', $this->string(const: 'shorten_expiry'));
        $shorten->addProperty(
            'expires_at',
            $this->string(format: 'date-time', pattern: '^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}Z$'),
        );
        $shorten->setRequired(['action', 'expires_at']);

        $revoke = new ContractObjectType();
        $revoke->addProperty('action', $this->string(const: 'revoke'));
        $revoke->setRequired(['action']);

        return new OneOfType([$shorten, $revoke]);
    }

    private function credentialRestrictionResponseSchema(): ObjectType
    {
        $schema = new ContractObjectType();
        $schema->addProperty('credential_id', new IntegerType());
        $schema->addProperty('action', $this->string(enum: ['shorten_expiry', 'revoke']));
        $schema->addProperty('status', $this->string(enum: ['active', 'revoked']));
        $schema->addProperty('expires_at', $this->string(nullable: true, format: 'date-time'));
        $schema->addProperty('revoked_at', $this->string(nullable: true, format: 'date-time'));
        $schema->addProperty('changed', new BooleanType());
        $schema->setRequired(['credential_id', 'action', 'status', 'expires_at', 'revoked_at', 'changed']);

        return $schema;
    }

    private function mediaUploadRequestSchema(): ObjectType
    {
        $schema = new ContractObjectType();
        $schema->addProperty('image', $this->string(format: 'binary'));
        $schema->setRequired(['image']);

        return $schema;
    }

    private function mediaUploadResponseSchema(): ObjectType
    {
        $schema = new ContractObjectType();
        $schema->addProperty('resource_type', $this->string(enum: AgentMediaResourceType::values()));
        $schema->addProperty('id', new IntegerType());
        $schema->addProperty('media_type', $this->string(enum: AgentMediaResourceType::mediaTypeValues()));
        $schema->setRequired(['resource_type', 'id', 'media_type']);

        return $schema;
    }

    private function errorSchema(): ObjectType
    {
        $error = new ObjectType();
        $error->addProperty('code', new StringType());
        $error->addProperty('message', new StringType());
        $error->addProperty('path', $this->string(nullable: true));
        $error->addProperty('operation_id', $this->string(nullable: true));
        $error->addProperty('details', $this->openObject());
        $error->addProperty('retryable', new BooleanType());
        $error->setRequired(['code', 'message', 'path', 'operation_id', 'details', 'retryable']);

        $schema = new ObjectType();
        $schema->addProperty('error', $error);
        $schema->setRequired(['error']);

        return $schema;
    }

    private function catalogResourceSchema(): ObjectType
    {
        $schema = new ObjectType();
        $schema->addProperty('resource_type', $this->string(enum: CatalogResourceType::values()));
        $schema->addProperty('id', new IntegerType());
        $schema->addProperty('status', $this->string(enum: ['active', 'archived']));
        $schema->addProperty('updated_at', $this->string(format: 'date-time'));
        $schema->additionalProperties(new MixedType());
        $schema->setRequired(['resource_type', 'id', 'status', 'updated_at']);

        return $schema;
    }

    private function changeSetSchema(): ObjectType
    {
        $schema = new ObjectType();
        $schema->addProperty('id', $this->ulid());
        $schema->addProperty('status', $this->string(enum: ['previewed', 'applied', 'expired', 'invalidated', 'stale']));
        $schema->addProperty('document_version', $this->integer(const: 1));
        $schema->addProperty('client_request_id', new StringType());
        $schema->addProperty('digest', $this->string(pattern: '^[a-f0-9]{64}$'));
        $schema->addProperty('credential', $this->openObject());
        $schema->addProperty('issuer', $this->openObject());
        $schema->addProperty('title', $this->string(nullable: true));
        $schema->addProperty('source_urls', $this->arrayOf($this->string(format: 'uri')));
        $schema->addProperty('note', $this->string(nullable: true));
        $schema->addProperty('supersedes_id', $this->ulid(nullable: true));
        $schema->addProperty('resource_types', $this->arrayOf($this->string(enum: CatalogResourceType::values())));
        $schema->addProperty('outcome', $this->string(nullable: true));
        $schema->addProperty('operation_count', new IntegerType());
        $schema->addProperty('payload_bytes', new IntegerType());
        $schema->addProperty('expires_at', $this->string(format: 'date-time'));
        $schema->addProperty('created_at', $this->string(nullable: true, format: 'date-time'));
        $schema->addProperty('applied_at', $this->string(nullable: true, format: 'date-time'));
        $schema->addProperty('terminal_at', $this->string(nullable: true, format: 'date-time'));
        $schema->addProperty('canonical_request', $this->openObject());
        $schema->addProperty('preview', $this->openObject());
        $schema->addProperty('warning_acknowledgements', $this->arrayOf(new StringType(), nullable: true));
        $schema->addProperty('identifier_mappings', $this->openObject(nullable: true, additional: new IntegerType()));
        $schema->addProperty('result', $this->openObject(nullable: true));
        $schema->setRequired([
            'id', 'status', 'document_version', 'client_request_id', 'digest', 'credential', 'issuer', 'title',
            'source_urls', 'note', 'supersedes_id', 'resource_types', 'outcome', 'operation_count', 'payload_bytes',
            'expires_at', 'created_at', 'applied_at', 'terminal_at', 'canonical_request', 'preview',
            'warning_acknowledgements', 'identifier_mappings', 'result',
        ]);

        return $schema;
    }

    private function catalogCollectionSchema(Reference $resource): ObjectType
    {
        $meta = new ObjectType();
        $meta->addProperty('count', new IntegerType());
        $meta->addProperty('resource_types', $this->arrayOf($this->string(enum: CatalogResourceType::values())));
        $meta->setRequired(['count', 'resource_types']);

        $schema = new ObjectType();
        $schema->addProperty('data', $this->arrayOf($resource));
        $schema->addProperty('meta', $meta);
        $schema->setRequired(['data', 'meta']);

        return $schema;
    }

    private function collectionEnvelope(Reference $item): ObjectType
    {
        $schema = new ObjectType();
        $schema->addProperty('data', $this->arrayOf($item));
        $schema->setRequired(['data']);

        return $schema;
    }

    private function dataEnvelope(Type $data): ObjectType
    {
        $schema = new ObjectType();
        $schema->addProperty('data', $data);
        $schema->setRequired(['data']);

        return $schema;
    }

    /** @param list<string> $enum */
    private function string(
        bool $nullable = false,
        string $format = '',
        array $enum = [],
        ?int $min = null,
        ?int $max = null,
        ?string $pattern = null,
        ?string $const = null,
    ): StringType {
        $type = new StringType();
        $type->nullable($nullable);
        if ($format !== '') {
            $type->format($format);
        }
        if ($enum !== []) {
            $type->enum($enum);
        }
        if ($min !== null) {
            $type->setMin($min);
        }
        if ($max !== null) {
            $type->setMax($max);
        }
        $type->pattern($pattern);
        if ($const !== null) {
            $type->const($const);
        }

        return $type;
    }

    private function integer(bool $nullable = false, ?int $const = null): IntegerType
    {
        $type = new IntegerType();
        $type->nullable($nullable);
        if ($const !== null) {
            $type->const($const);
        }

        return $type;
    }

    private function ulid(bool $nullable = false): StringType
    {
        return $this->string(
            nullable: $nullable,
            min: 26,
            max: 26,
            pattern: self::ULID_PATTERN,
        );
    }

    private function openObject(bool $nullable = false, ?Type $additional = null): ObjectType
    {
        $type = new ObjectType();
        $type->nullable($nullable);
        $type->additionalProperties($additional ?? new MixedType());

        return $type;
    }

    private function arrayOf(
        Type $items,
        bool $nullable = false,
        ?int $min = null,
        ?int $max = null,
        bool $unique = false,
    ): ArrayType {
        $type = new ArrayType();
        $type->setItems($items);
        $type->nullable($nullable);
        if ($min !== null) {
            $type->setMin($min);
        }
        if ($max !== null) {
            $type->setMax($max);
        }
        $type->setUniqueItems($unique);

        return $type;
    }

    private function addSchema(OpenApi $document, string $name, Type $type): Reference
    {
        $schema = Schema::fromType($type);
        if ( ! $schema instanceof Schema) {
            throw new LogicException('Scramble did not create an OpenAPI schema.');
        }

        return $document->components->addSchema($name, $schema);
    }

    private function requestBody(Reference $schema, string $contentType = 'application/json'): RequestBodyObject
    {
        $requestBody = new RequestBodyObject();
        $requestBody->setContent($contentType, $schema);
        $requestBody->required();

        return $requestBody;
    }

    private function response(int $status, string $description, Reference $schema): Response
    {
        $response = new Response($status);
        $response->setDescription($description);
        $response->setContent('application/json', $schema);

        return $response;
    }
}
