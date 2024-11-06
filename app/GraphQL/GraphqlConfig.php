<?php

declare(strict_types=1);

namespace App\GraphQL;

use Nette\Http\IRequest;

class GraphqlConfig
{
    private const ROLE_ADMIN = 'admin';

    public const ROLE = [
        self::ROLE_ADMIN => 1,
    ];

    private string $apiKeyName;
    private int $maxDepth;
    private int $maxComplexity;
    private string $defaultQuery;
    private int $defaultLangId;
    private string $guestSchemaName;

    /**
     * @var array<string, string>
     */
    private array $tokens;
    private IRequest $httpRequest;

    /**
     * @param array<string, string> $tokens
     */
    public function __construct(IRequest $httpRequest, int $maxDepth = 10, int $maxComplexity = 1000, string $defaultQuery = '', string $apiKeyName = '', string $guestSchemaName = '', array $tokens = [], int $defaultLangId = 1)
    {
        $this->maxDepth = $maxDepth;
        $this->maxComplexity = $maxComplexity;
        $this->defaultQuery = $defaultQuery;
        $this->apiKeyName = $apiKeyName;
        $this->tokens = $tokens;
        $this->guestSchemaName = $guestSchemaName;
        $this->defaultLangId = $defaultLangId;

        $this->httpRequest = $httpRequest;
    }

    public function getApiKeyName(): string
    {
        return $this->apiKeyName;
    }

    public function getMaxDepth(): int
    {
        return $this->maxDepth;
    }
    public function getGuestSchemaName(): string
    {
        return $this->guestSchemaName;
    }

    public function getMaxComplexity(): int
    {
        return $this->maxComplexity;
    }
    public function getDefaultQuery(): string
    {
        return $this->defaultQuery;
    }
    /**
     * @return array<string,string>
     */
    private function getTokens(): array
    {
        return $this->tokens;
    }
    public function getDefaultLangId(): int
    {
        return $this->defaultLangId;
    }

    public function getRoleId(): int
    {
        $requestToken = $this->httpRequest->getHeader($this->getApiKeyName());

        if (!is_string($requestToken)) {
            return 0;
        }

        foreach ($this->getTokens() as $roleName => $token) {
            if ($token == $requestToken && isset(self::ROLE[$roleName])) {
                return self::ROLE[$roleName];
            }
        }
        return 0;
    }

    public function getRoleName(): string
    {
        $roleName = array_search($this->getRoleId(), self::ROLE);
        if ($roleName === false) {
            if ($this->getRoleId() == 0) {
                $roleName = $this->getGuestSchemaName();
            } else {
                throw new \Exception(" GraphQL Schema: Role not found.");
            }
        }
        return $roleName;
    }
}
