<?php

require 'vendor/autoload.php';
require 'config.php';

$app = new \Slim\App();

$app->get('/products', 'getProducts');
$app->get('/product/{id}', 'getProductById');
$app->get('/products/{ids}', 'getProductsByIds');
$app->get('/reviews/{id}', 'getReviewsByProductId');
$app->post('/review/{id}', 'addReviewForProductId');
$app->put('/vote/{id}', 'addVoteForProductReview');
$app->post('/login', 'loginUser');
$app->post('/user', 'addUser');
$app->post('/checkuser', 'checkUsername');

$app->run();

/**
 * Get all products from database with limited content
 * http://www.yourwebsite.com/products
 * Method: GET
 */
function getProducts() {
  $sql = "SELECT id, name, price, images FROM products";
  
  try {
    $db = getConnection();
    $stmt = $db->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_OBJ);
    $db = null;
    foreach($products as $product) {
      $product->{'image'} = json_decode($product->{'images'})[0];
      unset($product->{'images'});
    }
    echo '{"data":' . json_encode($products) . '}';

  } catch (PDOException $e) {
    echo '{"error":' . $e->getMessage() . '}';
  }
}

/**
 * Gets product details by ID
 * http://www.yourwebsite.com/product/1
 * Method: GET
 */
function getProductById($request, $response, $args) {
  $id = $args['id'];
  $sql = "SELECT * FROM products WHERE id = $id";
  
  try {
    $db = getConnection();
    $stmt = $db->query($sql);
    $product = $stmt->fetchObject();
    $db = null;
    echo '{"data":' . json_encode($product) . '}';

  } catch (PDOException $e) {
    echo '{"error":' . $e->getMessage() . '}';
  }
}

/**
 * Get products with passed IDs with limited content
 * http://www.yourwebsite.com/products/1,2,3,4
 * Method: GET
 */
function getProductsByIds($request, $response, $args) {
  $ids = $args['ids'];
  $sql = "SELECT id, name, price, images FROM products WHERE id IN ($ids)";
  
  try {
    $db = getConnection();
    $stmt = $db->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_OBJ);
    $db = null;
    foreach($products as $product) {
      $product->{'image'} = json_decode($product->{'images'})[0];
      unset($product->{'images'});
    }
    echo '{"data":' . json_encode($products) . '}';

  } catch (PDOException $e) {
    echo '{"error":' . $e->getMessage() . '}';
  }
}

/**
 * Get review details for specific product by ID
 * http://www.yourwebsite.com/reviews/1
 * Method: GET
 */
function getReviewsByProductId($request, $response, $args) {
  $id = $args['id'];
  $sql = "SELECT * FROM reviews, votes WHERE reviews.product_id = $id AND votes.review_id = $id;";
  
  try {
    $db = getConnection();
    $stmt = $db->query($sql);
    $reviews = $stmt->fetchAll(PDO::FETCH_OBJ);
    $db = null;
    echo '{"data":' . json_encode($reviews) . '}';

  } catch (PDOException $e) {
    echo '{"error":' . $e->getMessage() . '}';
  }
}

/**
 * Add new review for product ID
 * http://www.yourwebsite.com/review/1
 * Method: POST
 * Request Payload: {"heading": "test", "description": "test description", "overallRating": "5", "qualityRating": "4", "designRating": "3", "comfortRating": "2", "valueRating": "1", "userId": "123"}
 */
