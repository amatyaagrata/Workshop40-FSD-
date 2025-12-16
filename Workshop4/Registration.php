<?php
/****************************
 * ENABLE ERROR REPORTING
 ****************************/
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/****************************
 * START SESSION
 ****************************/
session_start();

/****************************
 * VARIABLES
 ****************************/
$name = $email = "";
$errors = [];
$success_message = "";

/****************************
 * HANDLE FORM SUBMISSION
 ****************************/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get form data safely
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate data
    $errors = validateRegistration($name, $email, $password, $confirm_password);

    // Register user if no errors
    if (empty($errors)) {
        $result = registerUser($name, $email, $password);

        if ($result['success']) {
            $_SESSION['success_message'] = "🎉 Registration successful!";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $errors['system'] = $result['msg'];
        }
    }
}

/****************************
 * SUCCESS MESSAGE
 ****************************/
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

/****************************
 * FUNCTIONS
 ****************************/
function validateRegistration($name, $email, $password, $confirm_password) {

    $errors = [];

    if (empty($name) || strlen($name) < 2) {
        $errors['name'] = "Name must be at least 2 characters.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email address.";
    }

    if (strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[\W_]/', $password)) {

        $errors['password'] = "Password must be at least 8 characters and include uppercase, lowercase, number & special character.";
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    return $errors;
}

function registerUser($name, $email, $password) {

    $file = 'users.json';
    $users = [];

    // Read existing users
    if (file_exists($file)) {
        $users = json_decode(file_get_contents($file), true);
        if (!is_array($users)) {
            $users = [];
        }
    }

    // Check email uniqueness
    foreach ($users as $user) {
        if (strtolower($user['email']) === strtolower($email)) {
            return ['success' => false, 'msg' => 'Email already registered.'];
        }
    }

    // Add new user
    $users[] = [
        'id' => uniqid('user_'),
        'name' => $name,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'created_at' => date('Y-m-d H:i:s')
    ];

    // Save to file
    if (file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT)) === false) {
    return ['success' => false, 'msg' => 'Failed to save data.'];
}


    return ['success' => true];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Registration</title>
<style>
body {
    background: linear-gradient(135deg, #667eea, #764ba2);
    font-family: Arial, sans-serif;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}
.container {
    background: #fff;
    padding: 30px;
    width: 400px;
    border-radius: 10px;
}
h2 {
    text-align: center;
}
input {
    width: 100%;
    padding: 10px;
    margin-top: 6px;
}
button {
    width: 100%;
    padding: 12px;
    background: #667eea;
    color: #fff;
    border: none;
    margin-top: 15px;
    cursor: pointer;
}
.error {
    color: red;
    font-size: 14px;
}
.success {
    background: #2ecc71;
    color: #fff;
    padding: 10px;
    margin-bottom: 10px;
    text-align: center;
}
</style>
</head>
<body>

<div class="container">
<h2>Register</h2>

<?php if ($success_message): ?>
<div class="success"><?= htmlspecialchars($success_message) ?></div>
<?php endif; ?>

<?php if (isset($errors['system'])): ?>
<div class="success"><?php echo htmlspecialchars($success_message); ?></div>
<?php endif; ?>

<form method="POST">
    <label>Name</label>
    <input type="text" name="name" value="<?= htmlspecialchars($name) ?>">
    <div class="error"><?php echo $errors['name'] ?? ''; ?></div>


    <label>Email</label>
    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>">
    <div class="error"><?php echo $errors['email'] ?? ''; ?></div>


    <label>Password</label>
    <input type="password" name="password">
    <div class="error"><?php echo $errors['password'] ?? ''; ?></div>


    <label>Confirm Password</label>
    <input type="password" name="confirm_password">
    <div class="error"><?php echo $errors['confirm_password'] ?? ''; ?></div>


    <button type="submit">Register</button>
</form>
</div>

</body>
</html>
