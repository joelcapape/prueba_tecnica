<?php
// Incluir la clase database.php
require_once 'database.php';

try {
    // Crear instancia de PDOWrapper
    $pdoWrapper = new PDOWrapper();

    // Consulta para obtener pedidos con comentarios vacíos
    $sql = "SELECT id, order_id FROM comments WHERE name IS null AND email IS null AND comment IS null";
    $rows = $pdoWrapper->fetchAll($sql);
    //print_r($rows);

    // Iterar sobre los pedidos vacíos
    foreach ($rows as $row) {
        $orderId = $row['order_id'];
        
        // Llamada a la API para obtener comentarios
        $url = "https://jsonplaceholder.typicode.com/comments?postId=$orderId";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Verificar el código de respuesta HTTP
        if ($httpCode == 200) {
            // Decodificar la respuesta JSON
            $comments = json_decode($response, true);

            // Actualizar la base de datos con los comentarios obtenidos
            foreach ($comments as $comment) {
                $id = $comment['id'];
                $name = $pdoWrapper->quote($comment['name']); // Escapar y entrecomillar el nombre
                $email = $pdoWrapper->quote($comment['email']); // Escapar y entrecomillar el email
                $commentText = $pdoWrapper->quote($comment['body']); // Escapar y entrecomillar el comentario

                // Actualizar el comentario en la base de datos
                $updateSql = "UPDATE comments SET name = $name, email = $email, comment = $commentText WHERE order_id = $orderId AND id = $id";
                $pdoWrapper->exec($updateSql);

                // Loguear la acción
                error_log("Actualizado comentario para order_id=$orderId, comment_id=$id");
            }
        } else {
            // Manejo de errores HTTP
            error_log("Error al obtener comentarios para order_id=$orderId. Código HTTP: $httpCode");
        }
        
        curl_close($ch);
    }

} catch (Exception $e) {
    // Manejo de errores generales
    echo "Error: " . $e->getMessage() . "\n";
}
?>
