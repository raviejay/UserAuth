<?php

require 'db.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get the raw POST data and decode the JSON
    $data = json_decode(file_get_contents('php://input'), true);

    if (!empty($data['first_name']) && !empty($data['last_name']) && !empty($data['email']) && !empty($data['phone']) && !empty($data['password']) && !empty($data['address'])) {
        
        $first_name = $data['first_name'];
        $last_name = $data['last_name'];
        $email = $data['email'];
        $phone = $data['phone'];  
        $address = $data['address']; 
        $password = password_hash($data['password'], PASSWORD_DEFAULT);

        // Check if ang user already exists by email or phone number
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
        $stmt->bind_param('ss', $email, $phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // If the user doesn't exist, insert a new record
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, address, password) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssss', $first_name, $last_name, $email, $phone, $address, $password);

            if ($stmt->execute()) {
                echo json_encode(['message' => 'User registered successfully']);
            } else {
                echo json_encode(['error' => 'Failed to register user']);
            }
        } else {
            echo json_encode(['error' => 'User already exists']);
        }
    } else {
        echo json_encode(['error' => 'Invalid input data']);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>
