<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login | Malaysol</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: url('assets/background.jpg') no-repeat center center/cover;
}
.login-box {
    background: #fff;
    border-radius: 20px;
    padding: 40px;
    max-width: 400px;
    margin: 8% auto;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
}
.login-logo {
    text-align: center;
    margin-bottom: 30px;
}
.form-control {
    border-radius: 20px;
}
.btn-yellow {
    background: #FAD443;
    color: #000;
    font-weight: bold;
    border-radius: 20px;
}
.btn-yellow:hover {
    background: #e6c031;
}
</style>
</head>
<body>
  <div class="login-box">
    <div class="login-logo">
      <img src="assets/logo.png" alt="Logo" width="100">
    </div>
    <form action="login.php" method="POST">
      <div class="mb-3">
        <input type="text" name="username" class="form-control" placeholder="username" required>
      </div>
      <div class="mb-3">
        <input type="password" name="password" class="form-control" placeholder="password" required>
      </div>
      <div class="mb-2 text-end">
        <small><a href="#">forgot password?</a></small>
      </div>
      <button type="submit" class="btn btn-yellow w-100">LOGIN</button>
    </form>
  </div>
</body>
</html>
