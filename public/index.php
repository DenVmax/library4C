<?php
//Denver B. Umbay 
//BSIT 4C

//liblaries dowloaded inside the vendor
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response; 
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require '../src/vendor/autoload.php';

$app = new \Slim\App;

// Database credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "library";


// Function to generate a new token
function generateToken($userId, $username, $conn) {
    $key = 'your-secret-key';
    $issuedAt = time();
    $expirationTime = $issuedAt + 300; // Token valid for 5 minutes if not used

    // Invalidate previous tokens for the user
    $invalidate_sql = "DELETE FROM tokens WHERE userid = :userId";
    $invalidate_stmt = $conn->prepare($invalidate_sql);
    $invalidate_stmt->execute(['userId' => $userId]);

    // Generate and store the new token
    $payload = [
        'userId' => $userId,
        'iat' => $issuedAt,
        'exp' => $expirationTime,
        'data' => ['username' => $username]
    ];
    $jwt = JWT::encode($payload, $key, 'HS256');

    $store_sql = "INSERT INTO tokens (userid, token, expires_at) VALUES (:userId, :token, :expiresAt)";
    $store_stmt = $conn->prepare($store_sql);
    $store_stmt->execute(['userId' => $userId, 'token' => $jwt, 'expiresAt' => $expirationTime]);

    return $jwt;
}


// Validate token
function validateToken($token, $conn) {
    $key = 'your-secret-key';

    try {
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
        $userId = $decoded->userId;
        $currentTimestamp = time();

        $check_sql = "SELECT * FROM tokens WHERE userid = :userId AND token = :token AND expires_at > :currentTimestamp";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->execute(['userId' => $userId, 'token' => $token, 'currentTimestamp' => $currentTimestamp]);
        $validToken = $check_stmt->fetch();

        // Invalidate token after use if it is valid
        if ($validToken) {
            $invalidate_sql = "DELETE FROM tokens WHERE token = :token";
            $invalidate_stmt = $conn->prepare($invalidate_sql);
            $invalidate_stmt->execute(['token' => $token]);
            return $decoded; // Return the decoded token here
        }

        return false; // Token is invalid/expired
    } catch (Exception $e) {
        return false;
    }
}


// Register a new user
$app->post('/user/register', function (Request $request, Response $response, array $args) {
    error_reporting(E_ALL);
    $data = json_decode($request->getBody());
    $uname = $data->username;
    $pass = $data->password;

    try {
        $conn = new PDO("mysql:host=localhost;dbname=library", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $check_sql = "SELECT COUNT(*) FROM users WHERE username = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->execute([$uname]);
        $userExists = $stmt->fetchColumn();

        if ($userExists > 0) {
            return $response->getBody()->write(json_encode([
                "status" => "fail",
                "data" => ["title" => "Username already taken"]
            ]));
        }

        $sql = "INSERT INTO users (username, password) VALUES (:username, :password)";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['username' => $uname, 'password' => hash('sha256', $pass)]);

        return $response->getBody()->write(json_encode(["status" => "success", "data" => null]));
    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode([
            "status" => "fail",
            "data" => ["title" => $e->getMessage()]
        ]));
    }
});


// Authenticate user and generate token
$app->post('/user/auth', function (Request $request, Response $response, array $args) {
    error_reporting(E_ALL);
    $data = json_decode($request->getBody());
    $uname = $data->username;
    $pass = $data->password;

    try {
        $conn = new PDO("mysql:host=localhost;dbname=library", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT * FROM users WHERE username = :username AND password = :password";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['username' => $uname, 'password' => hash('sha256', $pass)]);
        $user = $stmt->fetch();

        if ($user) {
            $token = generateToken($user['userid'], $user['username'], $conn);
            return $response->getBody()->write(json_encode(["status" => "success", "token" => $token, "data" => null]));
        } else {
            return $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => "Authentication Failed"]]));
        }
    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
    }
});



// CRUD operations for authors with one-time-use tokens

