<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "form";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Initialize error array
    $errors = [];

    // Gather and sanitize form data
    $enrollment = filter_var($_POST['enrollment'], FILTER_SANITIZE_NUMBER_INT);
    if (empty($enrollment)) {
        $errors[] = "Enrollment is required.";
    }

    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    if (!preg_match("/^[a-zA-Z ]*$/", $name)) {
        $errors[] = "Invalid name format. Only letters and white space allowed.";
    }

    $class = filter_var($_POST['class'], FILTER_SANITIZE_STRING);
    if (empty($class)) {
        $errors[] = "Class is required.";
    }

    $dob = $_POST['dob'];  // Date does not need sanitization if formatted correctly
    if (empty($dob)) {
        $errors[] = "Date of birth is required.";
    }

    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
    if (!preg_match("/^[0-9]{10}$/", $phone)) {
        $errors[] = "Invalid phone number format. Only 10 digit numbers allowed.";
    }

    // Check if file was uploaded without errors
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image = $_FILES['image']['name'];
        $temp_file = $_FILES['image']['tmp_name'];
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($image);
    } else {
        $errors[] = "Error uploading file. Please try again.";
    }

    // If there are errors, redirect back to the form
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: index.php");
        exit();
    }

    // If no errors, move the uploaded file and insert data into the database
    if (move_uploaded_file($temp_file, $target_file)) {
        $sql = "INSERT INTO students (enrollment, name, class, dob, phone, image) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        // Bind parameters
        $stmt->bind_param("ssssss", $enrollment, $name, $class, $dob, $phone, $image);

        // Execute the statement
        if ($stmt->execute()) {
            $_SESSION['message'] = "New record created successfully";
        } else {
            $_SESSION['message'] = "Error: " . $sql . "<br>" . $conn->error;
        }

        // Close statement
        $stmt->close();
    } else {
        $_SESSION['message'] = "Sorry, there was an error uploading your file.";
    }

    // Close connection
    $conn->close();

    header("Location: index.php");
    exit();
}
?>
