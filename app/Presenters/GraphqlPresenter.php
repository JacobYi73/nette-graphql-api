<?php

declare(strict_types=1);

namespace App\Presenters;

use App\GraphQL\GraphqlConfig;
use App\GraphQL\Schema;
use GraphQL\Error\DebugFlag;
use GraphQL\Language\SourceLocation;
use GraphQL\Server\Helper;
use GraphQL\Server\ServerConfig;
use GraphQL\Server\StandardServer;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;
use Nette\Application\Responses\JsonResponse;

final class GraphqlPresenter extends BasePresenter
{
	private Schema $schema;

	private GraphqlConfig $graphqlConfig;

	public function __construct(Schema $schema, GraphqlConfig $graphqlConfig)
	{
		$this->schema = $schema;
		$this->graphqlConfig = $graphqlConfig;

		if (!$this->schema instanceof Schema) {
			throw new \Exception(' GraphQL Schema not found.');
		}

		parent::__construct();
	}

	public function actionDefault(): void
	{
		$helper = new Helper();
		$request = $helper->parseHttpRequest();

		// Check if $request is an instance of OperationParams
		if ($request instanceof \GraphQL\Server\OperationParams) {
			$query = $request->query;
		} else {
			// Handle the case where $request is an array or another type
			$query = $request[0]->query ?? null; // This is a guess; adjust as needed
		}

		$usedDefaultQuery = false;

		if ($query === null) {
			$usedDefaultQuery = true;

			if ($request instanceof \GraphQL\Server\OperationParams) {
				$request->query = $this->graphqlConfig->getDefaultQuery();
			} else {
				$request[0]->query = $this->graphqlConfig->getDefaultQuery();
			}
		}

		if (!$usedDefaultQuery && $this->graphqlConfig->getRoleId() === 0) {
			throw new \Exception('Invalid token');
		}

		$serverConfig = ServerConfig::create()
			->setSchema($this->schema->build($this->graphqlConfig->getRoleName()))
			->setDebugFlag(DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE)
			->setValidationRules([new QueryDepth($this->graphqlConfig->getMaxDepth()), new QueryComplexity($this->graphqlConfig->getMaxComplexity())]);

		$server = new StandardServer($serverConfig);
		$result = $server->executeRequest($request);

		if ($result instanceof \GraphQL\Executor\ExecutionResult) {
			$result->setErrorsHandler(
				static fn (array $errors): array => \array_map(
					static fn ($error) => [
								'message' => $error->getMessage(),
								'locations' => \array_map(fn (SourceLocation $location) => [
										'line' => $location->line,
										'column' => $location->column,
									], $error->getLocations()),
								'path' => $error->getPath() ?? [],
								'extensions' => $error->getExtensions() ?? [],
							],
					$errors,
				),
			);

			$this->sendResponse(new JsonResponse($result->toArray()));
		}
	}
}