// Author Create
$app->post('/user/author/create', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $author_name = trim($data->name); // Trim any whitespace
    $token = $data->token;

    try {
        $conn = new PDO("mysql:host=localhost;dbname=library", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $decoded = validateToken($token, $conn);
        if (!$decoded) {
            return $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => "Invalid token"]]));
        }

        // Check if the author name is blank
        if ($author_name === '') {
            return $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => "Author name cannot be blank"]]));
        }

        // Check if author already exists
        $checkSql = "SELECT authorid FROM authors WHERE name = :name";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->execute(['name' => $author_name]);

        if ($checkStmt->fetch()) {
            return $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => "Author already exists"]]));
        }

        // Insert new author
        $sql = "INSERT INTO authors (name) VALUES (:name)";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['name' => $author_name]);

        $new_token = generateToken($decoded->userId, $decoded->data->username, $conn);
        return $response->getBody()->write(json_encode(["status" => "success", "token" => $new_token, "data" => null]));
    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
    }
});


// Author Read
$app->get('/user/author/read', function (Request $request, Response $response, array $args) {
    $token = $request->getHeader('Authorization')[0];
    $token = str_replace('Token ', '', $token);

    try {
        $conn = new PDO("mysql:host=localhost;dbname=library", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $decoded = validateToken($token, $conn);
        if (!$decoded) {
            return $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => "Invalid token"]]));
        }

        $sql = "SELECT * FROM authors";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $new_token = generateToken($decoded->userId, $decoded->data->username, $conn);
        return $response->getBody()->write(json_encode(["status" => "success", "token" => $new_token, "data" => $authors]));
    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
    }
});


// Author Update
$app->put('/user/author/update', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $author_id = $data->authorid;
    $author_name = trim($data->name); // Trim any whitespace
    $token = $data->token;

    try {
        $conn = new PDO("mysql:host=localhost;dbname=library", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $decoded = validateToken($token, $conn);
        if (!$decoded) {
            return $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => "Invalid token"]]));
        }

        // Skip update if name is blank
        if ($author_name === '') {
            return $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => "Author name cannot be blank"]]));
        }

        // Check if new author name already exists for another author
        $checkSql = "SELECT authorid FROM authors WHERE name = :name AND authorid != :authorid";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->execute(['name' => $author_name, 'authorid' => $author_id]);

        if ($checkStmt->fetch()) {
            return $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => "Author name already in use by another author"]]));
        }

        // Proceed with update
        $sql = "UPDATE authors SET name = :name WHERE authorid = :authorid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['name' => $author_name, 'authorid' => $author_id]);

        $new_token = generateToken($decoded->userId, $decoded->data->username, $conn);
        return $response->getBody()->write(json_encode(["status" => "success", "token" => $new_token, "data" => null]));
    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
    }
});


// Author Delete
$app->delete('/user/author/delete', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $author_id = $data->authorid;
    $token = $data->token;

    try {
        $conn = new PDO("mysql:host=localhost;dbname=library", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $decoded = validateToken($token, $conn);
        if (!$decoded) {
            return $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => "Invalid token"]]));
        }

        // Check if author exists
        $checkSql = "SELECT authorid FROM authors WHERE authorid = :authorid";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->execute(['authorid' => $author_id]);

        if (!$checkStmt->fetch()) {
            return $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => "Author does not exist"]]));
        }

        // Delete author
        $sql = "DELETE FROM authors WHERE authorid = :authorid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['authorid' => $author_id]);

        $new_token = generateToken($decoded->userId, $decoded->data->username, $conn);
        return $response->getBody()->write(json_encode(["status" => "success", "token" => $new_token, "data" => null]));
    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
    }
});


// CRUD operations for books 

