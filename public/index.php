<?php
//liblaries dowloaded inside the vendor
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response; 
use Firebase\JWT\JWT;
use Firebase\JWT\Key;


require '../src/vendor/autoload.php';

$app = new \Slim\App;

$app->post('/user/register', function (Request $request, Response $response, 
array $args) {
    error_reporting(E_ALL);
    $data = json_decode($request->getBody());
    $uname =$data->username;
    $pass =$data->password;
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $check_sql = "SELECT COUNT(*) FROM users WHERE username = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->execute([$uname]);
        $userExists = $stmt->fetchColumn();
        if ($userExists > 0) {
            return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Username already taken"))));
        }
        $sql = "INSERT INTO users (username, password)
        VALUES ('". $uname ."', '". hash('sha256', $pass) ."' ) ";
        $conn->exec($sql);
        $response->getBody()->write(
            json_encode(array(
                "status"=>"success","data"=>null)));
    } catch(PDOException $e) {
        json_encode(array("
        status"=>"fail","data"=>
        array("title"=>$e->getMessage())));
    }

    $conn = null;
    return $response;


});

$app->post('/user/auth', function (Request $request, Response $response, 
array $args) {
    error_reporting(E_ALL);
    $data = json_decode($request->getBody());
    $uname =$data->username;
    $pass =$data->password;
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "SELECT * FROM users WHERE username='". $uname ."'
                AND password='". hash('SHA256',$pass) ."'";
        $stmt=$conn->prepare($sql);
        $stmt->execute();

        $result=$stmt->setFetchMode(PDO::FETCH_ASSOC);
        $data=$stmt->fetchAll();
        if(count($data)==1){
            $key = 'server_hack';
            $iat=time();
            $payload = [
                'iss' => 'http://liblary.org',
                'aus' => 'http://liblary.com',
                'exp' => $iat + 300,
                "data" => array(
                    "username" => $data[0]['username']
                )
            ];
            $jwt = JWT::encode($payload, $key, 'HS256');
            $response->getBody()->write(json_encode(
            array("status"=>"succes","token"=>$jwt,"data"=>null)));
        }else{
            $response->getBody()->write(json_encode(array("status"=>"fail",
            "data"=>array("title"=>"Authentication Failed"))));
            
        }

    } catch(PDOException $e) {
        $response->getBody()->write(json_encode(array("status"=>"fail",
        "data"=>array("title"=>$e->getMessage()))));
    }

    $conn = null;
    return $response;  

});


// CRUD operations for authors (token required)

function verifyToken($token) {
    $key = 'server_hack';
    try {
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
        return $decoded;
    } catch (Exception $e) {
        return null;
    }
}

//author create
$app->post('/user/author/create', function (Request $request, Response $response, array $args) {
    error_reporting(E_ALL);
    $data = json_decode($request->getBody());
    $author_name = $data->name;
    $token = $data->token;
    $decoded = verifyToken($token);
    if (!$decoded) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Invalid token"))));
    }
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "INSERT INTO authors (name) VALUES (:name)";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['name' => $author_name]);
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aus' => 'http://library.com',
            'exp' => $iat + 300,
            "data" => array("username" => $decoded->data->username)
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');

        $response->getBody()->write(json_encode(array("status" => "success", "token" => $jwt, "data" => null)));

    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }

    $conn = null;
    return $response;
});

//author read
$app->get('/user/author/read', function (Request $request, Response $response, array $args) {
    error_reporting(E_ALL);
    $token = $request->getHeader('Authorization')[0]; 
    $token = str_replace('Token ', '', $token);
    $decoded = verifyToken($token);
    if (!$decoded) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Invalid token"))));
    }
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "SELECT * FROM authors";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response->getBody()->write(json_encode(array("status" => "success", "data" => $authors)));

    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }

    $conn = null;
    return $response;
});

//author update
$app->put('/user/author/update', function (Request $request, Response $response, array $args) {
    error_reporting(E_ALL);
    $data = json_decode($request->getBody());
    $author_id = $data->authorid;
    $author_name = $data->name;
    $token = $data->token;
    $key = 'server_hack';
    try {
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
    } catch (Exception $e) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Invalid token"))));
    }
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $check_sql = "SELECT COUNT(*) FROM authors WHERE name = :name AND authorid != :authorid";
        $stmt = $conn->prepare($check_sql);
        $stmt->execute(['name' => $author_name, 'authorid' => $author_id]);
        $authorExists = $stmt->fetchColumn();
        if ($authorExists > 0) {
            return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "This author name already exists."))));
        }
        $sql = "UPDATE authors SET name = :name WHERE authorid = :authorid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['name' => $author_name, 'authorid' => $author_id]);
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'exp' => $iat + 300,
            "data" => array(
                "username" => $decoded->data->username
            )
        ];
        $new_token = JWT::encode($payload, $key, 'HS256');
        $response->getBody()->write(json_encode(array(
            "status" => "success",
            "token" => $new_token,
            "data" => null
        )));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }

    $conn = null;
    return $response;
});

    
//author delete
$app->delete('/user/author/delete', function (Request $request, Response $response, array $args) {
    error_reporting(E_ALL);
    $data = json_decode($request->getBody());
    $author_id = $data->authorid;
    $token = $data->token;
    $decoded = verifyToken($token);
    if (!$decoded) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Invalid token"))));
    }
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "DELETE FROM authors WHERE authorid = :authorid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['authorid' => $author_id]);
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aus' => 'http://library.com',
            'exp' => $iat + 300,
            "data" => array("username" => $decoded->data->username)
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');

        $response->getBody()->write(json_encode(array("status" => "success", "token" => $jwt, "data" => null)));

    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }

    $conn = null;
    return $response;
});