function addReviewForProductId($request, $response, $args) {
  $productId = $args['id'];
  $data = $request->getParsedBody();
  $heading = $data["heading"];
  $description = $data["description"];
  $overallRating = $data["overallRating"];
  $qualityRating = $data["qualityRating"];
  $designRating = $data["designRating"];
  $comfortRating = $data["comfortRating"];
  $valueRating = $data["valueRating"];
  $userId = $data["userId"];

  $sql = "INSERT INTO reviews (id, heading, description, overall_rating, quality_rating, design_rating, comfort_rating, value_rating, product_id, user_id) 
    VALUES (NULL, '$heading', '$description', '$overallRating', '$qualityRating', '$designRating', '$comfortRating', '$valueRating', '$productId', '$userId')";

  try {
    $db = getConnection();
    $stmt = $db->query($sql);
    $reviewId = $db->lastInsertId();

    if ($reviewId) {
      $sqlVotes = "INSERT INTO votes (id, was_helpful, not_helpful, review_id, user_id) VALUES (NULL, 0, 0, '$reviewId', NULL)";
      $stmtVotes = $db->query($sqlVotes);

      echo '{"data": "success"}';
    } else {

      echo '{"data": "unsuccess"}';
    }
    $db = null;

  } catch (PDOException $e) {
    echo '{"error":' . $e->getMessage() . '}';
  }
}

/**
 * Update votes for given review ID
 * http://www.yourwebsite/vote/1
 * Method: PUT
 * Request Payload: {"wasHelpful": "2", "notHelpful": "1", "userId": "1234"}
 */
function addVoteForProductReview($request, $response, $args) {
  $reviewId = $args["id"];
  $data = $request->getParsedBody();
  $wasHelpful = $data["wasHelpful"];
  $notHelpful = $data["notHelpful"];
  $userId = $data["userId"];

  $sql = "UPDATE votes SET was_helpful = '$wasHelpful', not_helpful = '$notHelpful', user_id = '$userId' WHERE review_id = '$reviewId'";

  try {
    $db = getConnection();
    $stmt = $db->query($sql);
    $db = null;
    echo '{"data": "success"}';

  } catch (PDOException $e) {
    echo '{"error":' . $e->getMessage() . '}';
  }
}

/**
 * Checks if username or email are available
 * http://www.yourwebsite/checkuser
 * Method: POST
 * Request Payload: {"username": "testUserName", "email": "test@email.com"}
 */
function checkUsername($request, $response, $args) {
  $data = $request->getParsedBody();
  $username = $data["username"];
  $email = $data["email"];
  $sql = "SELECT * FROM users WHERE username = '$username' OR email = '$email'";
  
  try {
    $db = getConnection();
    $stmt = $db->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $db = null;
    
    if (!$row) {
      echo '{"data":"available"}';
    } else {
      echo '{"data":"unavailable"}';
    }

  } catch (PDOException $e) {
    echo '{"error":' . $e->getMessage() . '}';
  }
}

/**
 * Verifiy passed credentials
 * http://www.yourwebsite/login
 * Method: POST
 * Request Payload: {"username": "john", "password": "Doe1234"}
 */
function loginUser($request, $response, $args) {
  $data = $request->getParsedBody();
  $username = $data["username"];
  $password = md5($data["password"]);

  $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";

  try {
    $db = getConnection();
    $stmt = $db->query($sql);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $db = null;

    if ($user) {
      echo '{"data":"valid"}';
    } else {
      echo '{"data":"invalid"}';
    }
    
  } catch (PDOException $e) {
    echo '{"error":' . $e->getMessage() . '}';
  }
}


/**
 * Add new user 
 * http://www.yourwebsite/user
 * Method: POST
 * Request Payload: {"firstName": "John", "lastName": "Doe", "email": "john.doe@mail.com", "username": "john.doe", "password": "1234"}
 */
function addUser($request, $response, $args) {
  $data = $request->getParsedBody();
  $firstName = $data["firstName"];
  $lastName = $data["lastName"];
  $email = $data["email"];
  $username = $data["username"];
  $password = md5($data["password"]);

  $sql = "INSERT INTO users (id, first_name, last_name, email, username, password) VALUES (NULL, '$firstName', '$lastName', '$email', '$username', '$password')";

  try {
    $db = getConnection();
    $stmt = $db->query($sql);
    $user = $db->lastInsertId();
    $db = null;
    echo '{"data":"success"}';

  } catch (PDOException $e) {
    echo '{"error":' . $e->getMessage() . '}';
  }
}
