<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$path   = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$parts  = explode('/', $path);

// Rutas: /products  /products/{id}
$resource = $parts[0] ?? '';
$id       = isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : null;

if ($resource !== 'products') {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);

switch ($method) {

    // GET /products          → todos los productos
    // GET /products?q=nombre → búsqueda por nombre
    // GET /products/{id}     → uno por id
    case 'GET':
        if ($id !== null) {
            $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($product) {
                echo json_encode($product);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Producto no encontrado']);
            }
        } else {
            $q = $_GET['q'] ?? '';
            if ($q !== '') {
                $stmt = $pdo->prepare('SELECT * FROM products WHERE name LIKE ? ORDER BY name');
                $stmt->execute(['%' . $q . '%']);
            } else {
                $stmt = $pdo->query('SELECT * FROM products ORDER BY name');
            }
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    // POST /products  → crear producto
    case 'POST':
        $name    = trim($body['name']    ?? '');
        $barcode = trim($body['barcode'] ?? '');
        $price   = (float)($body['price'] ?? 0);
        $stock   = (int)($body['stock']   ?? 0);

        if ($name === '') {
            http_response_code(400);
            echo json_encode(['error' => 'El nombre es requerido']);
            break;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO products (name, barcode, price, stock) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$name, $barcode, $price, $stock]);
        $newId = $pdo->lastInsertId();

        http_response_code(201);
        echo json_encode(['id' => $newId, 'name' => $name, 'barcode' => $barcode, 'price' => $price, 'stock' => $stock]);
        break;

    // PUT /products/{id}  → actualizar producto
    case 'PUT':
        if ($id === null) {
            http_response_code(400);
            echo json_encode(['error' => 'ID requerido']);
            break;
        }

        $name    = trim($body['name']    ?? '');
        $barcode = trim($body['barcode'] ?? '');
        $price   = (float)($body['price'] ?? 0);
        $stock   = (int)($body['stock']   ?? 0);

        $stmt = $pdo->prepare(
            'UPDATE products SET name=?, barcode=?, price=?, stock=? WHERE id=?'
        );
        $stmt->execute([$name, $barcode, $price, $stock, $id]);

        echo json_encode(['message' => 'Producto actualizado']);
        break;

    // DELETE /products/{id}
    case 'DELETE':
        if ($id === null) {
            http_response_code(400);
            echo json_encode(['error' => 'ID requerido']);
            break;
        }

        $stmt = $pdo->prepare('DELETE FROM products WHERE id=?');
        $stmt->execute([$id]);

        echo json_encode(['message' => 'Producto eliminado']);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}
