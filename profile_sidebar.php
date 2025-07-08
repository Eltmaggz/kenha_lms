<?php
$fullname = $_SESSION['fullname'] ?? 'User';
$role = $_SESSION['role'] ?? 'employee';
$email = $_SESSION['email'] ?? '';
$department = $_SESSION['department'] ?? 'N/A';
$region = $_SESSION['region'] ?? 'N/A';

$profilePhoto = 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
if (!empty($_SESSION['profile_photo']) && file_exists('uploads/' . $_SESSION['profile_photo'])) {
    $profilePhoto = 'uploads/' . $_SESSION['profile_photo'];
}
?>

<div class="sidebar-profile">
  <h3 style="text-align:center;">👤 Profile</h3>
  <div class="profile-pic-container">
    <img src="<?php echo $profilePhoto; ?>" alt="Profile Photo">
    <form action="upload_photo.php" method="POST" enctype="multipart/form-data">
      <label for="uploadInput">📷</label>
      <input type="file" id="uploadInput" name="profile_photo" accept="image/*" onchange="this.form.submit()" style="display:none;">
    </form>
  </div>
  <p><strong><?php echo htmlspecialchars($fullname); ?></strong></p>
  <p><?php echo htmlspecialchars($email); ?></p>
  <p>Dept: <?php echo htmlspecialchars($department); ?></p>
  <p>Region: <?php echo htmlspecialchars($region); ?></p>
  <p>Role: <?php echo htmlspecialchars($role); ?></p>

  <form class="upload-controls" action="remove_photo.php" method="POST">
    <button type="submit" class="remove-btn">Remove Photo</button>
  </form>
</div>
