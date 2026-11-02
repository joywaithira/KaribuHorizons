<?php
require_once __DIR__ . '/../includes/dbh.inc.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = $pdo->query("SELECT title, slug, body, cover_image, created_at FROM posts WHERE published = 1 ORDER BY created_at DESC");
    $posts = [];
    foreach ($stmt->fetchAll() as $post) {
        $posts[] = [
            'id' => $post['slug'],
            'title' => $post['title'],
            'date' => date('F Y', strtotime($post['created_at'])),
            'category' => 'Stories',
            'summary' => strlen($post['body']) > 180 ? substr($post['body'], 0, 177) . '...' : $post['body'],
            'cover' => $post['cover_image'],
            'link' => 'blog.html#' . rawurlencode($post['slug'])
        ];
    }
    echo json_encode($posts);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to load blog posts.']);
}