// Create book
$app->post('/user/book/create', function (Request $request, Response $response, array $args) {
    error_reporting(E_ALL);
    $data = json_decode($request->getBody());
    $title = trim($data->title);
    $authorid = $data->authorid;
    $token = $data->token;

    try {
        // Database connection
        $conn = new PDO("mysql:host=localhost;dbname=library", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Validate the token using the validateToken function
        $decoded = validateToken($token, $conn);
        if (!$decoded) {
            return $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => "Invalid token"]]));
        }

        // Check if the title or author ID is blank
        if (empty($title) || empty($authorid)) {
            return $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => "Title and Author ID cannot be blank"]]));
        }

        // Verify if the author exists in the database
        $author_check_sql = "SELECT authorid FROM authors WHERE authorid = :authorid";
        $author_check_stmt = $conn->prepare($author_check_sql);
        $author_check_stmt->execute(['authorid' => $authorid]);
        if (!$author_check_stmt->fetch()) {
            return $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => "Author does not exist"]]));
        }

        // Check if the book by the same author already exists
        $check_sql = "SELECT * FROM books WHERE title = :title AND authorid = :authorid";
        $stmt = $conn->prepare($check_sql);
        $stmt->execute(['title' => $title, 'authorid' => $authorid]);
        
        if ($stmt->fetch()) {
            return $response->getBody()->write(json_encode(["status" => "fail", "message" => "This book by the same author already exists."]));
        }

        // Insert the new book
        $insert_book_sql = "INSERT INTO books (title, authorid) VALUES (:title, :authorid)";
        $stmt = $conn->prepare($insert_book_sql);
        $stmt->execute(['title' => $title, 'authorid' => $authorid]);
        $bookid = $conn->lastInsertId();

        // Link the book with the author
        $link_sql = "INSERT INTO books_authors (bookid, authorid) VALUES (:bookid, :authorid)";
        $stmt = $conn->prepare($link_sql);
        $stmt->execute(['bookid' => $bookid, 'authorid' => $authorid]);

        // Generate a new token
        $new_token = generateToken($decoded->userId, $decoded->data->username, $conn);
        return $response->getBody()->write(json_encode(["status" => "success", "token" => $new_token, "data" => null]));

    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
    }
});


// Read book
$app->get('/user/book/read', function (Request $request, Response $response, array $args) {
    error_reporting(E_ALL);
    $token = $request->getHeader('Authorization')[0];
    $token = str_replace('Token ', '', $token);

    try {
        // Database connection
        $conn = new PDO("mysql:host=localhost;dbname=library", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Validate the token
        $decoded = validateToken($token, $conn);
        if (!$decoded) {
            return $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => "Invalid token"]]));
        }

        // Query to fetch books
        $sql = "SELECT * FROM books";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Generate a new token
        $new_token = generateToken($decoded->userId, $decoded->data->username, $conn);

        // Return the result with the new token
        return $response->getBody()->write(json_encode([
            "status" => "success",
            "token" => $new_token,
            "data" => $result
        ]));

    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
    } finally {
        $conn = null;
    }
});


// Update book
$app->put('/user/book/update', function (Request $request, Response $response, array $args) {
    error_reporting(E_ALL);
    $data = json_decode($request->getBody());
    $bookid = $data->bookid;
    $title = trim($data->title); // Trim whitespace
    $token = $data->token;

    try {
        // Database connection
        $conn = new PDO("mysql:host=localhost;dbname=library", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Validate the token
        $decoded = validateToken($token, $conn);
        if (!$decoded) {
            return $response->getBody()->write(json_encode(["status" => "fail", "message" => "Invalid Token"]));
        }

        // Check if the title is blank
        if ($title === '') {
            return $response->getBody()->write(json_encode(["status" => "fail", "message" => "Book title cannot be blank."]));
        }

        // Fetch current book details to get the author ID
        $currentBookSql = "SELECT authorid FROM books WHERE bookid = :bookid";
        $currentBookStmt = $conn->prepare($currentBookSql);
        $currentBookStmt->execute(['bookid' => $bookid]);
        $currentBook = $currentBookStmt->fetch(PDO::FETCH_ASSOC);

        if (!$currentBook) {
            return $response->getBody()->write(json_encode(["status" => "fail", "message" => "Book not found."]));
        }

        $authorid = $currentBook['authorid'];

        // Check for existing book with the same title and author (excluding the current book)
        $checkSql = "SELECT * FROM books WHERE title = ? AND authorid = ? AND bookid != ?";
        $stmt = $conn->prepare($checkSql);
        $stmt->execute([$title, $authorid, $bookid]);
        $existing_book = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing_book) {
            return $response->getBody()->write(json_encode([
                "status" => "fail",
                "message" => "This book by the same author already exists."
            ]));
        }

        // Proceed with the update
        $sql = "UPDATE books SET title = ? WHERE bookid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$title, $bookid]);

        // Generate a new token
        $new_token = generateToken($decoded->userId, $decoded->data->username, $conn);
        return $response->getBody()->write(json_encode([
            "status" => "success",
            "token" => $new_token,
            "data" => null
        ]));

    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode([
            "status" => "fail",
            "data" => ["title" => $e->getMessage()]
        ]));
    } finally {
        $conn = null;
    }
});


