# Library System API

PHP-based RESTful API provides CRUD (Create, Read, Update, Delete) operations for managing books and authors in a library system. It is built using the Slim Framework and follows standard RESTful principles, making it compatible with a wide range of client applications. By leveraging JWT-based authentication, this API ensures secure access to its endpoints, allowing only authorized users to manage the library’s catalog of books and authors.

## API Features

**Secure Token-Based Authentication:** Each request is authenticated with a JSON Web Token (JWT), ensuring only authorized users can perform operations. Tokens are validated with each request and refreshed as necessary for session continuity.

**User Registration and Authentication**: Allows users to register and log in securely. Each request is authenticated with a JSON Web Token (JWT), which is validated with each request and refreshed as needed to maintain session continuity.
  
**CRUD Operations for Authors**:
  - **Create Author**: Adds a new author to the catalog.
  - **Read Authors**: Retrieves a list of all authors, enabling clients to display and filter the author list.
  - **Update Author**: Allows modification of an author’s name.
  - **Delete Author**: Removes an author, first deleting any associated books to maintain referential integrity.
  

**CRUD Operations for Books:**

- **Create Book:** Adds a new book to the catalog, checking for duplicate titles by the same author.
- **Read Books:** Retrieves the list of all books in the catalog, allowing clients to display the library’s collection.
- **Update Book:** Modifies the title of an existing book, with validations to prevent duplicate entries by the same author.
- **Delete Book:** Removes a book from the catalog, first deleting any references to maintain referential integrity.

**Retrieve Authors and Their Books:** This endpoint lists all authors in the database along with the titles of their books, providing a structured response suitable for both displaying and filtering books by author.

# Endpoints

## User Registration

Registers a new user by providing a username and password.

- **Endpoint:** `/user/register`
- **Method:** `POST`
- **Request Body:**
  ```json
  {
    "username": "new_user",
    "password": "user_password"
  }
Response:
  {
  Success: {"status": "success", "data": null}
  }

  {
  Failure: {"status": "fail", "data": {"title": "Username already taken"}}
  }



This API implements security and reliability measures, including:

- **Token Refreshing:** Tokens are refreshed with each request, mitigating the risk of session hijacking.
- **Error Handling:** Detailed error messages are returned in case of database errors or validation failures, aiding both users and developers in understanding the issues.
- **Referential Integrity:** Careful handling of related entries (e.g., books and authors) ensures that deletions maintain database integrity by clearing linked references before proceeding.