// CRUD operations for books (token required)


//create book
$app->post('/user/book/create', function (Request $request, Response $response, array $args) {
    error_reporting(E_ALL);
    $data = json_decode($request->getBody());
    $title = $data->title;
    $authorid = $data->authorid;
    $token = $data->token;
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";
    $key = 'server_hack';

    try {
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
    } catch (Exception $e) {
        return $response->getBody()->write(json_encode(["status" => "fail", "message" => "Invalid Token"]));
    }

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $check_sql = "SELECT * FROM books WHERE title = ? AND authorid = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->execute([$title, $authorid]);
        $existing_book = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing_book) {
            return $response->getBody()->write(json_encode([
                "status" => "fail",
                "message" => "This book by the same author already exists."
            ]));
        }
        $sql = "INSERT INTO books (title, authorid) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$title, $authorid]);
        $bookid = $conn->lastInsertId();
        $sql = "INSERT INTO books_authors (bookid, authorid) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$bookid, $authorid]);
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'exp' => $iat + 300,
            "data" => array(
                "username" => $decoded->data->username
            )
        ];
        $new_token = JWT::encode($payload, $key, 'HS256');
        $response->getBody()->write(json_encode(array(
            "status" => "success",
            "token" => $new_token,
            "data" => null
        )));

    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }

    $conn = null;
    return $response;
});


// read book
$app->get('/user/book/read', function (Request $request, Response $response, array $args) {
    error_reporting(E_ALL);
    $token = $request->getHeader('Authorization')[0];
    $token = str_replace('Token ', '', $token);
    $decoded = verifyToken($token);
    if (!$decoded) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Invalid token"))));
    }
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "SELECT * FROM books";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response->getBody()->write(json_encode(array("status" => "success", "data" => $result)));

    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }

    $conn = null;
    return $response;
});

//update book
$app->put('/user/book/update', function (Request $request, Response $response, array $args) {
    error_reporting(E_ALL);
    $data = json_decode($request->getBody());
    $bookid = $data->id;
    $title = $data->title;
    $authorid = $data->authorid;
    $token = $data->token;
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";
    $key = 'server_hack';

    try {
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
    } catch (Exception $e) {
        return $response->getBody()->write(json_encode(["status" => "fail", "message" => "Invalid Token"]));
    }

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $check_sql = "SELECT * FROM books WHERE title = ? AND authorid = ? AND bookid != ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->execute([$title, $authorid, $bookid]);
        $existing_book = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing_book) {
            return $response->getBody()->write(json_encode([
                "status" => "fail",
                "message" => "This book by the same author already exists."
            ]));
        }
        $sql = "UPDATE books SET title = ?, authorid = ? WHERE bookid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$title, $authorid, $bookid]);
        $sql = "UPDATE books_authors SET authorid = ? WHERE bookid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$authorid, $bookid]);
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'exp' => $iat + 300,
            "data" => array(
                "username" => $decoded->data->username
            )
        ];
        $new_token = JWT::encode($payload, $key, 'HS256');
        $response->getBody()->write(json_encode(array(
            "status" => "success",
            "token" => $new_token,
            "data" => null
        )));

    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }

    $conn = null;
    return $response;
});


//delete book
$app->delete('/user/book/delete', function (Request $request, Response $response, array $args) {
    error_reporting(E_ALL);
    $data = json_decode($request->getBody());
    $bookid = $data->bookid;
    $token = $data->token;
    $decoded = verifyToken($token);
    if (!$decoded) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Invalid token"))));
    }
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "DELETE FROM books_authors WHERE bookid = :bookid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['bookid' => $bookid]);
        $sql = "DELETE FROM books WHERE bookid = :bookid";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['bookid' => $bookid]);
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aus' => 'http://library.com',
            'exp' => $iat + 300,
            "data" => array("username" => $decoded->data->username)
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');
        $response->getBody()->write(json_encode(array("status" => "success", "token" => $jwt, "data" => null)));

    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }

    $conn = null;
    return $response;
});



//authors and its books
$app->get('/books', function (Request $request, Response $response, array $args) {
    error_reporting(E_ALL);
    $token = $request->getHeader('Authorization')[0]; 
    $token = str_replace('Token ', '', $token);
    $decoded = verifyToken($token);
    if (!$decoded) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Invalid token"))));
    }

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "SELECT authors.authorid, authors.name, GROUP_CONCAT(books.title) AS books
                FROM authors
                LEFT JOIN books ON books.authorid = authors.authorid
                GROUP BY authors.authorid, authors.name";
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($data as $row) {
            $booksList = $row['books'] ? explode(",", $row['books']) : [];
            $result[] = [
                "author" => $row['name'],
                "books" => $booksList 
            ];
        }

        $response->getBody()->write(json_encode(["status" => "success", "data" => $result]));

    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }

    $conn = null;
    return $response;
});

$app->run();




