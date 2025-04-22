<?php

declare(strict_types=1);

namespace Tests;

use App\GraphQL\GraphqlConfig;
use App\GraphQL\Resolvers\BaseResolver;
use App\Model\Database\Entity\BaseEntity;
use App\Model\Database\Repository\BaseRepository;
use GraphQL\Type\Definition\ResolveInfo;
use InvalidArgumentException;
use Mockery;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../vendor/autoload.php';

/**
 * @template T of BaseRepository
 */
final class BaseResolverTest extends TestCase
{
	/**
	 * @var BaseResolver
	 */
	private BaseResolver $resolver;
	private $repositoryMock;
	private $graphqlConfigMock;

	protected function setUp(): void
	{
		$this->repositoryMock = Mockery::mock(\App\Model\Database\Repository\BookRepository::class);
		$this->graphqlConfigMock = Mockery::mock(GraphqlConfig::class);

		$this->resolver = new class($this->repositoryMock, $this->graphqlConfigMock) extends BaseResolver {
			public function __construct($repository, $graphqlConfig)
			{
				parent::__construct($repository, $graphqlConfig);
			}
		};
	}

	public function testGetDefaultLangId(): void
	{
		$this->graphqlConfigMock
			->shouldReceive('getDefaultLangId')
			->andReturn(1);

		Assert::same(1, $this->resolver->getDefaultLangId());
	}

	public function testQueryByIdValidId(): void
	{
		$entityMock = Mockery::mock(\App\Model\Database\Entity\Book::class);
		$entityMock->shouldReceive('toArray')->andReturn(['id' => 1, 'name' => 'Test Entity']);

		$this->repositoryMock
			->shouldReceive('findById')
			->with(1)
			->andReturn($entityMock);

		$result = $this->resolver->queryById(null, ['id' => 1], null, Mockery::mock(ResolveInfo::class));
		Assert::same(['id' => 1, 'name' => 'Test Entity'], $result);
	}

	public function testQueryByIdInvalidId(): void
	{
		$entityMock = Mockery::mock(\App\Model\Database\Entity\Book::class);
		$entityMock->shouldReceive('toArray')->andReturn([]);

		$this->repositoryMock
			->shouldReceive('findById')
			->with(100)
			->andReturn($entityMock);

		$result = $this->resolver->queryById(null, ['id' => 100], null, Mockery::mock(ResolveInfo::class));

		Assert::same([], $result);
	}

	public function testQueryByIdInvalidIdType(): void
	{
		$this->repositoryMock
			->shouldReceive('findById')
			->with(1)
			->andThrow(new InvalidArgumentException());

		Assert::exception(function () {
			$this->resolver->queryById(null, ['id' => 'asdf'], null, Mockery::mock(ResolveInfo::class));
		}, InvalidArgumentException::class);
	}

	public function testQueryAllReturnsEntities(): void
	{
		$entityMock1 = Mockery::mock(BaseEntity::class);
		$entityMock1->shouldReceive('toArray')->andReturn(['id' => 1, 'name' => 'Entity 1']);

		$entityMock2 = Mockery::mock(BaseEntity::class);
		$entityMock2->shouldReceive('toArray')->andReturn(['id' => 2, 'name' => 'Entity 2']);

		$this->repositoryMock
			->shouldReceive('findAll')
			->andReturn([$entityMock1, $entityMock2]);

		$result = $this->resolver->queryAll(null, [], null, Mockery::mock(ResolveInfo::class));

		Assert::same([
			['id' => 1, 'name' => 'Entity 1'],
			['id' => 2, 'name' => 'Entity 2'],
		], $result);
	}

	public function testQueryByIdsReturnsEntities(): void
	{
		$entityMock = Mockery::mock(BaseEntity::class);
		$entityMock->shouldReceive('toArray')->andReturn(['id' => 1, 'name' => 'Entity 1']);

		$this->repositoryMock
			->shouldReceive('findByIds')
			->with([1])
			->andReturn([$entityMock]);

		$result = $this->resolver->queryByIds(null, ['ids' => [1]], null, Mockery::mock(ResolveInfo::class));

		Assert::same([['id' => 1, 'name' => 'Entity 1']], $result);
	}

	public function testQueryByIdsNoEntitiesFound(): void
	{
		$this->repositoryMock
			->shouldReceive('findByIds')
			->with([1])
			->andReturn([]);

		$result = $this->resolver->queryByIds(null, ['ids' => [1]], null, Mockery::mock(ResolveInfo::class));

		Assert::same([], $result);
	}
}

(new BaseResolverTest())->run();
