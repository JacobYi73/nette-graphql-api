<?php

declare(strict_types=1);

namespace App\GraphQL;

use GraphQL\Language\Parser;
use GraphQL\Type\Schema as GraphQLSchema;
use GraphQL\Utils\BuildSchema;

class Schema
{
	private \Nette\DI\Container $container;

	public function __construct(\Nette\DI\Container $container)
	{
		$this->container = $container;
	}

	/**
	 * @throws \Exception
	 */
	public function build(string $userRole = 'admin'): GraphQLSchema
	{
		$SDL = $this->loadGraphQLSchemas($userRole);

		$schemaAst = Parser::parse($SDL);
		$parsedSchema = BuildSchema::build($schemaAst);
		$queryType = $parsedSchema->getQueryType();

		$schemaSection = [];

		if ($queryType === null) {
			throw new \Exception('Query type is missing in the GraphQL schema.');
		}

		$schemaSection['query'] = $queryType;

		$mutationType = $parsedSchema->getMutationType();

		if ($mutationType !== null) {
			$schemaSection['mutation'] = $mutationType;
		}

		$this->assignResolvers($parsedSchema);

		return new GraphQLSchema($schemaSection);
	}

	/**
	 * @throws \Exception
	 */
	private function loadGraphQLSchemas(string $userRole = 'user'): string
	{
		$dir = __DIR__ . '/Types/';
		$types = \glob($dir . '*.graphql');

		if ($types === false) {
			throw new \Exception('Failed to read GraphQL types directory.');
		}

		$dir = __DIR__ . '/Enums/';
		$enums = \glob($dir . '*.graphql');

		if ($enums === false) {
			throw new \Exception('Failed to read GraphQL Enums directory.');
		}

		$dir = __DIR__ . '/Schemas/';
		$schemas = \glob($dir . '*.graphql');

		if ($schemas === false) {
			throw new \Exception('Failed to read GraphQL schema directory.');
		}

		$dir = __DIR__ . '/Schemas/SchemaByRole/';
		$root = \glob($dir . $userRole . '.graphql');

		if ($root === false) {
			throw new \Exception('Failed to read GraphQL SchemaByRole directory.');
		}

		$all = \array_merge($enums, $types, $schemas, $root);

		$SDL = '';

		foreach ($all as $schemaFile) {
			$content = \file_get_contents($schemaFile);

			if ($content === false) {
				throw new \Exception('Failed to read GraphQL schema file: ' . \basename($schemaFile));
			}

			$SDL .= $content;
		}

		return $SDL;
	}

	private function assignResolvers(GraphQLSchema $parsedSchema): void
	{
		$schemaTypeNames = ['Query', 'Mutation'];

		foreach ($schemaTypeNames as $schemaTypeName) {
			$prefix = \strtolower($schemaTypeName);

			switch ($prefix) {
				case 'query':
					$schemaType = $parsedSchema->getQueryType();

					if (empty($schemaType)) {
						throw new \Exception('GraphQL Schema Type ' . $schemaTypeName . ' not found.');
					}

					break;
				case 'mutation':
					$schemaType = $parsedSchema->getMutationType();

					break;
				default:
					throw new \Exception('Failed GraphQL Schema Type: ' . $schemaTypeName);
			}

			if (empty($schemaType)) {
				continue;
			}

			$fields = $schemaType->getFields();

			if (!\is_array($fields) || empty($fields)) {
				throw new \Exception('GraphQL Schema Type ' . $schemaTypeName . ' not found fields.');
			}

			foreach (\array_keys($parsedSchema->getTypeMap()) as $typeName) {
				$resolverClassName = 'App\\GraphQL\\Resolvers\\' . \ucfirst($typeName) . 'Resolver';

				if (!\class_exists($resolverClassName)) {
					continue;
				}

				$resolverInstance = $this->container->createInstance($resolverClassName);

				foreach ($fields as $fieldName => $field) {
					$methodResolveName = \str_replace($typeName, $prefix, $fieldName);

					if (!empty($methodResolveName) && \method_exists($resolverInstance, $methodResolveName)) {
						$callable = [$resolverInstance, $methodResolveName];

						if (\is_callable($callable)) {
							$field->resolveFn = $callable;
						}
					}
				}
			}
		}
	}
}
