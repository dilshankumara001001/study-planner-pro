<?php
require_once 'config/config.php';
require_once 'config/functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Fetch user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $password = $_POST['password'];
    
    if (!empty($name)) {
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $hashed, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->execute([$name, $user_id]);
        }
        $_SESSION['user_name'] = $name;
        setFlash('profile', 'Profile updated successfully!', 'success');
        redirect('profile.php');
    } else {
        setFlash('profile', 'Name is required!', 'error');
    }
}
?>
<?php include 'includes/header.php'; ?>

<h2>👤 My Profile</h2>
<?php displayFlash('profile'); ?>

<div class="card" style="max-width: 500px; margin: 0 auto;">
    <form method="POST">
        <label style="font-weight: 600;">Full Name</label>
        <input type="text" name="name" value="<?php echo sanitize($user['name']); ?>" required style="width:100%; padding:12px; border:2px solid #e9ecef; border-radius:10px; margin:10px 0;">
        
        <label style="font-weight: 600;">Email (Read Only)</label>
        <input type="email" value="<?php echo sanitize($user['email']); ?>" disabled style="width:100%; padding:12px; border:2px solid #e9ecef; border-radius:10px; margin:10px 0; background:#f8f9fa;">
        
        <label style="font-weight: 600;">New Password (Leave blank to keep current)</label>
        <input type="password" name="password" placeholder="Enter new password..." style="width:100%; padding:12px; border:2px solid #e9ecef; border-radius:10px; margin:10px 0;">
        
        <button type="submit" style="width:100%; padding:14px; background:linear-gradient(135deg,#667eea,#764ba2); color:white; border:none; border-radius:12px; font-weight:600; cursor:pointer;">💾 Update Profile</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>