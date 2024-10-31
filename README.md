# Library System API

PHP-based RESTful API provides CRUD (Create, Read, Update, Delete) operations for managing books and authors in a library system. It is built using the Slim Framework and follows standard RESTful principles, making it compatible with a wide range of client applications. By leveraging JWT-based authentication, this API ensures secure access to its endpoints, allowing only authorized users to manage the library’s catalog of books and authors.

## API Features

**Secure Token-Based Authentication:** Each request is authenticated with a JSON Web Token (JWT), ensuring only authorized users can perform operations. Tokens are validated with each request and refreshed as necessary for session continuity.

**User Registration and Authentication**: Allows users to register and log in securely. Each request is authenticated with a JSON Web Token (JWT), which is validated with each request and refreshed as needed to maintain session continuity.
  
**CRUD Operations for Authors**:
  - **Create Author**: Adds a new author to the database.
  - **Read Authors**: Retrieves a list of all authors.
  - **Update Author**: Allows modification of an author’s name.
  - **Delete Author**: Removes an author, first deleting any associated books to maintain referential integrity.
  

**CRUD Operations for Books:**

- **Create Book:** Adds a new book to the database, checking for duplicate titles by the same author.
- **Read Books:** Retrieves the list of all books in the database.
- **Update Book:** Modifies the title of an existing book, with validations to prevent duplicate entries by the same author.
- **Delete Book:** Removes a book from the database, first deleting any references to maintain referential integrity.

**Retrieve Authors and Their Books:** This endpoint lists all authors in the database along with the titles of their books, providing a structured response suitable for both displaying and filtering books by author.

# Endpoints

## 1. User Registration

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

- Success
  ```json
  {
    "status": "success",
    "data": null
  }
  ```
- Fail
  ```json
  {
    "status": "fail",
    "data": {
      "title": "Username already taken"
    }
  }
  ```

## 2. User Authentication

Authenticates a user and generates a JWT.

- **Endpoint:** `/user/auth`
- **Method:** `POST`
- **Request Body:**
  ```json
  {
    "username": "existing_user",
    "password": "user_password"
  }
  
Response:

- Success
  ```json
  {
    "status": "success",
    "token": "jwt_token"
    "data": null
  }
  ```
- Fail
  ```json
  {
    "status": "fail",
    "data": {
      "title": "Authentication Failed"
    }
  }
  ```

## Endpoints for Author Management

### 3. Create Author

Creates a new author entry. Requires a valid one-time-use token.

- **Endpoint:** `/user/author/create`
- **Method:** `POST`
- **Request Body:**
  ```json
  {
    "name": "Author Name",
    "token": "valid_jwt_token"
  }

Response:

- Success
  ```json
  {
    "status": "success",
    "token": "new_jwt_token"
    "data": null
  }
  ```
- Fail
  ```json
  {
    "status": "fail",
    "data": {
      "title": "Invalid Token"
    }
  }
  ```
  ```json
  {
    "status": "fail",
    "data": {
      "title": "Author already exists"
    }
  }
  ```
  ```json
  {
    "status": "fail",
    "data": {
      "title": "Author name cannot be blank"
    }
  }
  ```

### 4. Read Authors

Retrieves a list of authors. Requires a valid token in the Authorization header.

- **Endpoint:** `/user/author/read`
- **Method:** `GET`
- **Headers:**
  ```plaintext
  Authorization:     Token eyJ0eXAiOiJ....(valid_jwt_token)

Response:

- Success
  ```json
  {
    "status": "success",
    "token": "new_jwt_token"
    "data": [
      {
        "authorid": 1,
        "name": "Denver Umbay"
      }
    ]
  }
  ```
- Fail
  ```json
  {
    "status": "fail",
    "data": {
      "title": "Invalid Token"
    }
  }
  ```

### 5. Update Author

Updates an author's details by authorid. Requires a valid token.

- **Endpoint:** `/user/author/update`
- **Method:** `PUT`
- **Request Body:**
  ```json
  {
    "authorid": 1,
    "name": "Updated Author Name",
    "token": "valid_jwt_token"
  }

Response:

- Success
  ```json
  {
    "status": "success",
    "token": "new_jwt_token"
    "data": null
  }
  ```
- Fail
  ```json
  {
    "status": "fail",
    "data": {
      "title": "Invalid Token"
    }
  }
  ```
  ```json
  {
    "status": "fail",
    "data": {
      "title": "Author name already in use by another author"
    }
  }
  ```
  ```json
  {
    "status": "fail",
    "data": {
      "title": "Author name cannot be blank"
    }
  }
  ```

### 6. Delete Author

Deletes an author by authorid. Requires a valid token.

- **Endpoint:** `/user/author/delete`
- **Method:** `DELETE`
- **Request Body:**
  ```json
  {
    "authorid": 1,
    "token": "valid_jwt_token"
  }

Response:

- Success
  ```json
  {
    "status": "success",
    "token": "new_jwt_token"
    "data": null
  }
  ```
- Fail
  ```json
  {
    "status": "fail",
    "data": {
      "title": "Invalid Token"
    }
  }
  ```
  ```json
  {
    "status": "fail",
    "data": {
      "title": "Author does not exist"
    }
  }
  ```

## Endpoints for Book Management

### 7. Create Book

Adds a new book to the library database The book title and author ID must be provided, and duplicates by the same author are prevented.

- **Endpoint:** `/user/book/create`
- **Method:** `POST`
- **Request Body:**
  ```json
  {
    "title": "Book Title",
    "authorid": "Author ID",
    "token": "valid_jwt_token"
  }

