<?php
session_start();
require_once '../src/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT id, password, password_reset, is_super_admin FROM admins WHERE email = ?";

    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("s", $email);

        if($stmt->execute()){
            $stmt->store_result();

            if($stmt->num_rows == 1){
                $stmt->bind_result($id, $hashed_password, $password_reset, $is_super_admin);
                if($stmt->fetch()){
                    if(password_verify($password, $hashed_password)){
                        // Password is correct, so start a new session
                        session_regenerate_id();

                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $id;
                        $_SESSION["email"] = $email;
                        $_SESSION["is_super_admin"] = $is_super_admin; // Store super admin status in session

                        if ($password_reset == 1) {
                            header("location: change_password.php");
                        } else {
                            header("location: dashboard.php");
                        }
                        exit;
                    } else{
                        // Password is not valid
                        header("location: admin.php?error=invalid_credentials");
                        exit;
                    }
                }
            } else{
                // Username doesn't exist
                header("location: admin.php?error=invalid_credentials");
                exit;
            }
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }
        $stmt->close();
    }
}
$conn->close();
?>
