# Library System API

This PHP-based RESTful API provides CRUD (Create, Read, Update, Delete) operations for managing books and authors in a library system. It is built using the Slim Framework and follows standard RESTful principles, making it compatible with a wide range of client applications. By leveraging JWT-based authentication, this API ensures secure access to its endpoints, allowing only authorized users to manage the library’s catalog of books and authors.

## API Features

**Secure Token-Based Authentication:** Each request is authenticated with a JSON Web Token (JWT), ensuring only authorized users can perform operations. Tokens are validated with each request and refreshed as necessary for session continuity.

**CRUD Operations for Books:**

- **Create Book:** Adds a new book to the catalog, checking for duplicate titles by the same author.
- **Read Books:** Retrieves the list of all books in the catalog, allowing clients to display the library’s collection.
- **Update Book:** Modifies the title of an existing book, with validations to prevent duplicate entries by the same author.
- **Delete Book:** Removes a book from the catalog, first deleting any references to maintain referential integrity.

**Retrieve Authors and Their Books:** This endpoint lists all authors in the database along with the titles of their books, providing a structured response suitable for both displaying and filtering books by author.

## Endpoint Descriptions and Workflow

**POST /user/book/create**
This endpoint allows users to create a new book entry in the library’s database. The request requires the book title, the author ID, and a valid JWT token. Before adding the book, it checks if an entry with the same title and author ID already exists to prevent duplicates.

**GET /user/book/read**
This endpoint retrieves a list of all books in the library. A valid JWT token must be provided. The response includes a refreshed token for session management and a list of books with basic details like title and author ID.

**PUT /user/book/update**
To update a book’s title, clients can use this endpoint by providing the book ID, new title, and a valid token. The API validates that the new title does not duplicate another entry by the same author and updates the book’s title in the database.

**DELETE /user/book/delete**
Users can delete a specific book by providing its book ID and a valid token. Before deletion, the API ensures all references in the books_authors table are removed to avoid foreign key constraint violations.

**GET /author_books**
This endpoint lists all authors and their books, grouped under each author’s entry. It provides an easy way to access an author’s catalog and is suitable for applications requiring hierarchical data presentation.

## Security and Error Handling

This API implements security and reliability measures, including:

- **Token Refreshing:** Tokens are refreshed with each request, mitigating the risk of session hijacking.
- **Error Handling:** Detailed error messages are returned in case of database errors or validation failures, aiding both users and developers in understanding the issues.
- **Referential Integrity:** Careful handling of related entries (e.g., books and authors) ensures that deletions maintain database integrity by clearing linked references before proceeding.