Response:

- Success
  ```json
  {
    "status": "success",
    "token": "new_jwt_token"
    "data": null
  }
  ```
- Fail
  ```json
  {
    "status": "fail",
    "data": {
      "title": "Invalid Token"
    }
  }
  ```
  ```json
  {
    "status": "fail",
    "data": {
      "title": "Title and Author ID cannot be blank"
    }
  }
  ```
   ```json
  {
    "status": "fail",
    "data": {
      "title": "Author does not exist"
    }
  }
  ```
  ```json
  {
    "status": "fail",
    "data": {
      "title": "This book by the same author already exists"
    }
  }
  ```
### 8. Read Books

Retrieves a list of all books in the library database.

- **Endpoint:** `/user/book/read`
- **Method:** `GET`
- **Headers:**
  ```plaintext
  Authorization:     Token eyJ0pXAsOiJ....(valid_jwt_token)

Response:

- Success
  ```json
  {
    "status": "success",
    "token": "new_jwt_token"
    "data": [
      {
        "bookid": 21,
        "title": "Soldier, Poet, King",
        "authorid": 1
      }
    ]
  }
  ```
- Fail
  ```json
  {
    "status": "fail",
    "data": {
      "title": "Invalid Token"
    }
  }
  ```

### 9. Update Book

Updates the title of an existing book in the library database. This endpoint ensures that the new title doesn’t duplicate another book by the same author.

- **Endpoint:** `/user/book/update`
- **Method:** `PUT`
- **Request Body:**
  ```json
  {
    "bookid": "Book ID",
    "title": "New Title",
    "token": "valid_jwt_token"
  }

Response:

- Success
  ```json
  {
    "status": "success",
    "token": "new_jwt_token"
    "data": null
  }
  ```
- Fail
  ```json
  {
    "status": "fail",
    "data": {
      "title": "Invalid Token"
    }
  }
  ```
  ```json
  {
    "status": "fail",
    "data": {
      "title": "Book title cannot be blank"
    }
  }
  ```
  ```json
  {
    "status": "fail",
    "data": {
      "title": "Book not found"
    }
  }
  ```
  ```json
  {
    "status": "fail",
    "data": {
      "title": "This book by the same author already exists"
    }
  }
  ```

### 10. Delete Book

Deletes a book from the library catalog. It removes the book’s references from linked tables before deleting the entry.

- **Endpoint:** `/user/book/delete`
- **Method:** `DELETE`
- **Request Body:**
  ```json
  {
    "bookid": "Book ID",
    "token": "valid_jwt_token"
  }

Response:

- Success
  ```json
  {
    "status": "success",
    "token": "new_jwt_token"
    "data": null
  }
  ```
- Fail
  ```json
  {
    "status": "fail",
    "data": {
      "title": "Invalid Token"
    }
  }
  ```
  ```json
  {
    "status": "fail",
    "data": {
      "title": "Book does not exist"
    }
  }
  ```

### 11. Retrieve Authors and Their Books

Retrieves a list of authors and the titles of books they have authored.

- **Endpoint:** `/author_books`
- **Method:** `GET`
- **Headers:**
  ```plaintext
  Authorization:     Token eyP0pXLsOiJ....(valid_jwt_token)

Response:

- Success
  ```json
  {
    "status": "success",
    "token": "new_jwt_token"
    "data": [
      {
        "authorid": 1,
        "author": "Denver Umbay",
        "title": "Soldier, Poet, King",
        "books": [
        {
          "bookid": 21,
          "book_title": "Soldier, Poet, King"
        },
        {
          "bookid": 30,
          "book_title": "No Sacrifice, No Victory"
        }
        ]
      }
    ]
  }
  ```
- Fail
  ```json
  {
    "status": "fail",
    "data": {
      "title": "Invalid Token"
    }
  }
  ```






#### These API implements security and reliability measures, including:

- Tokens are refreshed with each request.
- Detailed error messages are returned in case of database errors or validation failures.
- Careful handling of related entries (e.g., books and authors) ensures that deletions maintain database integrity by clearing linked references before proceeding.




    ___         __  __                        __   __                              
   /   | __  __/ /_/ /_  ____  ________  ____/ /  / /_  __  ___                    
  / /| |/ / / / __/ __ \/ __ \/ ___/ _ \/ __  /  / __ \/ / / (_)                   
 / ___ / /_/ / /_/ / / / /_/ / /  /  __/ /_/ /  / /_/ / /_/ /                      
/_/  |_\__,_/\__/_/ /_/\____/_/   \___/\__,_/  /_.___/\__, (_)                     
    ____                               ____       __ /____/       __               
   / __ \___  ____ _   _____  _____   / __ )     / / / /___ ___  / /_  ____ ___  __
  / / / / _ \/ __ \ | / / _ \/ ___/  / __  |    / / / / __ `__ \/ __ \/ __ `/ / / /
 / /_/ /  __/ / / / |/ /  __/ /     / /_/ /    / /_/ / / / / / / /_/ / /_/ / /_/ / 
/_____/\___/_/ /_/|___/\___/_/     /_____(_)   \____/_/ /_/ /_/_.___/\__,_/\__, /  
                                                                          /____/   





