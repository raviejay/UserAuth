<?php

require '../database/db.php';
require '../Auth/User.php'; // Make sure to include the UserAuthentication class

use database\Database;  // Import the Database class
use Auth\UserAuthentication;  // Import your UserAuthentication class

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $db = new Database();
    $auth = new UserAuthentication($db);

    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($_GET['action'])) {
        $action = $_GET['action'];

        if ($action === 'register') {
            // Ensure required fields are provided
            if (!empty($data['first_name']) && !empty($data['last_name']) && !empty($data['email']) && !empty($data['phone']) && !empty($data['address']) && !empty($data['password'])) {
                $response = $auth->register($data['first_name'], $data['last_name'], $data['email'], $data['phone'], $data['address'], $data['password']);
                echo json_encode($response);
            } else {
                echo json_encode(['error' => 'Invalid input data']);
            }
        } elseif ($action === 'login') {
            if (!empty($data['email']) && !empty($data['password'])) {
                $response = $auth->login($data['email'], $data['password']);
                echo json_encode($response);
            } else {
                echo json_encode(['error' => 'Invalid input data']);
            }
        } elseif ($action === 'verify_code') {
            if (!empty($data['user_id']) && !empty($data['verification_code'])) {
                $response = $auth->verifyCode($data['user_id'], $data['verification_code']);
                echo json_encode($response);
            } else {
                echo json_encode(['error' => 'Invalid input data']);
            }
        } elseif ($action === 'logout') { // New logout action
            if (!empty($data['token'])) {
                $response = $auth->logout($data['token']); // Call the logout method
                echo json_encode($response);
            } else {
                echo json_encode(['error' => 'Token not provided']);
            }
        } else {
            echo json_encode(['error' => 'Invalid action']);
        }
    } else {
        echo json_encode(['error' => 'Action not specified']);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
