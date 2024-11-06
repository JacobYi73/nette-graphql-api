# Nette GraphQL API

This API project is built with **Nette 3.2**, **Nettrine**, and integrates **GraphQL** for managing data through flexible querying and mutation operations.

It is containerized with Docker for consistent development and production environments.

## Table of Contents

1. [Overview](#overview)
2. [Installation](#installation)
3. [Project Usage](#project-usage)
4. [GraphQL API Usage](#graphql-api-usage)
   - [Headers and Authentication](#headers-and-authentication)
   - [Book Queries](#book-queries)
5. [Testing](#testing)
6. [Database](#database)
7. [Production](#production)


## Overview

This Nette-based project provides an API interface via GraphQL for managing data, using the [webonyx GraphQL library](https://github.com/webonyx/graphql-php). Key entities include **Book**, which supports various CRUD operations.

## Installation

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/JacobYi73/nette-graphql-api.git
   ```

2. **Install Dependencies via Composer**:
   ```bash
   composer install
   ```

3. **Set Up Environment Configuration**:
   Copy configuration templates and set parameters as needed:
   ```bash
   cp .env.template .env
   cp app/config/local.neon.template app/config/local.neon
   ```

4. **Start Containers**:
   Start Docker containers using:
   ```bash
   docker-compose up -d
   ```

## Project Usage

Composer and symfony console **HAVE TO** be run from container `$PROJECT-php-fpm`.

To get shell inside container, run:

`docker exec -ti $PROJECT-php-fpm bash`

OR

`docker exec -ti php-fpm bash`

Commands `php` and `composer` inside the container are aliased to use UID and GID from .env file so they have same privileges on generated files as php-fpm called by webserver for development.

You need to do this to work with [Database](#database) and [Testing](#testing) accordingly.

## GraphQL API Usage

Access the GraphQL API at:
```
http://localhost:8900/graphql
```

**Note**: Use **Postman** or another GraphQL client to test queries and mutations.

You can import **Postman Collection** from file `NetteGraphQLAPI.postman_collection.json`

### Headers and Authentication

- **API Key**: Set the `apiKey` header to authenticate requests. If not provided, the API will default to guest access.
- **Token Configuration**: API keys are configured in `app/config/local.neon` under `parameters.graphql.tokens`.

### Book Queries

#### Retrieve a Single Book by ID
```graphql
{ 
  BookById(id: 2) { 
    id 
    name 
    author 
    releaseYear 
    genre 
    description 
  }
}
```

#### Retrieve All Books
```graphql
{ 
  BookAll { 
    id 
    name 
    author 
    releaseYear 
    genre 
    description 
  }
}
```

#### Insert a New Book
```graphql
mutation {
  BookInsert(
    name: "Book Title"
    author: "Author Name"
    releaseYear: 2022
    genre: "Fiction"
    description: "A description of the book"
  ) {
    name
  }
}
```

#### Update a Book (ID = 1)
```graphql
mutation {
  BookUpdate(
    id: 1
    name: "Updated Title"
    author: "Updated Author"
    releaseYear: 2023
    genre: "Non-fiction"
    description: "Updated description"
  ) {
    name
  }
}
```

#### Delete a Book by ID
```graphql
mutation {
  BookRemove(id: 3) {
    id
    name
    genre
  }
}
```

## GraphQL API File Structure

The project structure is designed to separate concerns and organize the GraphQL API logic into modular components. Here’s an overview of the main directories and files:

### Root Files
- **Bootstrap.php**: Initializes and configures the application, loading necessary dependencies and configurations.

### GraphQL
This directory contains all components for the GraphQL API, including configuration, resolvers, and schema definitions.

- **GraphqlConfig.php**: Contains configuration settings specific to the GraphQL API.
- **Resolvers**: Houses resolver classes responsible for handling GraphQL queries and mutations.
   - **BaseResolver.php**: A base class with common resolver logic that other resolvers can extend.
   - **BookResolver.php**: A resolver for `Book`-related GraphQL operations.
- **Schema.php**: Defines the schema structure and is responsible for linking types, queries, and mutations to their resolvers.
- **Schemas**: Contains `.graphql` schema definitions, organized by entity and access role.
   - **SchemaByRole**: Role-specific schemas, such as `admin.graphql`.
   - **Book.graphql**: Schema definitions related to `Book` entities.

### Model
This directory contains the business logic and data layer, including exception handling, database repositories, entities, and migrations.

- **Exceptions**: Custom exceptions used within the model layer.
   - **EntityCommitException.php**: Thrown on errors committing an entity change.
   - **EntityNotFoundException.php**: Thrown when an entity cannot be found.
   - **DuplicateEntryException.php**: Thrown on attempts to create duplicate entries.
- **Database**: Manages database operations and interactions.
   - **Repository**: Contains classes that manage data retrieval and storage.
      - **BookRepository.php**: Repository for interacting with `Book` data.
      - **BaseRepository.php**: A base repository with shared logic for other repositories.
   - **EntityManager.php**: Manages entity lifecycle and interactions.
   - **Entity**: Defines database entities.
      - **BaseEntity.php**: A base entity class with common attributes and methods.
      - **Book.php**: The `Book` entity representing a table in the database.
   - **Migrations**: Database migration files for versioning schema changes.

## Testing

The following commands ensure code quality:

- **Static Analysis**: `composer phpstan`
- **Unit tests**: `composer tester`

## Database

### Entity Definition

Doctrine entities are defined using PHP 8 attributes. Types are automatically determined by property types, except for custom enum types.

### Migrations

To manage migrations:

```bash
php bin/console.php nette:cache:purge
php bin/console.php migrations:migrate
php bin/console.php migrations:diff
```

Ensure no pending or missing migrations are flagged by `migrations:diff` to avoid potential conflicts during merges.

## Production

For production deployment, basic configuration files are in the `production` folder. Copy the following files to the server:

```plaintext
.env.template
docker-compose.yml
nginx.conf
php-ini-overrides.ini
```

Rename `.env.template` to `.env` and modify settings as necessary. Adjust `docker-compose.yml` to pull Docker images from the registry instead of building locally