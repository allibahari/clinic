<?php
include "config.php";

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Prepare the SQL statement to delete a user with the given ID
    $sql = "DELETE FROM employees WHERE id = ?";
    
    // Prepare and bind the statement
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id); // 'i' indicates the type of the parameter (integer)
    
    // Execute the statement
    if ($stmt->execute()) {
        header("Location: users.php");
    } else {
        echo "Error deleting user: " . $stmt->error;
    }
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
} else {
    echo "No user ID specified.";
}
?>