// Delete book
$app->delete('/user/book/delete', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $bookid = $data->bookid;
    $token = $data->token;

    try {
        // Database connection
        $conn = new PDO("mysql:host=localhost;dbname=library", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Validate the token
        $decoded = validateToken($token, $conn);
        if (!$decoded) {
            return $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => "Invalid token"]]));
        }

        // Check if the book exists
        $checkSql = "SELECT bookid FROM books WHERE bookid = :bookid";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->execute(['bookid' => $bookid]);
        if (!$checkStmt->fetch()) {
            return $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => "Book does not exist"]]));
        }

        // Delete from `books_authors` table first to maintain referential integrity
        $sql = "DELETE FROM books_authors WHERE bookid = :bookid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['bookid' => $bookid]);

        // Delete from `books` table
        $sql = "DELETE FROM books WHERE bookid = :bookid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['bookid' => $bookid]);

        // Generate a new token
        $new_token = generateToken($decoded->userId, $decoded->data->username, $conn);
        return $response->getBody()->write(json_encode([
            "status" => "success",
            "token" => $new_token,
            "data" => null
        ]));

    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode([
            "status" => "fail",
            "data" => ["title" => $e->getMessage()]
        ]));
    } finally {
        $conn = null;
    }
});


// Retrieve authors and their books
$app->get('/author_books', function (Request $request, Response $response, array $args) {
    $token = $request->getHeader('Authorization')[0]; 
    $token = str_replace('Token ', '', $token);

    try {
        // Database connection
        $conn = new PDO("mysql:host=localhost;dbname=library", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Validate the token
        $decoded = validateToken($token, $conn);
        if (!$decoded) {
            return $response->getBody()->write(json_encode(["status" => "fail", "data" => ["title" => "Invalid token"]]));
        }

        // Fetch authors and their books with IDs
        $sql = "SELECT authors.authorid, authors.name AS author_name, books.bookid, books.title AS book_title
                FROM authors
                LEFT JOIN books ON books.authorid = authors.authorid";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the result
        $result = [];
        foreach ($data as $row) {
            // Initialize author entry if not already created
            if (!isset($result[$row['authorid']])) {
                $result[$row['authorid']] = [
                    "author_id" => $row['authorid'],
                    "author" => $row['author_name'],
                    "books" => []
                ];
            }
            // Append book information with ID
            $result[$row['authorid']]['books'][] = [
                "book_id" => $row['bookid'],
                "book_title" => $row['book_title']
            ];
        }

        // Reset array keys to be numeric
        $result = array_values($result);

        // Generate a new token for the session
        $new_token = generateToken($decoded->userId, $decoded->data->username, $conn);
        return $response->getBody()->write(json_encode([
            "status" => "success",
            "token" => $new_token,
            "data" => $result
        ]));

    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode([
            "status" => "fail",
            "data" => ["title" => $e->getMessage()]
        ]));
    } finally {
        $conn = null;
    }
});


$app->run();




