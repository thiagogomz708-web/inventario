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
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts  = explode('/', trim($uri, '/'));

$resource = $parts[0] ?? '';
$id       = isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : null;

if ($resource !== 'products') {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($method) {

    case 'GET':
        if ($id !== null) {
            $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row) {
                echo json_encode($row);
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
            echo json_encode($stmt->fetchAll());
        }
        break;

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
        http_response_code(201);
        echo json_encode([
            'id' => (int)$pdo->lastInsertId(),
            'name' => $name, 'barcode' => $barcode,
            'price' => $price, 'stock' => $stock
        ]);
        break;

    case 'PUT':
        if ($id === null) { http_response_code(400); echo json_encode(['error' => 'ID requerido']); break; }

        $name    = trim($body['name']    ?? '');
        $barcode = trim($body['barcode'] ?? '');
        $price   = (float)($body['price'] ?? 0);
        $stock   = (int)($body['stock']   ?? 0);

        $pdo->prepare('UPDATE products SET name=?, barcode=?, price=?, stock=? WHERE id=?')
            ->execute([$name, $barcode, $price, $stock, $id]);

        echo json_encode(['message' => 'Producto actualizado']);
        break;

    case 'DELETE':
        if ($id === null) { http_response_code(400); echo json_encode(['error' => 'ID requerido']); break; }

        $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
        echo json_encode(['message' => 'Producto eliminado']);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}
